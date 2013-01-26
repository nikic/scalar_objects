--TEST--
Tests case insensitive string APIs
--FILE--
<?php

require __DIR__ . '/bootstrap.php';
require $HANDLER_DIR . '/string.php';
require $HANDLER_DIR . '/string_concept_case_insensitive_view.php';

register_primitive_type_handler('string', 'str\\HandlerWithCaseInsensitiveView');

$str = 'foo bar foo bar';

echo "Working on string \"$str\"\n\n";

$ci = $str->caseInsensitive();

p('indexOf("FOO")',          $ci->indexOf("FOO"));
p('indexOf("FOO", 1)',       $ci->indexOf("FOO", 1));
p('lastIndexOf("FOO")',      $ci->lastIndexOf("FOO"));
p('lastIndexOf("FOO", -10)', $ci->lastIndexOf("FOO", -10));

p('contains("BAR")',         $ci->contains("BAR"));
p('contains("BAZ")',         $ci->contains("BAZ"));
p('startsWith("FOO")',       $ci->startsWith("FOO"));
p('startsWith("BAR")',       $ci->startsWith("BAR"));
p('endsWith("BAR")',         $ci->endsWith("BAR"));
p('endsWith("FOO")',         $ci->endsWith("FOO"));

p('count("FOO")',            $ci->count("FOO"));
p('count("FOO", 1)',         $ci->count("FOO", 1));

p('replace("FOO", "BAR")',   $ci->replace("FOO", "BAR"));
p(
    'replace(["FOO" => "BAR", "BAR" => "FOO"])',
    $ci->replace(["FOO" => "BAR", "BAR" => "FOO"])
);

?>
--EXPECT--
Working on string "foo bar foo bar"

indexOf("FOO"): int(0)
indexOf("FOO", 1): int(8)
lastIndexOf("FOO"): int(8)
lastIndexOf("FOO", -10): int(0)
contains("BAR"): bool(true)
contains("BAZ"): bool(false)
startsWith("FOO"): bool(true)
startsWith("BAR"): bool(false)
endsWith("BAR"): bool(true)
endsWith("FOO"): bool(false)
count("FOO"): int(2)
count("FOO", 1): int(1)
replace("FOO", "BAR"): string(15) "BAR bar BAR bar"
replace(["FOO" => "BAR", "BAR" => "FOO"]): string(15) "FOO FOO FOO FOO"
