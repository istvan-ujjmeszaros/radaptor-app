<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ResourceTreeHandlerSplitTest extends TestCase
{
	public function testSplitResourceAndAttributesDataSeparatesExpectedKeys(): void
	{
		$input = [
			'node_id' => 42,
			'resource_name' => 'demo',
			'catcher_page' => 1,
			'comment' => 'x',
			'node_type' => 'webpage',
			'path' => '/docs/',
			'is_inheriting_acl' => 0,
			'title' => 'Attribute title',
			'custom_flag' => true,
		];

		$method = new ReflectionMethod(ResourceTreeHandler::class, 'splitResourceAndAttributesData');
		[$resourceData, $attributeData] = $method->invoke(null, $input);

		$this->assertSame([
			'node_id' => 42,
			'resource_name' => 'demo',
			'catcher_page' => 1,
			'comment' => 'x',
			'node_type' => 'webpage',
			'path' => '/docs/',
			'is_inheriting_acl' => 0,
		], $resourceData);

		$this->assertSame([
			'title' => 'Attribute title',
			'custom_flag' => true,
		], $attributeData);
	}
}
