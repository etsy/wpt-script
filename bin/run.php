<?php

$dir = dirname(__FILE__);
chdir($dir);

require("../WebpagetestClient.php");
require("../ResultsHelper.php");

$shortopts = "c:";
$options = getopt($shortopts);

$config_file = isset($options['c']) ? __DIR__.'/../conf/'.$options['c'] : __DIR__.'/../conf/default.conf';
$config = json_decode(file_get_contents($config_file), true);

if (!$config) {
    $json_error = json_last_error();
    if ($json_error) {
        print "JSON Decode Error: $json_error\n";
    } else {
        print("Requires config file to run\n");
    }
    exit();
}

if (!$config['server']) {
    print("Requires server option to run\n");
    exit();
}

if (!$config['pending_dir']) {
    print("Requires pending dir option to run\n");
    exit();
}

if (isset($config['urls']) && isset($config['script'])) {
    print("Cannot have both URLs and a script, use prepend instead");
    exit();
}

$client = new WebpagetestClient($config);
$browser_locations = $client->getLocationsXML();

// Allow use to specify locations, but make sure they're locations that exist on the server
if (isset($config['locations'])) {
    $config_locations = $config['locations'];
    $browser_locations = array_filter($browser_locations, function($location) use($config_locations) {
        return in_array($location['id'], $config_locations);
    });
}

$target = isset($config['urls']) ? count($config['urls']) . " urls" : $config['script'];
$client->log("Started test with config: $config_file. Testing " . $target . " across " . count($browser_locations) . " locations.");
$runs = $client->test($browser_locations);

$results_helper = new ResultsHelper($config['pending_dir']);
$results_helper->storeRuns($runs);
