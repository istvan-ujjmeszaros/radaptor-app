<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CmsResourceListServiceTest extends TestCase
{
	private const int TEST_USER_ID = 424242;

	private ?int $root_id = null;
	private bool $created_root = false;
	private array $created_resource_ids = [];

	protected function setUp(): void
	{
		User::logout();
		User::bootstrapTrustedCurrentUser([
			'user_id' => self::TEST_USER_ID,
			'username' => 'cms-resource-list-test',
		]);
	}

	protected function tearDown(): void
	{
		try {
			foreach (array_reverse($this->created_resource_ids) as $resource_id) {
				if (is_int($resource_id) && ResourceTreeHandler::getResourceTreeEntryDataById($resource_id) !== null) {
					ResourceTreeHandler::deleteResourceEntriesRecursive($resource_id);
				}
			}

			if ($this->created_root && is_int($this->root_id) && ResourceTreeHandler::getResourceTreeEntryDataById($this->root_id) !== null) {
				ResourceTreeHandler::deleteResourceEntriesRecursive($this->root_id);
			}

			if (is_int($this->root_id)) {
				DbHelper::runCustomQuery(
					'DELETE FROM resource_acl WHERE resource_id=? AND subject_type=? AND subject_id=?',
					[$this->root_id, 'user', self::TEST_USER_ID]
				);
			}
		} finally {
			User::logout();
		}
	}

	public function testListResourcesReturnsDetailedDirectChildren(): void
	{
		$root_id = $this->ensureDomainRoot();

		$child_id = ResourceTreeHandler::addResourceEntry([
			'resource_name' => 'cms-resource-list-' . bin2hex(random_bytes(6)),
			'node_type' => 'folder',
		], $root_id);

		$this->assertIsInt($child_id);
		$this->created_resource_ids[] = $child_id;

		$resources = CmsResourceSpecService::listResources('/');
		$match = null;

		foreach ($resources as $resource) {
			if ((int) ($resource['node_id'] ?? 0) === $child_id) {
				$match = $resource;

				break;
			}
		}

		$this->assertIsArray($match);
		$this->assertArrayHasKey('resource_name', $match);
		$this->assertArrayHasKey('node_type', $match);
		$this->assertArrayHasKey('path', $match);
		$this->assertArrayHasKey('is_inheriting_acl', $match);
		$this->assertArrayHasKey('catcher_page', $match);
	}

	private function ensureDomainRoot(): int
	{
		$domain = ResourceTreeHandler::getActiveDomainContext();
		$existing_root_id = ResourceTreeHandler::getDomainRoot($domain);

		if ($existing_root_id !== null) {
			$this->root_id = $existing_root_id;
		} else {
			$this->created_root = true;
			$this->root_id = ResourceTreeHandler::addResourceEntry([
				'node_type' => 'root',
				'resource_name' => $domain,
			]);
			$this->assertIsInt($this->root_id);
		}

		DbHelper::insertOrUpdateHelper('resource_acl', [
			'resource_id' => $this->root_id,
			'subject_type' => 'user',
			'subject_id' => self::TEST_USER_ID,
			'allow_view' => 1,
			'allow_edit' => 1,
			'allow_delete' => 1,
			'allow_publish' => 1,
			'allow_list' => 1,
			'allow_create' => 1,
		]);

		return (int) $this->root_id;
	}
}
