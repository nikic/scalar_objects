# Docker images

Use `nikic/scalar_objects` with docker

# Steps

1. Build your image
```shell
$ docker build -t php-scalar-objects ./8.1-alpine
```

2. Create a docker file for your projects

Example `Dockerfile`
```dockerfile
FROM php-scalar-objects
COPY . /usr/src/myapp
WORKDIR /usr/src/myapp
CMD [ "php", "./index.php" ]
```

Example `index.php`
```php
<?php

class StringHandler
{
    public static function length($self)
    {
        return strlen($self);
    }

    public static function startsWith($self, $other)
    {
        return strpos($self, $other) === 0;
    }
}

register_primitive_type_handler('string', 'StringHandler');

$string = "abc";
var_dump($string->length()); // int(3)
var_dump($string->startsWith("a")); // bool(true)
```

3. Build your project

```shell
$ docker build -t my-php-app .
```

> Execute this in the same directory where your app is

4. Run your app

```shell
$ docker run -it --rm --name my-php-running-app my-php-app:latest
```
