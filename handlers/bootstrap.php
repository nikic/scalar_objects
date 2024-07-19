<?php

include __DIR__ . '/string.php';
include __DIR__ . '/array.php';

register_primitive_type_handler('string', 'str\\Handler');
register_primitive_type_handler('array', 'arr\\Handler');
