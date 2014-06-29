--TEST--
Tests constructor which throws an exception
--SKIPIF--
<?php
if (!extension_loaded('scalar_objects')) echo 'skip';
?>
--FILE--
<?php

class Handler {
    public function __construct($arg= null) {
        if (!$arg) {
	    throw new Exception('Cannot be used without arguments');
        }
    }
}

try {
    register_primitive_type_handler('null', 'Handler');
} catch (Exception $e) {
    echo "Caught expected exception '", $e->getMessage(), "'\n";
}
register_primitive_type_handler('null', new Handler(1));
echo "OK\n";
?>
--EXPECT--
Caught expected exception 'Cannot be used without arguments'
OK
