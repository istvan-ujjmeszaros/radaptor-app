<?php

/**
 * Long-running queue worker entrypoint.
 */

define('RADAPTOR_CLI', dirname(__DIR__) . '/');
define('USE_PERSISTENT_CACHE', false);

putenv('RADAPTOR_RUNTIME=swoole');

require_once dirname(__DIR__) . '/bootstrap/bootstrap.php';

$sessionHandler = new CLISessionHandler();
session_set_save_handler($sessionHandler, true);
session_start();

Kernel::initialize();
RequestContextHolder::setStorage(new SwooleRequestContextStorage());

if (!class_exists(Swoole\Coroutine::class)) {
	fwrite(STDERR, "Swoole extension is not available.\n");

	exit(1);
}

$workerSleepSeconds = max(0.001, (int) Config::EMAIL_QUEUE_WORKER_SLEEP_MS->value() / 1000);
$purgeIntervalSeconds = max(1, (int) Config::EMAIL_QUEUE_PURGE_INTERVAL_SECONDS->value());

/**
 * Queue execution model (Phase 1):
 *
 * This worker intentionally processes jobs sequentially (one job at a time per process),
 * even though Swoole can run jobs concurrently via coroutines.
 *
 * Why sequential now:
 * 1) Safety and determinism:
 *    - Email/queue status updates (recipient/outbox rollups, retries, dead-letter transitions)
 *      are easier to reason about and test when only one job mutates state at a time.
 * 2) Cache/context isolation:
 *    - We flush in-memory cache at the start of each job to emulate request-like isolation
 *      in a long-running process. This approach is straightforward and safe in sequential mode.
 * 3) Reduced race complexity:
 *    - Parallel in-process execution would require stricter per-coroutine cache strategy and
 *      stronger guarantees around shared mutable state to avoid cross-job contamination.
 *
 * Throughput strategy:
 * - Scale horizontally by running multiple worker processes/containers, each sequential.
 *
 * Future upgrade path:
 * - If coroutine parallelism is introduced, revisit:
 *   - cache lifecycle (per-coroutine, not process-wide flush assumptions),
 *   - authorization/data access isolation guarantees,
 *   - idempotency and transactional boundaries for status recomputation.
 */
\Swoole\Coroutine\run(function () use ($workerSleepSeconds, $purgeIntervalSeconds) {
	$stopRequested = false;
	$nextPurgeAt = time() + $purgeIntervalSeconds;
	$workerInstanceId = RuntimeWorkerRegistry::register(
		EmailQueueWorker::WORKER_TYPE,
		EmailQueueWorker::QUEUE_NAME,
		['command' => 'bin/swoole_queue_worker.php']
	);

	\Swoole\Process::signal(SIGTERM, static function () use (&$stopRequested) {
		$stopRequested = true;
	});
	\Swoole\Process::signal(SIGINT, static function () use (&$stopRequested) {
		$stopRequested = true;
	});

	try {
		while (!$stopRequested) {
			if (RuntimeWorkerPauseControl::pauseIfRequested($workerInstanceId, EmailQueueWorker::WORKER_TYPE, EmailQueueWorker::QUEUE_NAME)) {
				\Swoole\Coroutine::sleep($workerSleepSeconds);

				continue;
			}

			$processed = EmailQueueWorker::runOnce($workerInstanceId);

			if (RuntimeWorkerPauseControl::getActivePauseRequest(EmailQueueWorker::WORKER_TYPE, EmailQueueWorker::QUEUE_NAME) !== null) {
				\Swoole\Coroutine::sleep($workerSleepSeconds);

				continue;
			}

			if (time() >= $nextPurgeAt) {
				EmailQueueStorage::purgeArchives();
				$nextPurgeAt = time() + $purgeIntervalSeconds;
			}

			if (!$processed) {
				\Swoole\Coroutine::sleep($workerSleepSeconds);
			}
		}
	} finally {
		RuntimeWorkerRegistry::markStopping($workerInstanceId, EmailQueueWorker::WORKER_TYPE, EmailQueueWorker::QUEUE_NAME);
	}
});
