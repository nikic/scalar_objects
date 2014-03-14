--TEST--
Test modification of array inside handler
--SKIPIF--
<?php
if (!extension_loaded('scalar_objects')) echo 'skip';

/* Not supported for builds against PHP 5.6 or higher */
if (version_compare(PHP_VERSION, '5.6.0-dev', '>=')) echo 'skip';
?>
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
