<?php

class Grapher {

    function __construct($graphite_server, $base_ns) {
        $this->base_ns = $base_ns;
        $this->graphite_server = $graphite_server;
    }

    public function graphResults($results) {
        foreach ($results as $result) {
            $this->graph($result['location'], $result['label'], $result['date'], $result['data']);
        }
    }

    public function graph($location, $label, $date, $data) {
        $base_ns = $this->base_ns;
        foreach($data as $key => $value) {
            echo "$base_ns.$label.$key $value $date\n";
            `echo "$base_ns.$label.$key $value $date" | nc $this->graphite_server 2003`;
        }
    }
}
