<?php

/* VisualPHPUnit
 *
 * Copyright (c) 2011, Nick Sinopoli <nsinopoli@gmail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Nick Sinopoli nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Util/Log/JSON.php';

class VPU {

   /**
    *  The sandboxed exceptions.
    *
    *  @var string
    *  @access protected
    */
    protected $_exceptions = '';

   /**
    *  The list of files to be ignored when creating a stack trace.
    *
    *  @var array
    *  @access protected
    */
    protected $_ignore_trace = array(
        'vpu.php',
        'index.php'
    );

   /**
    *  Collects the statistics within a given date range and generates a graph.
    *
    *  @param string $type         The type of graph (`Suites` or `Tests`).
    *  @param string $time_frame   The time frame (`Daily`, `Weekly`, or `Monthly`).
    *  @param string $start_date   The starting date.
    *  @param string $end_date     The ending date.
    *  @param object $db           The database handler.
    *  @access public
    *  @return string
    */
    public function build_graph($type, $time_frame, $start_date, $end_date, $db) {
        switch ( $time_frame ) {
            case 'Daily':
                $interval = 86400;
                $sql_format = 'Y-m-d';
                $output = 'm/d';
                break;
            case 'Weekly':
                $interval = 604800;
                $sql_format = 'Y-m-d';
                $output = 'm/d';
                break;
            case 'Monthly':
                $interval = 2678400;
                $sql_format = 'Y-m-01';
                $output = 'M Y';
                break;
        }

        $current = $start = strtotime($start_date);
        $end = strtotime($end_date) + $interval;

        $table = ucfirst(rtrim($type, 's')) . 'Result';

        $categories = array();
        $plot_values = array(
            'failed'     => array(),
            'incomplete' => array(),
            'skipped'    => array(),
            'success'    => array()
        );

        while ( $current < $end ) {
            $categories[] = date($output, $current);
            $next = $current + $interval;
            $where = array(
                'run_date' => array(
                    'gte' => date($sql_format, $current),
                    'lt'  => date($sql_format, $next)
                )
            );

            $data = array(
                'failed'     => 0,
                'incomplete' => 0,
                'skipped'    => 0,
                'success'    => 0
            );

            $db->find(array_keys($data), $table, $where);
            $results = $db->fetch_all();
            $num_rows = count($results);

            if ( $num_rows > 0 ) {
                foreach ( $results as $result ) {
                    foreach ( $result as $key => $value ) {
                        $data[$key] += $value;
                    }
                }
            }

            foreach ( $data as $key => $val ) {
                if ( $num_rows > 0 ) {
                    $plot_values[$key][] = round($val / $num_rows, 2);
                } else {
                    $plot_values[$key][] = 0;
                }
            }

            $current = $next;
        }

        $plot_values = array_map(function($val) { return json_encode($val); }, $plot_values);
        $categories = json_encode($categories);

        ob_start();
        include 'ui/graph.json';
        $graph_content = ob_get_contents();
        ob_end_clean();
        return $graph_content;
    }

   /**
    *  Builds the suite statistics.
    *
    *  @param array $stats        The stat-related variables to be transformed.
    *  @access protected
    *  @return string
    */
    protected function _build_stats($statistics) {
        $results = array();
        foreach ( $statistics as $name => $stats ) {
            $results[$name] = $stats;
            foreach ( $stats as $key => $value ) {
                if ( $key == 'total' ) {
                    continue;
                }
                // Avoid divide by zero error
                if ( $stats['total'] ) {
                    $results[$name]['percent_' . $key] = $stats[$key] / $stats['total'] * 100;
                } else {
                    $results[$name]['percent_' . $key] = 0;
                }
            }
        }

        ob_start();
        include 'ui/stats.html';
        $stats_content = ob_get_contents();
        ob_end_clean();
        return $stats_content;
    }

   /**
    *  Builds a suite of tests.
    *
    *  @param array $suite        The suite-related variables to be displayed.
    *  @access protected
    *  @return string
    */
    protected function _build_suite($suite) {
        $suite['expand'] = ( $suite['status'] == 'failure' ) ? '-' : '+';
        $suite['display'] = ( $suite['status'] == 'failure' ) ? 'show' : 'hide';

        ob_start();
        include 'ui/suite.html';
        $suite_content = ob_get_contents();
        ob_end_clean();
        return $suite_content;
    }

   /**
    *  Builds a test.
    *
    *  @param array $test             The test-related variables to be displayed.
    *  @access protected
    *  @return string
    */
    protected function _build_test($test) {
        if ( $test['output'] && $test['status'] === 'failure' ) {
            $test['expand'] = '-';
            $test['display'] = 'show';
        }

        ob_start();
        include 'ui/test.html';
        $test_content = ob_get_contents();
        ob_end_clean();
        return $test_content;
    }

   /**
    *  Returns the class name without the namespace.
    *
    *  @param string $class           The class name.
    *  @access protected
    *  @return string
    */
    protected function _classname_only($class) {
        $name = explode('\\', $class);
        return array_pop($name);
    }

   /**
    *  Organizes the output from PHPUnit into a more manageable array
    *  of suites and statistics.
    *
    *  @param string $pu_output        The JSON output from PHPUnit.
    *  @access protected
    *  @return array
    */
    protected function _compile_suites($pu_output) {
        $results = $this->_parse_output($pu_output);

        $collection = array();
        $statistics = array(
            'suites' => array(
                'success'    => 0,
                'skipped'    => 0,
                'incomplete' => 0,
                'failure'    => 0,
                'total'      => 0
            )
        );
        $statistics['tests'] = $statistics['suites'];
        foreach ( $results as $result ) {
            if ( $result['event'] != 'test' ) {
                continue;
            }

            $suite_name = $this->_classname_only($result['suite']);

            if ( !isset($collection[$suite_name]) ) {
                $collection[$suite_name] = array(
                    'tests'  => array(),
                    'name'   => $suite_name,
                    'status' => 'success',
                    'time'   => 0
                );
            }
            $result = $this->_format_test_results($result);
            $collection[$suite_name]['tests'][] = $result;
            $collection[$suite_name]['status'] = $this->_get_suite_status($result['status'], $collection[$suite_name]['status']);
            $collection[$suite_name]['time'] += $result['time'];
            $statistics['tests'][$result['status']] += 1;
            $statistics['tests']['total'] += 1;
        }

        foreach ( $collection as $suite ) {
            $statistics['suites'][$suite['status']] += 1;
            $statistics['suites']['total'] += 1;
        }

        $final = array(
            'suites' => $collection,
            'stats'  => $statistics
        );

        return $final;
    }

   /**
    *  Creates an HTML snapshot of the test results.
    *
    *  @param string $data            The data to be written.
    *  @param string $dir             The directory in which to store the snapshot.
    *  @access public
    *  @return void
    */
    public function create_snapshot($data, $dir) {
        if ( $dir{strlen($dir) - 1} !== '/' ) {
            $dir .= '/';
        }
        $filename = $dir . date('Y-m-d_G-i') . '.html';
        $this->_write_file($filename, $data);
        // TODO: Add a try/catch for this
        chmod($filename, 0777);
    }

   /**
    *  Erases the contents of a file.
    *
    *  @param string $filename        The file to be emptied.
    *  @access protected
    *  @return void
    */
    protected function _empty_file($filename) {
        $this->_write_file($filename, '', 'w');
    }

   /**
    *  Normalizes the test results.
    *
    *  @see VPU->_parse_output()
    *  @see VPU->_compile_suites()
    *  @param array $test_results     The parsed test results.
    *  @access protected
    *  @return string
    */
    protected function _format_test_results($test_results) {
        $test_status = $this->_get_test_status($test_results['status'], $test_results['message']);
        $output = ( isset($test_results['output']) ) ? trim($test_results['output']) : '';
        $trace_message = $this->_get_trace($test_results['trace']);

        $test = array(
            'status'            => $test_status,
            'expand'            => ( $test_status == 'fail' ) ? '-' : '+',
            'display'           => ( $test_status == 'fail' ) ? 'show' : 'hide',
            'name'              => substr($test_results['test'], strpos($test_results['test'], '::') + 2),
            'message'           => $this->_get_message($test_results['message']) . 'Executed in ' . $test_results['time'] . ' seconds.',
            'time'              => $test_results['time'],
            'output'            => $output,
            'output_display'    => ( $output ) ? 'show' : 'hide',
            'trace_message'     => $trace_message,
            'trace_display'     => ( $trace_message ) ? 'show' : 'hide'
        );

        return $test;
    }

   /**
    *  Retrieves all of the formatted errors.
    *
    *  @access protected
    *  @return string
    */
    protected function _get_errors() {
        global $sandbox_filename;
        $errors = file_get_contents($sandbox_filename);
        $this->_empty_file($sandbox_filename);
        return $errors;
    }

   /**
    *  Retrieves any user-generated debugging messages from a PHPUnit test result.
    *
    *  @param string $message        The message supplied by VPU's transformed JSON.
    *  @access protected
    *  @return string
    */
    protected function _get_message($message) {
        if ( !$message ) {
            return '';
        }

        $first = substr($message, 0, strpos($message, 'Failed'));
        $message_rest = str_replace($first, '', $message);
        if ( $first && $message_rest ) {
            $message = '<strong>' . $first . '</strong><br />' . $message_rest;
        } elseif ( $message_rest ) {
            $message = '<strong>' . $message_rest . '</strong>';
        } else {
            $message = '<strong>' . $first . '</strong>';
        }

        $message .= '<br /><br />';

        return $message;
    }

   /**
    *  Determines the overall suite status based on the current status
    *  of the suite and the status of a single test.
    *
    *  @param string $test_status     The status of the test.
    *  @param string $suite_status    The current status of the suite.
    *  @access protected
    *  @return string
    */
    protected function _get_suite_status($test_status, $suite_status) {
        if ( $test_status === 'incomplete' && $suite_status !== 'failure' && $suite_status !== 'skipped' ) {
            return 'incomplete';
        } elseif ( $test_status === 'skipped' && $suite_status !== 'failure' ) {
            return 'skipped';
        } elseif ( $test_status === 'failure' ) {
            return 'failure';
        }
        return $suite_status;
    }

   /**
    *  Retrieves the status from a PHPUnit test result.
    *
    *  @param string $status        The status supplied by VPU's transformed JSON.
    *  @param string $message       The message supplied by VPU's transformed JSON.
    *  @access protected
    *  @return string
    */
    protected function _get_test_status($status, $message) {
        switch ( $status ) {
            case 'pass':
                return 'success';
            case 'error':
                if ( stripos($message, 'skipped') !== false ) {
                    return 'skipped';
                } elseif ( stripos($message, 'incomplete') !== false ) {
                    return 'incomplete';
                } else {
                    return 'failure';
                }
            case 'fail':
                return 'failure';
            default:
                return '';
        }
    }

   /**
    *  Retrieves the stack trace from a PHPUnit test result.
    *
    *  @param string $trace        The message supplied by VPU's transformed JSON.
    *  @access protected
    *  @return string
    */
    protected function _get_trace($trace) {
        if ( !$trace ) {
            return '';
        }

        $new_trace = array();
        foreach ( $trace as $arr ) {
            $found = false;
            foreach ( $this->_ignore_trace as $ignore ) {
                if ( stripos($arr['file'], $ignore) !== false ) {
                    $found = true;
                    break;
                }
            }

            if ( !$found ) {
                $new_trace[] = $arr;
            }
        }

        if ( !empty($new_trace) ) {
            ob_start();
            print_r($new_trace);
            $trace = trim(ob_get_contents());
            ob_end_clean();
        } else {
            $trace = '';
        }

        return $trace;
    }

   /**
    *  Serves as the error handler.  Formats the errors, and then writes them to the sandbox file.
    *
    *  @param int $err_no            The level of the error raised.
    *  @param string $err_str        The error message.
    *  @param string $err_file       The file in which the error was raised.
    *  @param int $err_line          The line number at which the error was raised.
    *  @access public
    *  @return bool
    */
    public function handle_errors($err_no, $err_str, $err_file, $err_line) {
        global $sandbox_ignore, $sandbox_filename;

        if ( $sandbox_ignore != '' ) {
            $ignore = explode('|', $sandbox_ignore);
            $transform_to_constant = function($value) { return constant($value); };
            $ignore = array_map($transform_to_constant, $ignore);
            if ( in_array($err_no, $ignore) ) {
                return true;
            }
        }

        $error = array();
        switch ( $err_no ) {
            case E_NOTICE:
                $error['type'] = 'Notice';
                break;
            case E_WARNING:
                $error['type'] = 'Warning';
                break;
            case E_ERROR:
                $error['type'] = 'Error';
                break;
            case E_PARSE:
                $error['type'] = 'Parse';
                break;
            case E_STRICT:
                $error['type'] = 'Strict';
                break;
            default:
                $error['type'] = 'Unknown';
                break;
        }
        $error['message'] = $err_str;
        $error['line'] = $err_line;
        $error['file'] = $err_file;
        ob_start();
        include 'ui/error.html';
        $this->_write_file($sandbox_filename, ob_get_contents());
        ob_end_clean();
        return true;
    }

   /**
    *  Formats exceptions for sandbox use.
    *
    *  @param Exception $exception            The thrown exception.
    *  @access protected
    *  @return void
    */
    protected function _handle_exception($exception) {
        $error = array(
            'type'    => 'Exception',
            'message' => $exception->getMessage(),
            'line'    => $exception->getLine(),
            'file'    => $exception->getFile()
        );

        ob_start();
        include 'ui/error.html';
        $this->_exceptions .= ob_get_contents();
        ob_end_clean();
    }

   /**
    *  Iterates through the supplied directory and loads the test files.
    *
    *  @param string $test_dir       The directory containing the tests.
    *  @access protected
    *  @return void
    */
    protected function _load_dir($test_dir) {
        try {
            $test_dir = realpath($test_dir);
            if ( !is_dir($test_dir) ) {
                throw new Exception($test_dir . 'is not a valid directory.');
            }

            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($test_dir), RecursiveIteratorIterator::LEAVES_ONLY);

            $test_cases = array();
            while ( $it->valid() ) {
                if ( !$it->isDot() && strtolower(pathinfo($it->key(), PATHINFO_EXTENSION)) == 'php' ) {
                    $test_cases[] = $it->key();
                }

                $it->next();
            }
            return $test_cases;
        } catch (Exception $e) {
            $this->_handle_exception($e);
            return false;
        }
    }

   /**
    *  Parses and formats the JSON output from PHPUnit into an associative array.
    *
    *  @param string $pu_output        The JSON output from PHPUnit.
    *  @access protected
    *  @return array
    */
    protected function _parse_output($pu_output) {
        $results = '';
        foreach ( $this->_pull($pu_output) as $elem ) {
            $elem = '{' . $elem . '}';
            $pu_output = $this->_replace($elem, '|||', $pu_output);
            $results .= $elem . ',';
        }

        $results = '[' . rtrim($results, ',') . ']';
        $results = str_replace('\n', '<br/>', $results);
        $results = str_replace('&quot;', '"', $results);

        $results = json_decode($results, true);

        // For PHPUnit 3.5.x, which doesn't include test output in the JSON
        $pu_output = explode('|||', $pu_output);
        foreach ( $pu_output as $key => $data ) {
            if ( $data ) {
                $results[$key]['output'] = $data;
            }
        }

        return $results;
    }

   /**
    *  Retrieves the files from any supplied directories, and filters
    *  the list of tests by ensuring that the files exist and are PHP files.
    *
    *  @param array $tests       The directories/filenames containing the tests to be run through PHPUnit.
    *  @access protected
    *  @return array
    */
    protected function _parse_tests($tests) {
        $collection = array();

        foreach ( $tests as $test )  {
            if ( is_dir($test) ) {
                $collection = array_merge($collection, $this->_load_dir($test));
            } elseif ( file_exists($test) && strtolower(pathinfo($test, PATHINFO_EXTENSION)) == 'php' )  {
                $collection[] = $test;
            }
        }
        // Avoid returning duplicates
        return array_keys(array_flip($collection));
    }

   /**
    *  Converts the first nested layer of PHPUnit-generated JSON to an associative array.
    *
    *  @param string $str        The JSON output from PHPUnit.
    *  @access protected
    *  @return array
    */
    protected function _pull($str) {
        try {
            $tags = array();
            $nest = 0;
            $start_mark = 0;

            $length = strlen($str);
            for ( $i=0; $i < $length; $i++ ) {
                $char = $str{$i};

                if ( $char == '{' ) {
                    // Ensure we're only adding events to the array
                    if ( $nest == 0 && substr($str, $i, 18) != '{&quot;event&quot;' ) {
                        continue;
                    }

                    $nest++;
                    if ( $nest == 1 ) {
                        $start_mark = $i;
                    }
                } elseif ( $char == '}' && $nest > 0 ) {
                    if ( $nest == 1 ) {
                        $tags[] = substr($str, $start_mark + 1, $i - $start_mark - 1);
                        $start_mark = $i;
                    }
                    $nest--;
                }
            }

            if ( $nest !== 0 ) {
                throw new Exception('Unable to parse JSON response from PHPUnit.');
            }

            return $tags;
        } catch (Exception $e) {
            $this->_handle_exception($e);
            return false;
        }

    }

   /**
    *  Replaces text within a string.
    *
    *  @param string $old            The substring to be replaced.
    *  @param string $new            The replacment string.
    *  @param string $subject        The string whose contents will be replaced.
    *  @access protected
    *  @return string
    */
    protected function _replace($old, $new, $subject) {
        try {
            $pos = strpos($subject, $old);

            if ( $pos === false ) {
                throw new Exception('Cannot find tag to replace (old: ' . $old . ', new: ' . htmlspecialchars($new) . ').');
            }

            return substr_replace($subject, $new, $pos, strlen($old));
        } catch (Exception $e) {
            $this->_handle_exception($e);
            return false;
        }
    }

   /**
    *  Runs supplied tests through PHPUnit.
    *
    *  @param array $tests        The directories/filenames containing the tests to be run through PHPUnit.
    *  @access public
    *  @return string
    */
    public function run($tests) {
        $suite = new PHPUnit_Framework_TestSuite();

        $tests = $this->_parse_tests($tests);
        $original_classes = get_declared_classes();
        foreach ( $tests as $test ) {
            require $test;
        }
        $new_classes = get_declared_classes();
        $tests = array_diff($new_classes, $original_classes);
        foreach ( $tests as $test ) {
            if ( $this->_classname_only($test) == 'PHPUnit_Framework_TestCase' ) {
                continue;
            }
            if ( stripos($this->_classname_only($test), 'test') !== false ) {
                $suite->addTestSuite($test);
            }
        }

        $result = new PHPUnit_Framework_TestResult;
        $result->addListener(new PHPUnit_Util_Log_JSON);

        // We need to temporarily turn off html_errors to ensure correct parsing of test debug output
        $html_errors = ini_get('html_errors');

        ob_start();
        ini_set('html_errors', 0);
        $suite->run($result);
        $results = ob_get_contents();
        ini_set('html_errors', $html_errors);
        ob_end_clean();

        return $results;
    }

   /**
    *  Saves the statistics to a database.
    *
    *  @see VPU->_compile_suites()
    *  @param string $results     The JSON output from PHPUnit.
    *  @param object $db          The database handler.
    *  @access public
    *  @return bool
    */
    public function save_results($results, $db) {
        $results = $this->_compile_suites($results);
        $now = date('Y-m-d H:i:s');
        foreach ( $results['stats'] as $key => $result ) {
            $data = array(
                'run_date'   => $now,
                'failed'     => $result['failure'],
                'incomplete' => $result['incomplete'],
                'skipped'    => $result['skipped'],
                'success'    => $result['success']
            );
            $table = ucfirst(rtrim($key, 's')) . 'Result';
            $db->insert($table, $data);
        }
        return true;
    }

   /**
    *  Outputs suite and statistics data in HTML.
    *
    *  @see VPU->_compile_suites()
    *  @param string $results         The JSON output from PHPUnit.
    *  @param bool $sandbox_errors    Whether or not to sandbox errors.
    *  @access public
    *  @return string
    */
    public function to_HTML($results, $sandbox_errors) {
        $collection = $this->_compile_suites($results);
        $final = '';
        foreach ( $collection['suites'] as $suite ) {
            $suite['test_results'] = '';
            foreach ( $suite['tests'] as $key => $test ) {
                $test['separator_display'] = ( isset($suite['tests'][$key + 1]) );
                $suite['test_results'] .= $this->_build_test($test);
            }
            $final .= $this->_build_suite($suite);
        }
        $final .= $this->_build_stats($collection['stats']);

        if ( $sandbox_errors ) {
            $final .= $this->_exceptions . $this->_get_errors();
        }

        return $final;
    }

   /**
    *  Writes data to a file.
    *
    *  @param string $filename        The name of the file.
    *  @param string $data            The data to be written.
    *  @param string $mode            The type of access to be granted to the file handle.
    *  @access protected
    *  @return string
    */
    protected function _write_file($filename, $data, $mode='a') {
        try {
            $handle = @fopen($filename, $mode);
            if ( !$handle ) {
                throw new Exception('Could not open ' . $filename . ' for writing.  Check the location and permissions of the file and try again.');
            }

            fwrite($handle, $data);
            fclose($handle);
            return true;
        } catch (Exception $e) {
            $this->_handle_exception($e);
            return false;
        }
    }

}

?>
