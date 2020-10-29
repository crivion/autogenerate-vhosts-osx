# autogenerate-vhosts-osx
Autogenerate local machine vhost (mac osx)

Set your paths in index.php
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

Usage (must be super user):

sudo php index.php

![Alt text](https://i.postimg.cc/Xq4kc3hs/Screenshot-2020-10-29-at-16-41-20.png )
