--TEST--
$this is not bound for closures in type handlers
--FILE--
<?php

register_primitive_type_handler('array', 'ArrayHandler');

class ArrayHandler {
    public static function method($self) {
        $closure = function() use($self) { var_dump(isset($this)); var_dump($self); };
        $closure();
    }
}

$array = [];
$array->method();

?>
--EXPECTF--
bool(false)
array(0) {
}
