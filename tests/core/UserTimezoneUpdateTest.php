<?php

final class UserTimezoneUpdateTest extends TransactionedTestCase
{
	public function testUpdateUserAllowsClearingTimezoneWithNull(): void
	{
		$userId = $this->createUserId();

		DbHelper::updateHelper('users', ['timezone' => 'Europe/Budapest'], $userId);
		$before = User::getUserFromId($userId);
		$this->assertSame('Europe/Budapest', (string) ($before['timezone'] ?? ''));

		User::updateUser(['timezone' => null], $userId);

		$after = User::getUserFromId($userId);
		$this->assertNull($after['timezone']);
	}

	public function testUpdateHelperUsesPrimaryKeyFromSavedataWhenIdIsOmitted(): void
	{
		$userId = $this->createUserId();

		$affected = DbHelper::updateHelper('users', [
			'user_id' => $userId,
			'timezone' => 'Europe/Vienna',
		]);

		$this->assertSame(1, $affected);

		$after = User::getUserFromId($userId);
		$this->assertSame('Europe/Vienna', (string) ($after['timezone'] ?? ''));
	}

	public function testUpdateHelperAcceptsAssociativePrimaryKeyMapForSinglePrimaryKeyTables(): void
	{
		$userId = $this->createUserId();

		$affected = DbHelper::updateHelper('users', [
			'timezone' => 'Europe/Paris',
		], [
			'user_id' => $userId,
		]);

		$this->assertSame(1, $affected);

		$after = User::getUserFromId($userId);
		$this->assertSame('Europe/Paris', (string) ($after['timezone'] ?? ''));
	}

	private function createUserId(): int
	{
		$user = EntityUser::saveFromArray([
			'username' => 'timezone_' . uniqid(),
			'password' => UserBase::encodePassword('secret'),
			'is_active' => 1,
		]);

		return (int) $user->pkey();
	}
}
