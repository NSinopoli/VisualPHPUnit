<?php

namespace app\lib;

if (!class_exists('PHPUnit_Util_Log_JSON_With_String_Comparison')) {

    class PHPUnit_Util_Log_JSON_With_String_Comparison extends \PHPUnit_Util_Log_JSON
    {
        public function addFailure(\PHPUnit_Framework_Test $test, \PHPUnit_Framework_AssertionFailedError $e, $time)
        {
            if ( $e instanceof \PHPUnit_Framework_ExpectationFailedException && $e->getComparisonFailure() ) {
                $new_message =  $e->getComparisonFailure()->toString() ;
                $e2 = new \PHPUnit_Framework_ExpectationFailedException($new_message, $e->getComparisonFailure(), $e);
                parent::addFailure($test, $e2, $time);
            } else {
                parent::addFailure($test, $e, $time);
            }
        }
    }

}
?>

