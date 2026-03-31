<?php

class WidgetConnectionGroupedLoadingTest extends TransactionedTestCase
{
	private const int PAGE_ID = 1;

	public function testGetWidgetsForPageGroupedBySlotKeepsPerSlotOrderingAndLinks(): void
	{
		$slotA = '__test_grouped_slot_a';
		$slotB = '__test_grouped_slot_b';

		$firstId = $this->insertConnection($slotA, WidgetList::PLAINHTML, 1001);
		$secondId = $this->insertConnection($slotA, WidgetList::FORM, 1002);
		$onlyId = $this->insertConnection($slotB, WidgetList::RICHTEXT, 1001);

		$grouped = WidgetConnection::getWidgetsForPageGroupedBySlot(self::PAGE_ID);

		$this->assertArrayHasKey($slotA, $grouped);
		$this->assertArrayHasKey($slotB, $grouped);
		$this->assertCount(2, $grouped[$slotA]);
		$this->assertCount(1, $grouped[$slotB]);

		$this->assertSame($firstId, $grouped[$slotA][0]->getConnectionId());
		$this->assertSame($secondId, $grouped[$slotA][1]->getConnectionId());
		$this->assertTrue($grouped[$slotA][0]->isFirst());
		$this->assertFalse($grouped[$slotA][0]->isLast());
		$this->assertFalse($grouped[$slotA][1]->isFirst());
		$this->assertTrue($grouped[$slotA][1]->isLast());
		$this->assertNull($grouped[$slotA][0]->previous());
		$this->assertSame($grouped[$slotA][1], $grouped[$slotA][0]->next());
		$this->assertSame($grouped[$slotA][0], $grouped[$slotA][1]->previous());
		$this->assertNull($grouped[$slotA][1]->next());

		$this->assertSame($onlyId, $grouped[$slotB][0]->getConnectionId());
		$this->assertTrue($grouped[$slotB][0]->isFirst());
		$this->assertTrue($grouped[$slotB][0]->isLast());
		$this->assertNull($grouped[$slotB][0]->previous());
		$this->assertNull($grouped[$slotB][0]->next());
	}

	public function testGetWidgetsForPageGroupedBySlotMatchesSlotSpecificLoader(): void
	{
		$slotName = '__test_grouped_slot_parity';

		$this->insertConnection($slotName, WidgetList::PLAINHTML, 2001);
		$this->insertConnection($slotName, WidgetList::FORM, 2002);

		$grouped = WidgetConnection::getWidgetsForPageGroupedBySlot(self::PAGE_ID);
		$slotSpecific = WidgetConnection::getWidgetsForSlot(self::PAGE_ID, $slotName);

		$this->assertArrayHasKey($slotName, $grouped);
		$this->assertCount(count($slotSpecific), $grouped[$slotName]);
		$this->assertSame(
			array_map(static fn (WidgetConnection $c) => $c->getConnectionId(), $slotSpecific),
			array_map(static fn (WidgetConnection $c) => $c->getConnectionId(), $grouped[$slotName])
		);
	}

	private function insertConnection(string $slotName, string $widgetName, int $seq): int
	{
		return DbHelper::insertHelper('widget_connections', [
			'page_id' => self::PAGE_ID,
			'slot_name' => $slotName,
			'widget_name' => $widgetName,
			'seq' => $seq,
		]);
	}
}
