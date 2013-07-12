Add support for method calls on primitive types in PHP
=====================================================

This extension implements the ability to register a class that handles the
method calls to a certain primitive type (string, array, ...). As such it
allows implementing APIs like `$str->length()`.

The main purpose of this repo is to provide a proof of concept implementation
that can be used to design the new APIs. The switch to object syntax for
operations on primitive types is a unique opportunity for PHP to redesign many
of its inconsistent core APIs. This repo provides the means to quickly
prototype and test new APIs as userland code. Once the APIs are figured out
it will be proposed for inclusion into PHP.

Note: The ability to register type handlers from userland is just for
prototyping. It's not something I would actually want in PHP in the end.

Registering type handlers
-------------------------

Type handlers are registered through `register_primitive_type_handler`. The
function takes a type name (like "string" or "array") and a class name. The
class is defined just like any other PHP class. The only difference is that
its `$this` variable won't be an object, but rather the primitive type that
the class operates on:

```php
<?php

class StringHandler {
    public function length() {
        return strlen($this);
    }
}

register_primitive_type_handler('string', 'StringHandler');
```

The valid type names are: `null`, `bool`, `int`, `float`, `string`, `array`
and `resource`. Not all of those will make sense in practice, but for now they
are all supported.

Implemented APIs
----------------

As already pointed out in the introduction the main purpose of this repo is
designing good APIs for the primitive types. The implemented APIs are available
in the `handlers/` folder (and are obviously work in progress). In order to
load these APIs just include the `handlers/bootstrap.php` file.

Installation
------------

### Unix

In order to compile and install the extension run the following commands:

    phpize
    ./configure --enable-scalar-objects
    make
    sudo make install

### Windows

Download a [prebuilt Windows DLL][windows_dlls] that matches your PHP version
and move it into the `ext/` directory of your PHP installation. Furthermore
you'll have to add `extension=php_scalar_objects.dll` to you `php.ini`.

Testing the extension
---------------------

The extension comes with a `run-tests.php` file to run the tests. (To see
examples of the implemented APIs you should also look in the tests.) The
script is run as follows:

    php run-tests.php -q -p php

Where `php` is the path to your PHP executable.

  [windows_dlls]: http://windows.php.net/downloads/pecl/snaps/scalar_objects/20130301/
