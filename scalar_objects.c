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
  | Author: Nikita Popov <nikic@php.net>                                 |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "zend_closures.h"
#include "php_scalar_objects.h"

ZEND_DECLARE_MODULE_GLOBALS(scalar_objects)

#ifdef COMPILE_DL_SCALAR_OBJECTS
ZEND_GET_MODULE(scalar_objects)
#endif

#ifdef ZEND_ENGINE_2_5
#define SO_EX_CV(i)     (*EX_CV_NUM(execute_data, i))
#define SO_EX_T(offset) (*EX_TMP_VAR(execute_data, offset))
#else
#define SO_EX_CV(i)     (execute_data)->CVs[(i)]
#define SO_EX_T(offset) (*(temp_variable *) ((char *) execute_data->Ts + offset))
#endif

#define FREE_OP(should_free)                                           \
    if (should_free.var) {                                             \
        if ((zend_uintptr_t)should_free.var & 1L) {                    \
            zval_dtor((zval*)((zend_uintptr_t)should_free.var & ~1L)); \
        } else {                                                       \
            zval_ptr_dtor(&should_free.var);                           \
        }                                                              \
    }

#define FREE_OP_IF_VAR(should_free)                                                 \
    if (should_free.var != NULL && (((zend_uintptr_t)should_free.var & 1L) == 0)) { \
        zval_ptr_dtor(&should_free.var);                                            \
    }

#ifdef ZEND_ENGINE_2_6 
#define SHOULD_SEND_ARG_BY_REF(zf, arg_num) \
        ((zf)->common.arg_info && \
        (arg_num <= (zf)->common.num_args \
                ? ((zf)->common.arg_info[arg_num-1].pass_by_reference & (ZEND_SEND_BY_REF|ZEND_SEND_PREFER_REF)) \
                : ((zf)->common.fn_flags & ZEND_ACC_VARIADIC) \
                        ? ((zf)->common.arg_info[(zf)->common.num_args-1].pass_by_reference & (ZEND_SEND_BY_REF|ZEND_SEND_PREFER_REF)) : 0))
#else
#define SHOULD_SEND_ARG_BY_REF(zf, arg_num) \
        ((zf)->common.arg_info && \
        (arg_num <= (zf)->common.num_args \
                ? ((zf)->common.arg_info[arg_num-1].pass_by_reference & (ZEND_SEND_BY_REF|ZEND_SEND_PREFER_REF)) \
                : ((zf)->common.fn_flags & (ZEND_ACC_PASS_REST_BY_REFERENCE|ZEND_ACC_PASS_REST_PREFER_REF)) \
	))
#endif

static zval *get_zval_ptr_safe(
	int op_type, const znode_op *node, const zend_execute_data *execute_data
) {
	switch (op_type) {
		case IS_CONST:
			return node->zv;
		case IS_TMP_VAR:
			return &SO_EX_T(node->var).tmp_var;
		case IS_VAR:
			return SO_EX_T(node->var).var.ptr;
		case IS_CV: {
			zval **tmp = SO_EX_CV(node->constant);
			return tmp ? *tmp : NULL;
		}
		default:
			return NULL;
	}
}

static zval *get_object_zval_ptr_safe(
	int op_type, const znode_op *node, const zend_execute_data *execute_data TSRMLS_DC
) {
	if (op_type == IS_UNUSED) {
		return EG(This);
	} else {
		return get_zval_ptr_safe(op_type, node, execute_data);
	}
}

static zval *get_zval_ptr_real(
	int op_type, const znode_op *node, const zend_execute_data *execute_data,
	zend_free_op *should_free, int type TSRMLS_DC
) {
#ifdef ZEND_ENGINE_2_5
	return zend_get_zval_ptr(op_type, node, execute_data, should_free, type TSRMLS_CC);
#else
	return zend_get_zval_ptr(op_type, node, execute_data->Ts, should_free, type TSRMLS_CC);
#endif
}

static zval *get_object_zval_ptr_real(
	int op_type, const znode_op *node, const zend_execute_data *execute_data,
	zend_free_op *should_free, int type TSRMLS_DC
) {
	if (op_type == IS_UNUSED) {
		if (!EG(This)) {
			zend_error(E_ERROR, "Using $this when not in object context");
		}

		should_free->var = 0;
		return EG(This);
	} else {
		return get_zval_ptr_real(op_type, node, execute_data, should_free, type TSRMLS_CC);
	}
}

static zval **get_zval_ptr_ptr_real(
	int op_type, const znode_op *node, const zend_execute_data *execute_data,
	zend_free_op *should_free, int type TSRMLS_DC
) {
#ifdef ZEND_ENGINE_2_5
	return zend_get_zval_ptr_ptr(op_type, node, execute_data, should_free, type TSRMLS_CC);
#else
	return zend_get_zval_ptr_ptr(op_type, node, execute_data->Ts, should_free, type TSRMLS_CC);
#endif
}

static zval **get_object_zval_ptr_ptr_real(
	int op_type, const znode_op *node, const zend_execute_data *execute_data,
	zend_free_op *should_free, int type TSRMLS_DC
) {
	if (op_type == IS_UNUSED) {
		if (!EG(This)) {
			zend_error(E_ERROR, "Using $this when not in object context");
		}

		should_free->var = 0;
		return &EG(This);
	} else {
		return get_zval_ptr_ptr_real(op_type, node, execute_data, should_free, type TSRMLS_CC);
	}
}

static zval *get_object_zval_with_ref_separation_attempt(
	int op_type, const znode_op *node, const zend_execute_data *execute_data,
	zend_free_op *should_free TSRMLS_DC
) {
	/* As of PHP 5.6 we can no longer support APIs that modify the object
	 * for IS_VAR (at least not in any way that I can see and that wouldn't
	 * require rewriting FETCH opcode logic). Just supporting it for IS_CV
	 * would make little sense, so we drop the by-ref thing altogether. */
#ifndef ZEND_ENGINE_2_6
	zval **obj_ptr = get_object_zval_ptr_ptr_real(
		op_type, node, execute_data, should_free, BP_VAR_R TSRMLS_CC
	);

	if (obj_ptr) {
		SEPARATE_ZVAL_TO_MAKE_IS_REF(obj_ptr);
		return *obj_ptr;
	}
#endif

	return get_object_zval_ptr_real(
		op_type, node, execute_data, should_free, BP_VAR_R TSRMLS_CC
	);
}

ZEND_FUNCTION(primitive_indirection)
{
	zval *handler, *retval_ptr;
	zval ***args = NULL;
	zval ***params = NULL;
	int i, num_args = 0;
	zend_function *active_function = EG(current_execute_data)->function_state.function;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "*", &args, &num_args) == FAILURE) {
		return;
	}

	/* Params = [object, ...args] */
	params = safe_emalloc(sizeof(zval **), num_args + 1, 0);
	params[0] = &EG(current_execute_data)->object;
	for (i = 0; i < num_args; i++) {
		params[i + 1] = args[i];
	}

	handler = SCALAR_OBJECTS_G(handlers)[Z_TYPE_P(EG(current_execute_data)->object)];
	{
		zend_fcall_info fci;
		fci.size = sizeof(fci);
		fci.function_table = NULL;
		fci.object_ptr = handler;
		MAKE_STD_ZVAL(fci.function_name);
		ZVAL_STRINGL(fci.function_name, active_function->common.function_name, strlen(active_function->common.function_name), 1);
		fci.retval_ptr_ptr = &retval_ptr;
		fci.param_count = num_args + 1;
		fci.params = params;
		fci.no_separation = (zend_bool) 1;
		fci.symbol_table = NULL;

		if (SUCCESS == zend_call_function(&fci, NULL TSRMLS_CC)) {
			if (retval_ptr) {
				COPY_PZVAL_TO_ZVAL(*return_value, retval_ptr);
			}
		} else {
			RETVAL_FALSE;
		}
	}
	efree(params);
	efree(args);
}

static int scalar_objects_method_call_handler(ZEND_OPCODE_HANDLER_ARGS)
{
	zend_op *opline = execute_data->opline;
	zend_free_op free_op1, free_op2;
	zval *obj, *method, *handler;
	zend_function *fbc;
	zend_class_entry *ce;

	/* First we fetch the ops without refcount changes or errors. Then we check whether we want
	 * to handle this opcode ourselves or fall back to the original opcode. Only once we know for
	 * certain that we will not fall back the ops are fetched for real. */
	obj = get_object_zval_ptr_safe(opline->op1_type, &opline->op1, execute_data TSRMLS_CC);
	method = get_zval_ptr_safe(opline->op2_type, &opline->op2, execute_data);

	if (!obj || Z_TYPE_P(obj) == IS_OBJECT || Z_TYPE_P(method) != IS_STRING) {
		return ZEND_USER_OPCODE_DISPATCH;
	}

	handler = SCALAR_OBJECTS_G(handlers)[Z_TYPE_P(obj)];
	if (!handler) {
		zend_error(E_ERROR, "Call to a member function %s() on a non-object", Z_STRVAL_P(method));
	}

	Z_ADDREF_P(handler);
	ce = Z_OBJCE_P(handler);

        /* For PHP 5.6, we can set num_additional_args and push object into the stack. For older
         * versions, we must use an indirection over a handler function */
#if ZEND_MODULE_API_NO < 20131226
	fbc = (zend_function*)emalloc(sizeof(zend_function));
	fbc->type = ZEND_INTERNAL_FUNCTION;
	fbc->internal_function.fn_flags = ZEND_ACC_PUBLIC | ZEND_ACC_CALL_VIA_HANDLER;
	fbc->internal_function.handler = ZEND_FN(primitive_indirection);
	fbc->internal_function.module = 0;
	fbc->internal_function.scope = EG(scope);
	fbc->internal_function.function_name = estrndup(Z_STRVAL_P(method), Z_STRLEN_P(method));
#else
	fbc = Z_OBJ_HT_P(handler)->get_method(
		&handler, Z_STRVAL_P(method), Z_STRLEN_P(method),
		opline->op2_type == IS_CONST ? opline->op2.literal + 1 : NULL TSRMLS_CC
	);
	if (!fbc) {
		zend_error(E_ERROR, "Call to undefined method %s::%s()", ce->name, Z_STRVAL_P(method));
	}
#endif

	method = get_zval_ptr_real(
		opline->op2_type, &opline->op2, execute_data, &free_op2, BP_VAR_R TSRMLS_CC
	);
	obj = get_object_zval_with_ref_separation_attempt(
		opline->op1_type, &opline->op1, execute_data, &free_op1 TSRMLS_CC
	);
	Z_ADDREF_P(obj);

#ifdef ZEND_ENGINE_2_5
        execute_data->call = execute_data->call_slots + opline->result.num;
        execute_data->call->fbc = fbc;
        execute_data->call->called_scope = ce;
        execute_data->call->is_ctor_call = 0;

# ifdef ZEND_ENGINE_2_6
        execute_data->call->object = handler;
        execute_data->call->num_additional_args = 1;

        /* Pass $self */
        ZEND_VM_STACK_GROW_IF_NEEDED(1);
        if (SHOULD_SEND_ARG_BY_REF(fbc, 1)) {
                Z_SET_ISREF_P(obj);
        }
        zend_vm_stack_push(obj TSRMLS_CC);
# else
	execute_data->call->object = obj;
# endif
#else
        zend_ptr_stack_3_push(&EG(arg_types_stack), execute_data->fbc, execute_data->object, execute_data->called_scope);

        execute_data->fbc = fbc;
        execute_data->called_scope = ce;
        execute_data->object = obj;
#endif

	FREE_OP(free_op2);
	FREE_OP_IF_VAR(free_op1);

	execute_data->opline++;
	return ZEND_USER_OPCODE_CONTINUE;
}

static int get_type_from_string(const char *str) {
	/* Not all of these types will make sense in practice, but for now
	 * we support all of them. */
	if (!strcasecmp(str, "null")) {
		return IS_NULL;
	} else if (!strcasecmp(str, "bool")) {
		return IS_BOOL;
	} else if (!strcasecmp(str, "int")) {
		return IS_LONG;
	} else if (!strcasecmp(str, "float")) {
		return IS_DOUBLE;
	} else if (!strcasecmp(str, "string")) {
		return IS_STRING;
	} else if (!strcasecmp(str, "array")) {
		return IS_ARRAY;
	} else if (!strcasecmp(str, "resource")) {
		return IS_RESOURCE;
	} else {
		zend_error(E_WARNING, "Invalid type \"%s\" specified", str);
		return -1;
	}
}

ZEND_FUNCTION(register_primitive_type_handler) {
	char *type_str;
	int type_str_len;
	int type;
	zval *arg;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sz", &type_str, &type_str_len, &arg) == FAILURE) {
		return;
	}

	type = get_type_from_string(type_str);
	if (type == -1) {
		return;
	}

	if (SCALAR_OBJECTS_G(handlers)[type] != NULL) {
		zend_error(E_WARNING, "Handler for type \"%s\" already exists, overriding", type_str);
		zval_dtor(SCALAR_OBJECTS_G(handlers)[type]);
	}

	if (arg->type == IS_STRING) {
		zend_class_entry **pce;
		zend_function *constructor;
		zval *instance;

		if (zend_lookup_class(Z_STRVAL_P(arg), Z_STRLEN_P(arg), &pce TSRMLS_CC) == FAILURE) {
			zend_error(E_WARNING, "Class %s not found", Z_STRVAL_P(arg));
			return;
		}
	        ALLOC_ZVAL(instance);
		object_init_ex(instance, *pce);
		INIT_PZVAL(instance);

		if (Z_OBJ_HT_P(instance)->get_method == NULL) {
			zend_error(E_WARNING, "Object does not support method calls");
			zval_dtor(instance);
			return;
		}

		/* Invoke constructor if existant */
		constructor = Z_OBJ_HT_P(instance)->get_constructor(instance TSRMLS_CC);
		if (constructor != NULL) {
			zval *retval;
			zend_call_method(
				&instance, *pce,
				&constructor, ZEND_CONSTRUCTOR_FUNC_NAME, sizeof(ZEND_CONSTRUCTOR_FUNC_NAME) - 1,
				&retval,
				0, NULL, NULL
				TSRMLS_CC
			);
			if (retval != NULL) {
				zval_dtor(retval);
			}
			if (EG(exception) != NULL) {
				zval_dtor(instance);
				return;
			}
		}
		SCALAR_OBJECTS_G(handlers)[type] = instance;
	} else if (arg->type == IS_OBJECT) {
		ALLOC_ZVAL(SCALAR_OBJECTS_G(handlers)[type]);
		*SCALAR_OBJECTS_G(handlers)[type] = *arg;
		zval_copy_ctor(SCALAR_OBJECTS_G(handlers)[type]);
		INIT_PZVAL(SCALAR_OBJECTS_G(handlers)[type]);
	} else {
		zend_error(E_WARNING, "Expecting either a string or an object, %s given", zend_zval_type_name(arg));
		return;
	}
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_register_handler, 0, 0, 2)
	ZEND_ARG_INFO(0, "type")
	ZEND_ARG_INFO(0, "arg")
ZEND_END_ARG_INFO()

const zend_function_entry scalar_objects_functions[] = {
	ZEND_FE(register_primitive_type_handler, arginfo_register_handler)
	ZEND_FE_END
};

zend_module_entry scalar_objects_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"scalar_objects",
	scalar_objects_functions,
	ZEND_MINIT(scalar_objects),
	ZEND_MSHUTDOWN(scalar_objects),
	ZEND_RINIT(scalar_objects),	
	ZEND_RSHUTDOWN(scalar_objects),
	ZEND_MINFO(scalar_objects),
	"0.2",
	ZEND_MODULE_GLOBALS(scalar_objects),
	NULL,
	NULL,
	NULL,
	STANDARD_MODULE_PROPERTIES_EX
};

ZEND_MINIT_FUNCTION(scalar_objects) {
	zend_set_user_opcode_handler(ZEND_INIT_METHOD_CALL, scalar_objects_method_call_handler);
	return SUCCESS;
}

ZEND_MSHUTDOWN_FUNCTION(scalar_objects)
{
	return SUCCESS;
}

ZEND_RINIT_FUNCTION(scalar_objects)
{
	memset(SCALAR_OBJECTS_G(handlers), 0, SCALAR_OBJECTS_NUM_HANDLERS * sizeof(zval *));
	return SUCCESS;
}

ZEND_RSHUTDOWN_FUNCTION(scalar_objects)
{
	int i;
	for (i = 0; i < SCALAR_OBJECTS_NUM_HANDLERS; i++) {
		if (SCALAR_OBJECTS_G(handlers)[i]) {
			zval_dtor(SCALAR_OBJECTS_G(handlers)[i]);
		}
	}
	return SUCCESS;
}

ZEND_MINFO_FUNCTION(scalar_objects)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "scalar-objects support", "enabled");
	php_info_print_table_end();
}
