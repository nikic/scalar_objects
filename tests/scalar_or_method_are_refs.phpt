--TEST--
Scalar or called method can be referenced variables
--FILE--
<?php

class StringHandler {
    public static function test($self) {
        echo "Called\n";
    }
}

register_primitive_type_handler('string', 'StringHandler');

$str = "foo";
$method = "test";
$ref1 =& $str;
$ref2 =& $method;
$str->$method();

?>
--EXPECT--
Called
