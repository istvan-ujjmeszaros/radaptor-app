<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for the Request class.
 *
 * Tests cover:
 * - _GET() with scalar and array values
 * - _POST() with scalar and array values
 * - URL decoding behavior
 * - Default value handling
 * - Type coercion based on default value type
 * - allowed_values validation
 */
class RequestTest extends TestCase
{
	protected function setUp(): void
	{
		// Create a fresh request context with empty GET/POST/SERVER/COOKIE.
		// This replaces the old Request::$_GET = [] / reflection-based $_initialized reset.
		RequestContextHolder::initializeRequest();
	}

	// =========================================================================
	// _GET() Tests
	// =========================================================================

	public function testGetScalarValue(): void
	{
		RequestContextHolder::current()->GET['name'] = 'John';

		$this->assertEquals('John', Request::_GET('name'));
	}

	public function testGetMissingValueReturnsDefault(): void
	{
		$this->assertEquals('default', Request::_GET('missing', 'default'));
	}

	public function testGetMissingValueReturnsNull(): void
	{
		$this->assertNull(Request::_GET('missing'));
	}

	public function testGetArrayValue(): void
	{
		RequestContextHolder::current()->GET['ids'] = ['1', '2', '3'];

		$result = Request::_GET('ids');

		$this->assertIsArray($result);
		$this->assertEquals(['1', '2', '3'], $result);
	}

	/**
	 * Scenario: Array parameter with spaces and special characters
	 * URL: ?items[]=hello%20world&items[]=foo%26bar
	 * Expected: Array with decoded values ['hello world', 'foo&bar'].
	 */
	public function testGetArrayWithSpecialCharacters(): void
	{
		// Simulates: ?items[]=hello%20world&items[]=foo%26bar after parse_str()
		RequestContextHolder::current()->GET['items'] = ['hello world', 'foo&bar'];

		$result = Request::_GET('items');

		$this->assertIsArray($result);
		$this->assertEquals(['hello world', 'foo&bar'], $result);
	}

	/**
	 * Scenario: Simple search query with space
	 * URL: ?search=hello%20world (or ?search=hello+world)
	 * Expected: 'hello world'.
	 */
	public function testGetSearchQueryWithSpace(): void
	{
		// Simulates: ?search=hello%20world after parse_str()
		RequestContextHolder::current()->GET['search'] = 'hello world';

		$this->assertEquals('hello world', Request::_GET('search'));
	}

	/**
	 * Scenario: Query string containing & and = characters
	 * URL: ?filter=foo%26bar%3Dbaz (user searching for literal "foo&bar=baz")
	 * Expected: 'foo&bar=baz'.
	 */
	public function testGetQueryWithAmpersandAndEquals(): void
	{
		// Simulates: ?filter=foo%26bar%3Dbaz after parse_str()
		RequestContextHolder::current()->GET['filter'] = 'foo&bar=baz';

		$this->assertEquals('foo&bar=baz', Request::_GET('filter'));
	}

	public function testGetEmptyStringReturnsEmptyString(): void
	{
		RequestContextHolder::current()->GET['empty'] = '';

		$this->assertEquals('', Request::_GET('empty'));
	}

	public function testGetNumericStringValue(): void
	{
		RequestContextHolder::current()->GET['page'] = '42';

		$this->assertEquals('42', Request::_GET('page'));
	}

	public function testGetWithIntegerDefault(): void
	{
		RequestContextHolder::current()->GET['count'] = '10';

		// When default is integer, return value should be cast to integer
		$result = Request::_GET('count', 0);

		$this->assertIsInt($result);
		$this->assertEquals(10, $result);
	}

	public function testGetWithIntegerDefaultInvalidValue(): void
	{
		RequestContextHolder::current()->GET['count'] = 'not-a-number';

		// When value can't be converted to int, should return default
		$result = Request::_GET('count', 5);

		$this->assertEquals(5, $result);
	}

	public function testGetWithBooleanDefault(): void
	{
		RequestContextHolder::current()->GET['enabled'] = '1';

		$result = Request::_GET('enabled', false);

		$this->assertIsBool($result);
		$this->assertTrue($result);
	}

	// =========================================================================
	// _POST() Tests
	// =========================================================================

	public function testPostScalarValue(): void
	{
		RequestContextHolder::current()->POST['name'] = 'Jane';

		$this->assertEquals('Jane', Request::_POST('name'));
	}

	public function testPostMissingValueReturnsDefault(): void
	{
		$this->assertEquals('default', Request::_POST('missing', 'default'));
	}

	public function testPostArrayValue(): void
	{
		RequestContextHolder::current()->POST['items'] = ['a', 'b', 'c'];

		$result = Request::_POST('items');

		$this->assertIsArray($result);
		$this->assertEquals(['a', 'b', 'c'], $result);
	}

	public function testPostDoesNotUrlDecode(): void
	{
		// POST values come from form data, not URL, so no urldecode
		RequestContextHolder::current()->POST['data'] = 'hello%20world';

		// POST should return as-is (no urldecode)
		$this->assertEquals('hello%20world', Request::_POST('data'));
	}

	public function testPostWithIntegerDefault(): void
	{
		RequestContextHolder::current()->POST['quantity'] = '25';

		$result = Request::_POST('quantity', 0);

		$this->assertIsInt($result);
		$this->assertEquals(25, $result);
	}

	// =========================================================================
	// allowed_values Tests
	// =========================================================================

	public function testGetWithAllowedValues(): void
	{
		RequestContextHolder::current()->GET['status'] = 'active';

		$result = Request::_GET('status', null, ['active', 'inactive', 'pending']);

		$this->assertEquals('active', $result);
	}

	public function testPostWithAllowedValues(): void
	{
		RequestContextHolder::current()->POST['type'] = 'admin';

		$result = Request::_POST('type', null, ['admin', 'user', 'guest']);

		$this->assertEquals('admin', $result);
	}

	// =========================================================================
	// Edge Cases
	// =========================================================================

	public function testGetZeroValueIsNotTreatedAsEmpty(): void
	{
		RequestContextHolder::current()->GET['offset'] = '0';

		// '0' should not be treated as empty (fixed: !== instead of !=)
		$result = Request::_GET('offset', 10);

		$this->assertIsInt($result);
		$this->assertEquals(0, $result);
	}

	public function testGetNestedArrayValue(): void
	{
		RequestContextHolder::current()->GET['filter'] = [
			'status' => 'active',
			'type' => 'user',
		];

		$result = Request::_GET('filter');

		$this->assertIsArray($result);
		$this->assertArrayHasKey('status', $result);
		$this->assertEquals('active', $result['status']);
	}

	public function testGetMixedArrayWithNonStrings(): void
	{
		// Array with non-string values (edge case)
		RequestContextHolder::current()->GET['data'] = ['string', 123, true];

		$result = Request::_GET('data');

		$this->assertIsArray($result);
		// Non-string values should pass through unchanged
		$this->assertEquals('string', $result[0]);
		$this->assertEquals(123, $result[1]);
		$this->assertTrue($result[2]);
	}

	// =========================================================================
	// getGET/getPOST Tests
	// =========================================================================

	public function testGetGETReturnsAllGetParams(): void
	{
		RequestContextHolder::initializeRequest(get: ['a' => '1', 'b' => '2']);

		$result = Request::getGET();

		$this->assertEquals(['a' => '1', 'b' => '2'], $result);
	}

	public function testGetPOSTReturnsAllPostParams(): void
	{
		RequestContextHolder::initializeRequest(post: ['x' => 'y', 'z' => 'w']);

		$result = Request::getPOST();

		$this->assertEquals(['x' => 'y', 'z' => 'w'], $result);
	}

	// =========================================================================
	// getMissingParams Tests
	// =========================================================================

	public function testGetMissingParamsReturnsEmptyWhenAllPresent(): void
	{
		RequestContextHolder::initializeRequest(get: ['id' => '1', 'name' => 'test']);

		$result = Request::getMissingParams([
			'id' => 'The ID',
			'name' => 'The name',
		]);

		$this->assertEmpty($result);
	}

	public function testGetMissingParamsReturnsMissing(): void
	{
		RequestContextHolder::initializeRequest(get: ['id' => '1']);

		$result = Request::getMissingParams([
			'id' => 'The ID',
			'name' => 'The name',
			'email' => 'Email address',
		]);

		$this->assertArrayHasKey('name', $result);
		$this->assertArrayHasKey('email', $result);
		$this->assertArrayNotHasKey('id', $result);
	}

	// =========================================================================
	// CLI Argument Tests
	// =========================================================================

	public function testGetArgReturnsValue(): void
	{
		global $argv;
		$originalArgv = $argv;

		$argv = ['script.php', 'context:event', '--name=value'];

		$this->assertEquals('value', Request::getArg('--name'));

		$argv = $originalArgv;
	}

	public function testGetArgReturnsNullWhenNotFound(): void
	{
		global $argv;
		$originalArgv = $argv;

		$argv = ['script.php', 'context:event'];

		$this->assertNull(Request::getArg('--missing'));

		$argv = $originalArgv;
	}

	public function testHasArgReturnsTrue(): void
	{
		global $argv;
		$originalArgv = $argv;

		$argv = ['script.php', 'context:event', '--force'];

		$this->assertTrue(Request::hasArg('force'));

		$argv = $originalArgv;
	}

	public function testHasArgReturnsFalse(): void
	{
		global $argv;
		$originalArgv = $argv;

		$argv = ['script.php', 'context:event'];

		$this->assertFalse(Request::hasArg('force'));

		$argv = $originalArgv;
	}

	public function testGetMainArgReturnsFirstArgAfterColon(): void
	{
		global $argv;
		$originalArgv = $argv;

		$argv = ['script.php', 'context:event', 'mainarg', '--flag'];

		$this->assertEquals('mainarg', Request::getMainArg());

		$argv = $originalArgv;
	}

	public function testGetMainArgReturnsNullWhenNoColonArg(): void
	{
		global $argv;
		$originalArgv = $argv;

		$argv = ['script.php', 'noargwithcolon'];

		$this->assertNull(Request::getMainArg());

		$argv = $originalArgv;
	}

	// =========================================================================
	// _SESSION() Tests
	// =========================================================================

	public function testSessionScalarValue(): void
	{
		Request::saveSessionData(['user_id'], '123');

		$this->assertEquals('123', Request::_SESSION('user_id'));
	}

	public function testSessionMissingValueReturnsDefault(): void
	{
		Request::unsetSessionData(['missing']);

		$this->assertEquals('default', Request::_SESSION('missing', 'default'));
	}

	public function testSessionArrayValue(): void
	{
		Request::saveSessionData(['permissions'], ['read', 'write', 'delete']);

		$result = Request::_SESSION('permissions');

		$this->assertIsArray($result);
		$this->assertEquals(['read', 'write', 'delete'], $result);
	}

	public function testSessionWithIntegerDefault(): void
	{
		Request::saveSessionData(['count'], '42');

		$result = Request::_SESSION('count', 0);

		$this->assertIsInt($result);
		$this->assertEquals(42, $result);
	}

	// =========================================================================
	// Session Data Methods Tests
	// =========================================================================

	public function testSaveAndGetSessionData(): void
	{
		Request::saveSessionData(['app', 'settings', 'theme'], 'dark');

		$result = Request::getSessionData(['app', 'settings', 'theme']);

		$this->assertEquals('dark', $result);
	}

	public function testGetSessionDataReturnsNullForMissingPath(): void
	{
		$result = Request::getSessionData(['nonexistent', 'path']);

		$this->assertNull($result);
	}

	public function testIsSessionDataSetReturnsTrue(): void
	{
		Request::saveSessionData(['test', 'key'], 'value');

		$this->assertTrue(Request::isSessionDataSet(['test', 'key']));
	}

	public function testIsSessionDataSetReturnsFalse(): void
	{
		$this->assertFalse(Request::isSessionDataSet(['nonexistent', 'deep', 'path']));
	}

	public function testUnsetSessionData(): void
	{
		Request::saveSessionData(['to', 'delete'], 'value');
		$this->assertTrue(Request::isSessionDataSet(['to', 'delete']));

		Request::unsetSessionData(['to', 'delete']);

		$this->assertNull(Request::getSessionData(['to', 'delete']));
		$this->assertFalse(Request::isSessionDataSet(['to', 'delete']));
	}

	public function testUnsetSessionDataNonexistentPath(): void
	{
		// Should not throw error when unsetting nonexistent path
		Request::unsetSessionData(['does', 'not', 'exist']);

		$this->assertNull(Request::getSessionData(['does', 'not', 'exist']));
	}

	// =========================================================================
	// getSESSION() Tests
	// =========================================================================

	public function testGetSESSIONReturnsAllSessionData(): void
	{
		$_SESSION = ['a' => '1', 'b' => '2'];

		$result = Request::getSESSION();

		$this->assertEquals(['a' => '1', 'b' => '2'], $result);
	}

	public function testGetSESSIONReturnsEmptyArrayWhenNoSession(): void
	{
		unset($_SESSION);

		$result = Request::getSESSION();

		$this->assertEquals([], $result);
	}

	// =========================================================================
	// hasGet() / hasPost() Tests
	// =========================================================================

	public function testHasGetReturnsTrueForPresentParam(): void
	{
		RequestContextHolder::current()->GET['name'] = 'John';

		$this->assertTrue(Request::hasGet('name'));
	}

	public function testHasGetReturnsFalseForMissingParam(): void
	{
		$this->assertFalse(Request::hasGet('nonexistent'));
	}

	public function testHasGetReturnsFalseForEmptyString(): void
	{
		RequestContextHolder::current()->GET['empty'] = '';

		$this->assertFalse(Request::hasGet('empty'));
	}

	public function testHasGetReturnsTrueForZero(): void
	{
		RequestContextHolder::current()->GET['parent_id'] = '0';

		$this->assertTrue(Request::hasGet('parent_id'));
	}

	public function testHasPostReturnsTrueForPresentParam(): void
	{
		RequestContextHolder::current()->POST['name'] = 'Jane';

		$this->assertTrue(Request::hasPost('name'));
	}

	public function testHasPostReturnsFalseForMissingParam(): void
	{
		$this->assertFalse(Request::hasPost('nonexistent'));
	}

	public function testHasPostReturnsFalseForEmptyString(): void
	{
		RequestContextHolder::current()->POST['empty'] = '';

		$this->assertFalse(Request::hasPost('empty'));
	}

	public function testHasPostReturnsTrueForZero(): void
	{
		RequestContextHolder::current()->POST['checked'] = '0';

		$this->assertTrue(Request::hasPost('checked'));
	}

	// =========================================================================
	// Edge Cases and Error Handling
	// =========================================================================

	public function testGetWithAllowedValuesRejectsInvalidValue(): void
	{
		RequestContextHolder::current()->GET['status'] = 'invalid';

		$this->expectException(Exception::class);

		Request::_GET('status', null, ['active', 'inactive']);
	}

	public function testPostWithAllowedValuesRejectsInvalidValue(): void
	{
		RequestContextHolder::current()->POST['type'] = 'hacker';

		$this->expectException(Exception::class);

		Request::_POST('type', null, ['admin', 'user']);
	}

	public function testGetDefaultErrorThrowsException(): void
	{
		$this->expectException(Exception::class);

		Request::_GET('required_param', Request::DEFAULT_ERROR);
	}

	public function testPostDefaultErrorThrowsException(): void
	{
		$this->expectException(Exception::class);

		Request::_POST('required_param', Request::DEFAULT_ERROR);
	}

	// =========================================================================
	// Type Coercion Edge Cases
	// =========================================================================

	public function testGetFloatAsIntegerReturnsDefault(): void
	{
		RequestContextHolder::current()->GET['count'] = '3.14';

		// '3.14' is numeric but not a valid integer
		$result = Request::_GET('count', 0);

		$this->assertEquals(0, $result);
	}

	public function testGetNegativeInteger(): void
	{
		RequestContextHolder::current()->GET['offset'] = '-10';

		$result = Request::_GET('offset', 0);

		$this->assertIsInt($result);
		$this->assertEquals(-10, $result);
	}

	public function testGetWithStringDefault(): void
	{
		RequestContextHolder::current()->GET['name'] = 'John';

		$result = Request::_GET('name', 'default');

		$this->assertIsString($result);
		$this->assertEquals('John', $result);
	}

	public function testPostEmptyStringWithDefault(): void
	{
		RequestContextHolder::current()->POST['field'] = '';

		// Empty string is treated as "not set", returns default
		$result = Request::_POST('field', 'default');

		$this->assertEquals('default', $result);
	}

	// =========================================================================
	// URL Encoding - Real World Scenarios
	//
	// These tests verify correct handling of URL-encoded values.
	// Note: GET is populated by parse_str() in initValues() which already decodes
	// URL parameters. Tests simulate the state AFTER parse_str() has run.
	// =========================================================================

	/**
	 * Scenario: User wants to pass a literal "+" character (e.g., "C++")
	 * URL: ?lang=C%2B%2B (+ encoded as %2B)
	 * After parse_str(): 'C++'
	 * Expected: 'C++' (the literal plus signs the user intended).
	 */
	public function testGetLiteralPlusSign(): void
	{
		// Simulates: ?lang=C%2B%2B after parse_str() decoding
		RequestContextHolder::current()->GET['lang'] = 'C++';

		$this->assertEquals('C++', Request::_GET('lang'));
	}

	/**
	 * Scenario: User passes a redirect URL as a parameter
	 * URL: ?redirect=https%3A%2F%2Fexample.com%2Fpath%3Fid%3D123
	 * After parse_str(): 'https://example.com/path?id=123'
	 * Expected: The full decoded URL ready to use.
	 */
	public function testGetEncodedUrl(): void
	{
		// Simulates encoded URL parameter after parse_str() decoding
		RequestContextHolder::current()->GET['redirect'] = 'https://example.com/path?id=123';

		$this->assertEquals('https://example.com/path?id=123', Request::_GET('redirect'));
	}

	/**
	 * Scenario: Value contains percent-encoded sequence that should be preserved
	 * (e.g., user is passing a URL-encoded string that shouldn't be decoded further)
	 * URL: ?encoded=hello%2520world (%25 = literal %, so this is "hello%20world")
	 * After parse_str(): 'hello%20world'
	 * Expected: 'hello%20world' (NOT decoded to 'hello world').
	 */
	public function testGetPreservesIntentionalPercentEncoding(): void
	{
		// User intentionally passed %20 as data (encoded as %2520 in URL)
		RequestContextHolder::current()->GET['encoded'] = 'hello%20world';

		$this->assertEquals('hello%20world', Request::_GET('encoded'));
	}

	/**
	 * Scenario: Unicode text in URL
	 * URL: ?text=caf%C3%A9 (UTF-8 encoded "café")
	 * After parse_str(): 'café'
	 * Expected: 'café'.
	 */
	public function testGetUnicodeText(): void
	{
		RequestContextHolder::current()->GET['text'] = 'café';

		$this->assertEquals('café', Request::_GET('text'));
	}

	// =========================================================================
	// Integration Tests - Full Flow Through initValues()
	//
	// These tests verify the complete URL → parse_str() → _GET() flow,
	// ensuring no double-decoding occurs in real usage.
	// =========================================================================

	/**
	 * Integration test: Space encoded as %20 in URL.
	 */
	public function testInitValuesDecodesSpaceCorrectly(): void
	{
		RequestContextHolder::initializeRequest(server: ['REQUEST_URI' => '/page.html?search=hello%20world']);

		Request::initValues();

		$this->assertEquals('hello world', Request::_GET('search'));
	}

	/**
	 * Integration test: Space encoded as + in URL (form submission style).
	 */
	public function testInitValuesDecodesPlusAsSpace(): void
	{
		RequestContextHolder::initializeRequest(server: ['REQUEST_URI' => '/page.html?search=hello+world']);

		Request::initValues();

		$this->assertEquals('hello world', Request::_GET('search'));
	}

	/**
	 * Integration test: Literal + character encoded as %2B.
	 */
	public function testInitValuesPreservesEncodedPlus(): void
	{
		RequestContextHolder::initializeRequest(server: ['REQUEST_URI' => '/page.html?lang=C%2B%2B']);

		Request::initValues();

		$this->assertEquals('C++', Request::_GET('lang'));
	}

	/**
	 * Integration test: Double-encoded value should only decode once
	 * URL has %2520 which should become %20 (not a space).
	 */
	public function testInitValuesDoesNotDoubleDecode(): void
	{
		RequestContextHolder::initializeRequest(server: ['REQUEST_URI' => '/page.html?value=hello%2520world']);

		Request::initValues();

		// Should be 'hello%20world', NOT 'hello world'
		$this->assertEquals('hello%20world', Request::_GET('value'));
	}

	/**
	 * Integration test: Encoded URL as parameter.
	 */
	public function testInitValuesDecodesUrlParameter(): void
	{
		RequestContextHolder::initializeRequest(server: ['REQUEST_URI' => '/page.html?redirect=https%3A%2F%2Fexample.com%2Fpath']);

		Request::initValues();

		$this->assertEquals('https://example.com/path', Request::_GET('redirect'));
	}

	/**
	 * Integration test: Array parameters with encoded values.
	 */
	public function testInitValuesDecodesArrayParameters(): void
	{
		RequestContextHolder::initializeRequest(server: ['REQUEST_URI' => '/page.html?tags[]=hello%20world&tags[]=foo%26bar']);

		Request::initValues();

		$result = Request::_GET('tags');
		$this->assertIsArray($result);
		$this->assertEquals(['hello world', 'foo&bar'], $result);
	}

	/**
	 * Integration test: UTF-8 encoded characters.
	 */
	public function testInitValuesDecodesUtf8(): void
	{
		RequestContextHolder::initializeRequest(server: ['REQUEST_URI' => '/page.html?text=caf%C3%A9']);

		Request::initValues();

		$this->assertEquals('café', Request::_GET('text'));
	}

	public function testInitValuesPreservesPrepopulatedGetValues(): void
	{
		RequestContextHolder::initializeRequest(
			get: ['existing' => 'keep-me', 'resource' => 'custom.html'],
			server: ['REQUEST_URI' => '/page.html?from_uri=value']
		);

		Request::initValues();

		$this->assertEquals('keep-me', Request::_GET('existing'));
		$this->assertEquals('value', Request::_GET('from_uri'));
		$this->assertEquals('custom.html', Request::_GET('resource'));
	}

	public function testInitValuesSupportsLowercaseSwooleServerKeys(): void
	{
		RequestContextHolder::initializeRequest(server: ['request_uri' => '/hello/world.html?x=1']);

		Request::initValues();

		$this->assertEquals('1', Request::_GET('x'));
		$this->assertEquals('/hello/', Request::_GET('folder'));
		$this->assertEquals('world.html', Request::_GET('resource'));
	}

	public function testInitValuesSupportsLowercasePathInfo(): void
	{
		RequestContextHolder::initializeRequest(server: ['request_uri' => '/index.html', 'path_info' => '/nested/thing.html']);

		Request::initValues();

		$this->assertEquals('/nested/', Request::_GET('folder'));
		$this->assertEquals('thing.html', Request::_GET('resource'));
	}

	// =========================================================================
	// __set and __get Magic Methods
	// =========================================================================

	public function testSetMagicMethodThrowsException(): void
	{
		$request = new Request();

		$this->expectException(Exception::class);

		$request->someProperty = 'value';
	}

	public function testGetMagicMethodThrowsException(): void
	{
		$request = new Request();

		$this->expectException(Exception::class);

		$_ = $request->someProperty;
	}

	// =========================================================================
	// _CONFIG() Tests
	// =========================================================================

	public function testConfigReturnsDefaultWhenNoUser(): void
	{
		// When no user is logged in, User::getConfig() returns null
		// so _CONFIG should return the default value
		$result = Request::_CONFIG('nonexistent_config_key', 'my_default_value');

		$this->assertEquals('my_default_value', $result);
	}

	public function testConfigAllowedValuesWithDefault(): void
	{
		// When default is in allowed_values list, it should be returned
		$result = Request::_CONFIG(
			'nonexistent_config_key',
			'option_b',
			['option_a', 'option_b', 'option_c']
		);

		$this->assertEquals('option_b', $result);
	}

	public function testConfigRejectsDisallowedDefaultValue(): void
	{
		// When default is NOT in allowed_values list, Kernel::abort() is called
		$this->expectException(Exception::class);

		Request::_CONFIG(
			'nonexistent_config_key',
			'invalid_option',
			['option_a', 'option_b', 'option_c']
		);
	}

	// =========================================================================
	// lookForSession() Tests
	// =========================================================================

	public function testLookForSessionStartsSessionWhenCookieExists(): void
	{
		// Make sure any existing session is closed
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}

		// Set the session cookie in the request context
		RequestContextHolder::initializeRequest(cookie: [session_name() => 'test_session_id']);

		Request::lookForSession();

		$this->assertEquals(PHP_SESSION_ACTIVE, session_status());

		// Cleanup
		session_write_close();
	}

	public function testLookForSessionDoesNotStartSessionWhenNoCookie(): void
	{
		// Make sure any existing session is closed
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}

		// initializeRequest() with no cookie (already done in setUp, but be explicit)
		RequestContextHolder::initializeRequest();

		// Store initial status
		$initialStatus = session_status();

		Request::lookForSession();

		// Session should not have been started
		$this->assertEquals($initialStatus, session_status());
	}

	public function testStartSessionCanBeCalledMultipleTimes(): void
	{
		// Start session
		Request::startSession();
		$this->assertEquals(PHP_SESSION_ACTIVE, session_status());

		// Calling again should not throw error
		Request::startSession();
		$this->assertEquals(PHP_SESSION_ACTIVE, session_status());

		session_write_close();
	}

	// =========================================================================
	// Unset Session Data Edge Cases
	// =========================================================================

	public function testUnsetSessionDataEmptyPath(): void
	{
		// Empty path should do nothing, not throw error
		Request::unsetSessionData([]);

		// Explicitly state this test only verifies no exception is thrown
		$this->expectNotToPerformAssertions();
	}

	public function testUnsetSessionDataDeepPath(): void
	{
		// Use unique key to avoid conflicts with other tests
		$uniqueKey = 'deep_test_' . uniqid();

		Request::saveSessionData([$uniqueKey, 'b', 'c', 'd'], 'deep_value');
		$this->assertEquals('deep_value', Request::getSessionData([$uniqueKey, 'b', 'c', 'd']));

		Request::unsetSessionData([$uniqueKey, 'b', 'c', 'd']);

		$this->assertNull(Request::getSessionData([$uniqueKey, 'b', 'c', 'd']));
		// Parent path should still exist
		$this->assertIsArray(Request::getSessionData([$uniqueKey, 'b', 'c']));

		// Cleanup
		unset($_SESSION[$uniqueKey]);
	}

	public function testUnsetSessionDataParentPreserved(): void
	{
		// Use unique key to avoid conflicts
		$uniqueKey = 'parent_test_' . uniqid();

		Request::saveSessionData([$uniqueKey, 'child1'], 'value1');
		Request::saveSessionData([$uniqueKey, 'child2'], 'value2');

		Request::unsetSessionData([$uniqueKey, 'child1']);

		$this->assertNull(Request::getSessionData([$uniqueKey, 'child1']));
		$this->assertEquals('value2', Request::getSessionData([$uniqueKey, 'child2']));

		// Cleanup
		unset($_SESSION[$uniqueKey]);
	}
}
