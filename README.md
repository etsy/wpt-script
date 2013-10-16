WebPagetest Script
===================

This tool is a simple wrapper for the WebPagetest API that allows you to easily generate tests and graph results

example cron entry:
```bash
0 */6 * * * php /path/to/webpagetest-public/bin/run.php
*/5 * * * * php /path/to/webpagetest-public/bin/get_results.php
```

Configuration
=============

Specify a conf file by passing -c:
```bash
php run.php -c /path/to/my.conf
```

By default, looks for a default.conf in the root folder.

Configs are JSON. Available Config Keys:

- server: (String) the webpage test server to run against.
- urls: (Object) where keys are the name of the page (e.g. 'search') and the values are the urls to test. Used only by run.php
 OR
- script: (String) a WPT script
- prepend: (String|Array) WPT scripts to prepend to url or script
- graphite: (String) the graphite server to graph to. Used only by get_results.php
- logging_js: (String) 'webpagetest.public' or 'webpagetest.private'. Used as the graphite namespace and added to splunk logs so we can easily tell which are public, which are private
- locations: (Array) List of browser locations. Use this to limit to only certain locations/browsers
             To see a list of available locations, run run.php with the flag -l. It will output locations and exit.
- run_options: (Object) The options to pass to WebPagetest, which follow the
  [RESTful APIs](https://sites.google.com/a/webpagetest.org/docs/advanced-features/webpagetest-restful-apis) documentation.


WebPagetest Server
==================

The test server, where you view results and set configuration for the agents, is an Ubuntu EC2 instance.
You can SSH to it with the following command:

```bash
ssh -i ~/.ssh/webpagetest.pem ubuntu@yourwptinstall.com
```

WebPagetest is installed on /var/www/.  The directories you will likely care about are results/ and settings/,
although if you want to clear out existing tests you can delete the files in /var/www/work/jobs/US_East_Foo/*.

The documentation for private WPT instances is [here](https://sites.google.com/a/webpagetest.org/docs/private-instances).
Some other useful links are:

* See pending work - /getLocations.php
* See the status of the test agents - /getTesters.php


Test Agents
===================

The test agents are built from EC2 AMIs that Patrick and the WPT team provide.  You can login to your
EC2 account to manage them, and you can remote desktop to the windows machines to watch the browsers
execute the tests or debug issues.


Debugging Issues
===================

Once in a while things will break with the private WPT instance - either test results will stop showing up,
individual metrics will be lost, or other oddities may occur.  Before doing anything else, the steps to take are:

1. [Upgrade WPT](https://sites.google.com/a/webpagetest.org/docs/private-instances) to the latest version
1. Upgrade the [test agents](https://sites.google.com/a/webpagetest.org/docs/private-instances#TOC-Updating-Test-Agents)
1. Remote desktop to the test agents that are having trouble and reboot them
1. Check to see if the disk is full on the private WPT instance - once it hits 70% or so we start to see issues
1. Check the XML results to make sure they are valid (e.g. http://www.yourwptinstall.com/xmlResult.php?test=*test_id*)

If you are still having issues, try posting in the
[WebPagetest forums](http://www.webpagetest.org/forums/forumdisplay.php?fid=12) - Pat is extremely responsive.


Etsy Specific Information
===================

For more Etsy specific setup details, check out the "Monitoring Frontend Performance with WebPagetest" article on our internal wiki.
