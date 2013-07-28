<?php

// Load CCDA class
require('php-ccda.php');

// Load file with example CCDA.
// Note: loading this into a string, as the ccda model will translate this to
// an XML object
$xml = file_get_contents('demo.xml');

// Create new patient
$patient = new Ccda($xml);

// If it's already in a simple XML object, you can pass that via the following:
//$patient = new Ccda();
//$patient->load_xml($xmlObject);

// Construct and echo JSON 
echo($patient->construct_json());

// Or you can call element of the CCDA directly, and get back a PHP object
//print_r($patient->rx);
