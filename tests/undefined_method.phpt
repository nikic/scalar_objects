--TEST--
Calling an undefined method on a scalar object
--FILE--
<?php

class StringHandler {}
register_primitive_type_handler('string', 'StringHandler');

set_exception_handler(function($e) {
    echo "\nFatal error: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";
});

$string = "foo";
$string->test();

?>
--EXPECTF--
Fatal error: Call to undefined method StringHandler::test() in %s on line %d
