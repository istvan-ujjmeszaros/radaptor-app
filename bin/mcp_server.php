<?php

declare(strict_types=1);

define('RADAPTOR_MCP', dirname(__DIR__) . '/');
define('RADAPTOR_CLI', dirname(__DIR__) . '/');
define('USE_PERSISTENT_CACHE', false);

putenv('RADAPTOR_RUNTIME=swoole');
putenv('APP_APPLICATION_IDENTIFIER=Radaptor MCP');

require_once dirname(__DIR__) . '/bootstrap/bootstrap.php';

if (!class_exists(Swoole\Http\Server::class)) {
	fwrite(STDERR, "Swoole HTTP server is not available.\n");

	exit(1);
}

RequestContextHolder::setStorage(new SwooleRequestContextStorage());

$host = getenv('APP_MCP_HOST') ?: '0.0.0.0';
$port = (int) (getenv('APP_MCP_CONTAINER_PORT') ?: 9512);
$router = new McpJsonRpcRouter();

$server = new Swoole\Http\Server($host, $port);
$server->set([
	'worker_num' => 1,
	'enable_coroutine' => true,
]);

$server->on('start', static function () use ($host, $port): void {
	fwrite(STDERR, "Radaptor MCP server listening on {$host}:{$port}\n");
});

$server->on('request', static function (Swoole\Http\Request $request, Swoole\Http\Response $response) use ($router): void {
	$path = (string) ($request->server['request_uri'] ?? '/');
	$method = strtoupper((string) ($request->server['request_method'] ?? 'GET'));

	RequestContextHolder::initializeRequest(
		$request->get ?? [],
		[],
		buildMcpServerArray($request),
		$request->cookie ?? []
	);

	try {
		Kernel::initialize();

		if ($path !== '/mcp') {
			$response->status(404);
			$response->header('Content-Type', 'application/json');
			$response->end(json_encode(['error' => 'Not found'], JSON_UNESCAPED_SLASHES));

			return;
		}

		if ($method === 'OPTIONS') {
			if (!McpAuthenticator::validateOrigin($request->header ?? [])) {
				$response->status(403);
				$response->header('Content-Type', 'application/json');
				$response->end(json_encode(['error' => 'Invalid Origin'], JSON_UNESCAPED_SLASHES));

				return;
			}

			foreach (buildMcpCorsHeaders($request->header ?? []) as $name => $value) {
				$response->header($name, $value);
			}

			$response->status(204);
			$response->end('');

			return;
		}

		if ($method !== 'POST') {
			$response->status(405);
			$response->header('Content-Type', 'application/json');
			$response->header('Allow', 'POST, OPTIONS');

			if (McpAuthenticator::validateOrigin($request->header ?? [])) {
				foreach (buildMcpCorsHeaders($request->header ?? []) as $name => $value) {
					$response->header($name, $value);
				}
			}

			$response->end(json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_SLASHES));

			return;
		}

		$result = $router->handle((string) $request->rawContent(), $request->header ?? [], $request->server ?? []);

		$response->status($result['status']);

		if (McpAuthenticator::validateOrigin($request->header ?? [])) {
			foreach (buildMcpCorsHeaders($request->header ?? []) as $name => $value) {
				$response->header($name, $value);
			}
		}

		foreach ($result['headers'] as $name => $value) {
			$response->header($name, $value);
		}

		$response->end($result['body']);
	} catch (Throwable $exception) {
		$response->status(500);
		$response->header('Content-Type', 'application/json');
		$response->end(json_encode([
			'error' => 'Internal MCP server error',
			'message' => Kernel::getEnvironment() === 'development' ? $exception->getMessage() : 'Internal error',
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}
});

$server->start();

/**
 * @return array<string, mixed>
 */
function buildMcpServerArray(Swoole\Http\Request $request): array
{
	$server = $request->server ?? [];

	foreach (($request->header ?? []) as $name => $value) {
		$key = 'HTTP_' . strtoupper(str_replace('-', '_', (string) $name));
		$server[$key] = $value;
	}

	$server['REQUEST_URI'] ??= $server['request_uri'] ?? '/mcp';
	$server['REQUEST_METHOD'] ??= $server['request_method'] ?? 'POST';
	$server['HTTP_ACCEPT'] ??= $server['accept'] ?? 'application/json';

	return $server;
}

/**
 * @param array<string, mixed> $headers
 * @return array<string, string>
 */
function buildMcpCorsHeaders(array $headers): array
{
	$origin = mcpHeader($headers, 'origin');

	if ($origin === null || trim($origin) === '') {
		return [];
	}

	return [
		'Access-Control-Allow-Origin' => rtrim(trim($origin), '/'),
		'Access-Control-Allow-Methods' => 'POST, OPTIONS',
		'Access-Control-Allow-Headers' => 'Authorization, Content-Type, Accept, MCP-Protocol-Version, Mcp-Method, Mcp-Name, Mcp-Session-Id',
		'Access-Control-Max-Age' => '600',
		'Vary' => 'Origin',
	];
}

/**
 * @param array<string, mixed> $headers
 */
function mcpHeader(array $headers, string $name): ?string
{
	foreach ($headers as $key => $value) {
		if (strtolower((string) $key) === strtolower($name)) {
			return is_array($value) ? (string) reset($value) : (string) $value;
		}
	}

	return null;
}
