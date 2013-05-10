# Changelog

## Development

* Allow `test_directory` and `xml_configuration_file` to be arrays of strings aswell so can run multiple test suites from one deployment.
* Allow certain arguments to be passed to the command line version, temporarily overwriting the ones in bootstrap `$config`.
* Throw an exception when the view template cannot be written to the cache directory.
* Throw an exception when the XML configuration file doesn't contain the required listener.
* Throw an exception when PHPUnit response doesn't contain the required JSON.

## v2.1.1

* Don't allow duplicate files if the parent folder is selected (GH-90)
* Pad snapshot time with zero for better sorting (GH-87)
* Only collect JSON when using XML configuration files (GH-84)
* Show server error if AJAX request fails (GH-83)
* Don't rewrite PHP_SELF (GH-80)
* Use namespace when checking if tests are subclasses of PHPUnit_Framework_TestCase (GH-78)
* Fix WAMP routing issues (GH-53)
* Implement keyboard shortcuts (GH-67)
* Fix output parsing to handle pretty-printed JSON (GH-65)
* Fix display of statistics (GH-63)
* Clarify directory selection key combination (GH-60)
* Check if tests are subclasses of PHPUnit_Framework_TestCase (GH-59)

## v2.1

* Add ability to ignore hidden files (GH-57)
* Add error handler for non-JSON responses from the server (GH-48, GH-58)
* Use strict checking with readdir() (GH-56)
* Handle unbalanced braces properly (GH-54)
* Fix error that occurs when no snapshot is selected on Archives page (GH-50)
* Reduce complexity of Apache installations (GH-45)
* Fix autoloader to only load files required by VPU (GH-46)
* Only return child directories of test_directory (GH-44)

## v2.0

* Overhaul the entire code base
* Give the UI a facelift
* Add ability to run tests using a phpunit.xml configuration file (GH-31)
* Add ability to generate test results from the command line (GH-32)

## v1.5.6

* Replace line breaks with <br>s instead of empty strings (GH-42)
* Fix jqueryFileTree folder selection for Macs (GH-41)
* Fix display of debugging output (GH-39)
* Add ability to set MySQL port (GH-37)

## v1.5.5

* Change require -> require_once to avoid errors (GH-34)
* Don't require files to share the same name as the test classes (GH-33)
* Fix output buffering (GH-23)

## v1.5.4

* Fix include_path issues (GH-26)

## v1.5.3

* Fix SANDBOX_IGNORE settings (GH-21)
* Update history file (GH-20)

## v1.5.2

* Add tooltips to compensate for colorblind usage problem (GH-17)
* Add ability to filter suite results (GH-14)

## v1.5.1

* Update color scheme
* Update snapshot list each time a test is run (GH-10)
* Fix snapshot filenames to be compatible with Windows systems (GH-11)
* Allow debug display of JSON within tests (GH-9)
* Fix POST locations to use relative URIs

## v1.5

* Add ability to generate graphs of test results

## v1.4.1

* Fix Windows path issues
* Add a progress bar to indicate that tests are being processed

## v1.4

* Overhaul the UI
* Fix issues with namespaced tests
* Implement a better check for archived files


## v1.3.2

* Add support for bootstraps
* Clean up the user interface
* Add the ability to view snapshots from the homepage
* Change the snapshot filename format to Y-m-d

## v1.3.1

* Allow for relative paths in TEST_DIRECTORY
* Use a better test loading mechanism

## v1.3

* Add a start page to allow for specific choosing of tests and options
* Add the ability to sort suite results by status and time
* Clean up some configuration settings
* Remove ability to save JSON snapshots

## v1.2

* Add statistic bars to display the suite results visually

## v1.1.1

* Fix to allow for loading a single test directly
* Adjust code to allow for proper execution with 'short_open_tag' off
* Fix to match test files with the word 'Test' at the end of the filename
* Fix to eliminate duplicate tests

## v1.1

* Add suite statistics

## v1.0

* Initial release
