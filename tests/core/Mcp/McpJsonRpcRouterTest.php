<?php

declare(strict_types=1);

final class McpJsonRpcRouterTest extends TransactionedTestCase
{
	private const string PROTOCOL_VERSION = '2025-11-25';

	private string $bearer = '';
	private int $userId = 0;

	protected function setUp(): void
	{
		parent::setUp();

		$this->userId = (int) EntityUser::saveFromArray([
			'username' => 'mcp_router_' . uniqid(),
			'password' => UserBase::encodePassword('secret'),
			'is_active' => 1,
		])->pkey();

		$created = McpTokenService::createToken($this->userId, 'router-test');
		$this->bearer = (string) $created['token'];
	}

	private function call(array $payload, array $extra_headers = [], int $expected_status = 200): array
	{
		$body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$headers = $this->defaultHeaders();

		foreach ($extra_headers as $name => $value) {
			$headers[$name] = $value;
		}

		$result = (new McpJsonRpcRouter())->handle($body, $headers, []);
		$this->assertSame($expected_status, $result['status'], 'unexpected HTTP status; body=' . $result['body']);

		if ($result['body'] === '') {
			return ['status' => $result['status'], 'headers' => $result['headers']];
		}

		$decoded = json_decode($result['body'], true);
		$this->assertIsArray($decoded, 'router body must decode as JSON');

		return $decoded + ['_status' => $result['status'], '_headers' => $result['headers']];
	}

	private function defaultHeaders(): array
	{
		return [
			'Authorization' => 'Bearer ' . $this->bearer,
			'Accept' => 'application/json, text/event-stream',
			'MCP-Protocol-Version' => self::PROTOCOL_VERSION,
			'Origin' => '',
		];
	}

	private function bearerForUser(string $username, string $token_name): string
	{
		$user = User::getUserByName($username);
		$this->assertNotNull($user, "{$username} fixture must be present");

		return (string) McpTokenService::createToken((int) $user['user_id'], $token_name)['token'];
	}

	private function loginPageFixture(): array
	{
		$page = DbHelper::selectOne('resource_tree', [
			'path' => '/',
			'resource_name' => 'login.html',
			'node_type' => 'webpage',
		]);
		$this->assertIsArray($page, 'login.html fixture page must be present');

		return $page;
	}

	public function testParseErrorReturnsMinus32700(): void
	{
		$result = (new McpJsonRpcRouter())->handle('not-json', $this->defaultHeaders(), []);
		$this->assertSame(200, $result['status']);
		$body = json_decode($result['body'], true);
		$this->assertSame(-32700, $body['error']['code']);
	}

	public function testInvalidJsonRpcEnvelopeReturnsMinus32600(): void
	{
		$missing_jsonrpc = $this->call(['method' => 'ping', 'id' => 1]);
		$this->assertSame(-32600, $missing_jsonrpc['error']['code']);

		$batch = (new McpJsonRpcRouter())->handle('[]', $this->defaultHeaders(), []);
		$batch_body = json_decode($batch['body'], true);
		$this->assertSame(-32600, $batch_body['error']['code']);
	}

	public function testPayloadWithoutMethodResultOrErrorIsInvalid(): void
	{
		$decoded = $this->call(['jsonrpc' => '2.0', 'id' => 1]);
		$this->assertSame(-32600, $decoded['error']['code']);
	}

	public function testValidJsonRpcResponseIsAcceptedWith202(): void
	{
		$payload = ['jsonrpc' => '2.0', 'id' => 'srv-1', 'result' => ['ok' => true]];
		$body = json_encode($payload);
		$result = (new McpJsonRpcRouter())->handle($body, $this->defaultHeaders(), []);
		$this->assertSame(202, $result['status']);
		$this->assertSame('', $result['body']);
	}

	public function testMissingAcceptIsRejected(): void
	{
		$payload = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'];
		$body = json_encode($payload);
		$headers = $this->defaultHeaders();
		unset($headers['Accept']);
		$result = (new McpJsonRpcRouter())->handle($body, $headers, []);
		$this->assertSame(400, $result['status']);
		$decoded = json_decode($result['body'], true);
		$this->assertSame(-32600, $decoded['error']['code']);
	}

	public function testWildcardAcceptIsAllowed(): void
	{
		$response = $this->call(
			['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
			['Accept' => '*/*']
		);
		$this->assertArrayHasKey('result', $response);
	}

	public function testJsonOnlyAcceptIsRejected(): void
	{
		$body = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping']);
		$headers = $this->defaultHeaders();
		$headers['Accept'] = 'application/json';
		$result = (new McpJsonRpcRouter())->handle($body, $headers, []);
		$this->assertSame(400, $result['status']);
	}

	public function testInitializeNegotiatesUnsupportedProtocolVersion(): void
	{
		$response = $this->call([
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'initialize',
			'params' => [
				'protocolVersion' => '2024-11-05',
				'capabilities' => new stdClass(),
				'clientInfo' => ['name' => 'test', 'version' => '0.1'],
			],
		]);

		$this->assertSame(self::PROTOCOL_VERSION, $response['result']['protocolVersion']);
	}

	public function testInitializeIgnoresProtocolVersionHeaderMismatch(): void
	{
		// Header says 2024-11-05 but the body negotiation should still succeed.
		$response = $this->call(
			[
				'jsonrpc' => '2.0',
				'id' => 1,
				'method' => 'initialize',
				'params' => [
					'protocolVersion' => '2024-11-05',
					'capabilities' => new stdClass(),
					'clientInfo' => ['name' => 'test', 'version' => '0.1'],
				],
			],
			['MCP-Protocol-Version' => '2024-11-05']
		);

		$this->assertSame(self::PROTOCOL_VERSION, $response['result']['protocolVersion']);
	}

	public function testInitializeRejectsMissingClientInfo(): void
	{
		$response = $this->call([
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'initialize',
			'params' => [
				'protocolVersion' => self::PROTOCOL_VERSION,
				'capabilities' => new stdClass(),
			],
		]);

		$this->assertSame(-32602, $response['error']['code']);
	}

	public function testPostInitializeProtocolHeaderMismatchIsRejected(): void
	{
		$body = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);
		$headers = $this->defaultHeaders();
		$headers['MCP-Protocol-Version'] = '2024-11-05';
		$result = (new McpJsonRpcRouter())->handle($body, $headers, []);
		$this->assertSame(400, $result['status']);
	}

	public function testNotificationReturns202(): void
	{
		$body = json_encode(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']);
		$result = (new McpJsonRpcRouter())->handle($body, $this->defaultHeaders(), []);
		$this->assertSame(202, $result['status']);
		$this->assertSame('', $result['body']);
	}

	public function testMissingBearerReturns401WithWwwAuthenticate(): void
	{
		$body = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);
		$headers = $this->defaultHeaders();
		unset($headers['Authorization']);
		$result = (new McpJsonRpcRouter())->handle($body, $headers, []);
		$this->assertSame(401, $result['status']);
		$this->assertSame('Bearer realm="radaptor-mcp"', $result['headers']['WWW-Authenticate']);
	}

	public function testToolsListIncludesAnnotationsAndTitle(): void
	{
		$response = $this->call([
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'tools/list',
		]);

		$tools = $response['result']['tools'];
		$this->assertNotEmpty($tools);

		foreach ($tools as $tool) {
			$annotations = $tool['annotations'];
			$this->assertArrayHasKey('readOnlyHint', $annotations);
			$this->assertArrayHasKey('destructiveHint', $annotations);
			$this->assertArrayHasKey('idempotentHint', $annotations);
			$this->assertArrayHasKey('openWorldHint', $annotations);
		}

		// Pick a known read-only tool defined by the CMS module.
		$by_name = [];

		foreach ($tools as $tool) {
			$by_name[$tool['name']] = $tool;
		}

		if (isset($by_name['radaptor.webpage.info'])) {
			$webpage_info = $by_name['radaptor.webpage.info'];
			$this->assertSame('Show webpage details', $webpage_info['title'] ?? null);
			$this->assertSame('Show webpage details', $webpage_info['annotations']['title'] ?? null);
			$this->assertTrue($webpage_info['annotations']['readOnlyHint']);
			$this->assertFalse($webpage_info['annotations']['destructiveHint']);
		}

		$this->assertArrayHasKey('radaptor.layout.usage', $by_name);
		$this->assertTrue($by_name['radaptor.layout.usage']['annotations']['readOnlyHint']);
		$this->assertFalse($by_name['radaptor.layout.usage']['annotations']['destructiveHint']);
		$this->assertArrayHasKey('radaptor.resource.file_usage', $by_name);
		$this->assertTrue($by_name['radaptor.resource.file_usage']['annotations']['readOnlyHint']);
		$this->assertFalse($by_name['radaptor.resource.file_usage']['annotations']['destructiveHint']);

		foreach ([
			'radaptor.layout.status',
			'radaptor.form.status',
			'radaptor.widget.status',
			'radaptor.integrity.summary',
		] as $tool_name) {
			$this->assertArrayHasKey($tool_name, $by_name);
			$this->assertTrue($by_name[$tool_name]['annotations']['readOnlyHint']);
			$this->assertFalse($by_name[$tool_name]['annotations']['destructiveHint']);
		}
	}

	public function testToolsCallMissingRequiredArgumentReturnsIsError(): void
	{
		$response = $this->call([
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.webpage.info',
				'arguments' => new stdClass(),
			],
		]);

		// Spec compliance: tool argument validation is a result-level isError,
		// not a JSON-RPC -32602.
		$this->assertArrayHasKey('result', $response);
		$this->assertTrue($response['result']['isError']);
		$this->assertSame('validation_failed', $response['result']['structuredContent']['error_code']);

		// Mirror: structuredContent must also appear as a JSON-encoded text
		// content block per 2025-11-25 back-compat guidance.
		$blocks = $response['result']['content'];
		$this->assertGreaterThanOrEqual(2, count($blocks));
		$json_block = end($blocks);
		$this->assertSame('text', $json_block['type']);
		$decoded = json_decode($json_block['text'], true);
		$this->assertSame('validation_failed', $decoded['error_code']);
	}

	public function testToolsCallSuccessMirrorsStructuredContentAsJsonTextBlock(): void
	{
		// Use the seeded admin user for a stable logged-in principal. widget.urls
		// does not require a seeded resource path when the widget has no placements.
		$admin_token = $this->bearerForUser('admin_developer', 'router-success');

		$body = json_encode([
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.widget.urls',
				'arguments' => ['widget' => 'WidgetDefinitelyNotAssigned'],
			],
		]);
		$headers = $this->defaultHeaders();
		$headers['Authorization'] = 'Bearer ' . $admin_token;
		$result = (new McpJsonRpcRouter())->handle($body, $headers, []);

		$this->assertSame(200, $result['status']);
		$response = json_decode($result['body'], true);
		$this->assertArrayHasKey('result', $response);
		$this->assertFalse($response['result']['isError'] ?? false, 'expected success, got: ' . $result['body']);

		$structured = $response['result']['structuredContent'];
		$this->assertIsArray($structured);
		$this->assertSame(true, $structured['ok'] ?? null);

		// Mirror: the last text block must be a JSON-encoded copy of structuredContent.
		$blocks = $response['result']['content'];
		$this->assertGreaterThanOrEqual(2, count($blocks));
		$json_block = end($blocks);
		$this->assertSame('text', $json_block['type']);
		$decoded = json_decode($json_block['text'], true);
		$this->assertSame($structured, $decoded);
	}

	public function testWidgetUpdateToolUpdatesConnectionAttributesThroughMcp(): void
	{
		$admin_token = $this->bearerForUser('admin_developer', 'router-widget-update');

		$login_page = $this->loginPageFixture();
		$connection_id = Widget::getWidgetConnectionId((int) $login_page['node_id'], 'content', WidgetList::FORM);
		$this->assertIsInt($connection_id);

		$body = json_encode([
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.widget.update',
				'arguments' => [
					'connection_id' => $connection_id,
					'attributes' => [
						'form_id' => FormList::USERLOGIN,
						'width' => 'min(100%, 28rem)',
						'margin-left' => 'auto',
						'margin-right' => 'auto',
					],
				],
			],
		], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$headers = $this->defaultHeaders();
		$headers['Authorization'] = 'Bearer ' . $admin_token;
		$result = (new McpJsonRpcRouter())->handle($body, $headers, []);

		$this->assertSame(200, $result['status']);
		$response = json_decode($result['body'], true);
		$this->assertIsArray($response);
		$this->assertFalse($response['result']['isError'] ?? false, 'expected success, got: ' . $result['body']);

		$connection = $response['result']['structuredContent']['data']['connection'] ?? null;
		$this->assertIsArray($connection);
		$this->assertSame($connection_id, $connection['connection_id']);
		$this->assertSame(FormList::USERLOGIN, $connection['attributes']['form_id'] ?? null);
		$this->assertSame('min(100%, 28rem)', $connection['attributes']['width'] ?? null);
		$this->assertSame('auto', $connection['attributes']['margin-left'] ?? null);

		$tools = $this->call([
			'jsonrpc' => '2.0',
			'id' => 2,
			'method' => 'tools/list',
		])['result']['tools'] ?? [];
		$by_name = [];

		foreach ($tools as $tool) {
			$by_name[$tool['name']] = $tool;
		}

		$this->assertArrayHasKey('radaptor.widget.update', $by_name);
		$this->assertFalse($by_name['radaptor.widget.update']['annotations']['readOnlyHint']);
	}

	public function testWidgetUpdateToolDeniesUserWithoutOwningPageEditAccess(): void
	{
		$login_page = $this->loginPageFixture();
		$connection_id = Widget::getWidgetConnectionId((int) $login_page['node_id'], 'content', WidgetList::FORM);
		$this->assertIsInt($connection_id);

		$response = $this->call([
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.widget.update',
				'arguments' => [
					'connection_id' => $connection_id,
					'attributes' => [
						'form_id' => FormList::USERLOGIN,
					],
				],
			],
		]);

		$this->assertTrue($response['result']['isError'] ?? false);
		$this->assertSame('authorization_denied', $response['result']['structuredContent']['error_code'] ?? null);
	}

	public function testConsistencyToolsRequireDeveloperAndReturnUsageData(): void
	{
		$denied = $this->call([
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.layout.usage',
				'arguments' => ['layout' => 'admin_login'],
			],
		]);
		$this->assertTrue($denied['result']['isError'] ?? false);
		$this->assertSame('authorization_denied', $denied['result']['structuredContent']['error_code'] ?? null);

		$admin_token = $this->bearerForUser('admin_developer', 'router-consistency-usage');
		$admin_headers = ['Authorization' => 'Bearer ' . $admin_token];

		$layout = $this->call([
			'jsonrpc' => '2.0',
			'id' => 2,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.layout.usage',
				'arguments' => ['layout' => 'admin_login'],
			],
		], $admin_headers);
		$this->assertFalse($layout['result']['isError'] ?? false);
		$layout_data = $layout['result']['structuredContent']['data'] ?? null;
		$this->assertIsArray($layout_data);
		$this->assertSame('admin_login', $layout_data['layout'] ?? null);
		$this->assertTrue($layout_data['in_use'] ?? false);
		$this->assertGreaterThanOrEqual(1, $layout_data['count'] ?? 0);
		$login_page = $this->loginPageFixture();
		$this->assertContains((int) $login_page['node_id'], array_column($layout_data['pages'], 'page_id'));

		$file_usage = $this->call([
			'jsonrpc' => '2.0',
			'id' => 3,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.resource.file_usage',
				'arguments' => ['file_id' => 987654321],
			],
		], $admin_headers);
		$this->assertFalse($file_usage['result']['isError'] ?? false);
		$file_data = $file_usage['result']['structuredContent']['data'] ?? null;
		$this->assertIsArray($file_data);
		$this->assertSame(987654321, $file_data['file_id'] ?? null);
		$this->assertFalse($file_data['file_exists'] ?? true);
		$this->assertFalse($file_data['referenced_by_vfs'] ?? true);
		$this->assertSame([], $file_data['resources'] ?? null);
	}

	public function testIntegrityStatusToolsRequireDeveloperAndReturnReadOnlyData(): void
	{
		$denied = $this->call([
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.integrity.summary',
				'arguments' => new stdClass(),
			],
		]);
		$this->assertTrue($denied['result']['isError'] ?? false);
		$this->assertSame('authorization_denied', $denied['result']['structuredContent']['error_code'] ?? null);

		$admin_token = $this->bearerForUser('admin_developer', 'router-integrity-status');
		$admin_headers = ['Authorization' => 'Bearer ' . $admin_token];

		$layout = $this->call([
			'jsonrpc' => '2.0',
			'id' => 2,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.layout.status',
				'arguments' => ['layout' => 'admin_login'],
			],
		], $admin_headers);
		$this->assertFalse($layout['result']['isError'] ?? false);
		$layout_data = $layout['result']['structuredContent']['data'] ?? null;
		$this->assertIsArray($layout_data);
		$this->assertSame('ok', $layout_data['status'] ?? null);
		$this->assertSame('admin_login', $layout_data['layout'] ?? null);

		$form = $this->call([
			'jsonrpc' => '2.0',
			'id' => 3,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.form.status',
				'arguments' => ['form' => FormList::USERLOGIN],
			],
		], $admin_headers);
		$this->assertFalse($form['result']['isError'] ?? false);
		$form_data = $form['result']['structuredContent']['data'] ?? null;
		$this->assertIsArray($form_data);
		$this->assertSame('ok', $form_data['status'] ?? null);
		$this->assertSame(FormList::USERLOGIN, $form_data['form'] ?? null);

		$widget = $this->call([
			'jsonrpc' => '2.0',
			'id' => 4,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.widget.status',
				'arguments' => ['widget' => WidgetList::USERLIST],
			],
		], $admin_headers);
		$this->assertFalse($widget['result']['isError'] ?? false);
		$widget_data = $widget['result']['structuredContent']['data'] ?? null;
		$this->assertIsArray($widget_data);
		$this->assertSame('ok', $widget_data['status'] ?? null);
		$this->assertSame(WidgetList::USERLIST, $widget_data['widget'] ?? null);

		$summary = $this->call([
			'jsonrpc' => '2.0',
			'id' => 5,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.integrity.summary',
				'arguments' => new stdClass(),
			],
		], $admin_headers);
		$this->assertFalse($summary['result']['isError'] ?? false);
		$summary_data = $summary['result']['structuredContent']['data'] ?? null;
		$this->assertIsArray($summary_data);
		$this->assertContains($summary_data['status'] ?? null, ['ok', 'warning']);
		$this->assertSame(['layouts', 'forms', 'widgets'], array_column($summary_data['checks'] ?? [], 'name'));
	}

	public function testToolsCallUnknownToolStaysProtocolErrorWithMinus32602(): void
	{
		$response = $this->call([
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'tools/call',
			'params' => [
				'name' => 'radaptor.does-not-exist',
				'arguments' => new stdClass(),
			],
		]);

		$this->assertSame(-32602, $response['error']['code']);
	}

	public function testDraftMcpMethodAndMcpNameHeadersAreIgnored(): void
	{
		$response = $this->call(
			['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'],
			['Mcp-Method' => 'something-else', 'Mcp-Name' => 'bogus']
		);

		$this->assertArrayHasKey('result', $response);
	}
}
