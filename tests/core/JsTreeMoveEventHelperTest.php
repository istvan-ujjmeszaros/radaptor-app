<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class JsTreeMoveEventHelperTest extends TestCase
{
	public function testBuildMoveResponseBuildsSuccessPayload(): void
	{
		$response = JsTreeApiService::buildMoveResponse(true, ['data' => ['node_id' => 5]]);
		$decoded = $response->toArray();

		$this->assertTrue($decoded['ok']);
		$this->assertSame(5, $decoded['data']['data']['node_id']);
	}

	public function testBuildMoveResponseBuildsStandardizedErrorPayload(): void
	{
		$response = JsTreeApiService::buildMoveResponse(false, [], ['debug' => ['reason' => 'x']]);
		$decoded = $response->toArray();

		$this->assertFalse($decoded['ok']);
		$this->assertSame('OPERATION_FAILED', $decoded['error']['code']);
		$this->assertSame('Move failed', $decoded['error']['message']);
		$this->assertSame('x', $decoded['meta']['debug']['reason'] ?? null);
	}
}
