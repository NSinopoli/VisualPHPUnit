<?php

$root = dirname(dirname(__DIR__));

$config = array(
    /* Required */

    // The directory where PEAR is located
    'pear_path'      => '/usr/share/pear',

    // The directory where the tests reside
    'test_directory' => "{$root}/app/test",


    /* Optional */

    // Whether or not to store the statistics in a database
    // (these statistics will be used to generate graphs)
    'store_statistics' => false,

    // The database configuration
    'db' => array(
        // MySQL is currently the only database supported
        // (do not change this)
        'plugin'   => '\app\lib\PDO_MySQL',

        'database' => 'vpu',
        'host'     => 'localhost',
        'port'     => '3306',
        'username' => 'root',
        'password' => 'admin'
    ),

    // Whether or not to create snapshots of the test results
    'create_snapshots' => false,

    // The directory where the test results will be stored
    'snapshot_directory' => "{$root}/app/history/",

    // Whether or not to sandbox PHP errors
    'sandbox_errors' => false,

    // Which errors to sandbox
    //
    // (note that E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING,
    // E_COMPILE_ERROR, E_COMPILE_WARNING, and most of E_STRICT cannot
    // be sandboxed)
    //
    // see the following for more information:
    // http://us3.php.net/manual/en/errorfunc.constants.php
    // http://us3.php.net/manual/en/function.error-reporting.php
    // http://us3.php.net/set_error_handler
    'error_reporting' => E_ALL | E_STRICT,

    // Whether or not to ignore hidden folders
    // (i.e., folders with a '.' prefix)
    'ignore_hidden_folders' => true,

    // The PHPUnit XML configuration file to use
    // (set to false to disable)
    //
    // In order for VPU to function correctly, the configuration file must
    // contain a JSON listener (see the README for more information)
    'xml_configuration_file' => false,
    //'xml_configuration_file' => "{$root}/app/config/phpunit.xml",
    
    'usable_group_names' => array(
		'DefaultTestGroup'
	),    

    // Paths to any necessary bootstraps
    'bootstraps' => array(
        // '/path/to/bootstrap.php'
    )
);

set_include_path(
    get_include_path()
    . PATH_SEPARATOR . $root
    . PATH_SEPARATOR . $config['pear_path']
);

require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Util/Log/JSON.php';

spl_autoload_register(function($class) use ($root) {
    $class = str_replace('\\', '/', $class);
    $file = "{$root}/{$class}.php";
    if ( file_exists($file) ) {
        require $file;
    }
});

foreach ( $config['bootstraps'] as $bootstrap ) {
    require $bootstrap;
}

\app\lib\Library::store($config);

?>
