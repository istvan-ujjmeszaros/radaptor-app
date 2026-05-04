<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CLICommandWebRunnerAclTest extends TestCase
{
	private ?int $folder_id = null;
	private ?int $root_id = null;
	private bool $created_root = false;
	private int $test_user_id;
	private string $test_username;

	protected function setUp(): void
	{
		$this->test_username = 'cliwr_' . bin2hex(random_bytes(4));
		$this->test_user_id = User::addUser([
			'username' => $this->test_username,
			'password' => User::encodePassword('password123'),
			'is_active' => 1,
		]);
		$this->assertGreaterThan(0, $this->test_user_id);

		User::logout();
		User::bootstrapTrustedCurrentUser([
			'user_id' => $this->test_user_id,
			'username' => $this->test_username,
		]);
	}

	protected function tearDown(): void
	{
		try {
			if (is_int($this->folder_id) && ResourceTreeHandler::getResourceTreeEntryDataById($this->folder_id) !== null) {
				DbHelper::runCustomQuery(
					'DELETE FROM resource_acl WHERE resource_id IN (
						SELECT node_id FROM resource_tree WHERE lft >= ? AND rgt <= ?
					)',
					$this->getSubtreeBounds($this->folder_id)
				);
				ResourceTreeHandler::deleteResourceEntriesRecursive($this->folder_id);
			}

			if ($this->created_root && is_int($this->root_id) && ResourceTreeHandler::getResourceTreeEntryDataById($this->root_id) !== null) {
				DbHelper::runCustomQuery(
					'DELETE FROM resource_acl WHERE resource_id=? AND subject_type=? AND subject_id=?',
					[$this->root_id, 'user', $this->test_user_id]
				);
				ResourceTreeHandler::deleteResourceEntriesRecursive($this->root_id);
			}
		} finally {
			if ($this->test_user_id > 0) {
				DbHelper::runCustomQuery(
					'DELETE FROM resource_acl WHERE subject_type=? AND subject_id=?',
					['user', $this->test_user_id]
				);
				DbHelper::deleteHelper('users', $this->test_user_id);
			}

			User::logout();
		}
	}

	public function testWebRunnerUsesBridgedUserAclForResourceList(): void
	{
		$parent_id = $this->ensureDomainRoot();

		$this->folder_id = ResourceTreeHandler::addResourceEntry([
			'resource_name' => 'cli-web-runner-parent-' . bin2hex(random_bytes(6)),
			'node_type' => 'folder',
			'is_inheriting_acl' => 0,
		], $parent_id);
		$this->assertIsInt($this->folder_id);

		$visible_child_id = ResourceTreeHandler::addResourceEntry([
			'resource_name' => 'visible-' . bin2hex(random_bytes(4)),
			'node_type' => 'folder',
		], $this->folder_id);
		$this->assertIsInt($visible_child_id);

		$hidden_child_id = ResourceTreeHandler::addResourceEntry([
			'resource_name' => 'hidden-' . bin2hex(random_bytes(4)),
			'node_type' => 'folder',
		], $this->folder_id);
		$this->assertIsInt($hidden_child_id);

		DbHelper::insertHelper('resource_acl', [
			'resource_id' => $this->folder_id,
			'subject_type' => 'user',
			'subject_id' => $this->test_user_id,
			'allow_view' => 1,
			'allow_edit' => 1,
			'allow_delete' => 1,
			'allow_publish' => 1,
			'allow_list' => 0,
			'allow_create' => 1,
		]);

		DbHelper::insertHelper('resource_acl', [
			'resource_id' => $visible_child_id,
			'subject_type' => 'user',
			'subject_id' => $this->test_user_id,
			'allow_view' => 1,
			'allow_edit' => 1,
			'allow_delete' => 1,
			'allow_publish' => 1,
			'allow_list' => 1,
			'allow_create' => 1,
		]);

		DbHelper::insertHelper('resource_acl', [
			'resource_id' => $hidden_child_id,
			'subject_type' => 'user',
			'subject_id' => $this->test_user_id,
			'allow_view' => 1,
			'allow_edit' => 1,
			'allow_delete' => 1,
			'allow_publish' => 1,
			'allow_list' => 0,
			'allow_create' => 1,
		]);

		$result = CLICommandWebRunner::execute(
			'resource:list',
			ResourceTreeHandler::getPathFromId($this->folder_id),
			[],
			['json'],
			30
		);

		$this->assertTrue($result['ok'], $result['error'] !== '' ? $result['error'] : $result['output']);
		$this->assertIsArray($result['json_data']);

		$resources = $result['json_data']['resources'] ?? null;
		$this->assertIsArray($resources);

		$resource_ids = array_map(
			static fn (array $resource): int => (int) ($resource['node_id'] ?? 0),
			$resources
		);

		$this->assertContains($visible_child_id, $resource_ids);
		$this->assertNotContains($hidden_child_id, $resource_ids);
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

		return (int) $this->root_id;
	}

	/**
	 * @return array{0: int, 1: int}
	 */
	private function getSubtreeBounds(int $resource_id): array
	{
		$node = ResourceTreeHandler::getResourceTreeEntryDataById($resource_id);
		$this->assertIsArray($node);

		return [(int) $node['lft'], (int) $node['rgt']];
	}
}
