<?php
/**
  * Installer front controller
  *
  * @package Panthera\core
  * @author Damian Kęska
  * @license GNU Affero General Public License 3, see license.txt
  */
  
session_start();
  
// load app.php and extract $config variable
$app = file_get_contents('content/app.php');
$configExported = substr($app, strpos($app, '$config'), strpos($app, ');')-4);
@eval($configExported);
$newAppFile = False;

if (!is_array($config))
{
    $newAppFile = True;
    $config = array();
}
   
if ($config['preconfigured'] !== True)
{
    // pre-configure installer environment
    $config['build_missing_tables'] = True;
    $config['db_socket'] = 'sqlite';
    $config['db_file'] = 'db.sqlite3';
    $config['SITE_DIR'] = dirname($_SERVER['SCRIPT_FILENAME']);
    
    if (!isset($config['upload_dir']))
        $config['upload_dir'] = 'content/uploads';
        
    if (!isset($config['db_prefix']))
        $config['db_prefix'] = 'pa_';
        
    $config['requires_instalation'] = True;
    
    if (!isset($config['timezone']))
        $config['timezone'] = 'UTC';

    // if lib directory is not provided try to get it manually
    if (!is_dir($config['lib']))
    {
        // if installer front controller is a symlink we can find Panthera library directory in very easy way
        if (is_link($_SERVER['SCRIPT_FILENAME']))
        {
            $config['lib'] = dirname(str_ireplace('/frontpages', '', readlink($_SERVER['SCRIPT_FILENAME']))). '/';
        }
        
        // search in parent directory
        if (is_file('../lib/panthera.php'))
        {
            $config['lib'] = realpath('../lib'). '/';
        }
    }

    $config['preconfigured'] = True;

    // save changes to file
    if ($newAppFile == True)
        $app = "<?php\n\$config = ".var_export($config, True).";\n\nrequire \$config['lib']. '/boot.php';"; // creating new configuration
    else
        $app = str_replace($configExported, '$config = ' .var_export($config, True). ';', $app); // updating existing

    $fp = @fopen('content/app.php', 'w');
    
    if (!$fp)
    {
        die('Cannot write to content/app.php, please check permissions');
    }
    
    fwrite($fp, $app);
    fclose($fp);
}

define('PANTHERA_FORCE_DEBUGGING', True);

// app starts here
require $config['lib']. '/boot.php';

if (!$panthera->config->getKey('url'))
{
    $protocol = 'http';

    if ($_SERVER['HTTPS'])
        $protocol = 'https';

    $panthera -> config -> setKey('url', $protocol. '://' .$_SERVER['HTTP_HOST'].str_replace(basename($_SERVER['REQUEST_URI']), '', $_SERVER['REQUEST_URI']));

}
// include step
$step = addslashes($_GET['step']);

if ($step == '')
    $step = 'index';

if (!$panthera -> moduleExists('installer/' .$step))
{
    $step = 'error_no_step';
}    

// initialize installer
$panthera -> importModule('pantherainstaller');
$installer = new pantheraInstaller($panthera);

// template options
$panthera -> template -> setTemplate('installer');
$panthera -> template -> setTitle('Panthera Installer');

// include step
define('PANTHERA_INSTALLER', True);
$panthera -> importModule('installer/' .$step);

$installer -> display();
