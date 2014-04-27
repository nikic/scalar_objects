--TEST--
$this is bound for closures in type handlers
--FILE--
<?php

register_primitive_type_handler('array', 'ArrayHandler');

class ArrayHandler {
    public function method() {
        $closure = function() { var_dump($this); };
        $closure();
    }
}

$array = [];
$array->method();

?>
--EXPECTF--
object(ArrayHandler)#%d (0) {
}
