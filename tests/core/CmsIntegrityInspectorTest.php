<?php

declare(strict_types=1);

final class CmsIntegrityInspectorTest extends TransactionedTestCase
{
	public function testLayoutStatusReportsRegisteredLoginLayoutWithValidContract(): void
	{
		$result = CmsIntegrityInspector::inspectLayouts('admin_login');

		$this->assertSame('ok', $result['status']);
		$this->assertSame(1, $result['summary']['ok']);
		$this->assertSame(0, $result['summary']['error']);
		$this->assertCount(1, $result['layouts']);
		$this->assertSame('admin_login', $result['layouts'][0]['layout']);
		$this->assertTrue($result['layouts'][0]['registered_layout_type']);
		$this->assertGreaterThanOrEqual(1, $result['layouts'][0]['template_count']);
	}

	public function testFormStatusReportsUserLoginUrlWithoutCreatingContent(): void
	{
		$result = CmsIntegrityInspector::inspectForms(FormList::USERLOGIN);

		$this->assertSame('ok', $result['status']);
		$this->assertSame(1, $result['summary']['ok']);
		$this->assertCount(1, $result['forms']);
		$this->assertSame(FormList::USERLOGIN, $result['forms'][0]['form']);
		$this->assertSame('/login.html', $result['forms'][0]['url']);
		$this->assertGreaterThanOrEqual(1, $result['forms'][0]['placement_count']);
	}

	public function testWidgetStatusReportsRegisteredUserListUrlWithoutCreatingContent(): void
	{
		$result = CmsIntegrityInspector::inspectWidgets(WidgetList::USERLIST);

		$this->assertSame('ok', $result['status']);
		$this->assertSame(1, $result['summary']['ok']);
		$this->assertCount(1, $result['widgets']);
		$this->assertSame(WidgetList::USERLIST, $result['widgets'][0]['widget']);
		$this->assertSame('/admin/users/', $result['widgets'][0]['url']);
		$this->assertGreaterThanOrEqual(1, $result['widgets'][0]['placement_count']);
	}

	public function testIntegritySummaryAggregatesAllReadOnlyChecks(): void
	{
		$result = CmsIntegrityInspector::inspectSummary();

		$this->assertContains($result['status'], ['ok', 'warning']);
		$this->assertCount(3, $result['checks']);
		$this->assertSame(['layouts', 'forms', 'widgets'], array_column($result['checks'], 'name'));
	}
}
