--TEST--
Exception occuring while passing arguments to a handler
--SKIPIF--
<?php
if (PHP_VERSION_ID < 50608) die('skip Requires PHP bug fix');
if (PHP_VERSION_ID > 70000) die('skip Leaks on PHP 7 again, but cannot be easily fixed');
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
