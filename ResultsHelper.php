<?php

class ResultsHelper {

    function __construct($pending_dir) {
        $this->run_dir = $this->pathJoin('/pending/', $pending_dir);
    }

    private function pathJoin() {
        $parts = func_get_args();
        return str_replace("//", "/", implode('/', $parts));
    }

    public function storeRuns($runs) {

        $output_dir = $this->pathJoin(__DIR__, $this->run_dir);

        if (!file_exists($output_dir)) {
            mkdir($output_dir, 0777, true);
        }

        foreach ($runs as $run) {
            $resultXML = simplexml_load_string($run);
            if ($resultXML->statusText == 'Ok') {
                $path = $this->pathJoin(__DIR__, $this->run_dir, $resultXML->data->testId . '.xml');
                if (!file_exists($path)) {
                    file_put_contents($path, $run);
                }
            } else {
                // TODO: Log or something
            }
        }

    }

    /* get all the runs available and sort them by mtime so we deal with the most likely
     * to be fininshed first */
    public function getRuns() {
        $glob_pattern = $this->pathJoin(__DIR__, $this->run_dir, '*.xml');
        $runs = glob($glob_pattern);
        usort($runs, function($a_path, $b_path) {
            return filemtime($b_path) - filemtime($a_path);
        });
        return $runs;
    }

}
