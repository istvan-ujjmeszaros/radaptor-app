<?php

class ThemedTemplateList
{
	/**
	 * Themed template mappings: 'templateName.ThemeName' => 'path/to/template.php'
	 * @var array<string, string>
	 */
	protected static array $_themedTemplateList = [
		'Disqus.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Social/template.Disqus.php',
		'PlainHtml.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/PlainHtml/template.PlainHtml.php',
		'RichText.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/RichText/template.RichText.php',
		'SocialButtons.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Social/template.SocialButtons.php',
		'WidgetGroupBeginning.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/WidgetGroup/template.WidgetGroupBeginning.php',
		'WidgetGroupEnd.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/WidgetGroup/template.WidgetGroupEnd.php',
		'_admin_dropdown.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/template._admin_dropdown.php',
		'_admin_dropdown.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template._admin_dropdown.php',
		'_administer.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template._administer.php',
		'_json.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template._json.php',
		'_missing.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template._missing.php',
		'_missing_library.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/template._missing_library.php',
		'_missing_library.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template._missing_library.php',
		'_missing_url_params.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template._missing_url_params.php',
		'addWidgetFromList.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/template.addWidgetFromList.php',
		'addWidgetFromList.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template.addWidgetFromList.php',
		'adminMenu.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/AdminMenu/template.adminMenu.php',
		'adminMenu.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/AdminMenu/template.adminMenu.php',
		'dina_content._buttonInsert.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/jstree.resources/template.dina_content._buttonInsert.php',
		'dina_content._buttonInsert.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.dina_content._buttonInsert.php',
		'dina_content.resources._help.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/jstree.resources/template.dina_content.resources._help.php',
		'dina_content.resources._help.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.dina_content.resources._help.php',
		'dina_content.roles._help.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeRoles/template.dina_content.roles._help.php',
		'dina_content.roles._help.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeRoles/template.dina_content.roles._help.php',
		'dina_content.usergroups._help.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeUsergroups/template.dina_content.usergroups._help.php',
		'dina_content.usergroups._help.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.dina_content.usergroups._help.php',
		'editBar.common.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/template.editBar.common.php',
		'editBar.common.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template.editBar.common.php',
		'editor.placeholder.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template.editor.placeholder.php',
		'fileUpload.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template.fileUpload.php',
		'form.closer.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.form.closer.php',
		'form.closer.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.form.closer.php',
		'jsTree.adminMenu.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/AdminMenu/jstree.adminmenu/template.jsTree.adminMenu.php',
		'jsTree.adminMenu.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/AdminMenu/jstree.adminmenu/template.jsTree.adminMenu.php',
		'jsTree.dina_content.adminMenu..RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu..php',
		'jsTree.dina_content.adminMenu..SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu..php',
		'jsTree.dina_content.adminMenu._multiple_.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu._multiple_.php',
		'jsTree.dina_content.adminMenu._multiple_.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu._multiple_.php',
		'jsTree.dina_content.adminMenu.root.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu.root.php',
		'jsTree.dina_content.adminMenu.root.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu.root.php',
		'jsTree.dina_content.adminMenu.submenu.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu.submenu.php',
		'jsTree.dina_content.adminMenu.submenu.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu.submenu.php',
		'jsTree.dina_content.resources._multiple_.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/jstree.resources/template.jsTree.dina_content.resources._multiple_.php',
		'jsTree.dina_content.resources._multiple_.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.dina_content.resources._multiple_.php',
		'jsTree.dina_content.resources.file.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/jstree.resources/template.jsTree.dina_content.resources.file.php',
		'jsTree.dina_content.resources.file.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.dina_content.resources.file.php',
		'jsTree.dina_content.resources.folder.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/jstree.resources/template.jsTree.dina_content.resources.folder.php',
		'jsTree.dina_content.resources.folder.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.dina_content.resources.folder.php',
		'jsTree.dina_content.resources.null.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/jstree.resources/template.jsTree.dina_content.resources.null.php',
		'jsTree.dina_content.resources.null.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.dina_content.resources.null.php',
		'jsTree.dina_content.resources.root.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/jstree.resources/template.jsTree.dina_content.resources.root.php',
		'jsTree.dina_content.resources.root.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.dina_content.resources.root.php',
		'jsTree.dina_content.resources.webpage.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/jstree.resources/template.jsTree.dina_content.resources.webpage.php',
		'jsTree.dina_content.resources.webpage.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.dina_content.resources.webpage.php',
		'jsTree.dina_content.roles._multiple_.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeRoles/template.jsTree.dina_content.roles._multiple_.php',
		'jsTree.dina_content.roles._multiple_.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeRoles/template.jsTree.dina_content.roles._multiple_.php',
		'jsTree.dina_content.roles.null.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeRoles/template.jsTree.dina_content.roles.null.php',
		'jsTree.dina_content.roles.null.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeRoles/template.jsTree.dina_content.roles.null.php',
		'jsTree.dina_content.roles.role.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeRoles/template.jsTree.dina_content.roles.role.php',
		'jsTree.dina_content.roles.role.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeRoles/template.jsTree.dina_content.roles.role.php',
		'jsTree.dina_content.roles.root.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeRoles/template.jsTree.dina_content.roles.root.php',
		'jsTree.dina_content.roles.root.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeRoles/template.jsTree.dina_content.roles.root.php',
		'jsTree.dina_content.usergroups._multiple_.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups._multiple_.php',
		'jsTree.dina_content.usergroups._multiple_.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups._multiple_.php',
		'jsTree.dina_content.usergroups.null.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.null.php',
		'jsTree.dina_content.usergroups.null.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.null.php',
		'jsTree.dina_content.usergroups.root.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.root.php',
		'jsTree.dina_content.usergroups.root.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.root.php',
		'jsTree.dina_content.usergroups.systemusergroup.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.systemusergroup.php',
		'jsTree.dina_content.usergroups.systemusergroup.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.systemusergroup.php',
		'jsTree.dina_content.usergroups.usergroup.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.usergroup.php',
		'jsTree.dina_content.usergroups.usergroup.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.usergroup.php',
		'jsTree.resources.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/jstree.resources/template.jsTree.resources.php',
		'jsTree.resources.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.resources.php',
		'jsTree.roleSelector.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeRoleSelector/template.jsTree.roleSelector.php',
		'jsTree.roleSelector.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeRoleSelector/template.jsTree.roleSelector.php',
		'jsTree.roles.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeRoles/template.jsTree.roles.php',
		'jsTree.roles.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeRoles/template.jsTree.roles.php',
		'jsTree.usergroupSelector.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeUsergroupSelector/template.jsTree.usergroupSelector.php',
		'jsTree.usergroupSelector.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeUsergroupSelector/template.jsTree.usergroupSelector.php',
		'jsTree.usergroups.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/jsTreeUsergroups/template.jsTree.usergroups.php',
		'jsTree.usergroups.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.jsTree.usergroups.php',
		'layoutElementWidgetHandler.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/template.layoutElementWidgetHandler.php',
		'layoutElementWidgetHandler.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template.layoutElementWidgetHandler.php',
		'layoutElement_admin_1row_content.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/_layout/layoutElements/template.layoutElement_admin_1row_content.php',
		'layoutElement_admin_empty_content.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/_layout/layoutElements/template.layoutElement_admin_empty_content.php',
		'layout_admin_default.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/_layouts/template.layout_admin_default.php',
		'layout_admin_default.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/_layout/template.layout_admin_default.php',
		'layout_admin_empty.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/_layout/template.layout_admin_empty.php',
		'layout_admin_nomenu.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/_layout/template.layout_admin_nomenu.php',
		'layout_public_2row.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/_layout/template.layout_public_2row.php',
		'layout_widget_previewer.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/_layouts/template.layout_widget_previewer.php',
		'layout_widget_previewer.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/_layout/template.layout_widget_previewer.php',
		'resourceAclSelector.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/User/resourceAclSelector/template.resourceAclSelector.php',
		'resourceAclSelector.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/resourceAclSelector/template.resourceAclSelector.php',
		'resourceTree.jstree3.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/resourceTree/template.resourceTree.jstree3.php',
		'sdui.form.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.php',
		'sdui.form.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.php',
		'sdui.form.adminmenuMenuelement.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/AdminMenu/template.sdui.form.adminmenuMenuelement.php',
		'sdui.form.helper.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.helper.php',
		'sdui.form.helper.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.helper.php',
		'sdui.form.input.checkbox.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.input.checkbox.php',
		'sdui.form.input.checkbox.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.checkbox.php',
		'sdui.form.input.checkboxgroup.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.input.checkboxgroup.php',
		'sdui.form.input.checkboxgroup.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.checkboxgroup.php',
		'sdui.form.input.clearfloat.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.clearfloat.php',
		'sdui.form.input.date.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.input.date.php',
		'sdui.form.input.date.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.date.php',
		'sdui.form.input.datetime.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.input.datetime.php',
		'sdui.form.input.datetime.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.datetime.php',
		'sdui.form.input.groupend.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.groupend.php',
		'sdui.form.input.hidden.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.hidden.php',
		'sdui.form.input.password.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.input.password.php',
		'sdui.form.input.password.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.password.php',
		'sdui.form.input.radiogroup.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.input.radiogroup.php',
		'sdui.form.input.radiogroup.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.radiogroup.php',
		'sdui.form.input.select.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.input.select.php',
		'sdui.form.input.select.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.select.php',
		'sdui.form.input.text.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.input.text.php',
		'sdui.form.input.text.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.text.php',
		'sdui.form.input.textarea.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.input.textarea.php',
		'sdui.form.input.textarea.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.textarea.php',
		'sdui.form.input.textarea.ckeditor.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.input.textarea.ckeditor.php',
		'sdui.form.input.textarea.ckeditor.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.textarea.ckeditor.php',
		'sdui.form.input.textarea.codemirror.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.input.textarea.codemirror.php',
		'sdui.form.input.textarea.codemirror.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.textarea.codemirror.php',
		'sdui.form.input.textarea.tinymce.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.textarea.tinymce.php',
		'sdui.form.input.widgetgroupbeginning.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.widgetgroupbeginning.php',
		'sdui.form.mainmenuMenuelement.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/MainMenu/template.sdui.form.mainmenuMenuelement.php',
		'sdui.form.row.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Form/template.sdui.form.row.php',
		'sdui.form.row.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Form/template.sdui.form.row.php',
		'sdui.statusMessage.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template.sdui.statusMessage.php',
		'sideMenuAdmin.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/SideMenuAdmin/template.sideMenuAdmin.php',
		'sitemapXml.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template.sitemapXml.php',
		'templateEngineDemoBlade.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/TemplateEngineDemo/template.templateEngineDemoBlade.blade.php',
		'templateEngineDemoPhp.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/TemplateEngineDemo/template.templateEngineDemoPhp.php',
		'templateEngineDemoTwig.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/TemplateEngineDemo/template.templateEngineDemoTwig.twig',
		'templateEngineDemoWrapper.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/TemplateEngineDemo/template.templateEngineDemoWrapper.php',
		'topMenuAdmin.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/TopMenuAdmin/template.topMenuAdmin.php',
		'topMenuAdmin.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/TopMenuAdmin/template.topMenuAdmin.php',
		'userDescription.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/template.userDescription.php',
		'userList.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/User/template.userList.php',
		'userMenu.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/UserMenu/template.userMenu.php',
		'widgetEdit.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/template.widgetEdit.php',
		'widgetEdit.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template.widgetEdit.php',
		'widgetEditAfter.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template.widgetEditAfter.php',
		'widgetEditBefore.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template.widgetEditBefore.php',
		'widgetInsert.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/template.widgetInsert.php',
		'widgetInsert.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template.widgetInsert.php',
		'widgetPreviewInfo.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/template.widgetPreviewInfo.php',
		'widgetPreviewInfo.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template.widgetPreviewInfo.php',
		'widgetPreviewList.RadaptorPortalAdmin' => 'themes/registry/portal-admin/theme/Cms/template.widgetPreviewList.php',
		'widgetPreviewList.SoAdmin' => 'core/registry/cms/templates-common/default-SoAdmin/Cms/template.widgetPreviewList.php',
	];

	public static function getThemedTemplatePath(string $templateName, string $themeName): ?string
	{
		$key = "{$templateName}.{$themeName}";
		return self::$_themedTemplateList[$key] ?? null;
	}

	/**
	 * Reverse lookup: find the key for a given path (for debug info).
	 */
	public static function getKeyForPath(string $path): ?string
	{
		$key = array_search($path, self::$_themedTemplateList, true);
		return $key !== false ? $key : null;
	}

	/**
	 * Find all themes that have a specific template.
	 *
	 * @param string $templateName The template name without theme suffix
	 * @return string[] Array of theme names that have this template
	 */
	public static function getThemesForTemplate(string $templateName): array
	{
		$themes = [];
		$prefix = $templateName . '.';

		foreach (self::$_themedTemplateList as $key => $path) {
			if (str_starts_with($key, $prefix)) {
				$themes[] = substr($key, strlen($prefix));
			}
		}

		return $themes;
	}
}
