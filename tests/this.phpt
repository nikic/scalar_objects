--TEST--
Test that $this points to handler instance 
--SKIPIF--
<?php
if (!extension_loaded('scalar_objects')) echo 'skip';
?>
--FILE--
<?php

class Handler {
    public function invoke($self) { 
        var_dump($self);
	var_dump($this);
    }
}

register_primitive_type_handler('string', 'Handler');

$var= "Value";
$var->invoke();
?>
--EXPECTF--
string(5) "Value"
object(Handler)#%d (0) {
}
