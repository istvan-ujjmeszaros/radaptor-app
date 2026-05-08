<?php

declare(strict_types=1);

final class LocaleContentRoutingTest extends TransactionedTestCase
{
	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();
		TestHelperEnvironment::setEnvironmentVariable('RADAPTOR_SITE_CONTEXT', 'app');
		TestHelperEnvironment::setEnvironmentVariable('APP_DOMAIN_CONTEXT', 'app');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME', 'locale_content_admin');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD', 'locale_content_password');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE', 'en-US');
		TestHelperEnvironment::setEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE', 'UTC');

		RequestContextHolder::initializeRequest(server: [
			'REQUEST_URI' => '/admin/',
			'HTTP_HOST' => 'localhost',
			'SERVER_PORT' => '80',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'HTTPS' => '',
		]);

		LocaleAdminService::ensureDefaultLocaleRegistered();
		LocaleAdminService::ensureLocale('hu-HU', true);

		$seed = new SeedSkeletonBootstrap();
		$seed->run(new SeedContext('app', 'mandatory', DEPLOY_ROOT . 'app', false));
	}

	#[\Override]
	protected function tearDown(): void
	{
		TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_TIMEZONE');
		TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_LOCALE');
		TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_PASSWORD');
		TestHelperEnvironment::revertEnvironmentVariable('APP_BOOTSTRAP_ADMIN_USERNAME');
		TestHelperEnvironment::revertEnvironmentVariable('APP_DOMAIN_CONTEXT');
		TestHelperEnvironment::revertEnvironmentVariable('RADAPTOR_SITE_CONTEXT');
		parent::tearDown();
	}

	public function testResourceLocaleIsInheritedFromNearestAncestor(): void
	{
		$folder_id = CmsResourceSpecService::upsertFolder(['path' => '/hu/']);
		$this->setResourceLocale($folder_id, 'hu-HU');

		$page_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/about.html',
			'layout' => 'public_default',
		]);

		$this->assertSame('hu-HU', ResourceLocaleService::getInheritedContentLocale($page_id));
		$this->assertSame('hu-HU', ResourceLocaleService::getRenderLocale($page_id));
	}

	public function testLocaleSwitchRedirectStatusUsesSeeOtherAfterPostAndTemporaryAfterGet(): void
	{
		$this->assertSame('HTTP/1.1 303 See Other', Url::redirectStatusHeader(EventUserSetLocale::getRedirectStatusForMethod('POST')));
		$this->assertSame('HTTP/1.1 302 Found', Url::redirectStatusHeader(EventUserSetLocale::getRedirectStatusForMethod('GET')));
	}

	public function testComputedHomeUsesIndexUnderExplicitLocaleFolderAndKeepsManualOverride(): void
	{
		$folder_id = CmsResourceSpecService::upsertFolder(['path' => '/hu/']);
		$this->setResourceLocale($folder_id, 'hu-HU');

		$index_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/index.html',
			'layout' => 'public_default',
		]);
		$manual_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/manual-home.html',
			'layout' => 'public_default',
		]);

		LocaleHomeResourceService::refreshAll();
		$this->assertSame($index_id, LocaleHomeResourceService::getEffectiveHomeResourceId('app', 'hu-HU'));

		$stmt = Db::instance()->prepare(
			"UPDATE `locale_home_resources`
			SET `manual_resource_id` = ?
			WHERE `site_context` = ? AND `locale` = ?"
		);
		$stmt->execute([$manual_id, 'app', 'hu-HU']);

		CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/new-child.html',
			'layout' => 'public_default',
		]);

		$this->assertSame($manual_id, LocaleHomeResourceService::getEffectiveHomeResourceId('app', 'hu-HU'));
	}

	public function testComputedHomeUsesFirstExplicitLocaleWebpage(): void
	{
		CmsResourceSpecService::upsertFolder(['path' => '/hu/']);
		$explicit_page_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/about.html',
			'layout' => 'public_default',
		]);
		$this->setResourceLocale($explicit_page_id, 'hu-HU');

		LocaleHomeResourceService::refreshAll();

		$this->assertSame($explicit_page_id, LocaleHomeResourceService::getEffectiveHomeResourceId('app', 'hu-HU'));
	}

	public function testComputedHomeDoesNotSkipFirstExplicitFolderWithoutDirectIndex(): void
	{
		$folder_id = CmsResourceSpecService::upsertFolder(['path' => '/hu/']);
		$this->setResourceLocale($folder_id, 'hu-HU');
		$explicit_child_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/about.html',
			'layout' => 'public_default',
		]);
		$this->setResourceLocale($explicit_child_id, 'hu-HU');
		CmsResourceSpecService::upsertFolder(['path' => '/hu/landing/']);
		CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/landing/index.html',
			'layout' => 'public_default',
		]);

		LocaleHomeResourceService::refreshAll();

		$this->assertNull(LocaleHomeResourceService::getEffectiveHomeResourceId('app', 'hu-HU'));
	}

	public function testDoctorReportsStaleComputedHomeForFirstExplicitWebpage(): void
	{
		CmsResourceSpecService::upsertFolder(['path' => '/hu/']);
		$explicit_page_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/about.html',
			'layout' => 'public_default',
		]);
		$this->setResourceLocale($explicit_page_id, 'hu-HU');

		LocaleHomeResourceService::refreshAll();
		$this->assertSame($explicit_page_id, LocaleHomeResourceService::getEffectiveHomeResourceId('app', 'hu-HU'));

		$stmt = Db::instance()->prepare(
			"UPDATE `locale_home_resources`
			SET `computed_resource_id` = NULL
			WHERE `site_context` = ? AND `locale` = ?"
		);
		$stmt->execute(['app', 'hu-HU']);

		$this->assertDiagnosticsIssue('locale_home_resource_stale_computed', [
			'stored_resource_id' => null,
			'expected_resource_id' => $explicit_page_id,
		]);
	}

	public function testComputedHomeReturnsNullWhenFirstExplicitLocaleNodeIsProtected(): void
	{
		$login = CmsPathHelper::resolveResource('/login.html');
		$this->assertIsArray($login);
		ResourceTreeHandler::withProtectedResourceMutationBypass(function () use ($login): void {
			$this->setResourceLocale((int) ($login['node_id'] ?? 0), 'hu-HU');
		});

		$hungarian_folder_id = CmsResourceSpecService::upsertFolder(['path' => '/hu/']);
		$this->setResourceLocale($hungarian_folder_id, 'hu-HU');
		$hungarian_index_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/index.html',
			'layout' => 'public_default',
		]);

		LocaleHomeResourceService::refreshAll();

		$this->assertIsInt($hungarian_index_id);
		$this->assertNull(LocaleHomeResourceService::getEffectiveHomeResourceId('app', 'hu-HU'));
	}

	public function testManualHomeOverrideFallsBackWhenResourceLocaleDoesNotMatch(): void
	{
		$english_folder_id = CmsResourceSpecService::upsertFolder(['path' => '/en/']);
		$hungarian_folder_id = CmsResourceSpecService::upsertFolder(['path' => '/hu/']);
		$this->setResourceLocale($english_folder_id, 'en-US');
		$this->setResourceLocale($hungarian_folder_id, 'hu-HU');

		$english_index_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/en/index.html',
			'layout' => 'public_default',
		]);
		$hungarian_index_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/index.html',
			'layout' => 'public_default',
		]);
		LocaleHomeResourceService::refreshAll();

		$stmt = Db::instance()->prepare(
			"UPDATE `locale_home_resources`
			SET `manual_resource_id` = ?
			WHERE `site_context` = ? AND `locale` = ?"
		);
		$stmt->execute([$english_index_id, 'app', 'hu-HU']);

		$this->assertSame($hungarian_index_id, LocaleHomeResourceService::getEffectiveHomeResourceId('app', 'hu-HU'));
	}

	public function testManualHomeOverrideFallsBackAndDoctorReportsWrongSiteResource(): void
	{
		$hungarian_folder_id = CmsResourceSpecService::upsertFolder(['path' => '/hu/']);
		$this->setResourceLocale($hungarian_folder_id, 'hu-HU');
		$hungarian_index_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/index.html',
			'layout' => 'public_default',
		]);
		$other_site_index_id = $this->createOtherSiteLocalizedIndex('hu-HU');

		LocaleHomeResourceService::refreshAll();
		$stmt = Db::instance()->prepare(
			"UPDATE `locale_home_resources`
			SET `manual_resource_id` = ?
			WHERE `site_context` = ? AND `locale` = ?"
		);
		$stmt->execute([$other_site_index_id, 'app', 'hu-HU']);

		$this->assertSame($hungarian_index_id, LocaleHomeResourceService::getEffectiveHomeResourceId('app', 'hu-HU'));
		$this->assertDiagnosticsIssue('locale_home_resource_invalid', [
			'field' => 'manual_resource_id',
			'reason' => 'wrong_site_context',
		]);
	}

	public function testDoctorReportsStaleComputedHomeResource(): void
	{
		$hungarian_folder_id = CmsResourceSpecService::upsertFolder(['path' => '/hu/']);
		$this->setResourceLocale($hungarian_folder_id, 'hu-HU');
		$hungarian_index_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/index.html',
			'layout' => 'public_default',
		]);
		$other_hungarian_page_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/other.html',
			'layout' => 'public_default',
		]);

		LocaleHomeResourceService::refreshAll();
		$this->assertSame($hungarian_index_id, LocaleHomeResourceService::getEffectiveHomeResourceId('app', 'hu-HU'));

		$stmt = Db::instance()->prepare(
			"UPDATE `locale_home_resources`
			SET `computed_resource_id` = ?
			WHERE `site_context` = ? AND `locale` = ?"
		);
		$stmt->execute([$other_hungarian_page_id, 'app', 'hu-HU']);

		$this->assertDiagnosticsIssue('locale_home_resource_stale_computed', [
			'stored_resource_id' => $other_hungarian_page_id,
			'expected_resource_id' => $hungarian_index_id,
		]);
	}

	public function testDoctorReportsRichTextWidgetLocaleMismatch(): void
	{
		$hungarian_folder_id = CmsResourceSpecService::upsertFolder(['path' => '/hu/']);
		$this->setResourceLocale($hungarian_folder_id, 'hu-HU');
		$page_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/rich.html',
			'layout' => 'public_default',
		]);
		$content_id = EntityRichtext::createFromArray([
			'content_type' => 'info',
			'locale' => 'en-US',
			'name' => 'test-mismatched-richtext-widget',
			'title' => 'English content',
			'content' => '<p>English content</p>',
		])->pkey();
		$connection_id = Widget::assignWidgetToWebpage($page_id, 'content', WidgetList::RICHTEXT);
		$this->assertIsInt($connection_id);
		AttributeHandler::addAttribute(
			new AttributeResourceIdentifier(ResourceNames::WIDGET_CONNECTION, (string) $connection_id),
			['content_id' => (int) $content_id],
			true
		);

		$this->assertSame('hu-HU', ResourceLocaleService::getInheritedContentLocale($page_id));
		$this->assertSame('en-US', EntityRichtext::findById((int) $content_id)?->dto()['locale'] ?? null);
		$this->assertSame(1, (int) DbHelper::selectOneColumnFromQuery(
			"SELECT COUNT(*)
			FROM `widget_connections` wc
			INNER JOIN `attributes` a
				ON a.`resource_name` = 'widget_connection'
				AND a.`resource_id` = wc.`connection_id`
				AND a.`param_name` = 'content_id'
			WHERE wc.`connection_id` = ?",
			[$connection_id]
		));
		$this->assertInstanceOf(
			RichTextWidgetContentLocaleStrategy::class,
			Widget::getContentLocaleStrategy(WidgetList::RICHTEXT)
		);
		$this->assertDiagnosticsIssue('richtext_widget_locale_mismatch', [
			'connection_id' => $connection_id,
			'page_locale' => 'hu-HU',
			'content_locale' => 'en-US',
		]);
	}

	public function testWidgetSlotSyncRejectsRichTextLocaleMismatch(): void
	{
		$hungarian_folder_id = CmsResourceSpecService::upsertFolder(['path' => '/hu-sync/']);
		$this->setResourceLocale($hungarian_folder_id, 'hu-HU');
		$page_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/hu-sync/rich.html',
			'layout' => 'public_default',
		]);
		$content_id = EntityRichtext::createFromArray([
			'content_type' => 'info',
			'locale' => 'en-US',
			'name' => 'test-sync-mismatched-richtext-widget',
			'title' => 'English sync content',
			'content' => '<p>English sync content</p>',
		])->pkey();

		try {
			CmsResourceSpecService::syncWidgetSlot('/hu-sync/rich.html', 'content', [
				[
					'widget' => WidgetList::RICHTEXT,
					'attributes' => ['content_id' => (int) $content_id],
				],
			]);

			$this->fail('Expected widget slot sync to reject mismatched RichText locale.');
		} catch (RuntimeException $exception) {
			$this->assertSame(t('cms.richtext.locale_mismatch'), $exception->getMessage());
		}

		$this->assertSame([], WidgetConnection::getWidgetsForSlot($page_id, 'content'));
	}

	public function testRichTextSelectListCanExposeAllLocaleContentWithLocaleLabels(): void
	{
		EntityRichtext::createFromArray([
			'content_type' => 'info',
			'locale' => 'en-US',
			'name' => 'test-richtext-all-locales-en',
			'title' => 'English selector content',
			'content' => '<p>English selector content</p>',
		]);
		EntityRichtext::createFromArray([
			'content_type' => 'info',
			'locale' => 'hu-HU',
			'name' => 'test-richtext-all-locales-hu',
			'title' => 'Hungarian selector content',
			'content' => '<p>Hungarian selector content</p>',
		]);

		$options = EntityRichtext::getListForSelect(null, null, true);
		$labels = array_column($options, 'label');

		$this->assertContains('test-richtext-all-locales-en (en-US)', $labels);
		$this->assertContains('test-richtext-all-locales-hu (hu-HU)', $labels);
	}

	public function testRichTextSelectorLocaleFilterRendersNavigableLocaleLinks(): void
	{
		RequestContextHolder::initializeRequest(
			get: ['connection_id' => '123', 'locale' => '_all', 'referer' => '/admin/'],
			server: [
				'REQUEST_URI' => '/admin/components/richtext/selector/index.html?connection_id=123&locale=_all&referer=%2Fadmin%2F',
				'HTTP_HOST' => 'localhost',
				'SERVER_PORT' => '80',
				'SERVER_PROTOCOL' => 'HTTP/1.1',
				'HTTPS' => '',
			]
		);

		$form = new FormTypeRichTextContentSelect('rich_text_select', 'locale_filter_test', $this->treeContext());
		$locale_filter = $form->getInput('locale_filter');

		$this->assertInstanceOf(FormInputLinkGroup::class, $locale_filter);
		$links = $locale_filter->values;
		$urls = array_map(static fn (array $link): string => urldecode((string) ($link['url'] ?? '')), $links);

		$this->assertTrue((bool) ($links[0]['active'] ?? false));
		$this->assertNotSame([], array_filter(
			$urls,
			static fn (string $url): bool => str_contains($url, '/admin/components/richtext/selector/index.html')
				&& str_contains($url, 'connection_id=123')
				&& str_contains($url, 'locale=_all')
				&& str_contains($url, 'referer=/admin/')
		));
		$this->assertNotSame([], array_filter(
			$urls,
			static fn (string $url): bool => str_contains($url, '/admin/components/richtext/selector/index.html')
				&& str_contains($url, 'connection_id=123')
				&& str_contains($url, 'locale=hu-HU')
				&& str_contains($url, 'referer=/admin/')
		));
	}

	public function testRichTextFormRejectsLocaleOnlyMoveIntoDuplicateName(): void
	{
		$name = 'test-richtext-locale-only-duplicate-name';
		$english_id = EntityRichtext::createFromArray([
			'content_type' => 'info',
			'locale' => 'en-US',
			'name' => $name,
			'title' => 'English form content',
			'content' => '<p>English form content</p>',
		])->pkey();
		EntityRichtext::createFromArray([
			'content_type' => 'info',
			'locale' => 'hu-HU',
			'name' => $name,
			'title' => 'Hungarian form content',
			'content' => '<p>Hungarian form content</p>',
		]);
		$original_get = $_GET;
		$original_post = $_POST;

		try {
			$_GET = ['item_id' => (string) $english_id, 'referer' => '/admin/'];
			$_POST = [];
			RequestContextHolder::initializeRequest(get: $_GET, post: $_POST);
			$draft_form = new FormTypeRichText('rich_text', 'locale_only_duplicate_test', $this->treeContext(), '/admin/');
			$name_input = $draft_form->getInput('name');
			$title_input = $draft_form->getInput('title');
			$locale_input = $draft_form->getInput('locale');
			$content_input = $draft_form->getInput('__content');
			$this->assertNotNull($name_input);
			$this->assertNotNull($title_input);
			$this->assertNotNull($locale_input);
			$this->assertNotNull($content_input);

			$_POST = [
				'submit_button' => AbstractForm::_SUBMIT_VALUE_SAVE,
				(string) $name_input->id => $name,
				(string) $title_input->id => 'English form content',
				(string) $locale_input->id => 'hu-HU',
				(string) $content_input->id => '<p>English form content</p>',
			];
			RequestContextHolder::initializeRequest(get: $_GET, post: $_POST);
			$submitted_form = new FormTypeRichText('rich_text', 'locale_only_duplicate_test', $this->treeContext(), '/admin/');

			$this->assertContains(t('cms.richtext.field.name.unique_error'), $submitted_form->getInput('name')?->getErrors() ?? []);
			$this->assertSame('en-US', EntityRichtext::findById((int) $english_id)?->dto()['locale'] ?? null);
		} finally {
			$_GET = $original_get;
			$_POST = $original_post;
			RequestContextHolder::initializeRequest(get: $_GET, post: $_POST);
		}
	}

	public function testLocaleSwitcherUsesHomeForFixedLocaleContentAndSanitizesExternalReturnUrl(): void
	{
		$english_folder_id = CmsResourceSpecService::upsertFolder(['path' => '/en/']);
		$hungarian_folder_id = CmsResourceSpecService::upsertFolder(['path' => '/hu/']);
		$this->setResourceLocale($english_folder_id, 'en-US');
		$this->setResourceLocale($hungarian_folder_id, 'hu-HU');

		$english_index_id = CmsResourceSpecService::upsertWebpage([
			'path' => '/en/index.html',
			'layout' => 'public_default',
		]);
		CmsResourceSpecService::upsertWebpage([
			'path' => '/hu/about.html',
			'layout' => 'public_default',
		]);
		LocaleHomeResourceService::refreshAll();

		$this->assertSame(
			Url::getCurrentHost(),
			LocaleSwitchService::sanitizeSameSiteReturnUrl('https://example.invalid/admin/')
		);
		$this->assertSame(
			Url::getCurrentHost(),
			LocaleSwitchService::sanitizeSameSiteReturnUrl('javascript:alert(1)')
		);
		$this->assertSame(
			Url::getCurrentHost(),
			LocaleSwitchService::sanitizeSameSiteReturnUrl('//localhost/admin/')
		);
		$this->assertSame(
			Url::getCurrentHost(),
			LocaleSwitchService::sanitizeSameSiteReturnUrl('http://localhost:8080/admin/')
		);
		$this->assertSame(
			'/admin/',
			LocaleSwitchService::sanitizeSameSiteReturnUrl('/admin/')
		);
		$this->assertSame(
			'http://localhost/admin/',
			LocaleSwitchService::sanitizeSameSiteReturnUrl('http://localhost/admin/')
		);
		$this->assertSame(
			Url::getSeoUrl($english_index_id) ?? Url::getCurrentHost(),
			LocaleSwitchService::resolveRedirectUrlForLocale('/hu/about.html', 'en-US')
		);
	}

	private function setResourceLocale(int $resource_id, string $locale): void
	{
		$result = ResourceTreeHandler::updateResourceTreeEntryResult(['locale' => $locale], $resource_id);
		$this->assertTrue($result->ok, $result->error?->message ?? 'Resource locale update failed.');
	}

	private function createOtherSiteLocalizedIndex(string $locale): int
	{
		$root_result = ResourceTreeHandler::addResourceEntryResult([
			'node_type' => 'root',
			'resource_name' => 'other-site',
		]);
		$this->assertTrue($root_result->ok, $root_result->error?->message ?? 'Other site root creation failed.');
		$root_id = (int) $root_result->data;
		$folder_result = ResourceTreeHandler::addResourceEntryResult([
			'node_type' => 'folder',
			'resource_name' => 'other-hu',
			'locale' => $locale,
		], $root_id);
		$this->assertTrue($folder_result->ok, $folder_result->error?->message ?? 'Other site locale folder creation failed.');
		$folder_id = (int) $folder_result->data;
		$page_result = ResourceTreeHandler::addResourceEntryResult([
			'node_type' => 'webpage',
			'resource_name' => 'other-index.html',
			'layout' => 'public_default',
		], $folder_id);
		$this->assertTrue($page_result->ok, $page_result->error?->message ?? 'Other site index creation failed.');

		return (int) $page_result->data;
	}

	private function treeContext(): iTreeBuildContext
	{
		return new class () implements iTreeBuildContext {
			public function getPageId(): ?int
			{
				return null;
			}

			public function getPagedata($key)
			{
				return null;
			}

			public function registerRenderedLayoutComponent(iLayoutComponent $layoutComponent): void
			{
			}

			public function getLayoutTypeName(): ?string
			{
				return 'admin_default';
			}

			public function addToTitle(string $addition): void
			{
			}

			public function isEditable(): bool
			{
				return true;
			}

			public function getTheme(): ?AbstractThemeData
			{
				return null;
			}

			public function overrideLayoutType(string $layoutTypeName): void
			{
			}
		};
	}

	/**
	 * @param array<string, mixed> $expected
	 */
	private function assertDiagnosticsIssue(string $code, array $expected): void
	{
		$diagnostics = LocaleDiagnosticsService::diagnose();
		$matches = array_values(array_filter(
			$diagnostics['issues'] ?? [],
			static function (array $issue) use ($code, $expected): bool {
				if (($issue['code'] ?? null) !== $code) {
					return false;
				}

				foreach ($expected as $key => $value) {
					if (($issue[$key] ?? null) !== $value) {
						return false;
					}
				}

				return true;
			}
		));

		$this->assertNotSame([], $matches, json_encode($diagnostics['issues'] ?? [], JSON_PRETTY_PRINT) ?: 'Expected diagnostics issue missing.');
	}
}
