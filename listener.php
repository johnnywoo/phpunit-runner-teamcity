<?php

class TeamCity_PHPUnit_Framework_TestListener implements PHPUnit_Framework_TestListener
{
    //
    // LISTENER
    //

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->echoTeamcityMessage('testFailed', array(
            'name'    => $this->getTestName($test),
            'message' => $this->getMessage($e),
            'details' => $this->getDetails($e),
        ));
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        $params = array(
            'name'    => $this->getTestName($test),
            'message' => $this->getMessage($e),
            'details' => $this->getDetails($e),
        );
        if ($e instanceof PHPUnit_Framework_ExpectationFailedException) {
            $comparisonFailure = $e->getComparisonFailure();
            if ($comparisonFailure instanceof PHPUnit_Framework_ComparisonFailure) {
                $actualString   = $this->getValueAsString($comparisonFailure->getActual());
                $expectedString = $this->getValueAsString($comparisonFailure->getExpected());
                if ($actualString !== null && $expectedString !== null) {
                    $params['actual']   = $actualString;
                    $params['expected'] = $expectedString;
                }
            }
        }
        $this->echoTeamcityMessage('testFailed', $params);
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->echoTeamcityMessage('testIgnored', array(
            'name'    => $this->getTestName($test),
            'message' => $this->getMessage($e),
            'details' => $this->getDetails($e),
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
            'name'                  => $testName,
            'captureStandardOutput' => 'true',
        );
        if ($test instanceof PHPUnit_Framework_TestCase) {
            $className = get_class($test);
            $fileName  = $this->getFileWithClass($className);
            $params['locationHint'] = "file://{$fileName}::\\{$className}::{$testName}";
        }
        $this->echoTeamcityMessage('testStarted', $params);
    }

    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        $this->echoTeamcityMessage('testFinished', array(
            'name'     => $this->getTestName($test),
            'duration' => (int) ($time * 1000),
        ));
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $suiteName = $suite->getName();
        if (empty($suiteName)) {
            return;
        }
        $params = array(
            'name' => $suiteName,
        );
        if (class_exists($suiteName, false)) {
            $fileName = $this->getFileWithClass($suiteName);
            $params['locationHint'] = "file://{$fileName}::\\{$suiteName}";
        }
        $this->echoTeamcityMessage('testSuiteStarted', $params);
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $suiteName = $suite->getName();
        if (empty($suiteName)) {
            return;
        }
        $this->echoTeamcityMessage('testSuiteFinished', array(
            'name' => $suiteName,
        ));
    }


    //
    // TEAMCITY MESSAGES
    //

    protected function echoTeamcityMessage($messageName, $properties)
    {
        $message = "\n##teamcity[{$messageName}";
        if (is_array($properties)) {
            foreach ($properties as $k => $v) {
                $message .= ' ' . $k . '=' . $this->escape($v);
            }
        } else {
            $message .= ' ' . $this->escape($properties);
        }
        $message .= "]\n";

        fwrite(STDERR, $message);
    }

    protected function escape($text)
    {
        $escaped = strtr($text, array(
            "'"  => "|'",
            "\n" => "|n",
            "\r" => "|r",
            "["  => "|[",
            "]"  => "|]",
            "|"  => "||",
        ));

        // converting unicode to |0xABCD
        $escaped = str_replace('\u', '|0x', preg_replace_callback(
            '/./u',
            function ($m) {
                $ord = ord($m[0]);
                if ($ord <= 127) {
                    return $m[0];
                }

                return trim(json_encode($m[0]), '"');
            },
            $escaped
        ));

        return "'{$escaped}'";
    }


    //
    // UTILITY
    //

    protected function getTestName(PHPUnit_Framework_Test $test)
    {
        if ($test instanceof PHPUnit_Framework_TestCase) {
            return $test->getName();
        }
        return get_class($test);
    }

    protected function getMessage(Exception $e)
    {
        $message = get_class($e);
        $exceptionMessage = $e->getMessage();
        if ($exceptionMessage != '') {
            $message .= ' ' . $e->getMessage();
        }
        return $message;
    }

    protected function getDetails(Exception $e)
    {
        return $e->getTraceAsString();
    }

    protected function getValueAsString($value)
    {
        if ($value === null) {
            return 'null';
        }

        if ($value === true) {
            return 'true';
        }

        if ($value === false) {
            return 'false';
        }

        if (is_array($value) || is_scalar($value)) {
            $valueAsString = print_r($value, true);
            if (strlen($valueAsString) > 10000) {
                return null;
            }
            return $valueAsString;
        }

        return null;
    }

    protected function getFileWithClass($className)
    {
        return (new ReflectionClass($className))->getFileName();
    }
}
