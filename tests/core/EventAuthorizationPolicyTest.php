<?php

class EventAuthorizationPolicyTest extends TransactionedTestCase
{
	#[\Override]
	protected function tearDown(): void
	{
		$this->impersonate(null);
		parent::tearDown();
	}

	private function impersonate(?string $username): void
	{
		$ctx = RequestContextHolder::current();

		if ($username === null) {
			$ctx->currentUser = null;
			$ctx->userSessionInitialized = true;
			Cache::flush(Roles::class);
			Cache::flush(User::class);

			return;
		}

		$user = EntityUser::findFirst(['username' => $username]);
		$this->assertNotNull($user, "Missing test user: {$username}");

		$ctx->currentUser = $user->data();
		$ctx->userSessionInitialized = true;
		Cache::flush(Roles::class);
		Cache::flush(User::class);
	}

	private function authorizeEvent(AbstractEvent $event): PolicyDecision
	{
		return $event->authorize(PolicyContext::fromEvent($event));
	}

	public function testAclWriteIsDeniedForAdminWithoutDeveloperRole(): void
	{
		$this->impersonate('user_noroles');
		$this->assertFalse($this->authorizeEvent(new EventResourceAclSelectorAjaxSetOperation())->allow);
	}

	public function testAclWriteIsAllowedForDeveloper(): void
	{
		$this->impersonate('admin_developer');
		$this->assertTrue($this->authorizeEvent(new EventResourceAclSelectorAjaxSetOperation())->allow);
	}

	public function testAclReadIsAllowedForSystemAdministratorViaUsergroup(): void
	{
		$this->impersonate('user_noroles');
		$this->assertTrue($this->authorizeEvent(new EventResourceAclSelectorAjaxSubjectList())->allow);
	}
}
