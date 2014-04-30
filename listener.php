<?php

class TeamCity_PHPUnit_Framework_TestListener implements PHPUnit_Framework_TestListener
{
    public static function printEvent($eventName, $params = array())
    {
        self::printText("\n##teamcity[$eventName");
        foreach ($params as $key => $value) {
            self::printText(" $key='$value'");
        }
        self::printText("]\n");
    }

    public static function printText($text)
    {
        file_put_contents('php://stderr', $text);
    }

    private static function getMessage(Exception $e)
    {
        $message = "";
        if (strlen(get_class($e)) != 0) {
            $message = $message . get_class($e);
        }
        if (strlen($message) != 0 && strlen($e->getMessage()) != 0) {
            $message = $message . " : ";
        }
        $message = $message . $e->getMessage();
        return self::escapeValue($message);
    }

    private static function getDetails(Exception $e)
    {
        return self::escapeValue($e->getTraceAsString());
    }

    public static function getValueAsString($value)
    {
        if (is_null($value)) {
            return "null";
        } else if (is_bool($value)) {
            return $value == true ? "true" : "false";
        } else if (is_array($value) || is_string($value)) {
            $valueAsString = print_r($value, true);
            if (strlen($valueAsString) > 10000) {
                return null;
            }
            return $valueAsString;
        } else if (is_scalar($value)) {
            return print_r($value, true);
        }
        return null;
    }

    private static function escapeValue($text)
    {
        $text = str_replace("|", "||", $text);
        $text = str_replace("'", "|'", $text);
        $text = str_replace("\n", "|n", $text);
        $text = str_replace("\r", "|r", $text);
        $text = str_replace("]", "|]", $text);
        return $text;
    }

    public static function getFileName($className)
    {
        $reflectionClass = new ReflectionClass($className);
        $fileName = $reflectionClass->getFileName();
        return $fileName;
    }

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        self::printEvent("testFailed", array(
            "name" => $this->getTestName($test),
            "message" => self::getMessage($e),
            "details" => self::getDetails($e)
        ));
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        $params = array(
            "name" => $this->getTestName($test),
            "message" => self::getMessage($e),
            "details" => self::getDetails($e)
        );
        if ($e instanceof PHPUnit_Framework_ExpectationFailedException) {
            $comparisonFailure = $e->getComparisonFailure();
            if ($comparisonFailure instanceof PHPUnit_Framework_ComparisonFailure) {
                $actualResult = $comparisonFailure->getActual();
                $expectedResult = $comparisonFailure->getExpected();
                $actualString = self::getValueAsString($actualResult);
                $expectedString = self::getValueAsString($expectedResult);
                if (!is_null($actualString) && !is_null($expectedString)) {
                    $params['actual'] = self::escapeValue($actualString);
                    $params['expected'] = self::escapeValue($expectedString);
                }
            }
        }
        self::printEvent("testFailed", $params);
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        self::printEvent("testIgnored", array(
            "name" => $this->getTestName($test),
            "message" => self::getMessage($e),
            "details" => self::getDetails($e)
        ));
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->addIncompleteTest($test, $e, $time);
    }

    public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->addIncompleteTest($test, $e, $time);
    }

    public function startTest(PHPUnit_Framework_Test $test)
    {
        $testName = $this->getTestName($test);
        $params = array(
            "name" => $testName,
            "captureStandardOutput" => "true"
        );
        if ($test instanceof PHPUnit_Framework_TestCase) {
            $className = get_class($test);
            $fileName = self::getFileName($className);
            $params['locationHint'] = "file://$fileName::\\$className::$testName";
        }
        self::printEvent("testStarted", $params);
    }

    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        self::printEvent("testFinished", array(
            "name" => $this->getTestName($test),
            "duration" => (int)(round($time, 2) * 1000)
        ));
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $suiteName = $suite->getName();
        if (empty($suiteName)) {
            return;
        }
        $params = array(
            "name" => $suiteName,
        );
        if (class_exists($suiteName, false)) {
            $fileName = self::getFileName($suiteName);
            $params['locationHint'] = "file://$fileName::\\$suiteName";
        }
        self::printEvent("testSuiteStarted", $params);
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $suiteName = $suite->getName();
        if (empty($suiteName)) {
            return;
        }
        self::printEvent("testSuiteFinished",
            array(
                "name" => $suite->getName()
            ));
    }

    protected function getTestName(PHPUnit_Framework_Test $test)
    {
        if ($test instanceof PHPUnit_Framework_TestCase) {
            return $test->getName();
        }
        return get_class($test);
    }
}
