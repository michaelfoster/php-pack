<?php

require 'packer.php';

$packer = new Packer();

$path = isset($argv[1]) ? $argv[1] : 'example/example.php';

$dir = dirname(realpath($path));
if(!$dir || !@chdir($dir))
	die("Could not open resolve path: $path. Ensure it exists.\n");

$files = explode("\n", trim(shell_exec('find . -type f')));
foreach($files as $file) {
	fprintf(STDERR, "[+] Packing %s\n", $file);
	$packer->add(substr($file, 2));
}

fprintf(STDERR, "[+] Generating %s\n", basename($path));
$code = $packer->code(basename($path));

echo $code;

