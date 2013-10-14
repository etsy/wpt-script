<?php

require_once(__DIR__.'/../WebpagetestClient.php');

class WebpagetestClientTest extends PHPUnit_Framework_Testcase {

    function testLoginScript() {
        $client = new WebpagetestClient('localhost');
        $this->assertRegExp("%setCookie http://www.etsy.com%", $client->Login());
    }

    function testBypassCDN() {
        $client = new WebpagetestClient('localhost');
        $this->assertRegExp("%setDns %", $client->prependScripts("bypass_cdn.txt"));
    }

    function testMobileTemplates() {
        $client = new WebpagetestClient('localhost');
        $this->assertRegExp("/setCookie .* kt-/", $client->prependScripts(array("Login", "mobile.txt")));
        $this->assertRegExp("/setCookie .* m=1/", $client->prependScripts(array("Login", "mobile.txt")));
    }
}
