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

ini_set('display_errors', 1);

// The directory where this application is installed
define("BASE_INSTALL", realpath(__DIR__));

// The directory where PEAR is located
define('PEAR_PATH', '/usr/share/pear');

set_include_path(get_include_path().PATH_SEPARATOR.
                 PEAR_PATH.PATH_SEPARATOR);

// The directory where the tests reside
define('TEST_DIRECTORY', BASE_INSTALL . '/tests/');

/*
 * Optional settings
 */

// Whether or not to create snapshots of the test results
define('CREATE_SNAPSHOTS', false);

// The directory where the test results will be stored
define('SNAPSHOT_DIRECTORY', BASE_INSTALL . '/history/');

// Whether or not to sandbox PHP errors
define('SANDBOX_ERRORS', false);

// The file to use as a temporary storage for PHP errors during PHPUnit runs
define('SANDBOX_FILENAME', BASE_INSTALL . '/errors/errors.tmp');

// Error types to ignore (separate each type with a `|`)
// e.g. 'E_STRICT|E_NOTICE'
define('SANDBOX_IGNORE', 'E_STRICT');

// Whether or not to store the statistics in a database
// (these statistics will be used to generate graphs)
define('STORE_STATISTICS', false);

// The database settings
define('DATABASE_NAME', 'vpu');
define('DATABASE_HOST', 'localhost');
define('DATABASE_PORT', '3306');
define('DATABASE_USER', 'root');
define('DATABASE_PASS', 'admin');

// Paths to any necessary bootstraps
$bootstraps = array(
    // '/path/to/tests/bootstrap.php'
);

foreach ( $bootstraps as $bootstrap ) {
    require $bootstrap;
}

?>
