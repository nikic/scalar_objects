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
class should contain static methods, which receive the primitive type as the
first parameter:

```php
<?php

class StringHandler {
    public static function length($self) {
        return strlen($self);
    }

    public static function startsWith($self, $other) {
        return strpos($self, $other) === 0;
    }
}

register_primitive_type_handler('string', 'StringHandler');

$string = "abc";
var_dump($string->length()); // int(3)
var_dump($string->startsWith("a")); // bool(true)
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

The master branch supports PHP version 7.0 to 8.1. The extension is incompatible
with the JIT compiler.

### Unix

In order to compile and install the extension run the following commands:

    phpize
    ./configure
    make
    sudo make install

### Windows

Download a [prebuilt Windows DLL][windows_dlls] that matches your PHP version
and move it into the `ext/` directory of your PHP installation. Furthermore
you'll have to add `extension=php_scalar_objects.dll` to your `php.ini`.

Testing the extension
---------------------

The extension comes with a `run-tests.php` file to run the tests. (To see
examples of the implemented APIs you should also look in the tests.) The
script is run as follows:

    php run-tests.php -q -p php

Where `php` is the path to your PHP executable.

Limitations
-----------

This extension has a number of limitations:

 * Due to technical limitations, it is not possible to create *mutable* APIs for primitive
   types. Modifying `$self` within the methods is not possible (or rather, will have no effect,
   as you'd just be changing a copy).

  [windows_dlls]: http://windows.php.net/downloads/pecl/snaps/scalar_objects/20170414/
  [version_0_2]: https://github.com/nikic/scalar_objects/tree/0.2
  [version_0_1]: https://github.com/nikic/scalar_objects/tree/0.1
