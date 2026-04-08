<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PackageReleaseVersionHelperTest extends TestCase
{
	public function testStableReleaseFromStableVersionBumpsPatch(): void
	{
		$plan = PackageReleaseVersionHelper::planStableRelease('0.1.0');

		$this->assertSame('0.1.0', $plan['previous_version']);
		$this->assertSame('0.1.1', $plan['new_version']);
		$this->assertNull($plan['channel']);
	}

	public function testPrereleaseFromStableVersionRequiresExplicitChannel(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Stable versions require an explicit prerelease channel.');

		PackageReleaseVersionHelper::planPrerelease('0.1.0');
	}

	public function testPrereleaseFromStableVersionUsesNextPatchBase(): void
	{
		$plan = PackageReleaseVersionHelper::planPrerelease('0.1.0', 'alpha');

		$this->assertSame('0.1.0', $plan['previous_version']);
		$this->assertSame('0.1.1-alpha.1', $plan['new_version']);
		$this->assertSame('alpha', $plan['channel']);
	}

	public function testPrereleaseContinuesExistingChannelWhenOmitted(): void
	{
		$plan = PackageReleaseVersionHelper::planPrerelease('0.1.1-alpha.1');

		$this->assertSame('0.1.1-alpha.1', $plan['previous_version']);
		$this->assertSame('0.1.1-alpha.2', $plan['new_version']);
		$this->assertSame('alpha', $plan['channel']);
	}

	public function testPrereleaseRejectsChannelSwitches(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Current version '0.1.1-alpha.1' is on 'alpha'.");

		PackageReleaseVersionHelper::planPrerelease('0.1.1-alpha.1', 'beta');
	}

	public function testStableReleaseFinalizesExistingPrerelease(): void
	{
		$plan = PackageReleaseVersionHelper::planStableRelease('0.1.1-alpha.2');

		$this->assertSame('0.1.1-alpha.2', $plan['previous_version']);
		$this->assertSame('0.1.1', $plan['new_version']);
		$this->assertNull($plan['channel']);
	}
}
