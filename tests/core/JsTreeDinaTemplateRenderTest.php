<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class JsTreeDinaTemplateRenderTest extends TestCase
{
	public function testRenderDinaTemplateUsesForcedThemeForRegisteredTemplate(): void
	{
		ob_start();
		JsTreeApiService::renderDinaTemplate('dina_content._buttonInsert', [
			'insertUrl' => 'https://example.test/demo',
		], 'RadaptorPortalAdmin', strings: JsTreeApiService::buildResourcesStrings());
		$output = (string) ob_get_clean();

		$this->assertStringContainsString('Insert', $output);
		$this->assertStringContainsString('https://example.test/demo', $output);
	}

	public function testRenderDinaTemplateFallsBackToMissingTemplateMarker(): void
	{
		ob_start();
		JsTreeApiService::renderDinaTemplate('tmp.does_not_exist_template', [], 'RadaptorPortalAdmin');
		$output = (string) ob_get_clean();

		$this->assertStringContainsString('Missing template', $output);
	}

	public function testRenderRolesHelpTemplateUsesResolvedUiPayload(): void
	{
		ob_start();
		JsTreeApiService::renderDinaTemplate('dina_content.roles._help', [], 'RadaptorPortalAdmin', strings: JsTreeApiService::buildRolesStrings());
		$output = (string) ob_get_clean();

		$this->assertStringContainsString(JsTreeApiService::buildRolesStrings()['user.role.help_create'], $output);
		$this->assertStringContainsString(JsTreeApiService::buildRolesStrings()['user.role.help_move'], $output);
	}

	public function testRenderUsergroupsHelpTemplateUsesResolvedUiPayload(): void
	{
		ob_start();
		JsTreeApiService::renderDinaTemplate('dina_content.usergroups._help', [], 'RadaptorPortalAdmin', strings: JsTreeApiService::buildUsergroupsStrings());
		$output = (string) ob_get_clean();

		$this->assertStringContainsString(JsTreeApiService::buildUsergroupsStrings()['user.usergroup.help_create'], $output);
		$this->assertStringContainsString(JsTreeApiService::buildUsergroupsStrings()['user.usergroup.help_move'], $output);
	}

	public function testRenderAdminMenuInvalidTemplateUsesResolvedUiPayload(): void
	{
		RequestContextHolder::initializeRequest(server: [
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'HTTP_HOST' => 'example.test',
		]);
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
		$_SERVER['HTTP_HOST'] = 'example.test';
		$_SERVER['SERVER_PORT'] = '80';
		$_SERVER['REQUEST_URI'] = '/admin/components/adminmenu/index.html';

		ob_start();
		JsTreeApiService::renderDinaTemplate('jsTree.dina_content.adminMenu.', [
			'id' => [0],
			'jstree_id' => 'jstree_adminmenu_test',
		], 'RadaptorPortalAdmin', strings: JsTreeApiService::buildAdminMenuStrings());
		$output = (string) ob_get_clean();

		$this->assertStringContainsString(JsTreeApiService::buildAdminMenuStrings()['selection.invalid_entry'], $output);
		$this->assertStringContainsString(JsTreeApiService::buildAdminMenuStrings()['common.delete'], $output);
	}
}
