<?php
/**
 * VisualPHPUnit
 *
 * VisualPHPUnit is a visual front-end for PHPUnit.
 *
 * PHP Version 5.3<
 *
 * @author    Johannes Skov Frandsen <localgod@heaven.dk>
 * @copyright 2011-2015 VisualPHPUnit
 * @license   http://opensource.org/licenses/BSD-3-Clause The BSD License
 * @link      https://github.com/VisualPHPUnit/VisualPHPUnit VisualPHPUnit
 */
namespace app\config;

use \app\lib\Library;

/**
 * Visualphpunit config class
 *
 * @author Johannes Skov Frandsen <localgod@heaven.dk>
 */
class Config
{

    /**
     * Get configuration options
     *
     * @return boolean[]|string[]|string[][]
     */
    public static function getConfig()
    {
        $config = array();
        
        // REQUIRED
        // The directories where the tests reside
        $config['test_directories'] = array(
            'Sample Tests' => realpath(__DIR__ . '/../test/')
        );
        
        // OPTIONAL
        
        // The database configuration
        $config['db'] = array(
            'plugin' => '\app\lib\PDOMySQL', // MySQL is currently the only database supported
            'database' => 'vpu',
            'host' => 'localhost',
            'port' => '3306',
            'username' => 'root',
            'password' => 'admin'
        );
        
        // Whether or not to store the statistics in a database (these statistics will be used to generate graphs)
        $config['store_statistics'] = false;
        
        // Whether or not to create snapshots of the test results
        $config['create_snapshots'] = false;
        
        // The directory where the test results will be stored
        $config['snapshot_directory'] = realpath(__DIR__ . "/../../snapshots/");
        
        // Whether or not to sandbox PHP errors
        $config['sandbox_errors'] = false;
        
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
        $config['error_reporting'] = E_ALL | E_STRICT;
        
        // Whether or not to ignore hidden folders
        // (i.e., folders with a '.' prefix)
        $config['ignore_hidden_folders'] = true;
        
        // The PHPUnit XML configuration files to use
        // (leave empty to disable)
        //
        // In order for VPU to function correctly, the configuration files must
        // contain a JSON listener (see the README for more information)
        $config['xml_configuration_files'] = array(
            realpath(__DIR__ . "/phpunit.xml")
        );
        
        // Paths to any necessary bootstraps
        $config['bootstraps'] = array();
        
        Library::store($config);
        return $config;
    }
}
