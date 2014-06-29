--TEST--
Test using the passed value 
--SKIPIF--
<?php
if (!extension_loaded('scalar_objects')) echo 'skip';
?>
--FILE--
<?php

class StringHandler {
    public function concat($self, $other) {
        return $self.$other;
    }
}

register_primitive_type_handler('string', 'StringHandler');

$string= 'Hello';
var_dump($string->concat(' ')->concat('World'));
?>
--EXPECT--
string(11) "Hello World"
