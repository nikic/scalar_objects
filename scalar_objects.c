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

#define SO_EX_CV(i)     (*EX_CV_NUM(execute_data, i))
#define SO_EX_T(offset) (*EX_TMP_VAR(execute_data, offset))

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
	return zend_get_zval_ptr(op_type, node, execute_data, should_free, type TSRMLS_CC);
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

typedef struct _indirection_function {
	zend_internal_function fn;
	zend_function *fbc;        /* Handler that needs to be invoked */
} indirection_function;

static void scalar_objects_indirection_func(INTERNAL_FUNCTION_PARAMETERS)
{
	indirection_function *ind =
		(indirection_function *) EG(current_execute_data)->function_state.function;
	zend_class_entry *ce = ind->fn.scope;
	zval *obj = getThis();
	zval ***params = safe_emalloc(sizeof(zval **), ZEND_NUM_ARGS() + 1, 0);
	zval *result_ptr;
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;

	params[0] = &obj;
	zend_get_parameters_array_ex(ZEND_NUM_ARGS(), &params[1]);

	fci.size = sizeof(fci);
	/* fci.function_table = &ce->function_table; */
	MAKE_STD_ZVAL(fci.function_name);
	ZVAL_STRING(fci.function_name, ind->fn.function_name, 0);
	fci.symbol_table = NULL;
	fci.retval_ptr_ptr = &result_ptr;
	fci.param_count = ZEND_NUM_ARGS() + 1;
	fci.params = params;
	fci.object_ptr = NULL;
	fci.no_separation = 1;

	fcc.initialized = 1;
	fcc.calling_scope = ce;
	fcc.called_scope = EG(called_scope) && instanceof_function(EG(called_scope), ce TSRMLS_CC)
		? EG(called_scope) : ce;
	fcc.object_ptr = NULL;
	fcc.function_handler = ind->fbc;

	if (zend_call_function(&fci, &fcc TSRMLS_CC) == SUCCESS && result_ptr) {
#ifdef ZEND_ENGINE_2_6
		RETVAL_ZVAL_FAST(result_ptr);
		zval_ptr_dtor(&result_ptr);
#else
		if (Z_ISREF_P(result_ptr) || Z_REFCOUNT_P(result_ptr) > 1) {
			RETVAL_ZVAL(result_ptr, 1, 1);
		} else {
			RETVAL_ZVAL(result_ptr, 0, 1);
		}
#endif
	}

	zval_ptr_dtor(&fci.function_name);
	efree(params);
	efree(ind);
}

static zend_function *scalar_objects_get_indirection_func(
	zend_class_entry *ce, zend_function *fbc, const char *method_name, int method_len
) {
	indirection_function *ind = emalloc(sizeof(indirection_function));

	ind->fn.type = ZEND_INTERNAL_FUNCTION;
	ind->fn.module = (ce->type == ZEND_INTERNAL_CLASS) ? ce->info.internal.module : NULL;
	ind->fn.handler = scalar_objects_indirection_func;
	ind->fn.scope = ce;
	ind->fn.fn_flags = ZEND_ACC_CALL_VIA_HANDLER;
	ind->fn.function_name = estrndup(method_name, method_len);

	ind->fbc = fbc;
	if (fbc->common.num_args > 1) {
		ind->fn.arg_info = &fbc->common.arg_info[1];
		ind->fn.num_args = fbc->common.num_args - 1;
	} else {
		ind->fn.arg_info = NULL;
		ind->fn.num_args = 0;
	}

	return (zend_function *) ind;
}

static int scalar_objects_method_call_handler(ZEND_OPCODE_HANDLER_ARGS)
{
	zend_op *opline = execute_data->opline;
	zend_free_op free_op1, free_op2;
	zval *obj, *method;
	zend_class_entry *ce;
	zend_function *fbc;

	/* First we fetch the ops without refcount changes or errors. Then we check whether we want
	 * to handle this opcode ourselves or fall back to the original opcode. Only once we know for
	 * certain that we will not fall back the ops are fetched for real. */
	obj = get_object_zval_ptr_safe(opline->op1_type, &opline->op1, execute_data TSRMLS_CC);
	method = get_zval_ptr_safe(opline->op2_type, &opline->op2, execute_data);

	if (!obj || Z_TYPE_P(obj) == IS_OBJECT || Z_TYPE_P(method) != IS_STRING) {
		return ZEND_USER_OPCODE_DISPATCH;
	}

	ce = SCALAR_OBJECTS_G(handlers)[Z_TYPE_P(obj)];
	if (!ce) {
		zend_error(E_ERROR, "Call to a member function %s() on a non-object", Z_STRVAL_P(method));
	}

	if (ce->get_static_method) {
		fbc = ce->get_static_method(ce, Z_STRVAL_P(method), Z_STRLEN_P(method) TSRMLS_CC);
	} else {
		fbc = zend_std_get_static_method(
			ce, Z_STRVAL_P(method), Z_STRLEN_P(method),
			opline->op2_type == IS_CONST ? opline->op2.literal + 1 : NULL TSRMLS_CC
		);
	}

	if (!fbc) {
		zend_error(E_ERROR, "Call to undefined method %s::%s()", ce->name, Z_STRVAL_P(method));
	}

	method = get_zval_ptr_real(
		opline->op2_type, &opline->op2, execute_data, &free_op2, BP_VAR_R TSRMLS_CC
	);
	obj = get_object_zval_ptr_real(
		opline->op1_type, &opline->op1, execute_data, &free_op1, BP_VAR_R TSRMLS_CC
	);

	Z_ADDREF_P(obj);

	execute_data->call = execute_data->call_slots + opline->result.num;
	execute_data->call->fbc = scalar_objects_get_indirection_func(
		ce, fbc, Z_STRVAL_P(method), Z_STRLEN_P(method));

	execute_data->call->called_scope = ce;
	execute_data->call->object = obj;
	execute_data->call->is_ctor_call = 0;
#ifdef ZEND_ENGINE_2_6
	execute_data->call->num_additional_args = 0;
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
	zend_class_entry *ce = NULL;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sC", &type_str, &type_str_len, &ce) == FAILURE) {
		return;
	}

	type = get_type_from_string(type_str);
	if (type == -1) {
		return;
	}

	if (SCALAR_OBJECTS_G(handlers)[type] != NULL) {
		zend_error(E_WARNING, "Handler for type \"%s\" already exists, overriding", type_str);
	}

	SCALAR_OBJECTS_G(handlers)[type] = ce;
}

ZEND_BEGIN_ARG_INFO_EX(arginfo_register_handler, 0, 0, 2)
	ZEND_ARG_INFO(0, "type")
	ZEND_ARG_INFO(0, "class")
ZEND_END_ARG_INFO()

const zend_function_entry scalar_objects_functions[] = {
	ZEND_FE(register_primitive_type_handler, arginfo_register_handler)
	ZEND_FE_END
};

zend_module_entry scalar_objects_module_entry = {
	STANDARD_MODULE_HEADER,
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
	memset(SCALAR_OBJECTS_G(handlers), 0, SCALAR_OBJECTS_NUM_HANDLERS * sizeof(zend_class_entry *));

	return SUCCESS;
}

ZEND_RSHUTDOWN_FUNCTION(scalar_objects)
{
	return SUCCESS;
}

ZEND_MINFO_FUNCTION(scalar_objects)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "scalar-objects support", "enabled");
	php_info_print_table_end();
}

