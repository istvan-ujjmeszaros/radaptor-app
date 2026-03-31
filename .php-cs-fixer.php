<?php

/** @noinspection ALL */

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = PhpCsFixer\Finder::create()
	->exclude([
		'tools',
		'generated', // Exclude auto-generated files
	])
	->notPath('#radaptor/radaptor-framework/generators/#') // Exclude code generators that use heredoc templates
	->in([
		'app',
		'config',
		'radaptor',
	]);

$config = new PhpCsFixer\Config();

$config->setRules([
	'@PSR12' => true,
	// Explicitly disabled: template files use if/endif, foreach/endforeach alternative syntax
	// for readability when mixing PHP control structures with HTML. The PSR-12 default
	// enforces curly braces everywhere, but alternative syntax is the conventional style
	// for view/template files and should not be normalized away.
	'no_alternative_syntax' => false,
	'no_spaces_inside_parenthesis' => true,
	'no_trailing_whitespace' => true,
	'no_whitespace_in_blank_line' => true,
	'single_blank_line_at_eof' => true,
	'statement_indentation' => true,
	'indentation_type' => true,
	'array_indentation' => true,
	'ternary_to_null_coalescing' => true,
	'whitespace_after_comma_in_array' => [
		'ensure_single_space' => true,
	],
	'align_multiline_comment' => true,
	'concat_space' => ['spacing' => 'one'],
	'array_syntax' => ['syntax' => 'short'],
	'echo_tag_syntax' => [
		'format' => 'short',
	],
	'include' => true,
	'linebreak_after_opening_tag' => true,
	'method_argument_space' => true,
	'no_unused_imports' => true,
	'object_operator_without_whitespace' => false,
	'operator_linebreak' => [
		'only_booleans' => false,
		'position' => 'beginning',
	],
	'phpdoc_summary' => true,
	'phpdoc_trim' => true,
	'phpdoc_trim_consecutive_blank_line_separation' => true,
	'no_extra_blank_lines' => [
		'tokens' => ['attribute', 'curly_brace_block', 'extra', 'parenthesis_brace_block', 'square_brace_block', 'use', 'use_trait'],
	],
	'blank_line_before_statement' => [
		'statements' => ['break', 'case', 'continue', 'declare', 'default', 'do', 'exit', 'for', 'foreach', 'goto', 'if', 'phpdoc', 'return', 'switch', 'throw', 'try', 'while', 'yield', 'yield_from'],
	],
	'@PHP83Migration' => true,
	'declare_strict_types' => false,
])
	->setIndent("\t")
	->setLineEnding("\n")
	->setFinder($finder)
	->setParallelConfig(ParallelConfigFactory::detect());

return $config;
