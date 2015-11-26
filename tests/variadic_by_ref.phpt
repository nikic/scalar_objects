--TEST--
Accept variadic arguments by reference
--SKIPIF--
<?php if (PHP_VERSION_ID < 50600) die("skip Variadics supported on PHP >= 5.6");
--FILE--
<?php

class StringHandler {
    public static function incrementArgs($self, &...$args) {
        foreach ($args as &$arg) $arg++;
    }
}

register_primitive_type_handler('string', 'StringHandler');

$string = "foo";
$array = [0, 1, 2];
$string->incrementArgs($array[0], $array[1], $array[2]);
var_dump($array);

?>
--EXPECT--
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
