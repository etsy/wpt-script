<?php

$dir = dirname(__FILE__);
chdir($dir);

require("../WebpagetestClient.php");
require("../ResultsHelper.php");
// require("../Grapher.php");
// require("../SplunkLogger.php");

$shortopts = "c:";
$options = getopt($shortopts);

$config_file = isset($options['c']) ? $options['c'] : 'default.conf';
$config = json_decode(file_get_contents(__DIR__.'/../conf/'.$config_file), true);

if (!$config) {
    print("Requires config file to run\n");
    exit(-1);
}

if (!$config['server']) {
    print("Requires server option to run");
    exit(-1);
}

if (!$config['logging_ns']) {
    print("Requires logging ns option to run");
    exit(-1);
}

if (!$config['pending_dir']) {
    print("Requires pending dir option to run\n");
    exit();
}

$results_helper = new ResultsHelper($config['pending_dir']);
$runs = $results_helper->getRuns();

$client = new WebpagetestClient($config);
$results = $client->getResults($runs);

$logging_ns = $config['logging_ns'];
