<?php

require __DIR__ . '/../handlers/bootstrap.php';

function p($name, $result) {
    echo $name, ': ';
    var_dump($result);
}
