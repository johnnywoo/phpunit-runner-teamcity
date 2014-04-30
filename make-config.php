<?php

/**
 * Creates a phpunit XML config with a listener that will output messages to TeamCity.
 * Outputs generated config filename (it will be a temporary file in CWD).
 */

$config = file_exists('phpunit.xml') ? 'phpunit.xml' : 'phpunit.xml.dist';

if (file_exists($config)) {
    $xml = file_get_contents($config);
} else {
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<phpunit></phpunit>";
}

$listenerXml = '<listener class="TeamCity_PHPUnit_Framework_TestListener" file="' . htmlentities(__DIR__ . '/listener.php', ENT_XML1) . '" />';
if (strpos($xml, '</listeners>')) {
    $xml = str_replace('</listeners>', $listenerXml . '</listeners>', $xml);
} else {
    $xml = str_replace('</phpunit>', '<listeners>' . $listenerXml . '</listeners></phpunit>', $xml);
}

// we need to create the tmp config next to the real one (in CWD)
// because real config can have paths in it, like a bootstrap script
$tmpFile = tempnam(getcwd(), 'phpunit_tc_');
file_put_contents($tmpFile, $xml);
echo $tmpFile;
