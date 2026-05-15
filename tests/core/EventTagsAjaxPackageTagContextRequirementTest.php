<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EventTagsAjaxPackageTagContextRequirementTest extends TestCase
{
	protected function tearDown(): void
	{
		RequestContextHolder::initializeRequest();

		parent::tearDown();
	}

	public function testRunRejectsUnknownPackageTagContext(): void
	{
		RequestContextHolder::initializeRequest(get: [
			'term' => 'bug',
			'tag_context' => 'dummy_context',
		]);

		$ctx = RequestContextHolder::current();
		$ctx->apiResponseCaptureEnabled = true;

		(new EventTagsAjax())->run();

		$this->assertSame(400, $ctx->capturedApiResponseHttpCode);
		$this->assertSame([
			'ok' => false,
			'error' => [
				'code' => 'UNKNOWN_CONTEXT',
				'message' => t('tags.validation.unknown_context'),
			],
		], $ctx->capturedApiResponse);
	}
}
