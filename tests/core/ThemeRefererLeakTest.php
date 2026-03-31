<?php

/**
 * Regression test for referer-based theme leaking.
 *
 * Bug: getThemeNameForUser() inherited ?theme= from HTTP_REFERER on ALL
 * requests in non-production environments. Regular browser navigations
 * carry the previous page's URL as HTTP_REFERER, which leaked the theme
 * across pages — even to the login form.
 *
 * Fix: The referer theme inheritance now only applies to AJAX/htmx
 * requests (HTTP_X_REQUESTED_WITH or HTTP_HX_REQUEST headers).
 */
class ThemeRefererLeakTest extends TransactionedTestCase
{
	/** @var array<string, mixed> */
	private array $_savedServer = [];

	private static array $_serverKeysToRestore = [
		'HTTP_REFERER',
		'HTTP_X_REQUESTED_WITH',
		'HTTP_HX_REQUEST',
	];

	protected function setUp(): void
	{
		parent::setUp();

		// Save current $_SERVER values
		foreach (self::$_serverKeysToRestore as $key) {
			if (array_key_exists($key, $_SERVER)) {
				$this->_savedServer[$key] = $_SERVER[$key];
			}
		}

		// Clear all relevant headers
		foreach (self::$_serverKeysToRestore as $key) {
			unset($_SERVER[$key]);
		}

		// Initialize a clean request context with no GET params
		RequestContextHolder::initializeRequest();
	}

	protected function tearDown(): void
	{
		// Restore $_SERVER
		foreach (self::$_serverKeysToRestore as $key) {
			if (array_key_exists($key, $this->_savedServer)) {
				$_SERVER[$key] = $this->_savedServer[$key];
			} else {
				unset($_SERVER[$key]);
			}
		}

		parent::tearDown();
	}

	/**
	 * REGRESSION: A normal browser navigation with HTTP_REFERER containing
	 * ?theme=RadaptorPortalAdmin must NOT inherit that theme.
	 *
	 * Before the fix, this returned 'RadaptorPortalAdmin' (leaked from referer).
	 */
	public function testNonAjaxRequestDoesNotInheritThemeFromReferer(): void
	{
		$_SERVER['HTTP_REFERER'] = 'http://localhost/admin/index.html?theme=RadaptorPortalAdmin';
		// No HTTP_X_REQUESTED_WITH or HTTP_HX_REQUEST — this is a normal navigation

		$result = Themes::getThemeNameForUser(null);

		$this->assertNotEquals(
			'RadaptorPortalAdmin',
			$result,
			'Regular browser navigation must not inherit ?theme= from HTTP_REFERER'
		);
		$this->assertEquals('SoAdmin', $result);
	}

	/**
	 * AJAX requests SHOULD inherit ?theme= from referer.
	 * This is the intentional behavior for dyna panel / htmx partial loads.
	 */
	public function testAjaxRequestInheritsThemeFromReferer(): void
	{
		$_SERVER['HTTP_REFERER'] = 'http://localhost/admin/index.html?theme=RadaptorPortalAdmin';
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$result = Themes::getThemeNameForUser(null);

		$this->assertEquals(
			'RadaptorPortalAdmin',
			$result,
			'AJAX requests should inherit ?theme= from HTTP_REFERER'
		);
	}

	/**
	 * htmx requests SHOULD also inherit ?theme= from referer.
	 */
	public function testHtmxRequestInheritsThemeFromReferer(): void
	{
		$_SERVER['HTTP_REFERER'] = 'http://localhost/admin/index.html?theme=Tracker';
		$_SERVER['HTTP_HX_REQUEST'] = 'true';

		$result = Themes::getThemeNameForUser(null);

		$this->assertEquals(
			'Tracker',
			$result,
			'htmx requests should inherit ?theme= from HTTP_REFERER'
		);
	}

	/**
	 * Referer without ?theme= param should not affect theme resolution.
	 */
	public function testRefererWithoutThemeParamDoesNotAffectResolution(): void
	{
		$_SERVER['HTTP_REFERER'] = 'http://localhost/admin/index.html';
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$result = Themes::getThemeNameForUser(null);

		$this->assertEquals('SoAdmin', $result);
	}

	/**
	 * Referer with an invalid theme name should not be applied.
	 */
	public function testRefererWithInvalidThemeIsIgnored(): void
	{
		$_SERVER['HTTP_REFERER'] = 'http://localhost/admin/index.html?theme=NonExistentTheme';
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$result = Themes::getThemeNameForUser(null);

		$this->assertNotEquals('NonExistentTheme', $result);
		$this->assertEquals('SoAdmin', $result);
	}

	/**
	 * Direct ?theme= URL parameter should still work (regardless of AJAX).
	 */
	public function testDirectUrlThemeParamStillWorks(): void
	{
		RequestContextHolder::initializeRequest(get: ['theme' => 'Tracker']);

		$result = Themes::getThemeNameForUser(null);

		$this->assertEquals('Tracker', $result);
	}
}
