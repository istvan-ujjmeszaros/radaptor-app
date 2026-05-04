<?php

declare(strict_types=1);

final class ResourceTreeProtectionTest extends TransactionedTestCase
{
	public function testProtectedSystemResourceCannotBeUpdatedDirectly(): void
	{
		$login = CmsPathHelper::resolveResource('/login.html');
		$this->assertIsArray($login);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('protected system resource');

		ResourceTreeHandler::updateResourceTreeEntry(['layout' => 'public_default'], (int) $login['node_id']);
	}

	public function testProtectedSystemResourcePathRecognizesFolderAliases(): void
	{
		$this->assertTrue(ResourceTreeHandler::isProtectedSystemResourcePath('/admin'));
		$this->assertTrue(ResourceTreeHandler::isProtectedSystemResourcePath('/admin/'));
		$this->assertTrue(ResourceTreeHandler::isProtectedSystemResourcePath('/admin/users/'));
		$this->assertTrue(ResourceTreeHandler::isProtectedSystemResourcePath('/account'));
		$this->assertTrue(ResourceTreeHandler::isProtectedSystemResourcePath('/account/mcp-tokens/'));
		$this->assertFalse(ResourceTreeHandler::isProtectedSystemResourcePath('/administration.html'));
	}

	public function testProtectedSystemResourceCannotBeRenamedIntoPlace(): void
	{
		$root = CmsPathHelper::resolveFolder('/');
		$this->assertIsArray($root);

		$temp_id = ResourceTreeHandler::addResourceEntry([
			'node_type' => 'folder',
			'resource_name' => 'protected-rename-source',
		], (int) $root['node_id']);
		$this->assertIsInt($temp_id);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('protected system resource path');

		ResourceTreeHandler::updateResourceTreeEntry(['resource_name' => 'admin'], $temp_id);
	}

	public function testProtectedNamespaceCannotReceiveGenericCreates(): void
	{
		$admin = CmsPathHelper::resolveFolder('/admin/');
		$this->assertIsArray($admin);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('protected system resource');

		ResourceTreeHandler::addResourceEntry([
			'node_type' => 'folder',
			'resource_name' => 'generic-admin-child',
		], (int) $admin['node_id']);
	}

	public function testProtectedNamespaceCannotBeDeletedRecursively(): void
	{
		$root = CmsPathHelper::resolveFolder('/');
		$this->assertIsArray($root);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('subtree containing protected system resource');

		ResourceTreeHandler::deleteResourceEntriesRecursive((int) $root['node_id']);
	}

	public function testProtectedSystemResourceCannotBeMoved(): void
	{
		$root = CmsPathHelper::resolveFolder('/');
		$login = CmsPathHelper::resolveResource('/login.html');
		$this->assertIsArray($root);
		$this->assertIsArray($login);

		$temp_parent_id = ResourceTreeHandler::addResourceEntry([
			'node_type' => 'folder',
			'resource_name' => 'protected-move-target',
		], (int) $root['node_id']);
		$this->assertIsInt($temp_parent_id);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('protected system resource');

		ResourceTreeHandler::moveResourceEntryToPosition((int) $login['node_id'], $temp_parent_id, 0);
	}

	public function testGenericResourceCannotBeMovedIntoProtectedNamespace(): void
	{
		$root = CmsPathHelper::resolveFolder('/');
		$admin = CmsPathHelper::resolveFolder('/admin/');
		$this->assertIsArray($root);
		$this->assertIsArray($admin);

		$temp_id = ResourceTreeHandler::addResourceEntry([
			'node_type' => 'folder',
			'resource_name' => 'protected-move-source',
		], (int) $root['node_id']);
		$this->assertIsInt($temp_id);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('protected system resource');

		ResourceTreeHandler::moveResourceEntryToPosition($temp_id, (int) $admin['node_id'], 0);
	}

	public function testProtectedMutationBypassAllowsSystemOwnedMaintenance(): void
	{
		$login = CmsPathHelper::resolveResource('/login.html');
		$this->assertIsArray($login);

		$changed = ResourceTreeHandler::withProtectedResourceMutationBypass(
			static fn (): int => ResourceTreeHandler::updateResourceTreeEntry(['layout' => 'admin_login'], (int) $login['node_id'])
		);

		$this->assertIsInt($changed);
	}
}
