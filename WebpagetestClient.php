<?php

class WebpagetestClient {

    static $BACKLOG_LIMIT = 20;
    static $locations_path = '/getLocations.php';
    static $run_path = '/runtest.php';

    public function __construct($config) {
        $this->config = $config;
        $this->server = $config['server'];
    }

    public function getLocationsXML() {
        $results = array();

        $params = array('f' => 'xml',);

        $response = $this->request(WebpagetestClient::$locations_path, $params);

        $locations = simplexml_load_string($response)->xpath('data/location');

        if (!$locations) {
            $this->log("Could not load locations]n");
            return $results;
        }
        // makin' this into an array, so its easier to work with.
        // We need these three parts from the location xml for each "location"
        foreach ($locations as $location) {

            $results[] = array(
                'id' => (string) $location->id,
                'browser' => (string) $location->Browser,
                'location' => (string) $location->location,
                'pendingTotal' => (string) $location->PendingTests->Total,
            );
        }
        return $results;
    }

    public function request($path, $params) {
        $query_str = "?" . http_build_query($params);
        $url = $this->server . $path . $query_str;
        return file_get_contents($url);

    }

    /* Helper to get auth (and other) cookies */
    public function getAuthCookies() {
        $username = $this->config['username'];
        $password = $this->config['password'];

        if (empty($username) || empty($password)) {
            return array();
        }

        // TODO consider moving this, adding a run id, or tmpfile? Any problems if one run steps on another?
        $cookiejar = __DIR__.'/cookies.txt';

        if (!is_file($cookiejar)) {
            touch($cookiejar);
        }

        $curl = curl_init('https://www.etsy.com/signin');
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookiejar);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'username=' . $username . '&password=' . $password);
        curl_exec($curl);
        curl_close($curl);
        $cookies = array();

        foreach (array_map('trim', file($cookiejar)) as $line) {
            if (!$line || strpos($line, '# ') === 0) {
                continue;
            }
            $fields = explode("\t", $line);
            $cookies[$fields[5]] = $fields[6];
        }
        return $cookies;
    }

    /* A helper to get the set of options we're almost always going to be using.
     * TODO: Maybe these should live in a conf or something */
    public function getDefaultRunOptions () {
        // Turn all these on for starters
        $defaults = array_fill_keys(array("runs", "fvonly", "sensitive", "private"), 1);
        $defaults['f'] = 'xml';
        $defaults['notify'] = 'webpagetest@etsy.com';
        return $defaults;
    }

    /* Returns the script to add cookies to the test (but without navigate portion, since that is URL specific.
     * It's added automattically by testUrl(s)
     */
    public function generateScriptForCookies($cookies) {
        $script = "";
        foreach($cookies as $cookie_name => $cookie_value) {
            $script .= "setCookie https://www.etsy.com $cookie_name=$cookie_value\n";
        }
        return $script;
    }

    public function prependScripts($prepends) {
        // Possible input types
        // "Login"
        // "../scripts/bypass_cdn.txt"
        // array("Login", "../scripts/bypass_cdn.txt")
        if (!is_array($prepends)) {
            $prepends = array($prepends);
        }

        $scripts_dir = __DIR__.'/scripts/';
        $script = '';
        foreach ($prepends as $prepend) {
            if(file_exists($scripts_dir.$prepend)) {
                $script .= file_get_contents($scripts_dir.$prepend);
            } elseif(method_exists($this, $prepend)) {
                $script .= $this->$prepend();
            }
        }
        return $script;
    }

    public function Login() {
        $cookies = $this->getAuthCookies();
        return $this->generateScriptForCookies($cookies);
    }

    public function getRunOptions() {
        return array_merge($this->getDefaultRunOptions(), isset($this->config['run_options']) ? $this->config['run_options'] : array());
    }

    /*
     * Use to test a single url
     * $url array<strings>  the urls to test
     * $options array key => value options to be passed to each api call
     */
    public function test($locations) {
        if(isset($this->config['script'])) {
            return $this->testScript($locations);
        } else {
            return $this->testUrls($locations);
        }
    }

    public function testScript($locations) {
        $options = $this->getRunOptions();
        $options['script'] = file_get_contents(__DIR__.'/scripts/'.$this->config['script']);
        if(isset($this->config['prepend'])) {
            $options['script'] .= $this->prependScripts($this->config['prepend']);
        }

        $results = $this->runTests(str_replace('.txt', '', $this->config['script']), 'scripted test', $locations, $options);

        return $results;
    }

    public function testUrls($locations) {
        $results = array();
        $options = $this->getRunOptions();
        $urls = $this->config['urls'];
        foreach ($urls as $name => $url) {
            $options['url'] = $url;

            $options['script'] = '';
            if (isset($this->config['prepend'])) {
                $options['script'] = $this->prependScripts($this->config['prepend']);
            }
            $options['script'] .= "\nnavigate $url";

            $results = array_merge($results, $this->runTests($name, $url, $locations, $options));
        }

        return $results;
    }

    public function runTests($name, $url, $locations, $options) {
        $results = array();

        foreach ($locations as $location) {

            if ($location['pendingTotal'] > WebpagetestClient::$BACKLOG_LIMIT) {
                $this->log("Too many pending tests. Skipping location: " . $location['location']);
                continue;
            }

            $options['location'] = $location['location'];
            $options['browser'] = $location['browser'];
            $login_string = 'signedout';
            if(isset($this->config['prepend'])) {
                if(is_string($this->config['prepend']) && $this->config['prepend'] == 'Login') {
                    $login_string = 'signedin';
                } elseif(is_array($this->config['prepend']) && in_array('Login', $this->config['prepend'])) {
                    $login_string = 'signedin';
                }
            }
            $label = implode('.', array(
                $name,
                $this->slug($location['location']),
                $this->slug($location['browser']),
                $login_string,
            ));
            $options['label'] = $label;

            $response = $this->request(WebpagetestClient::$run_path, $options);
            $this->log("Started $login_string test for $url / {$options['location']} / {$options['browser']}");
            $results[] = $response;
        }

        return $results;
    }

    public function getResults($runs) {
        $results = array();
        foreach ($runs as $run) {

            // This is a read from the file system
            $runXML = simplexml_load_string(file_get_contents($run));

            // This is network read.
            $resultXML = simplexml_load_string(file_get_contents($runXML->data->xmlUrl));
            $statusCode = $resultXML->statusCode;

            if ($statusCode == 200) {
                $test = $resultXML->data->median->firstView;

                if (!is_null($test)) {

                    // Chromatic customizations to save and then upload offsite.
                    $filename = preg_replace('#^https?://#', '', $resultXML->data->testUrl);
                    $filename = preg_replace('/', '--', $filename);
                    file_put_contents('/webpagetest-runs/' . $filename . '-' . date("m-d-Y") . '.xml', file_get_contents($runXML->data->xmlUrl));
                    // End Chromatic customizations.

                    $label_parts = explode(".", (string) $resultXML->data->label);
                    $browser = $label_parts[2];
                    $pageName = $label_parts[0];

                    $details = $resultXML->xpath('data/run/firstView/pages/details');
                    $detailsUrl = '';
                    if (!empty($details)) {
                        $detailsUrl = $details[0];
                    }

                    $results[] = array(
                        'location' => (string) $resultXML->data->location,
                        'label' => (string) $resultXML->data->label,
                        'pageName' => $pageName,
                        'browser' => $browser,
                        'date' => (int) $test->date,
                        'URL' => (string) $test->URL,
                        'testId' => (string) $resultXML->data->testId,
                        'connectivity' => (string) $resultXML->data->connectivity,
                        'data' => $this->extractNumericData($test),
                        'xmlUrl' => $runXML->data->xmlUrl,
                        'details' => $detailsUrl,
                    );

                } else {
                    // TODO this means there were no sucessful results.
                    // Maybe graph these, log these or alert somehow.
                }

                // Note I'm deleting bad runs too. Better than these things accumulating disasterously over time.
                unlink($run);
            } else if ($statusCode == 404) {
                $testId = (string) $resultXML->data->testId;
                $this->log("Test $testId was invalid: not found.");
                unlink($run);
            } else {
                // Just leave it I guess.
                // TODO: maybe check the date and delete if it gets old
            }
        }
        return $results;
    }

    private function extractNumericData($test) {
        $keys = array(
            'loadTime', 'TTFB', 'bytesIn', 'bytesInDoc', 'connections',
            'requests', 'requestsDoc', 'render', 'fullyLoaded',
            'docTime', 'domTime', 'domElements', 'score_cache',
            'score_cdn', 'score_gzip', 'score_cookies', 'score_keep-alive',
            'score_minify', 'score_combine', 'score_compress',
            'score_etags', 'gzip_total', 'gzip_savings',
            'minify_total', 'minify_savings', 'image_total', 'image_savings',
            'optimization_checked', 'titleTime', 'SpeedIndex',
        );

        $data = array();
        foreach ($keys as $key) {
            $data[$key] = (int) $test->$key;
        }

        // Maybe this map can get switched out depending on config or server
        return $data;
    }

    private function slug($str) {
        return preg_replace('/\W/', '_', $str);
    }

    public function log($str) {
        print(date("c") . " " . $str . "\n");
    }

}
