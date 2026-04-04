<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BrowserEventSlugHelperTest extends TestCase
{
	public function testEventNameAndSlugRoundTripUsesRouteSyntax(): void
	{
		$this->assertSame('resource:view', BrowserEventSlugHelper::eventNameToSlug('resource.view'));
		$this->assertSame('i18n_ajax:tm-suggest-fuzzy', BrowserEventSlugHelper::eventNameToSlug('i18n_ajax.tm-suggest-fuzzy'));
		$this->assertSame('i18n_ajax.tm-suggest-fuzzy', BrowserEventSlugHelper::slugToEventName('i18n_ajax:tm-suggest-fuzzy'));
	}

	public function testShortNameLookupStillSupportsLegacyUnderscoreContexts(): void
	{
		$this->assertSame('JstreeResourcesAjaxLoad', BrowserEventSlugHelper::slugToShortName('jstree_resources_ajax', 'load'));
		$this->assertSame('UsersUserListAjaxLoad', BrowserEventSlugHelper::slugToShortName('users_user_list_ajax', 'load'));
		$this->assertSame('SystemmessagesRenderSystemMessages', BrowserEventSlugHelper::slugToShortName('systemmessages', 'render-system-messages'));
	}
}
