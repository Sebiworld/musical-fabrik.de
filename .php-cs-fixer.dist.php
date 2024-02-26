<?php

return (new PhpCsFixer\Config())
	->setRules([
		'@PSR2' => true,
		'array_indentation' => true,
		'array_syntax' => ['syntax' => 'short'],
		'combine_consecutive_unsets' => true,
		'multiline_whitespace_before_semicolons' => false,
		'single_quote' => true,
		'braces' => [
			'allow_single_line_closure' => true,
			'position_after_functions_and_oop_constructs' => 'same',
		],
		'braces_position' => [
			'functions_opening_brace' => 'same_line',
			'classes_opening_brace' => 'same_line'
		],
		'concat_space' => ['spacing' => 'one'],
		'declare_equal_normalize' => true,
		'function_typehint_space' => true,
		'include' => true,
		'lowercase_cast' => true,
		'no_multiline_whitespace_around_double_arrow' => true,
		'no_spaces_around_offset' => true,
		'no_whitespace_before_comma_in_array' => true,
		'no_whitespace_in_blank_line' => true,
		'object_operator_without_whitespace' => true,
		'ternary_operator_spaces' => true,
		'trim_array_spaces' => true,
		'unary_operator_spaces' => true,
		'whitespace_after_comma_in_array' => true,
		'elseif' => false,
		'no_blank_lines_before_namespace' => true,
		'single_space_after_construct' => ['constructs' => [
			'abstract',
			'as',
			'attribute',
			'break',
			'case',
			'catch',
			'class',
			'clone',
			'comment',
			'const',
			'const_import',
			'continue',
			'do',
			'echo',
			'else',
			'elseif',
			'enum',
			'extends',
			'final',
			'finally',
			'function',
			'function_import',
			'global',
			'goto',
			'implements',
			'include',
			'include_once',
			'instanceof',
			'insteadof',
			'interface',
			'match',
			'named_argument',
			'namespace',
			'new',
			'open_tag_with_echo',
			'php_doc',
			'php_open',
			'print',
			'private',
			'protected',
			'public',
			'readonly',
			'require',
			'require_once',
			'return',
			'static',
			'switch',
			'throw',
			'trait',
			'try',
			'use',
			'use_lambda',
			'use_trait',
			'var',
			'while',
			'yield',
			'yield_from',
		]]
	])
	// ->setIndent(str_pad('', 2))
	->setIndent("\t")
	->setLineEnding("\n")
;
