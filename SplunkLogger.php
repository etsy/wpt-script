<?php

class SplunkLogger {

    public $path;

    function __construct($path, $logging_ns) {

        if (empty($path)) {
            throw new InvalidArgumentException("Must include path to log file");
        }

        if (!file_exists($path)) {
            throw new InvalidArgumentException("Path supplied must exist.");
        }

        $this->path = $path;
        $this->logging_ns = $logging_ns;
    }

    function log($result) {
        // Stick the logging namespace in there:
        $result['logging_ns'] = $this->logging_ns;
        $date = $result['date'];

        // Lazy flattening.
        $data = $result['data'];
        $result = array_merge($result, $data);
        unset($result['data']);

        $line = date('c', $date) . $this->formatData($result) . "\n";
        file_put_contents($this->path, $line, FILE_APPEND);
    }

    function formatData($data) {
        $str = "";
        foreach($data as $key => $val) {
            if (strpos($val, " ") !== false) {
                $val = "\"$val\"";
            }
            $str .= sprintf(" %s=%s", $key, $val);
        }
        return $str;
    }

}
