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

    require 'config.php';

    // Helper functions
    function get_snapshots() {
        $results = array();
        $handler = opendir(SNAPSHOT_DIRECTORY);
        while ( $file = readdir($handler) ) {
            if ( strpos($file, '.') !== 0 && strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'html' ) {
                $results[] = $file;
            }
        }
        closedir($handler);
        rsort($results);

        return $results;
    }

    // AJAX calls
    if ( isset($_GET['dir']) ) {
        if ( !file_exists($_GET['dir']) ) {
            if ( $_GET['type'] == 'dir' ) {
                echo 'Directory does not exist!';
            } else {
                echo 'File does not exist!';
            }
        } elseif ( !is_writable($_GET['dir']) ) {
            if ( $_GET['type'] == 'dir' ) {
                echo 'Directory is not writable! (Check permissions.)';
            } else {
                echo 'File is not writable! (Check permissions.)';
            }
        } else {
            echo 'OK';
        }
        exit;
    } elseif ( isset($_GET['snapshots']) && $_GET['snapshots'] == '1' ) {
        $results = get_snapshots();
        echo json_encode($results);
        exit;
    }

    if ( empty($_POST) ) {
        $results = get_snapshots();

        include 'ui/index.html';
        exit; 
    }

    // Archives
    if ( isset($_POST['view_snapshot']) && $_POST['view_snapshot'] == 1 ) {
        $dir = realpath(SNAPSHOT_DIRECTORY) . '/';
        $snapshot = realpath($dir . trim(strval(filter_var($_POST['select_snapshot'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES))));

        ob_start(); 
        include $snapshot;
        $content = ob_get_contents(); 
        ob_end_clean();
        echo $content;
        exit;
    }

    require 'lib/VPU.php';
    $vpu = new VPU();

    // Graphs
    if ( isset($_POST['graph_type']) ) {
        $graph_type = trim(strval(filter_var($_POST['graph_type'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
        $time_frame = trim(strval(filter_var($_POST['time_frame'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
        $start_date = trim(strval(filter_var($_POST['start_date'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
        $end_date = trim(strval(filter_var($_POST['end_date'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
        
        require 'lib/PDO_MySQL.php';
        $config = array(
            'database' => DATABASE_NAME,
            'host'     => DATABASE_HOST, 
            'username' => DATABASE_USER,
            'password' => DATABASE_PASS
        );
        $db = new PDO_MySQL($config);

        echo $vpu->build_graph($graph_type, $time_frame, $start_date, $end_date, $db);
        exit;
    }

    // Tests

    // Sanitize all the $_POST data
    $store_statistics = (boolean) filter_var($_POST['store_statistics'], FILTER_SANITIZE_NUMBER_INT);
    $create_snapshots = (boolean) filter_var($_POST['create_snapshots'], FILTER_SANITIZE_NUMBER_INT);
    $snapshot_directory = trim(strval(filter_var($_POST['snapshot_directory'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
    $sandbox_errors = (boolean) filter_var($_POST['sandbox_errors'], FILTER_SANITIZE_NUMBER_INT);
    $sandbox_filename = trim(strval(filter_var($_POST['sandbox_filename'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
    if ( isset($_POST['sandbox_ignore']) ) {
        $sandbox_ignore = array();
		if ( !empty($_POST['sandbox_ignore']) ) {
	        foreach ( $_POST['sandbox_ignore'] as $ignore ) {
	            $sandbox_ignore[] = trim(strval(filter_var($ignore, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
			}
		}
        $sandbox_ignore = implode('|', $sandbox_ignore);
    } else {
        $sandbox_ignore = '';
    }
    $test_files = trim(strval(filter_var($_POST['test_files'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)));
    $tests = explode('|', $test_files); 

    ob_start(); 

    if ( $sandbox_errors ) {
        set_error_handler(array($vpu, 'handle_errors'));
    }

    $results = $vpu->run($tests);

    if ( $store_statistics ) {
        require 'lib/PDO_MySQL.php';
        $config = array(
            'database' => DATABASE_NAME,
            'host'     => DATABASE_HOST, 
            'username' => DATABASE_USER,
            'password' => DATABASE_PASS
        );
        $db = new PDO_MySQL($config);
        $vpu->save_results($results, $db);
    }

    ob_start(); 
    include 'ui/header.html';
    echo $vpu->to_HTML($results, $sandbox_errors);
    $content = ob_get_contents(); 
    ob_end_clean();

    echo $content;

    if ( $create_snapshots ) {
        $snapshot = ob_get_contents(); 
        $vpu->create_snapshot($snapshot, $snapshot_directory);
    }

?>
