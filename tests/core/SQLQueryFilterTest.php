<?php

use PHPUnit\Framework\TestCase;

class SQLQueryFilterTest extends TestCase
{
	public function testInstancesDoNotLeakState(): void
	{
		$first = new SQLQueryFilter(['id' => 1], ['id' => 'id']);
		$second = new SQLQueryFilter(['name' => 'abc'], ['name' => 'nev']);

		$this->assertSame('HAVING  id=?', $first->getHaving());
		$this->assertSame([1], $first->getValues());

		$this->assertSame('HAVING  name=?', $second->getHaving());
		$this->assertSame(['abc'], $second->getValues());
	}

	public function testUnsupportedFilterThrowsException(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unsupported filter parameter');

		$filter = new SQLQueryFilter(['bad_filter' => 1], ['id' => 'id']);
		$filter->getHaving();
	}
}
