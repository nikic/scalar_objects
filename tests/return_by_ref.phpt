--TEST--
Return by reference from handler
--FILE--
<?php

class StringHandler {
    public static function &returnRef($self, &$var) {
        return $var;
    }
}

register_primitive_type_handler('string', 'StringHandler');

$string = "foo";
$var = 42;
$ref =& $string->returnRef($var);
$ref = 24;
var_dump($var);

?>
--EXPECT--
int(24)
