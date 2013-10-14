<?php

$dir = dirname(__FILE__);
chdir($dir);

$last_path = './version.txt';
$last_config_path = './config-version.txt';

if (! file_exists($last_path)) {
    file_put_contents($last_path, 0);
}

if (! file_exists($last_config_path)) {
    file_put_contents($last_path, 0);
}

$last = file_get_contents($last_path);
$last_config = file_get_contents($last_config_path);

$current = file_get_contents('http://www.etsy.com/version.txt');
$current_config = file_get_contents('http://www.etsy.com/config-version.txt');

if (!$current || !$current_config) {
    exit();
}

if ($current != $last || $current_config != $last_config) {
    $output = `php ./run.php`;
    print $output;
    file_put_contents($last_path, $current);
    file_put_contents($last_config_path, $current_config);
}
