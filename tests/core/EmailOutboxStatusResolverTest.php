<?php

declare(strict_types=1);

final class EmailOutboxStatusResolverTest extends TransactionedTestCase
{
	public function testRecomputeMarksPartialFailedAndThenSentWhenAllRecipientsSucceed(): void
	{
		$outbox = EntityEmailOutbox::saveFromArray([
			'message_uid' => 'status_test_' . uniqid('', true),
			'send_mode' => 'transactional',
			'subject' => 'Status test',
			'html_body' => '<p>Status test</p>',
			'text_body' => 'Status test',
			'status' => 'queued',
			'requested_by_type' => 'user',
			'requested_by_id' => 1,
		]);
		$outbox_id = (int) $outbox->outbox_id;

		$sentRecipient = EntityEmailOutboxRecipient::saveFromArray([
			'outbox_id' => $outbox_id,
			'recipient_type' => 'to',
			'recipient_email' => 'sent@example.com',
			'status' => 'sent',
			'sent_at' => '2026-04-08 10:00:00',
		]);
		$failedRecipient = EntityEmailOutboxRecipient::saveFromArray([
			'outbox_id' => $outbox_id,
			'recipient_type' => 'to',
			'recipient_email' => 'failed@example.com',
			'status' => 'failed',
			'last_error_code' => 'SMTP_SEND_FAILED',
			'last_error_message' => 'Mailbox rejected',
		]);

		EmailOutboxStatusResolver::recompute($outbox_id);

		$updated = EntityEmailOutbox::findById($outbox_id);
		$this->assertNotNull($updated);
		$this->assertSame('partial_failed', $updated->status);
		$this->assertSame('SMTP_SEND_FAILED', $updated->last_error_code);

		EntityEmailOutboxRecipient::updateById((int) $failedRecipient->recipient_id, [
			'status' => 'sent',
			'sent_at' => '2026-04-08 10:01:00',
			'last_error_code' => null,
			'last_error_message' => null,
		]);

		EmailOutboxStatusResolver::recompute($outbox_id);

		$updated = EntityEmailOutbox::findById($outbox_id);
		$this->assertNotNull($updated);
		$this->assertSame('sent', $updated->status);
		$this->assertSame('2026-04-08 10:01:00', $updated->sent_at);
		$this->assertNull($updated->last_error_code);
		$this->assertNull($updated->last_error_message);
	}
}
