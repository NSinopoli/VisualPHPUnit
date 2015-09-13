[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/VisualPHPUnit/VisualPHPUnit/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/VisualPHPUnit/VisualPHPUnit/?branch=master)
[![Build Status](https://travis-ci.org/VisualPHPUnit/VisualPHPUnit.svg)](https://travis-ci.org/VisualPHPUnit/VisualPHPUnit)
[![Dependency Status](https://www.versioneye.com/user/projects/55f547b3a4155f00090005b5/badge.svg?style=flat)](https://www.versioneye.com/user/projects/55f547b3a4155f00090005b5)
[![Project Stats](https://www.openhub.net/p/VisualPHPUnit/widgets/project_thin_badge.gif)](https://www.openhub.net/p/VisualPHPUnit)

# VisualPHPUnit

VisualPHPUnit is a visual front-end for PHPUnit.  It offers the following features:

* A stunning front-end which organizes test and suite results
* The ability to view unit testing progress via graphs
* An option to maintain a history of unit test results through the use of snapshots
* Enumeration of PHPUnit statistics and messages
* Convenient display of any debug messages written within unit tests
* Sandboxing of PHP errors
* The ability to generate test results from both a browser and the command line

## Screenshots

![Screenshot of VisualPHPUnit, displaying a breakdown of test results.](http://visualphpunit.github.io/VisualPHPUnit/vpu2_main.png "VisualPHPUnit Test Results")
![Screenshot of VisualPHPUnit, displaying a graph of test results.](http://visualphpunit.github.io/VisualPHPUnit/vpu2_graphs.png "VisualPHPUnit Statistics Graph")

## Requirements

VisualPHPUnit requires PHP 5.3+ and PHPUnit v3.5+.

## Upgrading From v1.x

VPU underwent a complete rewrite in v2.0.  Users who are looking to upgrade from v1.x are encouraged to follow the installation instructions outlined below.

### What About My Data?

Because the UI has been changed, snapshots from v1.x will not render correctly in v2.x.

Test statistics generated in v1.x, however, can still be used.  When installing, ignore the [migration](#graph-generation) and run the following commands against your old VPU database instead:

```sql
alter table SuiteResult change success succeeded int(11) not null;
alter table TestResult change success succeeded int(11) not null;
```

### I Miss v1.x!

While no longer actively supported, v1.x can be found on its [own branch](https://github.com/NSinopoli/VisualPHPUnit/tree/1.x).

## Installation

1. Download and extract (or git clone) the project to a web-accessible directory.
2. Change the permissions of `app/resource/cache` to `777` or give the Apache user write access another way.
3. If you have not already installed PHPUnit via Composer, do so using the following command:
	1. cd /path/to/VisualPHPUnit/../
	2. composer require phpunit/phpunit
3. Open `app/config/bootstrap.php` with your favorite editor.
    1. Within the `$config` array, change `composer_vendor_path` so that it points to the Composer vendor directory where PHPUnit is located (if installed with the above step, this shouldn't need to be edited)
    2. Within the `$config` array, change the contents of `test_directories` to reflect the location(s) of your unit tests. Note that each directory acts as a root directory.
4. Configure your web server (see below).

## Web Server Configuration

### Apache

VPU comes with .htaccess files, so you won't have to worry about configuring anything.  Simply point your browser at the location where you installed the code!

#### Troubleshooting

1. Make sure `mod_rewrite` is enabled.
2. Make sure `AllowOverride` in your `httpd.conf` is set to `all`.
3. If you're using WAMP, you'll need to adjust the two `.htaccess` files to reflect the location where you extracted VPU.  (In this example, VPU has been extracted to `C:\wamp\www\vpu`, where `C:/wamp/www/` has been set as the `DocumentRoot` in `httpd.conf`.)
  - In the `.htaccess` file located at the root of the repository, add the following line after line 2:
    `RewriteBase /vpu`
  - In `app/public/.htaccess`, add the following line after line 2:
    `RewriteBase /vpu/app/public`

### nginx

Place this code block within the `http {}` block in your `nginx.conf` file:

```nginx

    server {
	    server_name     vpu;
	    root            /srv/http/vpu/app/public;
	    index           index.php;

	    access_log      /var/log/nginx/vpu_access.log;
	    error_log       /var/log/nginx/vpu_error.log;

	    location / {
            try_files $uri /index.php;
	    }

	    location ~ \.php$ {
            fastcgi_pass    unix:/var/run/php-fpm/php-fpm.sock;
            fastcgi_index   index.php;
            fastcgi_param   SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include         fastcgi_params;
	    }
    }
```

Note that you will have to change the `server_name` to the name you use in your hosts file. You will also have to adjust the directories according to where you installed the code. In this configuration, /srv/http/vpu/ is the project root. The public-facing part of VisualPHPUnit, however, is located in app/public within the project root (so in this example, it's /srv/http/vpu/app/public).

When that's done, restart your web server, and then point your browser at the server name you chose above!

## Project Configuration (optional)

VPU comes with many of its features disabled by default.  In order to take advantage of them, you'll have to modify a few more lines in `app/config/bootstrap.php`.

## Using VisualPHPUnit with Laravel

It's simple to use VisualPHPUnit with Laravel. Assuming you have successfully installed with the above instructions, and the sample tests run, you need only do the following:

1. In `app/config/bootstrap.php` add the path to your Laravel project's tests directory to the `test_directories` array (eg: '/var/www/laravel/tests')
2. In `app/config/bootstrap.php` add your Laravel project's autoload.php to the `bootstraps` array (eg: '/var/www/laravel/bootstrap/autoload.php')
3. Now reload VisualPHPUnit and run the tests from your Laravel project.

### <a name='graph-generation'></a>Graph Generation

If you'd like to enable graph generation, you will have to do the following:

1. Within the `$config` array, change `store_statistics` to `true`.  If you'd like, you can keep this set as `false`, though you will have to change the 'Store Statistics' option to 'Yes' on the UI if you want the test statistics to be used in graph generation.
2. Run the migration `app/resource/migration/01_CreateSchema.sql` against a MySQL database.
    - Note that this will automatically create a database named `vpu` with the tables needed to save your test statistics.
3. Within the `$config` array, change the settings within the `db` array to reflect your database settings.
    - Note that if you're using the migration described above, `database` should remain set to `vpu`.
    - The `plugin` directive should not be changed.

### <a name='snapshots'></a>Snapshots

If you'd like to enable snapshots, you will have to do the following:

1. Within the `$config` array, change `create_snapshots` to `true`.  If you'd like, you can keep this set as `false`, though you will have to change the 'Create Snapshots' option to 'Yes' on the UI if you want the test results to be saved.
2. Within the `$config` array, change `snapshot_directory` to a directory where you would like the snapshots to be saved.
    - Note that this directory must have the appropriate permissions in order to allow PHP to write to it.
    - Note that the dropdown list on the 'Archives' page will only display the files found within `snapshot_directory`.

### <a name='sandboxing'></a>Error Sandboxing

If you'd like to enable error sandboxing, you will have to do the following:

1. Within the `$config` array, change `sandbox_errors` to `true`.  If you'd like, you can keep this set as `false`, though you will have to change the 'Sandbox Errors' option to 'Yes' on the UI if you want the errors encountered during the test run to be sandboxed.
2. Within the `$config` array, change `error_reporting` to reflect which errors you'd like to have sandboxed.  See PHP's manual entry on [error_reporting](http://php.net/manual/en/function.error-reporting.php) for more information.

### Ignore Hidden Folders

By default, the file selector does not display hidden folders (i.e., folders with a '.' prefix).  If you'd like to display hidden folders, you will have to do the following:

1.  Within the `$config` array, change `ignore_hidden_folders` to `false`.

### <a name='xml-configuration'></a>PHPUnit XML Configuration File

If you'd like to use a [PHPUnit XML configuration file](http://www.phpunit.de/manual/current/en/appendixes.configuration.html) to define which tests to run, you will have to do the following:

1. Within the `$config` array, change `xml_configuration_files` to reflect the location(s) where the configuration file(s) can be found.
2. Modify your PHPUnit XML configuration file(s) to include this block:

```xml
       <!-- This is required for VPU to work correctly -->
       <listeners>
         <listener class="PHPUnit_Util_Log_JSON"></listener>
       </listeners>
```

### Bootstraps

If you'd like to load any bootstraps, you will have to do the following:

1. Within the `$config` array, list the paths to each of the bootstraps within the `bootstraps` array.

## Keyboard Shortcuts

### Home Page

`t - Run Tests`

## Running VPU at the Command Line

VPU can be run at the command line, making it possible to automate the generation of test results via cron.

You may append --help for options

### Usage

```bash
# from the project root
bin/vpu
```

## Version Information

Current stable release is v2.3, last updated on Jul 21, 2015.

## Credits

Special thanks to Matt Mueller (http://mattmueller.me/blog/), who came up with the initial concept, wrote the original code (https://github.com/MatthewMueller/PHPUnit-Test-Report), and was kind enough to share it.

Thanks to Mike Zhou, Hang Dao, Thomas Ingham, and Fredrik Wollsén for their suggestions!
