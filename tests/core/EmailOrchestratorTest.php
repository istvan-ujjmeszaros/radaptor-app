<?php

declare(strict_types=1);

final class EmailOrchestratorTest extends TransactionedTestCase
{
	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();
		TestHelperEnvironment::setEnvironmentVariable('APP_DOMAIN_CONTEXT', 'tracker.virtuosoft.hu');
	}

	#[\Override]
	protected function tearDown(): void
	{
		TestHelperEnvironment::revertEnvironmentVariable('APP_DOMAIN_CONTEXT');
		parent::tearDown();
	}

	public function testEnqueueTransactionalSnapshotCreatesOutboxRecipientsAndQueueRows(): void
	{
		$this->bootstrapAndImpersonateEmailAdmin();

		$result = EmailOrchestrator::enqueueTransactionalSnapshot(
			'Portal access requested',
			'<p>Hello</p>',
			'Hello',
			[
				['email' => 'alpha@example.com', 'name' => 'Alpha'],
				['email' => 'ALPHA@example.com', 'name' => 'Duplicate'],
				['email' => 'beta@example.com'],
				['email' => 'not-an-email'],
			]
		);

		$this->assertGreaterThan(0, $result['outbox_id']);
		$this->assertSame(2, $result['queued_jobs']);

		$outbox = DbHelper::selectOne('email_outbox', ['outbox_id' => $result['outbox_id']]);
		$this->assertIsArray($outbox);
		$this->assertSame('queued', $outbox['status'] ?? null);
		$this->assertSame('transactional', $outbox['send_mode'] ?? null);

		$recipients = DbHelper::selectMany(
			'email_outbox_recipients',
			['outbox_id' => $result['outbox_id']],
			false,
			'recipient_id ASC'
		);
		$this->assertCount(2, $recipients);
		$this->assertSame('alpha@example.com', $recipients[0]['recipient_email'] ?? null);
		$this->assertSame('beta@example.com', $recipients[1]['recipient_email'] ?? null);

		$queueRows = DbHelper::selectMany('email_queue_transactional', [], false, 'job_id ASC');
		$this->assertCount(2, $queueRows);
		$this->assertSame('email.transactional.send_snapshot', $queueRows[0]['job_type'] ?? null);
		$this->assertSame(User::getCurrentUserId(), (int) ($queueRows[0]['requested_by_id'] ?? 0));
	}

	public function testEnqueueTransactionalSnapshotAsSystemCreatesSystemOwnedQueueRows(): void
	{
		$result = EmailOrchestrator::enqueueTransactionalSnapshotAsSystem(
			'Confirm your early access request',
			'<p>Confirm</p>',
			'Confirm',
			[
				['email' => 'system@example.com', 'name' => 'System Recipient'],
			]
		);

		$this->assertGreaterThan(0, $result['outbox_id']);
		$this->assertSame(1, $result['queued_jobs']);

		$outbox = DbHelper::selectOne('email_outbox', ['outbox_id' => $result['outbox_id']]);
		$this->assertIsArray($outbox);
		$this->assertSame('system', $outbox['requested_by_type'] ?? null);
		$this->assertNull($outbox['requested_by_id'] ?? null);

		$queueRows = DbHelper::selectMany('email_queue_transactional', [], false, 'job_id ASC');
		$this->assertCount(1, $queueRows);
		$this->assertSame('system', $queueRows[0]['requested_by_type'] ?? null);
		$this->assertNull($queueRows[0]['requested_by_id'] ?? null);
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
			'username' => 'mailadm_' . uniqid(),
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
