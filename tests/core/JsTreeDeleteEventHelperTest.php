<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class JsTreeDeleteEventHelperTest extends TestCase
{
	public function testNormalizeIdsWrapsScalarAndPreservesArray(): void
	{
		$this->assertSame(['15'], JsTreeApiService::normalizeIds('15'));
		$this->assertSame(['1', '2'], JsTreeApiService::normalizeIds(['1', '2']));
	}

	public function testBuildHxTriggerHeaderLineBuildsJsonPayload(): void
	{
		$line = JsTreeApiService::buildHxTriggerHeaderLine('roleTreeDeleted', [
			'nodeIds' => [1, 2],
			'success' => true,
		]);

		$this->assertSame('HX-Trigger: {"roleTreeDeleted":{"nodeIds":[1,2],"success":true}}', $line);
	}

	public function testBuildDeleteResponseBuildsSuccessPayload(): void
	{
		$response = JsTreeApiService::buildDeleteResponse(true, ['deleted_ids' => [3, 4]]);
		$decoded = $response->toArray();

		$this->assertTrue($decoded['ok']);
		$this->assertSame([3, 4], $decoded['data']['deleted_ids']);
	}

	public function testBuildDeleteResponseBuildsStandardizedErrorPayload(): void
	{
		$response = JsTreeApiService::buildDeleteResponse(false, [], ['node_id' => 42]);
		$decoded = $response->toArray();

		$this->assertFalse($decoded['ok']);
		$this->assertSame('OPERATION_FAILED', $decoded['error']['code']);
		$this->assertSame('Delete failed', $decoded['error']['message']);
		$this->assertSame(['node_id' => 42], $decoded['meta']);
	}
}
