#!/usr/bin/php
<?php
/**
 *	Generates autocomplete file for Sublime Text 2 (or TextMate?) based on all
 *	PHP files in folder argument
 */

# TODO: Don't auto complete class methods?

if ($argc !== 2) {
	printf("Usage: %s folder > Project.sublime-completions\n", $argv[0]);
	die();
}

$rdir = new RecursiveDirectoryIterator($argv[1]);

$flist = array();
foreach (new RecursiveIteratorIterator($rdir) as $f) {
	$blevel = 0;
	$t = token_get_all(file_get_contents((string)$f));

	$next_is_function = FALSE;
	for ($i = 0; $i < count($t); $i++) {
		switch (is_string($t[$i]) ? $t[$i] : $t[$i][0]) {
			case T_WHITESPACE:
			case T_COMMENT:
				continue;
			case T_FUNCTION:
				$next_is_function = TRUE;
				continue;
			case T_STRING:
				if ($next_is_function) {
					$func = array($t[$i][1]);
					$next_is_function = FALSE;
				}
				break;
			case "(":
				$blevel++;
				break;
			case ")":
				$blevel--;
				if (isset($func) && !$blevel) {
					$flist[] = $func;
					unset($func);
				}
				break;
			case T_VARIABLE:
				if ($blevel == 1 && isset($func)) {
					$func[] = ltrim($t[$i][1], '$');
				}
				break;
		}
	}
}

$st = array(
	"scope" => "source.php - variable.other.php",
	"completions" => array());
$done = array();
foreach ($flist as $f) {
	if (in_array($f[0], $done)) {
		continue;
	}
	$done[] = $f[0];

	$str = sprintf(
		"%s(%s)",
		$f[0],
		join(
			", ",
			array_map(
				create_function('$arg,$i', 'return \'${\' . "$i:$arg}";'),
				array_slice($f, 1),
				range(1, count($f) - 1))));
	$st["completions"][] = array(
		"trigger" => $f[0],
		"contents" => $str);
}

# Headers when using from a browser
#if (php_sapi_name() !== "cli") {
#	header('Content-Type: application/json');
#	header('Content-Disposition: attachment; filename="Project.sublime-completions"');
#}

echo json_encode($st);