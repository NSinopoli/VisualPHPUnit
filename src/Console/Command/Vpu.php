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
namespace Visualphpunit\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\ConsoleOutput;
use \app\config\Config;

/**
 * Visualphpunit consol command
 *
 * @author Johannes Skov Frandsen <localgod@heaven.dk>
 */
class Vpu extends Command
{

    /**
     * Configurate the command
     *
     * @return void
     */
    protected function configure()
    {
        Config::getConfig();
        $this->setName('vpu')
            ->addArgument('config-file', InputArgument::OPTIONAL, 'Path to phpunit xml configuration file', \app\lib\Library::retrieve('xml_configuration_files')[0])
            ->addOption('snapshot', 'a', InputOption::VALUE_NONE, 'Store snapshots')
            ->addOption('snapshot_directory', 'd', InputOption::VALUE_OPTIONAL, 'Path to store snapshots', \app\lib\Library::retrieve('snapshot_directory'))
            ->addOption('sandbox_errors', 'e', InputOption::VALUE_NONE, 'Sandbox PHP errors')
            ->addOption('store_statistics', 's', InputOption::VALUE_NONE, 'Store statistics in a database');
    }

    /**
     * Execute the command
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $input->getArgument('config-file');
        $snapshot = $input->getOption('snapshot') ? true : \app\lib\Library::retrieve('create_snapshots') ? true : false;
        $sandboxErrors = $input->getOption('sandbox_errors') ? true : \app\lib\Library::retrieve('sandbox_errors') ? true : false;
        $snapshotDir = $input->getOption('snapshot_directory') ? $input->getOption('snapshot_directory') : \app\lib\Library::retrieve('snapshot_directory') ? \app\lib\Library::retrieve('snapshot_directory') : '';
        $store = $input->getOption('store_statistics') ? true : \app\lib\Library::retrieve('store_statistics') ? true : false;
        
        $output->setFormatter(new OutputFormatter(true));
        if ($output->isVerbose()) {
            if ($snapshot) {
                if (file_exists($snapshotDir)) {
                    $output->writeln('Writing snapshots  [<comment> true </comment>]');
                } else {
                    $output->writeln('<error>Snapshots path:' . $snapshotDir . ' dos not exits</error>');
                    exit();
                }
            } else {
                $output->writeln('Writing snapshots  [<comment> false </comment>]');
            }
            
            $output->writeln('Sandbox php errors [<comment> ' . ($snapshot ? 'true' : 'false') . ' </comment>]');
            $output->writeln('Store statistics   [<comment> ' . ($store ? 'true' : 'false') . ' </comment>]');
        }
        if (file_exists($config)) {
            $output->writeln('');
            $output->writeln('Parsing ' . $input->getArgument('config-file'));
            $this->runLegacyVpu($output, $config, $snapshot, $snapshotDir, $sandboxErrors, $store);
        } else {
            $output->writeln('<error>Config file:' . $input->getArgument('config-file') . ' dos not exits</error>');
            exit();
        }
    }

    /**
     * Execute legacy vpu command
     *
     * @param ConsoleOutput $output            
     * @param string $config            
     * @param boolean $enableSnapshot            
     * @param string $snapshotPath            
     * @param boolean $enableSandbox            
     * @param boolean $enableStats            
     *
     * @return void
     */
    private function runLegacyVpu(ConsoleOutput $output, $config, $enableSnapshot, $snapshotPath, $enableSandbox, $enableStats)
    {
        $vpu = new \app\lib\VPU();
        
        if ($enableSandbox) {
            error_reporting(\app\lib\Library::retrieve('error_reporting'));
            set_error_handler(array(
                $vpu,
                'handleErrors'
            ));
        }
        $results = $vpu->runWithXml($config);
        $results = $vpu->compileSuites($results, 'cli');
        
        $enableSandbox ? restore_error_handler() : null;
        $enableSnapshot ? $this->snapshot($output, $results, $vpu, $snapshotPath) : null;
        $enableStats ? $this->stats($output, $results) : null;
    }

    /**
     * Create snapshot
     *
     * @param ConsoleOutput $output            
     * @param array $results            
     * @param \app\lib\VPU $vpu            
     * @param string $snapshotPath            
     *
     * @return void
     */
    private function snapshot(ConsoleOutput $output, array $results, \app\lib\VPU $vpu, $snapshotPath)
    {
        $suites = $results['suites'];
        $stats = $results['stats'];
        $errors = $vpu->getErrors();
        $to_view = compact('suites', 'stats', 'errors');
        
        $filename = realpath($snapshotPath) . '/' . date('Y-m-d_H-i') . '.html';
        
        $handle = fopen($filename, 'a');
        
        $view = new \app\core\View();
        $contents = $view->render('partial/test_results', $to_view);
        
        fwrite($handle, $contents);
        fclose($handle);
        
        $output->writeln("Snapshot successfully created at {$filename}");
    }

    /**
     * Store stats
     *
     * @param ConsoleOutput $output            
     * @param array $results            
     *
     * @return void
     */
    private function stats(ConsoleOutput $output, array $results)
    {
        Config::getConfig();
        $db_options = \app\lib\Library::retrieve('db');
        $db = new $db_options['plugin']();
        if (! $db->connect($db_options)) {
            $output->writeln("<error>There was an error connecting to the database:</error>");
            $output->writeln("<error>" . implode(' ', $db->getErrors()) . "</error>");
            exit();
        }
        
        $now = date('Y-m-d H:i:s');
        foreach ($stats as $key => $stat) {
            $data = array(
                'run_date' => $now,
                'failed' => $stat['failed'],
                'incomplete' => $stat['incomplete'],
                'skipped' => $stat['skipped'],
                'succeeded' => $stat['succeeded']
            );
            $table = ucfirst(rtrim($key, 's')) . 'Result';
            if (! $db->insert($table, $data)) {
                $output->writeln("<error>There was an error inserting a record into the database::</error>");
                $output->writeln("<error>" . implode(' ', $db->getErrors()) . "</error>");
                exit();
            }
        }
        
        $output->writeln("The statistics generated during this test run were successfully stored.");
    }
}