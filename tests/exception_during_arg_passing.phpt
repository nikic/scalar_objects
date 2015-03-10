--TEST--
Exception occuring while passing arguments to a handler
--SKIPIF--
<?php
if (version_compare(PHP_VERSION, '5.6.8-dev', '<')) die('skip Requires PHP bug fix');
--FILE--
<?php

function do_throw() {
    throw new Exception;
}

class StringHandler {
    public static function test($self, $a) {
        echo "Called test()\n";
    }
}

register_primitive_type_handler('string', 'StringHandler');

$str = "abc";
$str2 = "def";

try {
    $str->test(do_throw());
} catch (Exception $e) {
    echo $e, "\n";
}

try {
    $str->test($str2, do_throw());
} catch (Exception $e) {
    echo $e, "\n";
}

?>
--EXPECTF--
exception 'Exception' in %s:%d
Stack trace:
#0 %s(%d): do_throw()
#1 {main}
exception 'Exception' in %s:%d
Stack trace:
#0 %s(%d): do_throw()
#1 {main}
