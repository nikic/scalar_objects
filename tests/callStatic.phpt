--TEST--
__callStatic is called for primitive types
--FILE--
<?php

class ArrayHandler {
    public static function __callStatic($method, array $args) {
        var_dump($method, $args);
    }
}

register_primitive_type_handler('array', 'ArrayHandler');

$array = [1, 2, 3];
$array->foobar();
var_dump($array);

?>
--EXPECT--
string(6) "foobar"
array(1) {
  [0]=>
  array(3) {
    [0]=>
    int(1)
    [1]=>
    int(2)
    [2]=>
    int(3)
  }
}
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
