<?php

require __DIR__ . '/../handlers/bootstrap.php';

function p($name, $result) {
    echo $name, ':', strlen($name) > 50 ? "\n" : ' ';
    var_dump($result);
}
