<?php

final class UserTimezoneUpdateTest extends TransactionedTestCase
{
	public function testUpdateUserAllowsClearingTimezoneWithNull(): void
	{
		$user = EntityUser::findFirst(['username' => 'admin_developer']);
		$this->assertNotNull($user);
		$userId = (int) $user->pkey();

		DbHelper::updateHelper('users', ['timezone' => 'Europe/Budapest'], $userId);
		$before = User::getUserFromId($userId);
		$this->assertSame('Europe/Budapest', (string) ($before['timezone'] ?? ''));

		User::updateUser(['timezone' => null], $userId);

		$after = User::getUserFromId($userId);
		$this->assertNull($after['timezone']);
	}
}
