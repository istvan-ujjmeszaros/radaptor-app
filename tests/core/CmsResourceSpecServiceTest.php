<?php

declare(strict_types=1);

final class CmsResourceSpecServiceTest extends TransactionedTestCase
{
	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();
		TestHelperEnvironment::setEnvironmentVariable('APP_DOMAIN_CONTEXT', 'tracker.virtuosoft.hu');
	}

	#[\Override]
	protected function tearDown(): void
	{
		TestHelperEnvironment::revertEnvironmentVariable('APP_DOMAIN_CONTEXT');
		parent::tearDown();
	}

	public function testAddWidgetPersistsWidgetSettings(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME', 'cms_spec_admin');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD', 'cms_spec_password');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE', 'en_US');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');

		try {
			$seed = new SeedSkeletonBootstrap();
			$seed->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));

			$snapshot = CmsResourceSpecService::addWidget(
				'/login.html',
				'content',
				WidgetList::PLAINHTML,
				null,
				[],
				[
					'content' => '<p>Injected content</p>',
				]
			);

			$this->assertSame(WidgetList::PLAINHTML, $snapshot['widget']);
			$this->assertSame('<p>Injected content</p>', $snapshot['settings']['content'] ?? null);
		} finally {
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME');
		}
	}

	public function testUpsertWebpageUsesCatcherApiOnParentFolder(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME', 'cms_spec_admin');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD', 'cms_spec_password');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE', 'en_US');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');

		try {
			$seed = new SeedSkeletonBootstrap();
			$seed->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));

			CmsResourceSpecService::upsertFolder(['path' => '/docs/']);
			$page_id = CmsResourceSpecService::upsertWebpage([
				'path' => '/docs/index.html',
				'layout' => 'public_default',
				'catcher' => true,
			]);

			$page = ResourceTreeHandler::getResourceTreeEntryDataById($page_id);
			$parent = ResourceTreeHandler::getResourceTreeEntryDataById((int) $page['parent_id']);

			$this->assertSame($page_id, (int) ($parent['catcher_page'] ?? 0));

			CmsResourceSpecService::upsertWebpage([
				'path' => '/docs/index.html',
				'catcher' => false,
			]);

			$parent = ResourceTreeHandler::getResourceTreeEntryDataById((int) $page['parent_id']);
			$this->assertNull($parent['catcher_page'] ?? null);
		} finally {
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME');
		}
	}

	public function testResolveResourceKeepsExplicitIndexHtmlPath(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME', 'cms_spec_admin');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD', 'cms_spec_password');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE', 'en_US');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');

		try {
			$seed = new SeedSkeletonBootstrap();
			$seed->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));

			CmsResourceSpecService::upsertFolder(['path' => '/docs/']);
			$page_id = CmsResourceSpecService::upsertWebpage([
				'path' => '/docs/index.html',
				'layout' => 'public_default',
			]);

			$resolved = CmsPathHelper::resolveResource('/docs/index.html');

			$this->assertIsArray($resolved);
			$this->assertSame($page_id, (int) ($resolved['node_id'] ?? 0));
			$this->assertSame('webpage', (string) ($resolved['node_type'] ?? ''));
		} finally {
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME');
		}
	}

	public function testResolveFolderRejectsWebpageNodes(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME', 'cms_spec_admin');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD', 'cms_spec_password');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE', 'en_US');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');

		try {
			$seed = new SeedSkeletonBootstrap();
			$seed->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));

			$this->assertNull(CmsPathHelper::resolveFolder('/login.html'));
			$this->assertNull(CmsPathHelper::resolveFolder('/login.html/'));
		} finally {
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME');
		}
	}

	public function testExportWebpageSpecPrefersRenderableExtensionlessPath(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME', 'cms_spec_admin');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD', 'cms_spec_password');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE', 'en_US');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');

		try {
			$seed = new SeedSkeletonBootstrap();
			$seed->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));

			CmsResourceSpecService::upsertFolder(['path' => '/docs/']);
			CmsResourceSpecService::upsertWebpage([
				'path' => '/docs/index.html',
				'layout' => 'public_default',
			]);

			$spec = CmsResourceSpecService::exportWebpageSpec('/docs/index.html');

			$this->assertSame('/docs/', $spec['path']);
		} finally {
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME');
		}
	}

	public function testExportWebpageSpecOmitsRuntimeConnectionIdsFromWidgetSpecs(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME', 'cms_spec_admin');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD', 'cms_spec_password');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE', 'en_US');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');

		try {
			$seed = new SeedSkeletonBootstrap();
			$seed->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));

			$spec = CmsResourceSpecService::exportWebpageSpec('/login.html');

			$this->assertArrayHasKey('content', $spec['slots']);
			$this->assertNotEmpty($spec['slots']['content']);
			$this->assertArrayNotHasKey('connection_id', $spec['slots']['content'][0]);
		} finally {
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME');
		}
	}

	public function testUpsertWebpageRejectsReservedAttributeKeys(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME', 'cms_spec_admin');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD', 'cms_spec_password');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE', 'en_US');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');

		try {
			$seed = new SeedSkeletonBootstrap();
			$seed->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));

			CmsResourceSpecService::upsertFolder(['path' => '/docs/']);

			$this->expectException(InvalidArgumentException::class);
			$this->expectExceptionMessage("Webpage attribute key 'resource_name' is reserved.");

			CmsResourceSpecService::upsertWebpage([
				'path' => '/docs/index.html',
				'attributes' => [
					'resource_name' => 'oops.html',
				],
			]);
		} finally {
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME');
		}
	}

	public function testExportFolderSpecUsesSlashForDomainRootPath(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME', 'cms_spec_admin');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD', 'cms_spec_password');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE', 'en_US');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');

		try {
			$seed = new SeedSkeletonBootstrap();
			$seed->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));

			$spec = CmsResourceSpecService::exportFolderSpec('/');

			$this->assertSame('/', $spec['path']);
		} finally {
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME');
		}
	}

	public function testAddWidgetUsesWidgetSpecificSettingsHandlerWhenAvailable(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME', 'cms_spec_admin');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD', 'cms_spec_password');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE', 'en_US');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');

		try {
			$seed = new SeedSkeletonBootstrap();
			$seed->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));

			$snapshot = CmsResourceSpecService::addWidget(
				'/login.html',
				'content',
				WidgetList::PLAINHTML,
				null,
				[],
				[
					'content' => '<p>Injected content</p>',
				]
			);

			$this->assertSame(
				['content' => '<p>Injected content</p>'],
				PlainHtml::getSettings((int) $snapshot['connection_id'])
			);
			$this->assertSame([], WidgetSettings::getSettings((int) $snapshot['connection_id']));
		} finally {
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME');
		}
	}

	public function testRemoveWidgetRejectsConnectionIdsOutsideRequestedPageAndSlot(): void
	{
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME', 'cms_spec_admin');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD', 'cms_spec_password');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE', 'en_US');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');

		try {
			$seed = new SeedSkeletonBootstrap();
			$seed->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));

			CmsResourceSpecService::upsertFolder(['path' => '/docs/']);
			CmsResourceSpecService::upsertWebpage([
				'path' => '/docs/index.html',
				'layout' => 'public_default',
				'slots' => [
					'content' => [
						['widget' => WidgetList::PLAINHTML],
					],
				],
			]);

			$widget = CmsResourceSpecService::addWidget('/login.html', 'content', WidgetList::PLAINHTML);

			$this->expectException(RuntimeException::class);
			$this->expectExceptionMessage('does not belong to /docs/index.html');

			CmsResourceSpecService::removeWidget(
				'/docs/index.html',
				'content',
				(int) $widget['connection_id']
			);
		} finally {
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD');
			TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME');
		}
	}
}
