<?php

use PHPUnit\Framework\TestCase;

class TransactionedTestCase extends TestCase
{
	protected function setUp(): void
	{
		Db::instance()->beginTransaction();
	}

	protected function tearDown(): void
	{
		Db::instance()->rollBack();
	}
}
