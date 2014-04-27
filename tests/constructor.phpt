--TEST--
Tests constructor is invoked
--SKIPIF--
<?php
if (!extension_loaded('scalar_objects')) echo 'skip';
?>
--FILE--
<?php

class StringHandler {
    public function __construct() {
        var_dump($this);
    }
}

register_primitive_type_handler('string', 'StringHandler');
?>
--EXPECTF--
object(StringHandler)#%d (0) {
}

