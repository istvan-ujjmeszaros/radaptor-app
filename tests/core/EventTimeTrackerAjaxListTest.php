<?php

final class EventTimeTrackerAjaxListTest extends TransactionedTestCase
{
	public function testReturnsExpectedAaDataShapeWithClientComputedDurationColumn(): void
	{
		$user = EntityUser::findFirst(['username' => 'admin_developer']);
		$this->assertNotNull($user, 'Missing fixture user admin_developer.');

		DbHelper::insertHelper('timetracker', [
			'description' => 'Test timer row',
			'start_time' => '2026-03-01 09:00:00',
			'end_time' => '2026-03-01 10:15:00',
			'user_id' => $user->pkey(),
		]);

		RequestContextHolder::initializeRequest();

		$initialOutputBuffers = ob_get_level();
		ob_start();
		(new EventTimeTrackerAjaxList())->run();
		$output = (string) ob_get_contents();

		while (ob_get_level() > $initialOutputBuffers) {
			ob_end_clean();
		}

		$decoded = json_decode($output, true);
		$this->assertIsArray($decoded, 'Event output is not valid JSON. Raw output: ' . $output);
		$this->assertTrue((bool) ($decoded['ok'] ?? false));
		$this->assertIsArray($decoded['data']['aaData'] ?? null);
		$this->assertNotEmpty($decoded['data']['aaData']);

		$first = $decoded['data']['aaData'][0] ?? null;
		$this->assertIsArray($first);
		$this->assertCount(7, $first, 'aaData row must have exactly 7 columns.');
		$this->assertSame('', $first[4], 'Duration column should be empty in API payload and rendered client-side.');
	}
}
