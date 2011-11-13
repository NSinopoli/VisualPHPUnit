VisualPHPUnit
=============

VisualPHPUnit is a visual front-end for PHPUnit.  Written in PHP, it aims to make unit testing more appealing. 

Features
--------

VisualPHPUnit provides the following features:

* A stunning front-end which organizes test and suite results
* The ability to view unit testing progress via graphs
* An option to maintain a history of unit test results through the use of visual logs 
* Enumeration of PHPUnit statistics and messages
* Convenient display of any debug messages written within unit tests
* Sandboxing of PHP errors/exceptions

Screenshots
----------

.. image:: http://echodrop.net/code/vpu/ss7.png
.. image:: http://echodrop.net/code/vpu/ss8.png

Requirements
------------

VisualPHPUnit only supports PHPUnit v3.5 and above.

Installation
------------

1. Download and extract the project to a web-accessible directory.
2. Open config.php with your favorite editor.
    a. Change PHPUNIT_INSTALL so that it points to the directory where PHPUnit is installed.
    b. Update TEST_DIRECTORY so that it points to the root directory where your unit tests are stored.
    c. (optional) Change PEAR_INSTALL so that it points to the directory where PEAR is installed.
3. Point your browser to the location where you installed VisualPHPUnit!

Configuration (optional)
------------------------

If you wish to  set the default options for each test run, you can do so by modifying a few more lines in config.php. 

1. Change CREATE_SNAPSHOTS to *true* if you'd like to enable logging.  Logs are stored in the 'history' directory, though you can modify SNAPSHOT_DIRECTORY to point somewhere else if you please.  Please make note of the following:
    - You will have to give the directory specified in SNAPSHOT_DIRECTORY the appropriate permissions in order to allow PHP to write to it.
    - The dropdown list under the 'Archives' section on the homepage will only display the files found within SNAPSHOT_DIRECTORY.
2. Change SANDBOX_ERRORS to *true* if you'd like VPU to display any PHP errors after the test results.  If so, please make note of the following:
    - The file specified in SANDBOX_FILENAME will always be empty (VPU wipes it at the end of each test run).  However, PHP still needs to be able to write to it, so ensure that the filename specified with SANDBOX_FILENAME has the appropriate permissions. 
    - Specific error types can be ignored using the SANDBOX_IGNORE setting.  Separate multiple error types with a '|' (e.g. 'E_STRICT|E_NOTICE').
3. If you'd like to enable graph generation, you will have to do the following:
    - Change STORE_STATISTICS to *true*.  If you'd like, you can keep this set as 'false', though you will have to change the 'Store Statistics' option to 'Yes' on the UI if you want the test statistics to be used in graph generation.
    - Run the migration 01_CREATE_SCHEMA (found in the 'migrations' directory) against a MySQL database.  Note that this will automatically create a database named 'vpu' with the tables needed to save your test statistics.
    - Update each of the DATABASE_* constants to reflect your database settings.  Note that if you're using the migration described above, DATABASE_NAME should remain set to 'vpu'. 
4. If your unit tests require any bootstraps, you can define them at the bottom of config.php in the appropriate '$bootstraps' array.


Version Information
-------------------

Current stable release is v1.5.3, last updated on 8 November 2011.

Feedback
--------

Feel free to send any feedback you may have regarding this project to NSinopoli@gmail.com. 

Credits
-------

Special thanks to Matt Mueller (http://mattmueller.me/blog/), who came up with the initial concept, wrote the original code (https://github.com/MatthewMueller/PHPUnit-Test-Report), and was kind enough to share it.

Thanks to Mike Zhou, Hang Dao, Thomas Ingham, and Fredrik Wollsén for their suggestions!
