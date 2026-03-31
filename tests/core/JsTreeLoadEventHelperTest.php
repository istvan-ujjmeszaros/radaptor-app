<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class JsTreeLoadEventHelperTest extends TestCase
{
	public function testResolveParentNodeIdUsesDefaultForRoot(): void
	{
		$parentNodeId = JsTreeApiService::resolveParentNodeId('root', 0);

		$this->assertSame(0, $parentNodeId);
	}

	public function testResolveParentNodeIdUsesResolverForRootWhenProvided(): void
	{
		$parentNodeId = JsTreeApiService::resolveParentNodeId('#', 0, fn () => 55);

		$this->assertSame(55, $parentNodeId);
	}

	public function testResolveParentNodeIdCastsNumericNodeIdToInt(): void
	{
		$parentNodeId = JsTreeApiService::resolveParentNodeId('17', 0);

		$this->assertSame(17, $parentNodeId);
	}
}
