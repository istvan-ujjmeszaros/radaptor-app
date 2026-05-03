<?php

declare(strict_types=1);

final class RuntimeDiagnosticsTest extends TransactionedTestCase
{
	/** @var array<string, string|false> */
	private array $originalEnv = [];

	/** @var array<int, string> */
	private array $originalArgv = [];

	protected function setUp(): void
	{
		parent::setUp();

		global $argv;
		$this->originalArgv = $argv ?? [];
		$this->rememberEnv([
			'PHPUNIT',
			'ENVIRONMENT',
			'EMAIL_FORCE_CATCHER_IN_NON_PROD',
			'EMAIL_SMTP_HOST',
			'EMAIL_SMTP_PORT',
			'EMAIL_SMTP_USERNAME',
			'EMAIL_SMTP_PASSWORD',
			'EMAIL_SMTP_USE_STARTTLS',
			'EMAIL_FROM_ADDRESS',
			'EMAIL_FROM_NAME',
			'APP_MAILPIT_HTTP_PORT',
		]);
		$this->setRequestContext();
	}

	protected function tearDown(): void
	{
		global $argv;
		$argv = $this->originalArgv;
		$this->restoreEnv();
		$this->impersonate(null);
		parent::tearDown();
	}

	public function testAccessPolicyAllowsDeveloperOnly(): void
	{
		$this->impersonate('admin_developer');
		$this->assertTrue($this->authorizePolicy()->allow);

		$this->impersonate('user_noroles');
		$this->assertFalse($this->authorizePolicy()->allow);

		$this->impersonate(null);
		$this->assertFalse($this->authorizePolicy()->allow);
	}

	public function testEventAuthorizationDelegatesToSharedPolicy(): void
	{
		$event = new EventRuntimeDiagnostics();

		$this->impersonate('user_noroles');
		$this->assertFalse($event->authorize(PolicyContext::fromEvent($event))->allow);

		$this->impersonate('admin_developer');
		$this->assertTrue($event->authorize(PolicyContext::fromEvent($event))->allow);
	}

	public function testEmailDiagnosticsMarksNonProductionCatcherSafe(): void
	{
		$this->setEnv('APP_MAILPIT_HTTP_PORT', '8026');

		$summary = RuntimeDiagnosticsReadModel::getSummary();

		$this->assertTrue($summary['email']['using_catcher']);
		$this->assertTrue($summary['email']['safe_to_test']);
		$this->assertSame('http://localhost:8026', $summary['email']['catcher']['mailpit_ui_url']);
	}

	public function testEmailDiagnosticsWarnsForProductionCatcherHost(): void
	{
		$this->setEnv('PHPUNIT', '0');
		$this->setEnv('ENVIRONMENT', 'production');
		$this->setEnv('EMAIL_SMTP_HOST', 'mailpit');
		$this->setEnv('EMAIL_FROM_ADDRESS', 'no-reply@example.test');

		$summary = RuntimeDiagnosticsReadModel::getSummary();

		$this->assertContains('Production email appears to use a catcher host.', $summary['warnings']);
	}

	public function testEmailDiagnosticsWarnsForNonProductionWithoutCatcher(): void
	{
		$this->setEnv('PHPUNIT', '0');
		$this->setEnv('ENVIRONMENT', 'development');
		$this->setEnv('EMAIL_FORCE_CATCHER_IN_NON_PROD', 'false');
		$this->setEnv('EMAIL_SMTP_HOST', 'smtp.example.test');
		$this->setEnv('EMAIL_FROM_ADDRESS', 'no-reply@example.test');

		$summary = RuntimeDiagnosticsReadModel::getSummary();

		$this->assertFalse($summary['email']['using_catcher']);
		$this->assertFalse($summary['email']['safe_to_test']);
		$this->assertContains('Non-production email is not using the catcher; test sends may leave this environment.', $summary['warnings']);
	}

	public function testCliCommandJsonReturnsRedactedSummaryForDeveloper(): void
	{
		$this->impersonate('admin_developer');

		global $argv;
		$argv = ['radaptor', 'runtime:diagnostics', '--json'];

		ob_start();

		try {
			(new CLICommandRuntimeDiagnostics())->run();
		} finally {
			$output = (string) ob_get_clean();
		}

		$decoded = json_decode($output, true);
		$this->assertIsArray($decoded);
		$this->assertSame('mysql', $decoded['database']['driver']);
		$this->assertStringContainsString('password=[redacted]', $decoded['database']['redacted_dsn']);
		$this->assertStringNotContainsString('password=radaptor_app', $output);
	}

	public function testCliCommandAuthorizationDeniesNonDeveloper(): void
	{
		$this->impersonate('user_noroles');
		$command = new CLICommandRuntimeDiagnostics();

		$this->assertFalse($command->authorize(PolicyContext::fromCli($command))->allow);
	}

	public function testAdminWidgetVisibilityAndTreeUseSharedPolicy(): void
	{
		$widget = new WidgetRuntimeDiagnostics();
		$context = $this->treeContext();
		$connection = new WidgetConnection([]);

		$this->impersonate('user_noroles');
		$this->assertFalse(WidgetRuntimeDiagnostics::getListVisibility());
		$this->assertFalse($widget->canAccess($context, $connection));

		$this->impersonate('admin_developer');
		$this->assertTrue(WidgetRuntimeDiagnostics::getListVisibility());
		$this->assertTrue($widget->canAccess($context, $connection));

		$tree = $widget->buildTree($context, $connection);
		$this->assertSame('runtimeDiagnostics', $tree['component']);
		$this->assertArrayHasKey('summary', $tree['props']);
	}

	public function testMcpToolIsReadOnlyAndRequiresDeveloperRole(): void
	{
		$this->impersonate(null);
		$plain_user_id = (int) EntityUser::saveFromArray([
			'username' => 'runtime_mcp_plain_' . uniqid(),
			'password' => UserBase::encodePassword('secret'),
			'is_active' => 1,
		])->pkey();
		$plain_token = (string) McpTokenService::createToken($plain_user_id, 'runtime-plain')['token'];

		$tools_response = $this->callMcp($plain_token, [
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'tools/list',
		]);
		$tools = $tools_response['result']['tools'] ?? [];
		$by_name = [];

		foreach ($tools as $tool) {
			$by_name[$tool['name']] = $tool;
		}

		$this->assertArrayHasKey('radaptor.runtime.diagnostics', $by_name);
		$this->assertTrue($by_name['radaptor.runtime.diagnostics']['annotations']['readOnlyHint']);
		$this->assertFalse($by_name['radaptor.runtime.diagnostics']['annotations']['destructiveHint']);

		$denied = $this->callMcp($plain_token, [
			'jsonrpc' => '2.0',
			'id' => 2,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.runtime.diagnostics',
				'arguments' => new stdClass(),
			],
		]);
		$this->assertTrue($denied['result']['isError']);
		$this->assertSame('authorization_denied', $denied['result']['structuredContent']['error_code']);

		$developer = User::getUserByName('admin_developer');
		$this->assertNotNull($developer);
		$developer_token = (string) McpTokenService::createToken((int) $developer['user_id'], 'runtime-dev')['token'];
		$allowed = $this->callMcp($developer_token, [
			'jsonrpc' => '2.0',
			'id' => 3,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.runtime.diagnostics',
				'arguments' => new stdClass(),
			],
		]);
		$this->assertFalse($allowed['result']['isError'] ?? false);
		$this->assertSame(true, $allowed['result']['structuredContent']['ok'] ?? null);
		$this->assertArrayHasKey('email', $allowed['result']['structuredContent']['data']);
		$this->assertStringNotContainsString('password=radaptor_app', (string) json_encode($allowed));
	}

	private function authorizePolicy(): PolicyDecision
	{
		return RuntimeDiagnosticsAccessPolicy::authorize(new PolicyContext(
			PolicyPrincipal::fromCurrentUser(),
			'test'
		));
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
		$this->assertNotNull($user, "Missing test fixture user: {$username}");

		$ctx->currentUser = $user->data();
		$ctx->userSessionInitialized = true;
		Cache::flush(Roles::class);
		Cache::flush(User::class);
	}

	private function setRequestContext(): void
	{
		$server = [
			'REQUEST_URI' => '/admin/developer/runtime-diagnostics.html',
			'HTTP_HOST' => 'localhost',
			'SERVER_PORT' => '80',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'HTTPS' => '',
			'HTTP_ACCEPT' => 'application/json',
			'REQUEST_METHOD' => 'GET',
		];

		RequestContextHolder::initializeRequest(server: $server);
		RequestContextHolder::disablePersistentCacheWrite();
		$_SERVER = array_replace($_SERVER, $server);
	}

	/**
	 * @param list<string> $names
	 */
	private function rememberEnv(array $names): void
	{
		foreach ($names as $name) {
			$this->originalEnv[$name] = getenv($name);
		}
	}

	private function setEnv(string $name, string $value): void
	{
		putenv($name . '=' . $value);
	}

	private function restoreEnv(): void
	{
		foreach ($this->originalEnv as $name => $value) {
			if ($value === false) {
				putenv($name);
			} else {
				putenv($name . '=' . $value);
			}
		}
	}

	private function callMcp(string $token, array $payload): array
	{
		$body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$result = (new McpJsonRpcRouter())->handle((string) $body, [
			'Authorization' => 'Bearer ' . $token,
			'Accept' => 'application/json, text/event-stream',
			'MCP-Protocol-Version' => '2025-11-25',
			'Origin' => '',
		], []);

		$this->assertSame(200, $result['status'], 'unexpected MCP status; body=' . $result['body']);
		$decoded = json_decode($result['body'], true);
		$this->assertIsArray($decoded);

		return $decoded;
	}

	private function treeContext(): iTreeBuildContext
	{
		return new class () implements iTreeBuildContext {
			public function getPageId(): ?int
			{
				return 1;
			}

			public function getPagedata($key)
			{
				return null;
			}

			public function registerRenderedLayoutComponent(iLayoutComponent $layoutComponent): void
			{
			}

			public function getLayoutTypeName(): ?string
			{
				return 'admin_default';
			}

			public function addToTitle(string $addition): void
			{
			}

			public function isEditable(): bool
			{
				return false;
			}

			public function getTheme(): ?AbstractThemeData
			{
				return null;
			}

			public function overrideLayoutType(string $layoutTypeName): void
			{
			}
		};
	}
}
