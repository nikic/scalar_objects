dnl $Id$
dnl config.m4 for extension scalar_objects

PHP_ARG_ENABLE(scalar-objects, whether to enable scalar_objects support,
Make sure that the comment is aligned:
[  --enable-scalar-objects           Enable scalar-objects support])

if test "$PHP_SCALAR_OBJECTS" != "no"; then
  PHP_NEW_EXTENSION(scalar_objects, scalar_objects.c, $ext_shared)
fi
