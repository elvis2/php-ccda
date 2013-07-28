<?php

// Load CCDA class
require('php-ccda.php');

// Load file with example CCDA.
// Note: loading this into a string, as the ccda model will translate this to
// an XML object.  It will also take an XML object
$xml = file_get_contents('demo3.xml');

// Create new patient
$patient = new Ccda($xml);

// Construct and echo JSON 
echo('<PRE>');
echo($patient->construct_json());
echo('</PRE>');

// Or you can call element of the CCDA directly, and get back a PHP object
//print_r($patient->rx);
