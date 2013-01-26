<?php

$HANDLER_DIR = __DIR__ . '/../handlers';

function p($name, $result) {
    echo $name, ':', strlen($name) > 50 ? "\n" : ' ';
    var_dump($result);
}
