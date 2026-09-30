<?php

declare(strict_types=1);
namespace ProcessWire;

// Test bootstrap: boots ProcessWire against the test database and refuses to
// run against anything else. Never run this against the development or
// production database.

$selectedDatabase = getenv('MF_DB');
if ($selectedDatabase === false) {
	putenv('MF_DB=test');
	$_ENV['MF_DB'] = 'test';
	$_SERVER['MF_DB'] = 'test';
} elseif ($selectedDatabase !== 'test') {
	// Stop before ProcessWire boots, since it would connect to another database.
	fwrite(STDERR, "Refusing to run tests: MF_DB is set to a value other than 'test'. Unset it or set MF_DB=test.\n");
	exit(1);
}

$backendRoot = dirname(__DIR__);
chdir($backendRoot);

require $backendRoot . '/index.php';

$expectedDatabase = 'mf_test';
$actualDatabase = wire('database')->query('SELECT DATABASE()')->fetchColumn();

if ($actualDatabase !== $expectedDatabase) {
	fwrite(
		STDERR,
		"Refusing to run tests: the connected database is not '{$expectedDatabase}'. " .
		"Set MF_DB=test before running PHPUnit.\n"
	);
	exit(1);
}
