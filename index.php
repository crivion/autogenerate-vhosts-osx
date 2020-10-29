<?php
declare(strict_types=1);
namespace Crivion;

// include the class
require_once __DIR__ . '/OSXVhosts.php';

/*
 *
 * Autogenerate local vhosts for mac os x
 * PHP-cli tool
 * Site Directories must end in .local to be picked up
 * Usage: sudo php index.php
 */

// which folder to scan for *.sites folders
define('SITES_PATH', '/Users/crivion/Sites');

// apache hosts file
define('VHOSTS_FILE', '/etc/apache2/extra/httpd-vhosts.conf');

// mac hosts file
define('HOSTS_FILE', '/etc/hosts');

// instantiate the class
$OSXVhosts = new OSXVHosts();

// set required paths
$OSXVhosts::setSitesPath(SITES_PATH);
$OSXVhosts::setApacheVhostsFile(VHOSTS_FILE);
$OSXVhosts::setOsHostsFile(HOSTS_FILE);

// build sites list
$OSXVhosts->buildSitesList();

// output sites available
print $OSXVhosts->generateSitesListTable();

// capture key
print 'Press [e] to enable OR [d] to disable, [ctrl+c] to quit: ' . PHP_EOL;

// action
try {
    $OSXVhosts->doActionBasedOnkey();
}catch(\Exception $e) {
    print 'Error: ' . $e->getMessage() . PHP_EOL;
}