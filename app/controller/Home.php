<?php

namespace app\controller;

class Home extends \app\core\Controller {

    protected function _create_snapshot($view_data) {
        $filename = realpath(
            \app\lib\Library::retrieve('snapshot_directory')
        ) . '/' . date('Y-m-d_H-i') . '.html';

        $contents = $this->render_html('partial/test_results', $view_data);

        $handle = @fopen($filename, 'a');
        if ( !$handle ) {
            return array(
                'type'    => 'failed',
                'title'   => 'Error Creating Snapshot',
                'message' => 'Please ensure that the '
                    . '<code>snapshot_directory</code> in '
                    . '<code>app/config/bootstrap.php</code> exists and '
                    . 'has the proper permissions.'
            );
        }

        fwrite($handle, $contents);
        fclose($handle);
        return array(
            'type'    => 'succeeded',
            'title'   => 'Snapshot Created',
            'message' => "Snapshot can be found at <code>{$filename}</code>."
        );
    }

    // GET
    public function help($request) {
        return array();
    }

    // GET/POST
    public function index($request) {
        if ( $request->is('get') ) {
            $test_directory = str_replace(
                '\\', '/', realpath(\app\lib\Library::retrieve('test_directory'))
            );
            $suites = array();
            $stats = array();
            $store_statistics = \app\lib\Library::retrieve('store_statistics');
            $optional_bootstraps = \app\lib\Library::retrieve('optional_bootstraps');
            $optional_parameters = \app\lib\Library::retrieve('optional_parameters');
            $create_snapshots = \app\lib\Library::retrieve('create_snapshots');
            $sandbox_errors = \app\lib\Library::retrieve('sandbox_errors');
            $use_xml = \app\lib\Library::retrieve('xml_configuration_file');
            $post_handler = \app\lib\Library::retrieve('alternate_post_handler');
            if (!$post_handler) {
              $post_handler = './';
            }
            return compact(
                'create_snapshots',
                'sandbox_errors',
                'stats',
                'store_statistics',
                'optional_bootstraps',
                'optional_parameters',
                'suites',
                'test_directory',
                'use_xml',
                'post_handler'
            );
        }

        $tests = explode('|', $request->data['test_files']);
        $vpu = new \app\lib\VPU();

        if ( $request->data['sandbox_errors'] ) {
            error_reporting(\app\lib\Library::retrieve('error_reporting'));
            set_error_handler(array($vpu, 'handle_errors'));
        }

        $xml_config = false;

        $notifications = array();
        if ( $request->data['use_xml'] ) {
            $xml_config = \app\lib\Library::retrieve('xml_configuration_file');
            if ( !$xml_config || !$xml_config = realpath($xml_config) ) {
                $notifications[] = array(
                    'type'    => 'failed',
                    'title'   => 'No Valid XML Configuration File Found',
                    'message' => 'Please ensure that the '
                    . '<code>xml_configuration_file</code> in '
                    . '<code>app/config/bootstrap.php</code> exists and '
                    . 'has the proper permissions.'
                );
            }
        }
        if ( isset( $request->data['optional_bootstraps'] ) ) {
          $this->_run_optional_bootstraps($request->data['optional_bootstraps']) ;
        }
        if ( isset( $request->data['optional_parameters'] ) ) {
          $this->_set_optional_parameters(
            $request->data['optional_parameters'],
            $request->data['optional_parameters_value']
          );
        }

        $results = ( $xml_config )
            ? $vpu->run_with_xml($xml_config)
            : $vpu->run_tests($tests);
        $results = $vpu->compile_suites($results, 'web');

        if ( $request->data['sandbox_errors'] ) {
            restore_error_handler();
        }

        $suites = $results['suites'];
        $stats = $results['stats'];
        $errors = $vpu->get_errors();
        $to_view = compact('suites', 'stats', 'errors');

        if ( $request->data['create_snapshots'] ) {
            $notifications[] = $this->_create_snapshot($to_view);
        }
        if ( $request->data['store_statistics'] ) {
            $notifications[] = $this->_store_statistics($stats);
        }

        return $to_view + compact('notifications');
    }

    protected function _store_statistics($stats) {
        $db_options = \app\lib\Library::retrieve('db');
        $db = new $db_options['plugin']();
        if ( !$db->connect($db_options) ) {
            return array(
                'type'    => 'failed',
                'title'   => 'Error Connecting to Database',
                'message' => implode(' ', $db->get_errors())
            );
        }

        $now = date('Y-m-d H:i:s');
        foreach ( $stats as $key => $stat ) {
            $data = array(
                'run_date'   => $now,
                'failed'     => $stat['failed'],
                'incomplete' => $stat['incomplete'],
                'skipped'    => $stat['skipped'],
                'succeeded'  => $stat['succeeded']
            );
            $table = ucfirst(rtrim($key, 's')) . 'Result';
            if ( !$db->insert($table, $data) ) {
                return array(
                    'type'    => 'failed',
                    'title'   => 'Error Inserting Record',
                    'message' => implode(' ', $db->get_errors())
                );
            }
        }

        return array(
            'type'    => 'succeeded',
            'title'   => 'Statistics Stored',
            'message' => 'The statistics generated during this test run were '
                . 'successfully stored.'
        );

    }

    function _run_optional_bootstraps($bootstraps) {
      foreach($bootstraps as $boot => $value) {
        $this->_require_optional_bootstrap($boot);
      }
    }

    function _require_optional_bootstrap($bootstrap) {
      $allowed_bootstraps = \app\lib\Library::retrieve('optional_bootstraps');
      if (in_array($bootstrap, $allowed_bootstraps)) {
        require($allowed_bootstraps[$bootstrap]['file']);
      }
    }

    function _set_optional_parameters($params, $params_values) {
      $optional_parameters = \app\lib\Library::retrieve('optional_parameters');

      global $optional_parameters_for_test;
      foreach($params as $index => $checked_or_not) {
        if (isset( $optional_parameters[$index]) ) {
          $param_name = $optional_parameters[$index]['name'];
          $optional_parameters_for_test[$param_name] = $params_values[$index];
        }
      }
    }

}

?>
