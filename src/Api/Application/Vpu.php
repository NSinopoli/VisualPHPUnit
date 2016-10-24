<?php
/**
 * VisualPHPUnit
 *
 * VisualPHPUnit is a visual front-end for PHPUnit.
 *
 * PHP Version 5.6<
 *
 * @author Johannes Skov Frandsen <localgod@heaven.dk>
 * @copyright 2011-2016 VisualPHPUnit
 * @license http://opensource.org/licenses/BSD-3-Clause The BSD License
 * @link https://github.com/VisualPHPUnit/VisualPHPUnit VisualPHPUnit
 */
namespace Visualphpunit\Api\Application;

use Silex\Application;
use Visualphpunit\Api\Controller\Vpu as VpuController;
use Visualphpunit\Provider\ConfigServiceProvider;
use JDesrosiers\Silex\Provider\CorsServiceProvider;
use Silex\Provider\DoctrineServiceProvider;

/**
 * Visualphpunit Rest Api application
 *
 * @author Johannes Skov Frandsen <localgod@heaven.dk>
 */
class Vpu extends Application
{

    /**
     * Bootstrap the application
     */
    public function __construct()
    {
        parent::__construct();
        $app = $this;
        $app['debug'] = true;
        
        $appRoot = realpath(__DIR__ . '/../../..');
        $app->register(new ConfigServiceProvider("../vpu.json"));
        
        $app->register(new DoctrineServiceProvider(), array(
            'db.options' => array(
                'driver' => $app['config']['database']['driver'],
                'path' => $appRoot . '/vpu.db',
            )
        ));
        
        $app->register(new CorsServiceProvider(), array(
            "cors.allowOrigin" => "*"
        ));
        
        $app->after($app["cors"]);
        $app->mount('/', new VpuController());
    }
}
