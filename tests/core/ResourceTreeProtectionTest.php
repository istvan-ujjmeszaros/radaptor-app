<?php

declare(strict_types=1);

final class ResourceTreeProtectionTest extends TransactionedTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		RequestContextHolder::initializeRequest();
		SystemMessages::flushAllMessages();
	}

	public function testProtectedSystemResourceCannotBeUpdatedDirectly(): void
	{
		$login = CmsPathHelper::resolveResource('/login.html');
		$this->assertIsArray($login);

		$result = ResourceTreeHandler::updateResourceTreeEntryResult(['layout' => 'public_default'], (int) $login['node_id']);

		$this->assertFalse($result->ok);
		$this->assertSame('PROTECTED_RESOURCE_MUTATION', $result->error?->code);
		$this->assertSame(0, ResourceTreeHandler::updateResourceTreeEntry(['layout' => 'public_default'], (int) $login['node_id']));
		$this->assertSame(0, SystemMessages::countSystemMessages());
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

		$result = ResourceTreeHandler::updateResourceTreeEntryResult(['resource_name' => 'admin'], $temp_id);

		$this->assertFalse($result->ok);
		$this->assertSame('PROTECTED_RESOURCE_PATH_MUTATION', $result->error?->code);
	}

	public function testProtectedNamespaceCannotReceiveGenericCreates(): void
	{
		$admin = CmsPathHelper::resolveFolder('/admin/');
		$this->assertIsArray($admin);

		$result = ResourceTreeHandler::addResourceEntryResult([
			'node_type' => 'folder',
			'resource_name' => 'generic-admin-child',
		], (int) $admin['node_id']);

		$this->assertFalse($result->ok);
		$this->assertSame('PROTECTED_RESOURCE_MUTATION', $result->error?->code);
		$this->assertNull(ResourceTreeHandler::addResourceEntry([
			'node_type' => 'folder',
			'resource_name' => 'generic-admin-child',
		], (int) $admin['node_id']));
		$this->assertSame(0, SystemMessages::countSystemMessages());
	}

	public function testProtectedNamespaceCannotBeDeletedRecursively(): void
	{
		$root = CmsPathHelper::resolveFolder('/');
		$this->assertIsArray($root);

		$result = ResourceTreeHandler::deleteResourceEntriesRecursiveResult((int) $root['node_id']);

		$this->assertFalse($result->ok);
		$this->assertSame('PROTECTED_RESOURCE_SUBTREE_MUTATION', $result->error?->code);
		$this->assertSame([
			'success' => false,
			'erroneous' => 1,
			'folder' => 0,
			'webpage' => 0,
			'file' => 0,
		], ResourceTreeHandler::deleteResourceEntriesRecursive((int) $root['node_id']));
		$this->assertSame(0, SystemMessages::countSystemMessages());
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

		$result = ResourceTreeHandler::moveResourceEntryToPositionResult((int) $login['node_id'], $temp_parent_id, 0);

		$this->assertFalse($result->ok);
		$this->assertSame('PROTECTED_RESOURCE_MUTATION', $result->error?->code);
		$this->assertFalse(ResourceTreeHandler::moveResourceEntryToPosition((int) $login['node_id'], $temp_parent_id, 0));
		$this->assertSame(0, SystemMessages::countSystemMessages());
	}

	public function testNonHtmlResourceMoveFailureDoesNotWriteSystemMessages(): void
	{
		$root = CmsPathHelper::resolveFolder('/');
		$login = CmsPathHelper::resolveResource('/login.html');
		$this->assertIsArray($root);
		$this->assertIsArray($login);

		$response = $this->runCapturedEvent(new EventJstreeResourcesAjaxMove(), [
			'id' => (string) $login['node_id'],
			'ref' => (string) $root['node_id'],
			'position' => '0',
		]);

		$this->assertFalse($response['ok'] ?? true);
		$this->assertSame('PROTECTED_RESOURCE_MUTATION', $response['error']['code'] ?? null);
		$this->assertSame(0, SystemMessages::countSystemMessages());
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

		$result = ResourceTreeHandler::moveResourceEntryToPositionResult($temp_id, (int) $admin['node_id'], 0);

		$this->assertFalse($result->ok);
		$this->assertSame('PROTECTED_RESOURCE_MUTATION', $result->error?->code);
	}

	public function testGenericResourceCannotBeMovedToProtectedRootPath(): void
	{
		$root = CmsPathHelper::resolveFolder('/');
		$this->assertIsArray($root);

		$temp_id = ResourceTreeHandler::createResourceTreeEntryFromPath(
			'/protected-move-container/',
			'login.html',
			'webpage',
			'public_default'
		);
		$this->assertIsInt($temp_id);

		$result = ResourceTreeHandler::moveResourceEntryToPositionResult($temp_id, (int) $root['node_id'], 0);

		$this->assertFalse($result->ok);
		$this->assertSame('PROTECTED_RESOURCE_PATH_MUTATION', $result->error?->code);
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

	/**
	 * @return array<string, mixed>
	 */
	private function runCapturedEvent(AbstractEvent $event, array $get): array
	{
		RequestContextHolder::initializeRequest(
			get: $get,
			server: ['HTTP_ACCEPT' => 'application/json']
		);
		SystemMessages::flushAllMessages();

		$ctx = RequestContextHolder::current();
		$previous_capture = $ctx->apiResponseCaptureEnabled;
		$previous_response = $ctx->capturedApiResponse;
		$previous_http_code = $ctx->capturedApiResponseHttpCode;

		try {
			$ctx->apiResponseCaptureEnabled = true;
			$ctx->capturedApiResponse = null;
			$ctx->capturedApiResponseHttpCode = null;

			$event->run();

			return $ctx->capturedApiResponse ?? [];
		} finally {
			$ctx->apiResponseCaptureEnabled = $previous_capture;
			$ctx->capturedApiResponse = $previous_response;
			$ctx->capturedApiResponseHttpCode = $previous_http_code;
		}
	}
}
