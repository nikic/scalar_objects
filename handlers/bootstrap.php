<?php

include __DIR__ . '/string.php';

register_primitive_type_handler('string', 'str\\Handler');
