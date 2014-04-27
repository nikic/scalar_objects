--TEST--
Test basic functionality
--SKIPIF--
<?php
if (!extension_loaded('scalar_objects')) echo 'skip';
?>
--FILE--
<?php

class StringHandler {
    public function length($self) {
        return strlen($self);
    }
}

register_primitive_type_handler('string', 'StringHandler');

$string= 'Hello';
var_dump($string->length());
var_dump($string);
?>
--EXPECT--
int(5)
string(5) "Hello"
