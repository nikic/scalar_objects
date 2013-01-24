/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2013 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author:                                                              |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

#ifndef PHP_SCALAR_OBJECTS_H
#define PHP_SCALAR_OBJECTS_H

extern zend_module_entry scalar_objects_module_entry;
#define phpext_scalar_objects_ptr &scalar_objects_module_entry

#ifdef PHP_WIN32
#	define PHP_SCALAR_OBJECTS_API __declspec(dllexport)
#elif defined(__GNUC__) && __GNUC__ >= 4
#	define PHP_SCALAR_OBJECTS_API __attribute__ ((visibility("default")))
#else
#	define PHP_SCALAR_OBJECTS_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

ZEND_MINIT_FUNCTION(scalar_objects);
ZEND_MSHUTDOWN_FUNCTION(scalar_objects);
ZEND_RINIT_FUNCTION(scalar_objects);
ZEND_RSHUTDOWN_FUNCTION(scalar_objects);
ZEND_MINFO_FUNCTION(scalar_objects);

#define SCALAR_OBJECTS_MAX_HANDLER  IS_RESOURCE
#define SCALAR_OBJECTS_NUM_HANDLERS (SCALAR_OBJECTS_MAX_HANDLER + 1)

ZEND_BEGIN_MODULE_GLOBALS(scalar_objects)
	zend_class_entry *handlers[SCALAR_OBJECTS_NUM_HANDLERS];
ZEND_END_MODULE_GLOBALS(scalar_objects)

#ifdef ZTS
#define SCALAR_OBJECTS_G(v) TSRMG(scalar_objects_globals_id, zend_scalar_objects_globals *, v)
#else
#define SCALAR_OBJECTS_G(v) (scalar_objects_globals.v)
#endif

#endif

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
