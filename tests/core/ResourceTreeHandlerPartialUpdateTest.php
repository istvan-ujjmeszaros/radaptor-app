<?php

declare(strict_types=1);

final class ResourceTreeHandlerPartialUpdateTest extends TransactionedTestCase
{
	public function testUpdateResourceTreeEntryAllowsLayoutOnlyUpdatesWithoutWarnings(): void
	{
		$pages = DbHelper::selectMany('resource_tree', ['node_type' => 'webpage'], 1, 'node_id ASC');
		$this->assertNotSame([], $pages);
		$page = $pages[0];

		$resource_id = (int) ($page['node_id'] ?? 0);
		$this->assertGreaterThan(0, $resource_id);

		$attributes_before = AttributeHandler::getAttributes(
			new AttributeResourceIdentifier(ResourceNames::RESOURCE_DATA, (string) $resource_id)
		);
		$resource_before = ResourceTreeHandler::getResourceTreeEntryDataById($resource_id);

		$this->assertIsArray($resource_before);
		$this->assertArrayHasKey('resource_name', $resource_before);

		$captured_errors = [];
		$previous_handler = set_error_handler(
			static function (int $severity, string $message, string $file, int $line) use (&$captured_errors): bool {
				$captured_errors[] = compact('severity', 'message', 'file', 'line');

				return true;
			}
		);

		try {
			$modified = ResourceTreeHandler::updateResourceTreeEntry(['layout' => 'public_default'], $resource_id);
		} finally {
			restore_error_handler();
		}

		$this->assertSame([], $captured_errors, 'Layout-only resource updates must not emit PHP warnings/notices.');
		$this->assertIsInt($modified);

		$resource_after = ResourceTreeHandler::getResourceTreeEntryDataById($resource_id);
		$attributes_after = AttributeHandler::getAttributes(
			new AttributeResourceIdentifier(ResourceNames::RESOURCE_DATA, (string) $resource_id)
		);

		$this->assertIsArray($resource_after);
		$this->assertSame($resource_before['resource_name'], $resource_after['resource_name']);
		$this->assertSame('public_default', $attributes_after['layout'] ?? null);
		$this->assertSame($attributes_before['title'] ?? null, $attributes_after['title'] ?? null);
	}
}
