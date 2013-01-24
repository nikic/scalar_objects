--TEST--
Testing string handler methods
--SKIPIF--
<?php if (!extension_loaded('scalar_objects')) echo 'skip'; ?>
--FILE--
<?php

require __DIR__ . '/bootstrap.php';

$str = 'foobar';

echo "Working on string \"$str\"\n\n";

p('length()',          $str->length());
p('indexOf("o")',      $str->indexOf("o"));
p('lastIndexOf("o")',  $str->lastIndexOf("o"));
p('contains("ooba")',  $str->contains("ooba"));
p('contains("aboo")',  $str->contains("aboo"));
p('startsWith("foo")', $str->startsWith("foo"));
p('startsWith("bar")', $str->startsWith("bar"));
p('endsWith("bar")',   $str->endsWith("bar"));
p('endsWith("foo")',   $str->endsWith("foo"));
p('repeat(3)',         $str->repeat(3));
p('reverse()',         $str->reverse());

?>
--EXPECTF--
Working on string "foobar"

length(): int(6)
indexOf("o"): int(1)
lastIndexOf("o"): int(2)
contains("ooba"): bool(true)
contains("aboo"): bool(false)
startsWith("foo"): bool(true)
startsWith("bar"): bool(false)
endsWith("bar"): bool(true)
endsWith("foo"): bool(false)
repeat(3): string(18) "foobarfoobarfoobar"
reverse(): string(6) "raboof"
