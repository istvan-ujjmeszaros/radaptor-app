<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ResourceTreeDeletionTest extends TestCase
{
	private const int TEST_USER_ID = 424242;

	private array $temp_files = [];
	private array $blob_paths = [];
	private array $file_ids = [];
	private array $root_ids = [];

	protected function setUp(): void
	{
		User::logout();
		User::bootstrapTrustedCurrentUser([
			'user_id' => self::TEST_USER_ID,
			'username' => 'resource-tree-deletion-test',
		]);
	}

	protected function tearDown(): void
	{
		try {
			foreach ($this->root_ids as $root_id) {
				if (is_int($root_id) && ResourceTreeHandler::getResourceTreeEntryDataById($root_id) !== null) {
					ResourceTreeHandler::deleteResourceEntriesRecursive($root_id);
				}

				if (is_int($root_id)) {
					DbHelper::runCustomQuery('DELETE FROM resource_acl WHERE resource_id=?', [$root_id]);
				}
			}

			foreach ($this->file_ids as $file_id) {
				if (is_int($file_id) && FileContainer::getDataFromFileId($file_id) !== false) {
					FileContainer::delFile($file_id);
				}
			}
		} finally {
			User::logout();

			foreach ($this->temp_files as $temp_file) {
				if (is_string($temp_file) && $temp_file !== '' && file_exists($temp_file)) {
					@unlink($temp_file);
				}
			}

			foreach ($this->blob_paths as $blob_path) {
				if (is_string($blob_path) && $blob_path !== '' && file_exists($blob_path)) {
					@unlink($blob_path);
				}
			}
		}
	}

	public function testDeleteResourceEntryRemovesFileBlobAndFileRow(): void
	{
		$root = $this->getDomainRootNode();
		$file_id = $this->createStoredFile();
		$blob_path = FileContainer::realPathFromFileId($file_id);

		$resource_id = $this->addFileResource((int) $root['node_id'], $file_id, 'resource-tree-delete-file');

		$this->assertTrue(ResourceTreeHandler::deleteResourceEntry($resource_id));
		$this->assertNull(ResourceTreeHandler::getResourceTreeEntryDataById($resource_id));
		$this->assertFalse(file_exists($blob_path));
		$this->assertFalse(FileContainer::getDataFromFileId($file_id));
		$this->forgetFile($file_id, $blob_path);
	}

	public function testDeleteResourceEntryKeepsSharedHashBlobUntilLastFileIdReferenceIsGone(): void
	{
		$root = $this->getDomainRootNode();
		$payload = random_bytes(1024) . bin2hex(random_bytes(16));

		$file_id_a = $this->createStoredFile($payload);
		$file_id_b = $this->createStoredFile($payload);

		$this->assertNotSame($file_id_a, $file_id_b);

		$blob_path = FileContainer::realPathFromFileId($file_id_a);
		$this->assertSame($blob_path, FileContainer::realPathFromFileId($file_id_b));

		$resource_id_a = $this->addFileResource((int) $root['node_id'], $file_id_a, 'resource-tree-delete-shared-hash-a');
		$resource_id_b = $this->addFileResource((int) $root['node_id'], $file_id_b, 'resource-tree-delete-shared-hash-b');

		$this->assertTrue(ResourceTreeHandler::deleteResourceEntry($resource_id_a));
		$this->assertNull(ResourceTreeHandler::getResourceTreeEntryDataById($resource_id_a));
		$this->assertNotNull(ResourceTreeHandler::getResourceTreeEntryDataById($resource_id_b));
		$this->assertFalse(FileContainer::getDataFromFileId($file_id_a));
		$this->assertNotFalse(FileContainer::getDataFromFileId($file_id_b));
		$this->assertFileExists($blob_path);
		$this->forgetFile($file_id_a);

		$this->assertTrue(ResourceTreeHandler::deleteResourceEntry($resource_id_b));
		$this->assertNull(ResourceTreeHandler::getResourceTreeEntryDataById($resource_id_b));
		$this->assertFalse(FileContainer::getDataFromFileId($file_id_b));
		$this->assertFalse(file_exists($blob_path));
		$this->forgetFile($file_id_b, $blob_path);
	}

	public function testDeleteResourceEntryKeepsSharedExactFileIdUntilLastResourceReferenceIsGone(): void
	{
		$root = $this->getDomainRootNode();
		$file_id = $this->createStoredFile();
		$blob_path = FileContainer::realPathFromFileId($file_id);

		$resource_id_a = $this->addFileResource((int) $root['node_id'], $file_id, 'resource-tree-delete-shared-file-id-a');
		$resource_id_b = $this->addFileResource((int) $root['node_id'], $file_id, 'resource-tree-delete-shared-file-id-b');

		$this->assertTrue(ResourceTreeHandler::deleteResourceEntry($resource_id_a));
		$this->assertNull(ResourceTreeHandler::getResourceTreeEntryDataById($resource_id_a));
		$this->assertNotNull(ResourceTreeHandler::getResourceTreeEntryDataById($resource_id_b));
		$this->assertNotFalse(FileContainer::getDataFromFileId($file_id));
		$this->assertFileExists($blob_path);

		$this->assertTrue(ResourceTreeHandler::deleteResourceEntry($resource_id_b));
		$this->assertNull(ResourceTreeHandler::getResourceTreeEntryDataById($resource_id_b));
		$this->assertFalse(FileContainer::getDataFromFileId($file_id));
		$this->assertFalse(file_exists($blob_path));
		$this->forgetFile($file_id, $blob_path);
	}

	public function testDeleteResourceEntriesRecursiveCountsFiles(): void
	{
		$root = $this->getDomainRootNode();

		$folder_id = ResourceTreeHandler::addResourceEntry([
			'resource_name' => '_resource-tree-delete-' . bin2hex(random_bytes(4)),
			'node_type' => 'folder',
		], (int) $root['node_id']);

		$this->assertIsInt($folder_id);

		$file_id = $this->createStoredFile();
		$blob_path = FileContainer::realPathFromFileId($file_id);

		$file_resource_id = $this->addFileResource($folder_id, $file_id, 'resource-tree-delete-recursive');

		$result = ResourceTreeHandler::deleteResourceEntriesRecursive($folder_id);

		$this->assertTrue($result['success']);
		$this->assertSame(1, $result['folder']);
		$this->assertSame(1, $result['file']);
		$this->assertSame(0, $result['webpage']);
		$this->assertSame(0, $result['erroneous']);
		$this->assertNull(ResourceTreeHandler::getResourceTreeEntryDataById($folder_id));
		$this->assertNull(ResourceTreeHandler::getResourceTreeEntryDataById($file_resource_id));
		$this->assertFalse(file_exists($blob_path));
		$this->assertFalse(FileContainer::getDataFromFileId($file_id));
		$this->forgetFile($file_id, $blob_path);
	}

	private function createStoredFile(?string $payload = null): int
	{
		$source_path = tempnam(sys_get_temp_dir(), 'radaptor-resource-delete-');
		$this->assertNotFalse($source_path);
		$this->temp_files[] = $source_path;

		file_put_contents($source_path, $payload ?? (random_bytes(1024) . bin2hex(random_bytes(16))));

		$file_id = FileContainer::addFile($source_path);
		$this->assertIsInt($file_id);
		$this->file_ids[] = $file_id;
		$this->blob_paths[] = FileContainer::realPathFromFileId($file_id);

		return $file_id;
	}

	private function addFileResource(int $parent_id, int $file_id, string $prefix): int
	{
		$resource_id = ResourceTreeHandler::addResourceEntry([
			'resource_name' => $prefix . '-' . bin2hex(random_bytes(6)) . '.bin',
			'node_type' => 'file',
			'file_id' => $file_id,
			'mime' => 'application/octet-stream',
		], $parent_id);

		$this->assertIsInt($resource_id);

		return $resource_id;
	}

	private function forgetFile(int $file_id, ?string $blob_path = null): void
	{
		$this->file_ids = array_values(array_filter(
			$this->file_ids,
			static fn (int $id): bool => $id !== $file_id
		));

		if ($blob_path !== null) {
			$this->blob_paths = array_values(array_filter(
				$this->blob_paths,
				static fn (string $path): bool => $path !== $blob_path
			));
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getDomainRootNode(): array
	{
		$root_id = ResourceTreeHandler::addResourceEntry([
			'node_type' => 'root',
			'resource_name' => 'resource-tree-delete-' . bin2hex(random_bytes(6)) . '.local',
		]);
		$this->assertIsInt($root_id);
		$this->root_ids[] = $root_id;

		DbHelper::insertHelper('resource_acl', [
			'resource_id' => $root_id,
			'subject_type' => 'user',
			'subject_id' => self::TEST_USER_ID,
			'allow_view' => 1,
			'allow_edit' => 1,
			'allow_delete' => 1,
			'allow_publish' => 1,
			'allow_list' => 1,
			'allow_create' => 1,
		]);

		$root = ResourceTreeHandler::getResourceTreeEntryDataById($root_id);
		$this->assertIsArray($root);

		return $root;
	}
}
