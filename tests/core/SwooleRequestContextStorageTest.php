<?php

use PHPUnit\Framework\TestCase;

/**
 * Swoole coroutine isolation tests.
 *
 * Swoole + Xdebug in the same process causes a segfault.
 * Run with XDEBUG_MODE=off to include these tests.
 */
class SwooleRequestContextStorageTest extends TestCase
{
	protected function setUp(): void
	{
		// XDEBUG_MODE env var overrides ini; check env first (Xdebug respects it at startup).
		$mode = getenv('XDEBUG_MODE') ?: ini_get('xdebug.mode');
		if (extension_loaded('xdebug') && $mode !== 'off') {
			$this->markTestSkipped('Swoole + Xdebug segfaults. Run with XDEBUG_MODE=off to include.');
		}

		if (!class_exists(\Swoole\Coroutine::class)) {
			$this->markTestSkipped('Swoole extension is not available.');
		}
	}

	/**
	 * @param callable(): void $callback
	 */
	private function runCoroutine(callable $callback): void
	{
		$runCoroutine = '\Swoole\Coroutine\run';

		if (!function_exists($runCoroutine)) {
			$this->markTestSkipped('Swoole coroutine runtime function is not available.');
		}

		// phpstan cannot infer namespaced runtime function callability here.
		// @phpstan-ignore-next-line
		$runCoroutine($callback);
	}

	public function testEachCoroutineGetsItsOwnContext(): void
	{
		$storage = new SwooleRequestContextStorage();
		$results = [];

		$this->runCoroutine(function () use ($storage, &$results) {
			\Swoole\Coroutine::create(function () use ($storage, &$results) {
				$storage->initialize();
				$storage->get()->GET['key'] = 'coroutine-1';
				\Swoole\Coroutine::sleep(0.01); // yield — let the other coroutine run
				$results[1] = $storage->get()->GET['key'];
			});

			\Swoole\Coroutine::create(function () use ($storage, &$results) {
				$storage->initialize();
				$storage->get()->GET['key'] = 'coroutine-2';
				\Swoole\Coroutine::sleep(0.01);
				$results[2] = $storage->get()->GET['key'];
			});
		});

		$this->assertEquals('coroutine-1', $results[1]);
		$this->assertEquals('coroutine-2', $results[2]);
	}

	public function testInitializeCreatesNewContext(): void
	{
		$storage = new SwooleRequestContextStorage();

		$this->runCoroutine(function () use ($storage) {
			$storage->initialize();
			$storage->get()->GET['x'] = 'before';

			$storage->initialize();  // fresh context

			$this->assertEmpty($storage->get()->GET);
		});
	}

	public function testGetReturnsSameInstanceWithinCoroutine(): void
	{
		$storage = new SwooleRequestContextStorage();

		$this->runCoroutine(function () use ($storage) {
			$storage->initialize();
			$ctx1 = $storage->get();
			$ctx2 = $storage->get();

			$this->assertSame($ctx1, $ctx2);
		});
	}
}
