--TEST--
Test modification of array inside handler
--SKIPIF--
<?php if (!extension_loaded('scalar_objects')) echo 'skip'; ?>
--FILE--
<?php

class ArrayHandler {
    public function pop() {
        return array_pop($this);
    }
}

register_primitive_type_handler('array', 'ArrayHandler');

$array = ['foo'];
$array->pop();

var_dump($array);

?>
--EXPECT--
array(0) {
}
