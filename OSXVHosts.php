<?php
declare(strict_types=1);

namespace Crivion;

class OSXVHosts
{

    public static $sitesPath;
    public static $apacheVhostsFile;
    public static $osHostsFile;
    public static $sitesList = [];

    public function __construct()
    {
        try {

            // is this php-cli
            $this->checkIsPHPCLI();

        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    // check if is php-cli
    private function checkIsPHPCLI(): void {

        if( php_sapi_name() != 'cli' )
            throw new \Exception("This app must be invoked via php-cli.");

    }

    // Scan & Build the list of sites
    public function buildSitesList(): void
    {

        // read hosts file
        $hostsFile = file_get_contents(self::$apacheVhostsFile);

        // loop through each folder
        foreach (glob(self::$sitesPath . '/*.local', GLOB_ONLYDIR) as $dir) {

            // get folder
            $dirname = basename($dir);

            // define hostname
            $hostname = explode('.', $dirname);
            $hostUri = $hostname[1] . '.' . $hostname[0];

            // match the hostname in the hosts file
            $matchHostname = 'Servername ' . $hostname[1] . '\.' . $hostname[0];
            $status = preg_match('/' . $matchHostname . '/i', $hostsFile) ? 'Enabled' : 'Not Enabled';

            // append site in the list
            self::$sitesList[] = ['site' => $hostUri,
                                    'path' => $dir,
                                    'dirname' => $dirname,
                                    'status' => $status];

        }

    }

    /**
     * @return String
     */
    public static function getSitesPath(): string
    {
        return self::$sitesPath;
    }

    /**
     * @param String $sitesPath
     */
    public static function setSitesPath(string $sitesPath): void
    {
        self::$sitesPath = $sitesPath;
    }

    /**
     * @return String
     */
    public static function getApacheVhostsFile(): string
    {
        return self::$apacheVhostsFile;
    }

    /**
     * @param String $apacheVhostsFile
     */
    public static function setApacheVhostsFile(string $apacheVhostsFile): void
    {
        self::$apacheVhostsFile = $apacheVhostsFile;
    }

    /**
     * @return String
     */
    public static function getOsHostsFile(): string
    {
        return self::$osHostsFile;
    }

    /**
     * @param String $osHostsFile
     */
    public static function setOsHostsFile(string $osHostsFile): void
    {
        self::$osHostsFile = $osHostsFile;
    }

    // generate table contents
    public function generateSitesListTable(): string
    {

        $output = $this->generateTableHeader();

        // loop through each site
        foreach (self::$sitesList as $i => $site) {

            // output details
            $output .= PHP_EOL;
            $output .= ($site['status'] == 'Enabled') ? "\e[32m" : "\e[31m";
            $output .= str_pad("[" . $i . "]", 5);
            $output .= str_pad($site['status'], 15);
            $output .= "\e[0m";
            $output .= str_pad($site['dirname'], 30);
            $output .= "\e[1m http://" . $site['site'] . "\e[0m";

        }

        return $output . PHP_EOL;

    }


    // generate table header
    public function generateTableHeader(): string
    {

        $tableHeader = PHP_EOL;
        $tableHeader .= "\e[96m\e[1m";
        $tableHeader .= str_pad('ID', 5);
        $tableHeader .= str_pad('Status', 15);
        $tableHeader .= str_pad('Folder', 30);
        $tableHeader .= ' Host';
        $tableHeader .= PHP_EOL;
        $tableHeader .= str_repeat('=', 100);
        $tableHeader .= "\e[0m";

        return $tableHeader;

    }

    // capture user key
    public function captureUserKey(): string
    {
        $handle = fopen("php://stdin", "r");

        $line = fgets($handle);
        $line = trim($line);
        fclose($handle);

        return $line;

    }

    // action based on key
    public function doActionBasedOnkey(): void {

        $key = $this->captureUserKey();

        if( 'e' == $key ) {
            print 'Enter site number to enable: ' . PHP_EOL;
            print $this->enableSite();
        } elseif( 'd' == $key ) {
            print 'Enter site number to disable: ' . PHP_EOL;
            echo $this->disableSite();
        } else {
            throw new \Exception('Unrecognized key');
        }

    }

    public function enableSite(): string {

        $id = $this->captureUserKey();

        if(!isset(self::$sitesList[$id]))
            return 'ID of site does not exist';

        $dir = self::$sitesList[$id]['path'];
        $host = self::$sitesList[$id]['site'];
        $status = self::$sitesList[$id]['status'];

        if( $status == 'Enabled' ) {
            $return = PHP_EOL . 'Site ALREADY Enabled and available at: http://' . $host;
            $return .= PHP_EOL . 'Path: ' . $dir;
            return $return;
        }

        // try adding the VirtualHost Entry in apache vhosts file
        $enableSite = $this->writeVhostEntry($dir, $host);

        if($enableSite) {

            $return = PHP_EOL . 'Site Added in hosts file, should be available at: http://' . $host;
            $return .= PHP_EOL . 'Path: ' . $dir;


            // also add to hosts file
            if( $this->addToHostsFile($host) ) {
                $return .= PHP_EOL . 'Also added entry in ' . self::$osHostsFile;
            }else{
                $return .= PHP_EOL . 'Could not add the entry in ' . self::$osHostsFile;
            }

            $return .= PHP_EOL . 'Restarting apache';

            // restart apache
            $output = shell_exec('apachectl -k restart');
            $return .= print_r($output,true);

            return $return;

        }else{
            return 'Could not enable site.' . PHP_EOL;
        }

    }

    public function writeVhostEntry( string $dir, string $host): bool {

    $vhostEntry = PHP_EOL . '<VirtualHost *:80>
    DocumentRoot "' . $dir . '"
    Servername ' . $host . '
</VirtualHost>' . PHP_EOL;

        return (bool) file_put_contents(self::$apacheVhostsFile, $vhostEntry, FILE_APPEND | LOCK_EX);

    }

    public function addToHostsFile( string $host): bool
    {

        $hostsFileEntry = PHP_EOL . '127.0.0.1	' . $host . PHP_EOL;
        return (bool) file_put_contents(self::$osHostsFile, $hostsFileEntry, FILE_APPEND | LOCK_EX);

    }

    public function disableSite(): string
    {

        $id = $this->captureUserKey();

        if(!isset(self::$sitesList[$id]))
            return 'ID of site does not exist';

        $dir = self::$sitesList[$id]['path'];
        $host = self::$sitesList[$id]['site'];
        $status = self::$sitesList[$id]['status'];

        if( $status == 'Disabled' ) {
            $return = PHP_EOL . 'Site ALREADY Disabled: http://' . $host;
            $return .= PHP_EOL . 'Path: ' . $dir;
            return $return;
        }

        $disableWebsite = $this->removeVhostEntry($dir, $host);

        if( $disableWebsite ) {

            $return = PHP_EOL . 'Site Removed from vhosts file: http://' . $host;
            $return .= PHP_EOL . 'Path: ' . $dir;

            if( $this->removeFromHostsFile($host) ) {
                $return .= PHP_EOL . 'Also removed entry from ' . HOSTS_FILE;
            }else{
                $return .= PHP_EOL . 'Could not remove the entry from ' . HOSTS_FILE;
            }

            $return .= PHP_EOL . 'Restarting apache';

            // restart apache
            $output = shell_exec('apachectl -k restart');
            $return .= print_r($output,true);

            return $return;

        }else{
            return 'Could not disable site.' . PHP_EOL;
        }


    }

    // remove VirtualHost entry from vhosts file
    public function removeVhostEntry( string $dir, string $host): bool
    {
        $vHostFileContent = file_get_contents(self::$apacheVhostsFile);

    $vhostEntry = PHP_EOL . '<VirtualHost *:80>
    DocumentRoot "' . $dir . '"
    Servername ' . $host . '
</VirtualHost>' . PHP_EOL;

    $vhostEntry = str_replace($vhostEntry,'', $vHostFileContent);

        return (bool) file_put_contents(self::$apacheVhostsFile, $vhostEntry);

    }

    // remove from /etc/hosts file
    public function removeFromHostsFile(string $host):bool {

        $hostsFileEntry = '127.0.0.1	' . $host . PHP_EOL;
        $hostsFileContent = file_get_contents(self::$osHostsFile);
        $hostsFileEntry = str_replace($hostsFileEntry, '', $hostsFileContent);

        return (bool) file_put_contents(self::$osHostsFile, $hostsFileEntry);

    }

}