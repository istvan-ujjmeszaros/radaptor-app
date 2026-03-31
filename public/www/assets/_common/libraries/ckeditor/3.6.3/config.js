/*
Copyright (c) 2003-2009, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

var current_editor={};

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	config.uiColor = '#dddddd';
	config.entities = false;
	config.entities_latin = false;
	config.entities_greek = false;
	config.entities_additional = 'acirc';
	config.fillEmptyBlocks = false;
	config.forcePasteAsPlainText = true;

	config.dialog_backgroundCoverColor = '#000000';
	config.dialog_backgroundCoverColor = '#ffffff';
	config.dialog_backgroundCoverOpacity = 0.5;

	config.startupShowBorders = true;

	config.removePlugins = 'scayt';

	config.language = 'hu';

	config.filebrowserBrowseUrl = "/admin/resources/";
	config.filebrowserImageBrowseUrl = "/admin/resources/";

};

CKEDITOR.config.toolbar_test =
[
	['Maximize','About']
]

CKEDITOR.config.toolbar_Minimal =
[
	['Maximize'],
	['Bold','Italic'],
	['NumberedList','BulletedList'],
	['Link','Unlink','Anchor'],
	['Image','Table'],
	['Source'],
	['PasteText','PasteFromWord'],
	['Undo','Redo','RemoveFormat'],
	['ShowBlocks']
]

CKEDITOR.config.toolbar_Fulltext =
[
	['Maximize','About', '-', 'Source','-','NewPage','Preview'],
	['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
	['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	'/',
	['Bold','Italic','Underline','Strike'],
	['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	['NumberedList','BulletedList','-','Subscript','Superscript','-','Outdent','Indent','Blockquote'],
	['Link','Unlink','Anchor'],
	['Image','Table','HorizontalRule','SpecialChar'],
	'/',
	['Styles','Format','FontSize'],
	['TextColor','BGColor'],
	['CreateDiv','ShowBlocks']
];

CKEDITOR.config.toolbar_Fulltext_Plugin =
[
	['Maximize', 'ShowBlocks','About', '-', 'Source','-','NewPage','Preview'],
	['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
	['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	'/',
	['Bold','Italic','Underline','Strike'],
	['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	['NumberedList','BulletedList','-','Subscript','Superscript','-','Outdent','Indent','Blockquote'],
	['Link','Unlink','Anchor'],
	['Image','Table','HorizontalRule','SpecialChar'],
	'/',
	['Styles','Format','FontSize'],
	['TextColor','BGColor'],
	'/',
	['RoyalkertH1', 'royalkert_plugin', 'royalkert_customheader']
];

CKEDITOR.config.toolbar_Full =
[
	['Maximize', 'ShowBlocks','About', '-', 'Source','-','NewPage','Preview','-','Templates'],
	['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
	['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	'/',
	['Bold','Italic','Underline','Strike'],
	['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	['NumberedList','BulletedList','-','Subscript','Superscript','-','Outdent','Indent','Blockquote'],
	['Link','Unlink','Anchor'],
	['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],
	'/',
	['Styles','Format','Font','FontSize'],
	['TextColor','BGColor'],
	['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
];
