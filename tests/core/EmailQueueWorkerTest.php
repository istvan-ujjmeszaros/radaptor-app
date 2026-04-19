<?php

declare(strict_types=1);

final class EmailQueueWorkerTest extends TransactionedTestCase
{
	#[\Override]
	protected function tearDown(): void
	{
		EmailSmtpTransport::setTestSender(null);
		parent::tearDown();
	}

	public function testRunOnceProcessesTransactionalSnapshotAndUpdatesHeartbeat(): void
	{
		$this->bootstrapAndImpersonateEmailAdmin();

		$captured = [];
		EmailSmtpTransport::setTestSender(static function (string $subject, string $htmlBody, string $textBody, array $to, array $settings) use (&$captured): void {
			$captured = [
				'subject' => $subject,
				'html_body' => $htmlBody,
				'text_body' => $textBody,
				'to' => $to,
				'settings' => $settings,
			];
		});

		$result = EmailOrchestrator::enqueueTransactionalSnapshot(
			'Portal login link',
			'<p>Use your link</p>',
			'Use your link',
			[
				['email' => 'worker@example.com', 'name' => 'Worker Recipient'],
			]
		);

		$this->assertTrue(EmailQueueWorker::runOnce());

		$this->assertSame('Portal login link', $captured['subject'] ?? null);
		$this->assertSame('worker@example.com', $captured['to'][0]['email'] ?? null);
		$this->assertTrue((bool) ($captured['settings']['using_catcher'] ?? false));

		$outbox = EntityEmailOutbox::findById($result['outbox_id']);
		$this->assertNotNull($outbox);
		$this->assertSame('sent', $outbox->status);
		$this->assertNotNull($outbox->sent_at);

		$recipient = DbHelper::selectOne('email_outbox_recipients', ['outbox_id' => $result['outbox_id']]);
		$this->assertIsArray($recipient);
		$this->assertSame('sent', $recipient['status'] ?? null);

		$this->assertSame(0, (int) DbHelper::selectOneColumnFromQuery('SELECT COUNT(*) FROM email_queue_transactional'));
		$this->assertSame(1, (int) DbHelper::selectOneColumnFromQuery('SELECT COUNT(*) FROM email_queue_archive'));

		$heartbeat = EmailQueueHeartbeat::getState();
		$this->assertSame('running', $heartbeat['status']);
		$this->assertNotNull($heartbeat['last_seen_at']);
		$this->assertNotNull($heartbeat['last_processed_at']);
	}

	public function testRunOnceDeadLettersJobWhenRequestedPrincipalLosesAuthorization(): void
	{
		RequestContextHolder::initializeRequest(server: [
			'REQUEST_URI' => '/admin/email-outbox/index.html',
			'HTTP_HOST' => 'localhost',
			'SERVER_PORT' => '80',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'HTTP_ACCEPT' => 'text/html',
		]);
		RequestContextHolder::disablePersistentCacheWrite();
		(new SeedSkeletonBootstrap())->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));

		$user = EntityUser::saveFromArray([
			'username' => 'unauthq_' . uniqid(),
			'password' => UserBase::encodePassword('secret'),
			'is_active' => 1,
		]);

		EmailQueueStorage::enqueue(new EmailQueueJob(
			jobId: 'job_' . bin2hex(random_bytes(8)),
			jobType: 'email.transactional.send_snapshot',
			payload: [
				'outbox_id' => 999999,
				'recipient_id' => 999999,
			],
			requestedByType: 'user',
			requestedById: (int) $user->user_id,
		));

		$this->assertTrue(EmailQueueWorker::runOnce());
		$this->assertSame(0, (int) DbHelper::selectOneColumnFromQuery('SELECT COUNT(*) FROM email_queue_transactional'));

		$deadLetters = DbHelper::selectMany('email_queue_dead_letter', [], false, 'dead_letter_id DESC');
		$this->assertNotSame([], $deadLetters);
		$this->assertSame('AUTH_DENIED', $deadLetters[0]['error_code'] ?? null);
	}

	public function testRunOnceRetryableFailureReschedulesJobWithoutDeadLettering(): void
	{
		$this->bootstrapAndImpersonateEmailAdmin();

		EmailSmtpTransport::setTestSender(static function (): void {
			throw new EmailJobProcessingException('SMTP_TEMPORARY_FAILURE', 'Temporary SMTP outage.', true);
		});

		$result = EmailOrchestrator::enqueueTransactionalSnapshot(
			'Retry me later',
			'<p>Retry me later</p>',
			'Retry me later',
			[
				['email' => 'retry@example.com'],
			]
		);

		$this->assertTrue(EmailQueueWorker::runOnce());

		$queueRows = DbHelper::selectMany('email_queue_transactional', [], false, 'job_id ASC');
		$this->assertCount(1, $queueRows);
		$this->assertSame('retry_wait', $queueRows[0]['status'] ?? null);
		$this->assertNull($queueRows[0]['reserved_at'] ?? null);
		$this->assertGreaterThanOrEqual(1, (int) ($queueRows[0]['attempts'] ?? 0));

		$deadLetterCount = (int) DbHelper::selectOneColumnFromQuery('SELECT COUNT(*) FROM email_queue_dead_letter');
		$this->assertSame(0, $deadLetterCount);

		$outbox = EntityEmailOutbox::findById($result['outbox_id']);
		$this->assertNotNull($outbox);
		$this->assertSame('queued', $outbox->status);

		$recipient = DbHelper::selectOne('email_outbox_recipients', ['outbox_id' => $result['outbox_id']]);
		$this->assertIsArray($recipient);
		$this->assertSame('queued', $recipient['status'] ?? null);
	}

	private function bootstrapAndImpersonateEmailAdmin(): void
	{
		RequestContextHolder::initializeRequest(server: [
			'REQUEST_URI' => '/admin/email-outbox/index.html',
			'HTTP_HOST' => 'localhost',
			'SERVER_PORT' => '80',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'HTTP_ACCEPT' => 'text/html',
		]);
		RequestContextHolder::disablePersistentCacheWrite();
		(new SeedSkeletonBootstrap())->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));

		$user = EntityUser::saveFromArray([
			'username' => 'mailwrk_' . uniqid(),
			'password' => UserBase::encodePassword('secret'),
			'is_active' => 1,
		]);
		$user_id = (int) $user->user_id;
		$emails_admin_role_id = (int) DbHelper::selectOneColumn('roles_tree', ['role' => RoleList::ROLE_EMAILS_ADMIN], '', 'node_id');
		Roles::assignToUser($emails_admin_role_id, $user_id);

		$ctx = RequestContextHolder::current();
		$ctx->currentUser = DbHelper::selectOne('users', ['user_id' => $user_id]);
		$ctx->userSessionInitialized = true;
		Cache::flush(Roles::class);
		Cache::flush(User::class);
	}
}
