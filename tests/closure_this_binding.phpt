--TEST--
$this is not bound for closures in type handlers
--FILE--
<?php

register_primitive_type_handler('array', 'ArrayHandler');

class ArrayHandler {
    public static function method($self) {
        $closure = function() use($self) { var_dump($this); var_dump($self); };
        $closure();
    }
}

$array = [];
$array->method();

?>
--EXPECTF--
Notice: Undefined variable: this in %s on line %d
NULL
array(0) {
}
