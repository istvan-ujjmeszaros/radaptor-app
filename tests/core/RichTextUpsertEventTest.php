<?php

declare(strict_types=1);

final class RichTextUpsertEventTest extends TransactionedTestCase
{
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
