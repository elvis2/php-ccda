php-ccda
========

This is a Consolidated Clinical Document Architecture (CCDA) parser written in PHP.  It creates a Ccda class, which allows you to pass the CCDA to it, and it'll produce a PHP object with relevant clinical data.  There are a few known issues, primarily that it isn't intended to extract every single data element present in the CCDA, but only the ones that are most relevant.  Also, for simplicity, in some situations it will only extract a single element when it's possible for multiple elements to exist (e.g., findings during an encounter).  It was designed to mimic the behavior of bluebutton.js.

To use, load the file into your PHP code, and pass an XML string or XML object to the constructor.  Once that occurs, it will parse the XML, and produce an object with the following properties:

* rx: prescriptions
* dx: problems
* lab: lab values
* immunization: immunizations
* proc: procedures
* vital: vital signs
* allergy: allergies
* enc: encounters

