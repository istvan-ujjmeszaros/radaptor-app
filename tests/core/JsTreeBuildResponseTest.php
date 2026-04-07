<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class JsTreeBuildResponseTest extends TestCase
{
	private function baseContext(): array
	{
		return [
			'parent_node_id' => 0,
			'parent_data' => null,
			'id_prefix' => 'jstree_test',
			'for_type' => 'user',
			'for_id' => 1,
		];
	}

	public static function templateAndTypeProvider(): array
	{
		$templates = [
			JsTreeApiService::TEMPLATE_JSTREE_1,
			JsTreeApiService::TEMPLATE_JSTREE_3,
		];

		$types = [
			JsTreeApiService::TYPE_ADMINMENU,
			JsTreeApiService::TYPE_MAINMENU,
			JsTreeApiService::TYPE_RESOURCES,
			JsTreeApiService::TYPE_ROLES,
			JsTreeApiService::TYPE_USERGROUPS,
			JsTreeApiService::TYPE_ROLE_SELECTOR,
			JsTreeApiService::TYPE_USERGROUP_SELECTOR,
		];

		$cases = [];

		foreach ($templates as $template) {
			foreach ($types as $type) {
				$cases[] = [$template, $type];
			}
		}

		return $cases;
	}

	public function testBuildResponseRequiresShapeTemplateArgument(): void
	{
		$response = JsTreeApiService::buildResponse(
			[JsTreeApiService::TEMPLATE_JSTREE_3],
			JsTreeApiService::TYPE_ROLES,
			[],
			[],
			null
		);

		$payload = $response->toArray();

		$this->assertFalse($payload['ok']);
		$this->assertSame('TEMPLATE_REQUIRED', $payload['error']['code']);
	}

	public function testBuildResponseRejectsDisallowedShapeTemplate(): void
	{
		$response = JsTreeApiService::buildResponse(
			[JsTreeApiService::TEMPLATE_JSTREE_3],
			JsTreeApiService::TYPE_ROLES,
			[],
			[],
			'invalid_shape'
		);

		$payload = $response->toArray();

		$this->assertFalse($payload['ok']);
		$this->assertSame('TEMPLATE_NOT_ALLOWED', $payload['error']['code']);
	}

	#[DataProvider('templateAndTypeProvider')]
	public function testBuildResponseAcceptsAllKnownTypesForBothTemplates(string $shapeTemplate, string $type): void
	{
		$response = JsTreeApiService::buildResponse(
			[JsTreeApiService::TEMPLATE_JSTREE_1, JsTreeApiService::TEMPLATE_JSTREE_3],
			$type,
			[],
			$this->baseContext(),
			$shapeTemplate
		);

		$payload = $response->toArray();

		$this->assertTrue($payload['ok']);
		$this->assertIsArray($payload['data']);
	}

	public function testBuildResponseRejectsUnsupportedTreeType(): void
	{
		$response = JsTreeApiService::buildResponse(
			[JsTreeApiService::TEMPLATE_JSTREE_3],
			'unknown_tree_type',
			[],
			$this->baseContext(),
			JsTreeApiService::TEMPLATE_JSTREE_3
		);

		$payload = $response->toArray();

		$this->assertFalse($payload['ok']);
		$this->assertSame('TREE_TYPE_NOT_ALLOWED', $payload['error']['code']);
	}
}
