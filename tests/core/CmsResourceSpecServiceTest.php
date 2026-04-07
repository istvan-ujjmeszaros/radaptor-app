<?php

declare(strict_types=1);

final class CmsResourceSpecServiceTest extends TransactionedTestCase
{
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
