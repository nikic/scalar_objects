--TEST--
Test using an object 
--SKIPIF--
<?php
if (!extension_loaded('scalar_objects')) echo 'skip';
?>
--FILE--
<?php

class StringHandler {
    protected $encoding;

    public function __construct($encoding= 'utf-8') {
         $this->encoding= $encoding;
    }

    public function length($self) {
        return iconv_strlen($self, $this->encoding);
    }
}

register_primitive_type_handler('string', new StringHandler());

$string= 'Hällo';
var_dump($string->length());
var_dump($string);
?>
--EXPECT--
int(5)
string(6) "Hällo"
