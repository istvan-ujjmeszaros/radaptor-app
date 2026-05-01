<?php

declare(strict_types=1);

final class McpTokenServiceTest extends TransactionedTestCase
{
	public function testCreateAndAuthenticateHappyPath(): void
	{
		$user_id = $this->createUserId();

		$created = McpTokenService::createToken($user_id, 'happy-path token');
		$this->assertNotEmpty($created['token']);
		$this->assertNotEmpty($created['prefix']);

		$auth = McpTokenService::authenticate($created['token']);
		$this->assertIsArray($auth);
		$this->assertSame($user_id, (int) $auth['user']['user_id']);
		$this->assertSame((int) $created['token_id'], (int) $auth['token']['mcp_token_id']);
	}

	public function testAuthenticateUpdatesLastUsedAt(): void
	{
		$user_id = $this->createUserId();
		$created = McpTokenService::createToken($user_id, 'last-used');

		$entity_before = EntityMcpToken::findById((int) $created['token_id']);
		$this->assertNotNull($entity_before);
		$this->assertNull($entity_before->last_used_at);

		$this->assertNotNull(McpTokenService::authenticate($created['token']));

		$entity_after = EntityMcpToken::findById((int) $created['token_id']);
		$this->assertNotNull($entity_after);
		$this->assertNotNull($entity_after->last_used_at);
	}

	public function testAuthenticateRejectsRevokedToken(): void
	{
		$user_id = $this->createUserId();
		$created = McpTokenService::createToken($user_id, 'revoke-me');

		$this->assertTrue(McpTokenService::revokeTokenForUser($user_id, (int) $created['token_id']));
		$this->assertNull(McpTokenService::authenticate($created['token']));
	}

	public function testAuthenticateRejectsExpiredToken(): void
	{
		$user_id = $this->createUserId();
		$created = McpTokenService::createToken($user_id, 'will-expire');

		// createToken does not expose past expiry; push expires_at into the
		// past via the framework data-access API (no raw PDO).
		$past = gmdate('Y-m-d H:i:s', time() - 3600);
		EntityMcpToken::updateById((int) $created['token_id'], ['expires_at' => $past]);

		$this->assertNull(McpTokenService::authenticate($created['token']));
	}

	public function testAuthenticateRejectsInactiveUser(): void
	{
		$user_id = $this->createUserId();
		$created = McpTokenService::createToken($user_id, 'inactive-user');

		User::updateUser(['is_active' => 0], $user_id);

		$this->assertNull(McpTokenService::authenticate($created['token']));
	}

	public function testExtractPrefixBoundaries(): void
	{
		$valid = 'mcp_' . str_repeat('a', 8) . '_' . str_repeat('b', 43);
		$this->assertSame(str_repeat('a', 8), McpTokenService::extractPrefix($valid));

		$this->assertNull(McpTokenService::extractPrefix(''));
		$this->assertNull(McpTokenService::extractPrefix('not-a-token'));
		$this->assertNull(McpTokenService::extractPrefix('mcp_short_xx'));
		// Prefix one char too short.
		$this->assertNull(McpTokenService::extractPrefix('mcp_' . str_repeat('a', 7) . '_' . str_repeat('b', 43)));
		// Secret one char too short.
		$this->assertNull(McpTokenService::extractPrefix('mcp_' . str_repeat('a', 8) . '_' . str_repeat('b', 42)));
		// Disallowed character (`!`) in prefix.
		$this->assertNull(McpTokenService::extractPrefix('mcp_!aaaaaaa_' . str_repeat('b', 43)));
	}

	public function testListTokensForUserMarksStatusFlags(): void
	{
		$user_id = $this->createUserId();
		$active = McpTokenService::createToken($user_id, 'active');
		$expired = McpTokenService::createToken($user_id, 'expired');
		EntityMcpToken::updateById((int) $expired['token_id'], ['expires_at' => gmdate('Y-m-d H:i:s', time() - 3600)]);
		$revoked = McpTokenService::createToken($user_id, 'revoked');
		McpTokenService::revokeTokenForUser($user_id, (int) $revoked['token_id']);

		$tokens = McpTokenService::listTokensForUser($user_id);
		$by_id = [];

		foreach ($tokens as $token) {
			$by_id[(int) $token['mcp_token_id']] = $token;
		}

		$this->assertSame('active', $by_id[(int) $active['token_id']]['status']);
		$this->assertSame('expired', $by_id[(int) $expired['token_id']]['status']);
		$this->assertSame('revoked', $by_id[(int) $revoked['token_id']]['status']);
	}

	private function createUserId(): int
	{
		$user = EntityUser::saveFromArray([
			'username' => 'mcp_token_test_' . uniqid(),
			'password' => UserBase::encodePassword('secret'),
			'is_active' => 1,
		]);

		return (int) $user->pkey();
	}
}
