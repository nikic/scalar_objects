--TEST--
Test __call gets invoked 
--SKIPIF--
<?php
if (!extension_loaded('scalar_objects')) echo 'skip';
?>
--FILE--
<?php
class NullValueException extends Exception {
}

class NullHandler {
    public function __call($name, $args) {
        var_dump($args);
        throw new NullValueException('Calling '.$name.'()'); 
    }
}

register_primitive_type_handler('null', 'NullHandler');

$null= null;
try {
  $null->length();
} catch (NullValueException $e) {
  echo "Caught expected NVE '", $e->getMessage(), "'\n";
}
echo "Alive\n";
?>
--EXPECTF--
array(1) {
  [0]=>
  %sNULL
}
Caught expected NVE 'Calling length()'
Alive
