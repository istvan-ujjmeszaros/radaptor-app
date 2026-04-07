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
}
