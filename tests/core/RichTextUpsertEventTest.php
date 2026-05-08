<?php

declare(strict_types=1);

final class RichTextUpsertEventTest extends TransactionedTestCase
{
	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();
		LocaleAdminService::ensureDefaultLocaleRegistered();
		LocaleAdminService::ensureLocale('hu-HU', true);
	}

	public function testUpsertAllowsEmptyTitleForHeadinglessLegacyContent(): void
	{
		$response = $this->runCapturedPostEvent(new EventRichTextUpsert(), [
			'name' => 'test-headingless-richtext',
			'title' => '',
			'content_type' => 'info',
			'content' => '<p>Headingless body</p>',
		]);

		$this->assertTrue($response['ok'] ?? false);
		$this->assertSame('', $response['data']['title'] ?? null);
		$this->assertIsInt($response['data']['content_id'] ?? null);

		$content = EntityRichtext::findById((int) $response['data']['content_id'])?->dto();
		$this->assertNotNull($content);
		$this->assertSame('', $content['title']);
		$this->assertSame('test-headingless-richtext', $content['name']);
		$this->assertSame('en-US', $content['locale']);
	}

	public function testUpsertScopesStableNameByLocale(): void
	{
		$english = $this->runCapturedPostEvent(new EventRichTextUpsert(), [
			'name' => 'test-shared-richtext-name',
			'locale' => 'en-US',
			'title' => 'English title',
			'content_type' => 'info',
			'content' => '<p>English body</p>',
		]);
		$hungarian = $this->runCapturedPostEvent(new EventRichTextUpsert(), [
			'name' => 'test-shared-richtext-name',
			'locale' => 'hu-HU',
			'title' => 'Magyar cim',
			'content_type' => 'info',
			'content' => '<p>Magyar torzs</p>',
		]);

		$this->assertTrue($english['ok'] ?? false);
		$this->assertTrue($hungarian['ok'] ?? false);
		$this->assertNotSame($english['data']['content_id'] ?? null, $hungarian['data']['content_id'] ?? null);
		$this->assertSame((int) $english['data']['content_id'], EntityRichtext::getContentIdByName('test-shared-richtext-name', 'en-US'));
		$this->assertSame((int) $hungarian['data']['content_id'], EntityRichtext::getContentIdByName('test-shared-richtext-name', 'hu-HU'));
	}

	public function testUpsertRejectsNewDisabledLocaleButAllowsExistingContentEdit(): void
	{
		$created = $this->runCapturedPostEvent(new EventRichTextUpsert(), [
			'name' => 'test-disabled-locale-richtext',
			'locale' => 'hu-HU',
			'title' => 'Original',
			'content_type' => 'info',
			'content' => '<p>Original</p>',
		]);
		$this->assertTrue($created['ok'] ?? false);

		LocaleAdminService::setEnabled('hu-HU', false);

		$rejected = $this->runCapturedPostEvent(new EventRichTextUpsert(), [
			'name' => 'test-new-disabled-locale-richtext',
			'locale' => 'hu-HU',
			'title' => 'Rejected',
			'content_type' => 'info',
			'content' => '<p>Rejected</p>',
		]);
		$updated = $this->runCapturedPostEvent(new EventRichTextUpsert(), [
			'name' => 'test-disabled-locale-richtext',
			'locale' => 'hu-HU',
			'title' => 'Updated',
			'content_type' => 'info',
			'content' => '<p>Updated</p>',
		]);

		$this->assertFalse($rejected['ok'] ?? true);
		$this->assertSame('INVALID_LOCALE', $rejected['error']['code'] ?? null);
		$this->assertTrue($updated['ok'] ?? false);
		$this->assertFalse($updated['data']['created'] ?? true);
	}

	public function testUpsertRejectsMalformedLocale(): void
	{
		$response = $this->runCapturedPostEvent(new EventRichTextUpsert(), [
			'name' => 'test-malformed-locale-richtext',
			'locale' => 'not_a_locale',
			'title' => 'Rejected',
			'content_type' => 'info',
			'content' => '<p>Rejected</p>',
		]);

		$this->assertFalse($response['ok'] ?? true);
		$this->assertSame('INVALID_LOCALE', $response['error']['code'] ?? null);
	}

	/**
	 * @param array<string, mixed> $post
	 *
	 * @return array<string, mixed>
	 */
	private function runCapturedPostEvent(AbstractEvent $event, array $post): array
	{
		$ctx = RequestContextHolder::current();
		$previous_post = $ctx->POST;
		$previous_capture = $ctx->apiResponseCaptureEnabled;
		$previous_response = $ctx->capturedApiResponse;
		$previous_http_code = $ctx->capturedApiResponseHttpCode;

		try {
			$ctx->POST = $post;
			$ctx->apiResponseCaptureEnabled = true;
			$ctx->capturedApiResponse = null;
			$ctx->capturedApiResponseHttpCode = null;

			$event->run();

			return $ctx->capturedApiResponse ?? [];
		} finally {
			$ctx->POST = $previous_post;
			$ctx->apiResponseCaptureEnabled = $previous_capture;
			$ctx->capturedApiResponse = $previous_response;
			$ctx->capturedApiResponseHttpCode = $previous_http_code;
		}
	}
}
