<?php

include __DIR__ . '/string.php';

register_primitive_type_handler(6 /* IS_STRING */, 'StringHandler');
