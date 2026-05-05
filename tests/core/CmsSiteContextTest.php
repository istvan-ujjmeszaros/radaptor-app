<?php

declare(strict_types=1);

final class CmsSiteContextTest extends TransactionedTestCase
{
	#[\Override]
	protected function tearDown(): void
	{
		TestHelperEnvironment::revertEnvironmentVariable('APP_SITE_HOST_ALIASES');
		TestHelperEnvironment::revertEnvironmentVariable('APP_SITE_CONTEXT');
		TestHelperEnvironment::revertEnvironmentVariable('RADAPTOR_SITE_CONTEXT');
		$_SERVER['HTTP_HOST'] = 'localhost';
		RequestContextHolder::current()->SERVER['HTTP_HOST'] = 'localhost';
		Cache::flush();

		parent::tearDown();
	}

	public function testEnsureConfiguredSiteRootRenamesSingleLegacyRoot(): void
	{
		$app_root_id = ResourceTreeHandler::getDomainRoot('app');
		$this->assertIsInt($app_root_id);

		ResourceTreeHandler::updateResourceTreeEntry([
			'resource_name' => 'legacy.example',
		], $app_root_id);

		$normalized_root_id = ResourceTreeHandler::ensureConfiguredSiteRoot();

		$this->assertSame($app_root_id, $normalized_root_id);
		$this->assertSame($app_root_id, ResourceTreeHandler::getDomainRoot('app'));
		$this->assertNull(ResourceTreeHandler::getDomainRoot('legacy.example'));
		$this->assertGreaterThan(0, ResourceTreeHandler::countChildren($app_root_id));
	}

	public function testEnsureConfiguredSiteRootUsesExplicitHostAliasWhenMultipleRootsExist(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_SITE_CONTEXT', 'testapp');
		TestHelperEnvironment::setEnvironmentVariable('APP_SITE_HOST_ALIASES', '{"testapp":{"primary":"localhost","aliases":[]}}');

		$legacy_folder_id = ResourceTreeHandler::createFolderFromPath('/legacy-content/', 'legacy.example');
		$this->assertIsInt($legacy_folder_id);

		$localhost_folder_id = ResourceTreeHandler::createFolderFromPath('/localhost-content/', 'localhost');
		$this->assertIsInt($localhost_folder_id);

		$localhost_root_id = ResourceTreeHandler::getDomainRoot('localhost');
		$this->assertIsInt($localhost_root_id);

		$empty_configured_root_id = ResourceTreeHandler::createFolderFromPath('/', 'testapp');
		$this->assertIsInt($empty_configured_root_id);

		$normalized_root_id = ResourceTreeHandler::ensureConfiguredSiteRoot();

		$this->assertSame($localhost_root_id, $normalized_root_id);
		$this->assertSame($localhost_root_id, ResourceTreeHandler::getDomainRoot('testapp'));
		$this->assertNull(ResourceTreeHandler::getDomainRoot('localhost'));
		$this->assertNull(ResourceTreeHandler::getResourceTreeEntryDataById($empty_configured_root_id));
	}

	public function testAmbiguousSiteRootsExceptionNamesRootsAndConfigHint(): void
	{
		$exception = CmsSiteContext::ambiguousSiteRootsException([
			['resource_name' => 'app'],
			['resource_name' => 'other'],
		]);

		$this->assertStringContainsString('Multiple populated CMS site roots', $exception->getMessage());
		$this->assertStringContainsString('app', $exception->getMessage());
		$this->assertStringContainsString('other', $exception->getMessage());
		$this->assertStringContainsString('APP_SITE_HOST_ALIASES', $exception->getMessage());
	}

	public function testHostlessMultiSiteResolveUsesValidConfiguredSiteKey(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_SITE_CONTEXT', 'app');
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_SITE_CONTEXT', '');
		TestHelperEnvironment::setEnvironmentVariable('APP_SITE_HOST_ALIASES', '{"app":{"primary":"localhost","aliases":[]},"other":{"primary":"other.local","aliases":[]}}');
		$this->clearRequestHost();

		$folder_id = ResourceTreeHandler::createFolderFromPath('/other-content/', 'other');
		$this->assertIsInt($folder_id);

		$this->assertSame('app', CmsSiteContext::resolve());
	}

	public function testHostlessMultiSiteResolveRejectsInvalidConfiguredSiteKey(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_SITE_CONTEXT', 'missing');
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_SITE_CONTEXT', '');
		TestHelperEnvironment::setEnvironmentVariable('APP_SITE_HOST_ALIASES', '{"app":{"primary":"localhost","aliases":[]},"other":{"primary":"other.local","aliases":[]}}');
		$this->clearRequestHost();

		$folder_id = ResourceTreeHandler::createFolderFromPath('/other-content/', 'other');
		$this->assertIsInt($folder_id);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Hostless CMS site context');
		$this->expectExceptionMessage('missing');

		CmsSiteContext::resolve();
	}

	public function testSameSiteSeoUrlNeverGeneratesLogicalSiteHost(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_SITE_CONTEXT', 'app');
		TestHelperEnvironment::setEnvironmentVariable('APP_SITE_HOST_ALIASES', '');
		$this->setRequestHost('localhost');

		$login_page = ResourceTreeHandler::getResourceTreeEntryData('/', 'login.html', 'app');
		$this->assertIsArray($login_page);

		$url = Url::getSeoUrl((int) $login_page['node_id'], false);

		$this->assertIsString($url);
		$this->assertFalse(str_starts_with($url, '//app/'));
		$this->assertNull(CmsSiteContext::getPrimaryHostForSite('other'));
	}

	public function testRolesLoadAllOnlyExpandsRootRequest(): void
	{
		$root_response = $this->runCapturedEvent(new EventJstreeRolesAjaxLoad(), [
			'id' => '#',
			'id_prefix' => 'jstree_roles_test',
			'shape_template' => JsTreeApiService::TEMPLATE_JSTREE_3,
			'load_all' => '1',
		]);

		$this->assertTrue($root_response['ok'] ?? false);
		$this->assertSame(true, $root_response['data'][0]['state']['opened'] ?? null);
		$this->assertIsArray($root_response['data'][0]['children'] ?? null);

		$top_roles = Roles::getRoleTree(0);
		$this->assertNotEmpty($top_roles);

		$branch_response = $this->runCapturedEvent(new EventJstreeRolesAjaxLoad(), [
			'id' => (string) $top_roles[0]['node_id'],
			'id_prefix' => 'jstree_roles_test',
			'shape_template' => JsTreeApiService::TEMPLATE_JSTREE_3,
			'load_all' => '1',
		]);

		$this->assertTrue($branch_response['ok'] ?? false);
		$this->assertArrayNotHasKey('state', $branch_response['data'][0] ?? []);
	}

	public function testUsergroupFullTreeShapeMatchesBrowserAdapterContract(): void
	{
		$response = $this->runCapturedEvent(new EventJstreeUsergroupsAjaxLoad(), [
			'id' => '#',
			'id_prefix' => 'jstree_usergroups_test',
			'shape_template' => JsTreeApiService::TEMPLATE_JSTREE_3,
			'load_all' => '1',
		]);

		$this->assertTrue($response['ok'] ?? false);
		$this->assertSame('0', $response['data'][0]['id'] ?? null);
		$this->assertSame('root', $response['data'][0]['type'] ?? null);
		$this->assertSame(true, $response['data'][0]['state']['opened'] ?? null);
		$this->assertIsArray($response['data'][0]['children'] ?? null);
	}

	public function testResourceTreeLoadReturnsStructuredErrorForAmbiguousSiteRoots(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_SITE_HOST_ALIASES', '');

		$folder_id = ResourceTreeHandler::createFolderFromPath('/other-content/', 'other');
		$this->assertIsInt($folder_id);

		$response = $this->runCapturedEvent(new EventJstreeResourcesAjaxLoad(), [
			'id' => '#',
			'id_prefix' => 'jstree_resources_test',
			'shape_template' => JsTreeApiService::TEMPLATE_JSTREE_3,
		]);

		$this->assertFalse($response['ok'] ?? true);
		$this->assertSame('ROOT_RESOLUTION_FAILED', $response['error']['code'] ?? null);
		$this->assertStringContainsString('Multiple populated CMS site roots', $response['error']['message'] ?? '');
	}

	public function testResourceTreeInitialLoadReturnsSelectableRootNode(): void
	{
		$root_id = CmsSiteContext::getCurrentRootId();
		$this->assertIsInt($root_id);

		$response = $this->runCapturedEvent(new EventJstreeResourcesAjaxLoad(), [
			'id' => '#',
			'id_prefix' => 'jstree_resources_test',
			'shape_template' => JsTreeApiService::TEMPLATE_JSTREE_3,
		]);

		$this->assertTrue($response['ok'] ?? false);
		$this->assertSame(ResourceTreeHandler::JSTREE_SITE_ROOT_ID, $response['data'][0]['id'] ?? null);
		$this->assertSame('root', $response['data'][0]['type'] ?? null);
		$this->assertSame(true, $response['data'][0]['state']['opened'] ?? null);
		$this->assertSame(0, $response['data'][0]['data']['node_id'] ?? null);

		$branch_response = $this->runCapturedEvent(new EventJstreeResourcesAjaxLoad(), [
			'id' => ResourceTreeHandler::JSTREE_SITE_ROOT_ID,
			'id_prefix' => 'jstree_resources_test',
			'shape_template' => JsTreeApiService::TEMPLATE_JSTREE_3,
		]);

		$this->assertTrue($branch_response['ok'] ?? false);
		$this->assertIsArray($branch_response['data']);

		if ($branch_response['data'] !== []) {
			$this->assertNotSame((string) $root_id, $branch_response['data'][0]['id'] ?? null);
		}
	}

	public function testProtectedResourceMoveReturnsStructuredError(): void
	{
		$root_id = CmsSiteContext::getCurrentRootId();
		$this->assertIsInt($root_id);
		$login_page = ResourceTreeHandler::getResourceTreeEntryData('/', 'login.html', 'app');
		$this->assertIsArray($login_page);

		$response = $this->runCapturedEvent(new EventJstreeResourcesAjaxMove(), [
			'id' => (string) $login_page['node_id'],
			'ref' => (string) $root_id,
			'position' => '0',
		]);

		$this->assertFalse($response['ok'] ?? true);
		$this->assertSame('PROTECTED_RESOURCE_MUTATION', $response['error']['code'] ?? null);
		$this->assertStringContainsString('/login.html', $response['meta']['message'] ?? '');
	}

	public function testProtectedResourceDeleteReturnsStructuredError(): void
	{
		$login_page = ResourceTreeHandler::getResourceTreeEntryData('/', 'login.html', 'app');
		$this->assertIsArray($login_page);

		$response = $this->runCapturedEvent(new EventJstreeResourcesAjaxDeleteRecursive(), [
			'id' => (string) $login_page['node_id'],
		]);

		$this->assertFalse($response['ok'] ?? true);
		$this->assertSame('RESOURCE_DELETE_FAILED', $response['error']['code'] ?? null);
		$this->assertStringContainsString('/login.html', implode(' ', $response['meta']['messages'] ?? []));
	}

	public function testVirtualResourceTreeRootCannotBeDeleted(): void
	{
		$response = $this->runCapturedEvent(new EventJstreeResourcesAjaxDeleteRecursive(), [
			'id' => ResourceTreeHandler::JSTREE_SITE_ROOT_ID,
		]);

		$this->assertFalse($response['ok'] ?? true);
		$this->assertSame('RESOURCE_DELETE_FAILED', $response['error']['code'] ?? null);
		$this->assertStringContainsString('/', implode(' ', $response['meta']['messages'] ?? []));
	}

	/**
	 * @return array<string, mixed>
	 */
	private function runCapturedEvent(AbstractEvent $event, array $get): array
	{
		$ctx = RequestContextHolder::current();
		$previous_get = $ctx->GET;
		$previous_capture = $ctx->apiResponseCaptureEnabled;
		$previous_response = $ctx->capturedApiResponse;
		$previous_http_code = $ctx->capturedApiResponseHttpCode;

		try {
			$ctx->GET = $get;
			$ctx->apiResponseCaptureEnabled = true;
			$ctx->capturedApiResponse = null;
			$ctx->capturedApiResponseHttpCode = null;

			$event->run();

			return $ctx->capturedApiResponse ?? [];
		} finally {
			$ctx->GET = $previous_get;
			$ctx->apiResponseCaptureEnabled = $previous_capture;
			$ctx->capturedApiResponse = $previous_response;
			$ctx->capturedApiResponseHttpCode = $previous_http_code;
		}
	}

	private function setRequestHost(string $host): void
	{
		$_SERVER['HTTP_HOST'] = $host;
		RequestContextHolder::current()->SERVER['HTTP_HOST'] = $host;
		Cache::flush();
	}

	private function clearRequestHost(): void
	{
		$_SERVER['HTTP_HOST'] = '';
		$_SERVER['SERVER_NAME'] = '';
		RequestContextHolder::current()->SERVER['HTTP_HOST'] = '';
		RequestContextHolder::current()->SERVER['SERVER_NAME'] = '';
		Cache::flush();
	}
}
