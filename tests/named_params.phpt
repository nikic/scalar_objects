--TEST--
Named parameters
--SKIPIF--
<?php
if (PHP_VERSION_ID < 80000) die('skip For PHP >= 8.0 only');
?>
--FILE--
<?php

register_primitive_type_handler('string', 'StringHandler');

class StringHandler {
    public static function test($self, $prefix = '', $suffix = '') {
        return $prefix . $self . $suffix;
    }
}

$string = "Test";
var_dump($string->test(prefix: "P"));
var_dump($string->test(suffix: "S"));
var_dump($string->test(prefix: "P", suffix: "S"));
var_dump($string->test(suffix: "S", prefix: "P"));

?>
--EXPECT--
string(5) "PTest"
string(5) "TestS"
string(6) "PTestS"
string(6) "PTestS"
