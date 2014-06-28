--TEST--
Test __call gets invoked 
--SKIPIF--
<?php
if (!extension_loaded('scalar_objects')) echo 'skip';
?>
--FILE--
<?php
class NumberHandler {
    public function __call($name, $args) {
        var_dump($args);
    }
}

register_primitive_type_handler('int', 'NumberHandler');

$number= 1;
$number->toString();
echo "Alive\n";
?>
--EXPECT--
array(1) {
  [0]=>
  int(1)
}
Alive
