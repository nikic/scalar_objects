--TEST--
Test nested functionality
--SKIPIF--
<?php
if (!extension_loaded('scalar_objects')) echo 'skip';
?>
--FILE--
<?php

class StringHandler {
    public function length($self) {
        return strlen($self);
    }

    public function endsWith($self, $search) { 
        return 0 == substr_compare($self, $search, $self->length() - $search->length());
    }
}

register_primitive_type_handler('string', 'StringHandler');

$string= 'Hello';
var_dump($string->endsWith('lo'));
?>
--EXPECT--
bool(true)
