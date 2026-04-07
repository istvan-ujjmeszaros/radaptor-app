<?php

class ThemedTemplateList
{
	/**
	 * Themed template mappings: 'templateName.ThemeName' => 'path/to/template.php'
	 * @var array<string, string>
	 */
	protected static array $_themedTemplateList = [
		'Disqus.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Social/template.Disqus.php',
		'PlainHtml.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/PlainHtml/template.PlainHtml.php',
		'RichText.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/RichText/template.RichText.php',
		'SocialButtons.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Social/template.SocialButtons.php',
		'WidgetGroupBeginning.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/WidgetGroup/template.WidgetGroupBeginning.php',
		'WidgetGroupEnd.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/WidgetGroup/template.WidgetGroupEnd.php',
		'_admin_dropdown.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/template._admin_dropdown.php',
		'_admin_dropdown.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template._admin_dropdown.php',
		'_administer.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template._administer.php',
		'_json.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template._json.php',
		'_missing.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template._missing.php',
		'_missing_library.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/template._missing_library.php',
		'_missing_library.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template._missing_library.php',
		'_missing_url_params.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template._missing_url_params.php',
		'addWidgetFromList.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/template.addWidgetFromList.php',
		'addWidgetFromList.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template.addWidgetFromList.php',
		'adminMenu.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/AdminMenu/template.adminMenu.php',
		'adminMenu.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/AdminMenu/template.adminMenu.php',
		'dina_content._buttonInsert.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/jstree.resources/template.dina_content._buttonInsert.php',
		'dina_content._buttonInsert.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.dina_content._buttonInsert.php',
		'dina_content.resources._help.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/jstree.resources/template.dina_content.resources._help.php',
		'dina_content.resources._help.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.dina_content.resources._help.php',
		'dina_content.roles._help.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeRoles/template.dina_content.roles._help.php',
		'dina_content.roles._help.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeRoles/template.dina_content.roles._help.php',
		'dina_content.usergroups._help.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeUsergroups/template.dina_content.usergroups._help.php',
		'dina_content.usergroups._help.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.dina_content.usergroups._help.php',
		'editBar.common.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/template.editBar.common.php',
		'editBar.common.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template.editBar.common.php',
		'editor.placeholder.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template.editor.placeholder.php',
		'fileUpload.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template.fileUpload.php',
		'form.closer.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.form.closer.php',
		'form.closer.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.form.closer.php',
		'jsTree.adminMenu.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/AdminMenu/jstree.adminmenu/template.jsTree.adminMenu.php',
		'jsTree.adminMenu.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/AdminMenu/jstree.adminmenu/template.jsTree.adminMenu.php',
		'jsTree.dina_content.adminMenu..RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu..php',
		'jsTree.dina_content.adminMenu..SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu..php',
		'jsTree.dina_content.adminMenu._multiple_.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu._multiple_.php',
		'jsTree.dina_content.adminMenu._multiple_.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu._multiple_.php',
		'jsTree.dina_content.adminMenu.root.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu.root.php',
		'jsTree.dina_content.adminMenu.root.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu.root.php',
		'jsTree.dina_content.adminMenu.submenu.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu.submenu.php',
		'jsTree.dina_content.adminMenu.submenu.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/AdminMenu/jstree.adminmenu/template.jsTree.dina_content.adminMenu.submenu.php',
		'jsTree.dina_content.resources._multiple_.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/jstree.resources/template.jsTree.dina_content.resources._multiple_.php',
		'jsTree.dina_content.resources._multiple_.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.dina_content.resources._multiple_.php',
		'jsTree.dina_content.resources.file.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/jstree.resources/template.jsTree.dina_content.resources.file.php',
		'jsTree.dina_content.resources.file.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.dina_content.resources.file.php',
		'jsTree.dina_content.resources.folder.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/jstree.resources/template.jsTree.dina_content.resources.folder.php',
		'jsTree.dina_content.resources.folder.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.dina_content.resources.folder.php',
		'jsTree.dina_content.resources.null.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/jstree.resources/template.jsTree.dina_content.resources.null.php',
		'jsTree.dina_content.resources.null.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.dina_content.resources.null.php',
		'jsTree.dina_content.resources.root.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/jstree.resources/template.jsTree.dina_content.resources.root.php',
		'jsTree.dina_content.resources.root.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.dina_content.resources.root.php',
		'jsTree.dina_content.resources.webpage.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/jstree.resources/template.jsTree.dina_content.resources.webpage.php',
		'jsTree.dina_content.resources.webpage.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.dina_content.resources.webpage.php',
		'jsTree.dina_content.roles._multiple_.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeRoles/template.jsTree.dina_content.roles._multiple_.php',
		'jsTree.dina_content.roles._multiple_.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeRoles/template.jsTree.dina_content.roles._multiple_.php',
		'jsTree.dina_content.roles.null.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeRoles/template.jsTree.dina_content.roles.null.php',
		'jsTree.dina_content.roles.null.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeRoles/template.jsTree.dina_content.roles.null.php',
		'jsTree.dina_content.roles.role.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeRoles/template.jsTree.dina_content.roles.role.php',
		'jsTree.dina_content.roles.role.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeRoles/template.jsTree.dina_content.roles.role.php',
		'jsTree.dina_content.roles.root.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeRoles/template.jsTree.dina_content.roles.root.php',
		'jsTree.dina_content.roles.root.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeRoles/template.jsTree.dina_content.roles.root.php',
		'jsTree.dina_content.usergroups._multiple_.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups._multiple_.php',
		'jsTree.dina_content.usergroups._multiple_.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups._multiple_.php',
		'jsTree.dina_content.usergroups.null.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.null.php',
		'jsTree.dina_content.usergroups.null.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.null.php',
		'jsTree.dina_content.usergroups.root.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.root.php',
		'jsTree.dina_content.usergroups.root.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.root.php',
		'jsTree.dina_content.usergroups.systemusergroup.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.systemusergroup.php',
		'jsTree.dina_content.usergroups.systemusergroup.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.systemusergroup.php',
		'jsTree.dina_content.usergroups.usergroup.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.usergroup.php',
		'jsTree.dina_content.usergroups.usergroup.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.jsTree.dina_content.usergroups.usergroup.php',
		'jsTree.resources.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/jstree.resources/template.jsTree.resources.php',
		'jsTree.resources.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/jstree.resources/template.jsTree.resources.php',
		'jsTree.roleSelector.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeRoleSelector/template.jsTree.roleSelector.php',
		'jsTree.roleSelector.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeRoleSelector/template.jsTree.roleSelector.php',
		'jsTree.roles.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeRoles/template.jsTree.roles.php',
		'jsTree.roles.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeRoles/template.jsTree.roles.php',
		'jsTree.usergroupSelector.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeUsergroupSelector/template.jsTree.usergroupSelector.php',
		'jsTree.usergroupSelector.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeUsergroupSelector/template.jsTree.usergroupSelector.php',
		'jsTree.usergroups.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/jsTreeUsergroups/template.jsTree.usergroups.php',
		'jsTree.usergroups.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/jsTreeUsergroups/template.jsTree.usergroups.php',
		'layoutElementWidgetHandler.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/template.layoutElementWidgetHandler.php',
		'layoutElementWidgetHandler.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template.layoutElementWidgetHandler.php',
		'layoutElement_admin_1row_content.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/_layout/layoutElements/template.layoutElement_admin_1row_content.php',
		'layoutElement_admin_empty_content.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/_layout/layoutElements/template.layoutElement_admin_empty_content.php',
		'layout_admin_default.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/_layouts/template.layout_admin_default.php',
		'layout_admin_default.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/_layout/template.layout_admin_default.php',
		'layout_admin_empty.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/_layouts/template.layout_admin_empty.php',
		'layout_admin_empty.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/_layout/template.layout_admin_empty.php',
		'layout_admin_nomenu.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/_layout/template.layout_admin_nomenu.php',
		'layout_public_2row.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/_layout/template.layout_public_2row.php',
		'layout_public_empty.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/_layouts/template.layout_public_empty.php',
		'layout_widget_previewer.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/_layouts/template.layout_widget_previewer.php',
		'layout_widget_previewer.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/_layout/template.layout_widget_previewer.php',
		'resourceAclSelector.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/User/resourceAclSelector/template.resourceAclSelector.php',
		'resourceAclSelector.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/resourceAclSelector/template.resourceAclSelector.php',
		'resourceTree.jstree3.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/resourceTree/template.resourceTree.jstree3.php',
		'sdui.form.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.php',
		'sdui.form.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.php',
		'sdui.form.adminmenuMenuelement.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/AdminMenu/template.sdui.form.adminmenuMenuelement.php',
		'sdui.form.helper.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.helper.php',
		'sdui.form.helper.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.helper.php',
		'sdui.form.input.checkbox.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.input.checkbox.php',
		'sdui.form.input.checkbox.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.checkbox.php',
		'sdui.form.input.checkboxgroup.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.input.checkboxgroup.php',
		'sdui.form.input.checkboxgroup.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.checkboxgroup.php',
		'sdui.form.input.clearfloat.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.clearfloat.php',
		'sdui.form.input.date.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.input.date.php',
		'sdui.form.input.date.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.date.php',
		'sdui.form.input.datetime.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.input.datetime.php',
		'sdui.form.input.datetime.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.datetime.php',
		'sdui.form.input.groupend.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.groupend.php',
		'sdui.form.input.hidden.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.hidden.php',
		'sdui.form.input.password.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.input.password.php',
		'sdui.form.input.password.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.password.php',
		'sdui.form.input.radiogroup.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.input.radiogroup.php',
		'sdui.form.input.radiogroup.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.radiogroup.php',
		'sdui.form.input.select.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.input.select.php',
		'sdui.form.input.select.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.select.php',
		'sdui.form.input.text.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.input.text.php',
		'sdui.form.input.text.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.text.php',
		'sdui.form.input.textarea.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.input.textarea.php',
		'sdui.form.input.textarea.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.textarea.php',
		'sdui.form.input.textarea.ckeditor.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.input.textarea.ckeditor.php',
		'sdui.form.input.textarea.ckeditor.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.textarea.ckeditor.php',
		'sdui.form.input.textarea.codemirror.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.input.textarea.codemirror.php',
		'sdui.form.input.textarea.codemirror.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.textarea.codemirror.php',
		'sdui.form.input.textarea.tinymce.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.textarea.tinymce.php',
		'sdui.form.input.widgetgroupbeginning.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.input.widgetgroupbeginning.php',
		'sdui.form.mainmenuMenuelement.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/MainMenu/template.sdui.form.mainmenuMenuelement.php',
		'sdui.form.row.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Form/template.sdui.form.row.php',
		'sdui.form.row.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Form/template.sdui.form.row.php',
		'sdui.statusMessage.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template.sdui.statusMessage.php',
		'sideMenuAdmin.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/SideMenuAdmin/template.sideMenuAdmin.php',
		'sitemapXml.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template.sitemapXml.php',
		'templateEngineDemoBlade.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/TemplateEngineDemo/template.templateEngineDemoBlade.blade.php',
		'templateEngineDemoPhp.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/TemplateEngineDemo/template.templateEngineDemoPhp.php',
		'templateEngineDemoTwig.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/TemplateEngineDemo/template.templateEngineDemoTwig.twig',
		'templateEngineDemoWrapper.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/TemplateEngineDemo/template.templateEngineDemoWrapper.php',
		'topMenuAdmin.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/TopMenuAdmin/template.topMenuAdmin.php',
		'topMenuAdmin.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/TopMenuAdmin/template.topMenuAdmin.php',
		'userDescription.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/template.userDescription.php',
		'userList.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/User/template.userList.php',
		'userMenu.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/UserMenu/template.userMenu.php',
		'widgetEdit.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/template.widgetEdit.php',
		'widgetEdit.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template.widgetEdit.php',
		'widgetEditAfter.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template.widgetEditAfter.php',
		'widgetEditBefore.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template.widgetEditBefore.php',
		'widgetInsert.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/template.widgetInsert.php',
		'widgetInsert.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template.widgetInsert.php',
		'widgetPreviewInfo.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/template.widgetPreviewInfo.php',
		'widgetPreviewInfo.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template.widgetPreviewInfo.php',
		'widgetPreviewList.RadaptorPortalAdmin' => 'packages/registry/themes/portal-admin/theme/Cms/template.widgetPreviewList.php',
		'widgetPreviewList.SoAdmin' => 'packages/registry/core/cms/templates-common/default-SoAdmin/Cms/template.widgetPreviewList.php',
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
