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

// Save the results to some file.

// foreach ($results as $result) {
//   $path = "/Users/chrisfree/Desktop/test.xml";
//   var_dump($result);
//   $resultXML = simplexml_load_string($result);
//   file_put_contents($path, $resultXML);
// }


// $splunkLogger = new SplunkLogger($config['splunkLog'], $logging_ns);
// foreach ($results as $result) {
//     $splunkLogger->log($result);
// }

// $graphite = $config['graphite'];

// $grapher = new Grapher($graphite, $logging_ns);
// $grapher->graphResults($results);
