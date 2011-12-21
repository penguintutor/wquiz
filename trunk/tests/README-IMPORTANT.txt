This folder contains test scripts for the wquiz software.
These are primarly intended for testing that the classes are working correctly
rather than testing the actual page code (although some parts of that are tested
 as well)

The scripts in this folder MUST NOT be installed on any production system.
Doing so would risk the integrity of any production data as well as well as 
providing a potential security vulnerability.

This is included in the Subversion development code.

This folder must not be included in any release packages. If this has been inadvertaintly
left into a shipped package please delete the tests folder and contact the relevant person
to have the package removed.

To run any of these tests then the files need to be moved into a subdirectory of the
www files - on a test system only.


Note for some tests to work correctly database settings relating to file locations  
should be entered as abosolute rather than relative paths. eg. template_directory