--TEST--
Visibility for handler methods is honored
--FILE--
<?php

class StringHandler {
    public static function pub($self) {
        echo "Public\n";
        $self->prot();
    }
    protected static function prot($self) {
        echo "Protected\n";
    }
}

register_primitive_type_handler('string', 'StringHandler');

$str = "abc";
$str->pub();
$str->prot();

?>
--EXPECTF--
Public
Protected

Fatal error: Call to protected method StringHandler::prot() from context '' in %s on line %d
