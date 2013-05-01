# VisualPHPUnit

Forked from the excellent VisualPHPUnit by NSinopoli.  I just added some optional features that I've found useful for selenium testing:

* Optional Bootstraps
These options in the config file let you add some optional bootstrap files to run before the tests.  I use them to define
a bootstrap for each browser that I want to test with selenium.

* Optional Parameters
These options allow you to set some parameters at the time that you run the tests.  I use this to define the host name 
that is running the selenium web driver.

![Screenshot of added options.](https://raw.github.com/saltlakeryan/VisualPHPUnit/master/options_screenshot.png)
