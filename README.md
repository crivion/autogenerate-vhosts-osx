# autogenerate-vhosts-osx
Autogenerate local machine vhost (mac osx)


# Autogenerate local vhosts for mac os x

* PHP-cli tool

* Site Directories must end in .local to be picked up

* example /path/site.local will become http://local.site 


```
define('SITES_PATH', '/Users/your-user/Sites');
define('VHOSTS_FILE', '/etc/apache2/extra/httpd-vhosts.conf');
define('HOSTS_FILE', '/etc/hosts');
```

# Usage (must be super user):

```sudo php index.php```

# PHP-Cli Output Example

![Alt text](https://i.postimg.cc/Xq4kc3hs/Screenshot-2020-10-29-at-16-41-20.png )
