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
namespace Visualphpunit\Api\Action;

use \ReflectionClass;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;

/**
 * Visualphpunit list tests action
 *
 * @author Johannes Skov Frandsen <localgod@heaven.dk>
 */
class Test extends Action
{

    /**
     * Retrive tests
     *
     * Retrive tests from test folder
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Silex\Application $app
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request, Application $app)
    {
        $data = array();
        $app['config'] += [
            'test-directories' => [],
            'test-configurations' => [],
        ];
        foreach ($app['config']['test-directories'] as $suite) {
            if (! isset($suite['testCaseRegxpPattern']) || $suite['testCaseRegxpPattern'] == '') {
                $suite['testCaseRegxpPattern'] = 'extends.+PHPUnit_Framework_TestCase$';
            }
            $data[] = array(
                'text' => $suite['name'],
                'type' => 'suite',
                'nodes' => $this->parse($suite['path'], $suite['ignoreHidden'], $suite['testCaseRegxpPattern']),
                'selectable' => false
            );
        }
        foreach ($app['config']['test-configurations'] as $test_configuration) {
            $data = array_merge($data, $this->parseTestConfiguration($test_configuration));
        }
        return $this->ok($data);
    }

    /**
     * Parse the dir for files
     *
     * @param string $dir
     * @param boolean $ignoreHidden
     * @param string $pattern
     *
     * @return mixed[]
     */
    private function parse($dir, $ignoreHidden, $pattern)
    {
        $bootstrap = Finder::create()->ignoreDotFiles($ignoreHidden)
            ->depth(0)
            ->name('/bootstrap.php/')
            ->in($dir);
        $files = Finder::create()->ignoreDotFiles($ignoreHidden)
            ->sortByType()
            ->depth(0)
            ->name('*.php')
            ->notName('/bootstrap.php/')
            ->in($dir);
        $directories = Finder::create()->ignoreDotFiles($ignoreHidden)
            ->sortByType()
            ->depth(0)
            ->directories()
            ->append($files)
            ->in($dir);

        if (! empty($bootstrap)) {
            foreach ($bootstrap as $file) {
                require_once $file->getRealPath();
            }
        }

        $list = array();

        foreach ($directories as $file) {
            if ($file->getType() == 'dir') {
                $list[] = array(
                    'text' => $file->getBasename('.php'),
                    'type' => $file->getType(),
                    'path' => $file->getRealPath(),
                    'nodes' => $this->parse($file->getRealPath(), $ignoreHidden, $pattern),
                    'selectable' => false
                );
            } else {
                if (! empty(preg_grep('/'.$pattern.'/', file($file)))) {
                    $list[] = array(
                        'text' => $file->getBasename('.php'),
                        'type' => $file->getType(),
                        'path' => $file->getRealPath(),
                        'selectable' => true,
                        'tags' => $this->getNumberOfMethods($file->getRealPath())
                    );
                }
            }
        }

        return $this->excludeEmptyDirectories($list);
    }

    /**
     * Exclude empty Directories
     *
     * @param mixed[] $list
     * @return mixed[]
     */
    private function excludeEmptyDirectories($list)
    {
        foreach ($list as $key => $value) {
            if ($value['type'] == 'dir') {
                if (count($value['nodes']) != 0) {
                    $this->excludeEmptyDirectories($value['nodes']);
                } else {
                    unset($list[$key]);
                }
            }
        }

        return $list;
    }

    /**
     * Get number of methods in test class
     *
     * @todo likely there are better ways of doing this
     * @param string $path
     *
     * @return integer[]
     */
    private function getNumberOfMethods($path)
    {
        $result1 = preg_grep('/^namespace/', file($path));
        $result2 = preg_grep('/^class/', file($path));
        $matches1 = [];
        $matches2 = [];

        preg_match('/^class\s([A-Za-z0-9]+).+$/', array_pop($result2), $matches2);
        if (count($result1) > 0) {
            preg_match('/^namespace\s(.+);$/', array_pop($result1), $matches1);
            $namespace = $matches1[1];
            $result2 = preg_grep('/^class/', file($path));
            preg_match('/^class\s([A-Za-z0-9]+).+$/', array_pop($result2), $matches2);
            $class = $matches2[1];
            require_once $path;
            $obj = new ReflectionClass($namespace . '\\' . $class);
            $methods = [];
            foreach ($obj->getMethods() as $method) {
                if ($method->class == $namespace . '\\' . $class && $method->isPublic()) {
                    $methods[] = $method->name;
                }
            }
            return [
                count($methods)
            ];
        }
        $result2 = preg_grep('/^class/', file($path));
        if (count($result2) > 0) {
            $class = $matches2[1];

            require_once $path;
            $obj = new ReflectionClass($class);
            $methods = [];
            foreach ($obj->getMethods() as $method) {
                if ($method->class == $class && $method->isPublic()) {
                    $methods[] = $method->name;
                }
            }
            return [
                count($methods)
            ];
        } else {
            return [
                0
            ];
        }
    }

    private function parseTestConfiguration($test_configuration)
    {
        $configuration = \PHPUnit_Util_Configuration::getInstance($test_configuration);
        $phpunitConfiguration = $configuration->getPHPUnitConfiguration();

        if (isset($phpunitConfiguration['bootstrap'])) {
            \PHPUnit_Util_Fileloader::checkAndLoad($phpunitConfiguration['bootstrap']);
        }

        $test_suite = $configuration->getTestSuiteConfiguration();
        $list = $this->parseTestSuite($test_suite);

        return $list;
    }

    private function parseTestSuite(\PHPUnit_Framework_TestSuite $test_suite)
    {
        $is_test = function () {
            return $this->testCase;
        };
        \Closure::bind($is_test, $test_suite);
        $list = [];
        if (class_exists($test_suite->getName())) {
            $reflection_class = new \ReflectionClass($test_suite->getName());
            $list[] = [
              'text' => $test_suite->getName(),
              'type' => 'file',
              'path' => $reflection_class->getFileName(),
              'selectable' => true
            ];
        } else {
            $nodes = [];
            foreach ($test_suite->tests() as $test) {
                $nodes = array_merge($nodes, $this->parseTestSuite($test));
            }
            $list[] = array(
                'text' => $test_suite->getName(),
                'type' => 'file',
                'path' => '',
                'nodes' => $nodes,
                'selectable' => false
            );
        }
        return $list;
    }
}
