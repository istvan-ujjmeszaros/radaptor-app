<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PackageVersionHelperTest extends TestCase
{
	public function testMatchesExactVersion(): void
	{
		$this->assertTrue(PackageVersionHelper::matches('1.2.3', '1.2.3'));
		$this->assertFalse(PackageVersionHelper::matches('1.2.4', '1.2.3'));
		$this->assertTrue(PackageVersionHelper::matches('0.1.0-alpha.1', '0.1.0-alpha.1'));
	}

	public function testMatchesCaretConstraint(): void
	{
		$this->assertTrue(PackageVersionHelper::matches('1.4.0', '^1.2'));
		$this->assertFalse(PackageVersionHelper::matches('2.0.0', '^1.2'));
		$this->assertTrue(PackageVersionHelper::matches('0.2.5', '^0.2.0'));
		$this->assertFalse(PackageVersionHelper::matches('0.3.0', '^0.2.0'));
		$this->assertFalse(PackageVersionHelper::matches('0.1.0-alpha.1', '^0.1.0'));
		$this->assertTrue(PackageVersionHelper::matches('0.1.0-alpha.1', '^0.1.0-alpha.1'));
	}

	public function testMatchesTildeAndWildcardConstraints(): void
	{
		$this->assertTrue(PackageVersionHelper::matches('1.2.9', '~1.2.0'));
		$this->assertFalse(PackageVersionHelper::matches('1.3.0', '~1.2.0'));
		$this->assertTrue(PackageVersionHelper::matches('1.4.7', '1.4.x'));
		$this->assertFalse(PackageVersionHelper::matches('1.5.0', '1.4.x'));
	}

	public function testSelectsBestMatchingVersion(): void
	{
		$selected = PackageVersionHelper::selectBestMatchingVersion([
			'1.0.0',
			'1.2.0',
			'2.0.0',
			'1.1.5',
		], '^1.0');

		$this->assertSame('1.2.0', $selected);
	}

	public function testSelectsStableVersionAheadOfPrerelease(): void
	{
		$selected = PackageVersionHelper::selectBestMatchingVersion([
			'0.1.0-alpha.1',
			'0.1.0',
			'0.1.0-beta.1',
		]);

		$this->assertSame('0.1.0', $selected);
	}

	public function testStableConstraintIgnoresPrereleaseCandidates(): void
	{
		$selected = PackageVersionHelper::selectBestMatchingVersion([
			'0.1.0-alpha.1',
			'0.1.0-beta.1',
			'0.1.0',
		], '^0.1.0');

		$this->assertSame('0.1.0', $selected);
	}
}
