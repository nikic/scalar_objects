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

#if ZEND_MODULE_API_NO >= 20121204
#define ZEND_ENGINE_2_5
#endif

#if ZEND_MODULE_API_NO >= 20131226
#define ZEND_ENGINE_2_6
#endif

#if ZEND_MODULE_API_NO >= 20160303
#define ZEND_ENGINE_3_1
#endif

#ifdef ZEND_ENGINE_3
typedef size_t strlen_t;
# define EX_LITERAL(op) EX_CONSTANT(op)
# define SO_THIS (Z_OBJ(EX(This)) ? &EX(This) : NULL)
# define FREE_OP(should_free) \
	if (should_free) { \
		zval_ptr_dtor_nogc(should_free); \
	}
# define FREE_OP_IF_VAR(should_free) FREE_OP(should_free)
#else
typedef int strlen_t;
# define Z_STR_P(zv) Z_STRVAL_P(zv), Z_STRLEN_P(zv)
# define Z_TRY_ADDREF_P(zv) Z_ADDREF_P(zv)
# define ZVAL_DEREF(zv)

# define EX_LITERAL(op) (op).literal
# define SO_THIS EG(This)
# define FREE_OP(should_free)                                           \
    if (should_free.var) {                                             \
        if ((zend_uintptr_t)should_free.var & 1L) {                    \
            zval_dtor((zval*)((zend_uintptr_t)should_free.var & ~1L)); \
        } else {                                                       \
            zval_ptr_dtor(&should_free.var);                           \
        }                                                              \
    }

# define FREE_OP_IF_VAR(should_free)                                                 \
    if (should_free.var != NULL && (((zend_uintptr_t)should_free.var & 1L) == 0)) { \
        zval_ptr_dtor(&should_free.var);                                            \
    }
#endif

#ifdef ZEND_ENGINE_2_5
#define SO_EX_CV(i)     (*EX_CV_NUM(execute_data, i))
#define SO_EX_T(offset) (*EX_TMP_VAR(execute_data, offset))
#else
#define SO_EX_CV(i)     (execute_data)->CVs[(i)]
#define SO_EX_T(offset) (*(temp_variable *) ((char *) execute_data->Ts + offset))
#endif


static zval *get_zval_ptr_safe(
	int op_type, const znode_op *node, const zend_execute_data *execute_data
) {
#ifdef ZEND_ENGINE_3
	switch (op_type) {
		case IS_CONST:
			return EX_CONSTANT(*node);
		case IS_CV:
		case IS_TMP_VAR:
		case IS_VAR:
		{
			zval *zv = EX_VAR(node->var);
			ZVAL_DEREF(zv);
			return !Z_ISUNDEF_P(zv) ? zv : NULL;
		}
		default:
			return NULL;
	}
#else
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
#endif
}

static zval *get_object_zval_ptr_safe(
	int op_type, const znode_op *node, zend_execute_data *execute_data TSRMLS_DC
) {
	if (op_type == IS_UNUSED) {
		return SO_THIS;
	} else {
		return get_zval_ptr_safe(op_type, node, execute_data);
	}
}

static zval *get_zval_ptr_real(
	int op_type, const znode_op *node, const zend_execute_data *execute_data,
	zend_free_op *should_free, int type TSRMLS_DC
) {
#ifdef ZEND_ENGINE_2_5
	zval *zv = zend_get_zval_ptr(op_type, node, execute_data, should_free, type TSRMLS_CC);
#else
	zval *zv = zend_get_zval_ptr(op_type, node, execute_data->Ts, should_free, type TSRMLS_CC);
#endif
	ZVAL_DEREF(zv);
	return zv;
}

static zval *get_object_zval_ptr_real(
	int op_type, const znode_op *node, zend_execute_data *execute_data,
	zend_free_op *should_free, int type TSRMLS_DC
) {
	if (op_type == IS_UNUSED) {
		if (!SO_THIS) {
			zend_error(E_ERROR, "Using $this when not in object context");
		}

#ifdef ZEND_ENGINE_3
		should_free = NULL;
#else
		should_free->var = 0;
#endif
		return SO_THIS;
	} else {
		return get_zval_ptr_real(op_type, node, execute_data, should_free, type TSRMLS_CC);
	}
}

typedef struct _indirection_function {
	zend_internal_function fn;
	zend_function *fbc;        /* Handler that needs to be invoked */
#ifdef ZEND_ENGINE_3
	zval obj;
#endif
} indirection_function;

static void scalar_objects_indirection_func(INTERNAL_FUNCTION_PARAMETERS)
{
#ifdef ZEND_ENGINE_3
	indirection_function *ind = (indirection_function *) execute_data->func;
	zval *obj = &ind->obj;
	zval *params = safe_emalloc(sizeof(zval), ZEND_NUM_ARGS() + 1, 0);
	zval result;
#else
	indirection_function *ind =
		(indirection_function *) EG(current_execute_data)->function_state.function;
	zval *obj = getThis();
	zval ***params = safe_emalloc(sizeof(zval **), ZEND_NUM_ARGS() + 1, 0);
	zval *result;
#endif

	zend_class_entry *ce = ind->fn.scope;
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;

	fci.size = sizeof(fci);
#ifndef ZEND_ENGINE_3_1
	fci.symbol_table = NULL;
#endif
	fci.param_count = ZEND_NUM_ARGS() + 1;
	fci.params = params;
	fci.no_separation = 1;

	fcc.initialized = 1;
	fcc.calling_scope = ce;
	fcc.function_handler = ind->fbc;

	zend_get_parameters_array_ex(ZEND_NUM_ARGS(), &params[1]);

#ifdef ZEND_ENGINE_3
	ZVAL_COPY_VALUE(&params[0], obj);
	ZVAL_STR(&fci.function_name, ind->fn.function_name);
	fci.retval = &result;
	fci.object = NULL;

	fcc.object = NULL;
	fcc.called_scope = EX(called_scope) && instanceof_function(EX(called_scope), ce TSRMLS_CC)
		? EX(called_scope) : ce;
#else
	params[0] = &obj;
	MAKE_STD_ZVAL(fci.function_name);
	ZVAL_STRING(fci.function_name, ind->fn.function_name, 0);
	fci.retval_ptr_ptr = &result;
	fci.object_ptr = NULL;

	fcc.object_ptr = NULL;
	fcc.called_scope = EG(called_scope) && instanceof_function(EG(called_scope), ce TSRMLS_CC)
		? EG(called_scope) : ce;
#endif

#ifdef ZEND_ENGINE_3
	if (zend_call_function(&fci, &fcc TSRMLS_CC) == SUCCESS && !Z_ISUNDEF(result)) {
		ZVAL_COPY_VALUE(return_value, &result);
	}
	zval_ptr_dtor(obj);
	execute_data->func = NULL;
#else
	if (zend_call_function(&fci, &fcc TSRMLS_CC) == SUCCESS && result) {
# ifdef ZEND_ENGINE_2_6
		zval_ptr_dtor(&return_value);
		*return_value_ptr = result;
# else
		if (Z_ISREF_P(result)) {
			zval_ptr_dtor(&return_value);
			*return_value_ptr = result;
		} else if (Z_REFCOUNT_P(result) > 1) {
			RETVAL_ZVAL(result, 1, 1);
		} else {
			RETVAL_ZVAL(result, 0, 1);
		}
# endif
	}
#endif

	zval_ptr_dtor(&fci.function_name);
	efree(params);
	efree(ind);
}

static zend_function *scalar_objects_get_indirection_func(
	zend_class_entry *ce, zend_function *fbc, zval *method, zval *obj
) {
	indirection_function *ind = emalloc(sizeof(indirection_function));
	zend_function *fn = (zend_function *) &ind->fn;
	long keep_flags = ZEND_ACC_RETURN_REFERENCE;
#ifdef ZEND_ENGINE_2_6
	keep_flags |= ZEND_ACC_VARIADIC;
#endif

	ind->fn.type = ZEND_INTERNAL_FUNCTION;
	ind->fn.module = (ce->type == ZEND_INTERNAL_CLASS) ? ce->info.internal.module : NULL;
	ind->fn.handler = scalar_objects_indirection_func;
	ind->fn.scope = ce;
	ind->fn.fn_flags = ZEND_ACC_CALL_VIA_HANDLER | (fbc->common.fn_flags & keep_flags);
	ind->fn.num_args = fbc->common.num_args - 1;

	ind->fbc = fbc;
	if (fbc->common.arg_info) {
		fn->common.arg_info = &fbc->common.arg_info[1];
	} else {
		fn->common.arg_info = NULL;
	}

#ifdef ZEND_ENGINE_3
	ind->fn.function_name = zend_string_copy(Z_STR_P(method));
	zend_set_function_arg_flags(fn);
	ZVAL_COPY_VALUE(&ind->obj, obj);
#else
	ind->fn.function_name = estrndup(Z_STRVAL_P(method), Z_STRLEN_P(method));
#endif

	return fn;
}

static int scalar_objects_method_call_handler(zend_execute_data *execute_data TSRMLS_DC)
{
	const zend_op *opline = execute_data->opline;
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
		return ZEND_USER_OPCODE_DISPATCH;
	}

	if (ce->get_static_method) {
		fbc = ce->get_static_method(ce, Z_STR_P(method) TSRMLS_CC);
	} else {
		fbc = zend_std_get_static_method(
			ce, Z_STR_P(method),
			opline->op2_type == IS_CONST ? EX_LITERAL(opline->op2) + 1 : NULL TSRMLS_CC
		);
	}

	method = get_zval_ptr_real(
		opline->op2_type, &opline->op2, execute_data, &free_op2, BP_VAR_R TSRMLS_CC
	);
	obj = get_object_zval_ptr_real(
		opline->op1_type, &opline->op1, execute_data, &free_op1, BP_VAR_R TSRMLS_CC
	);

	if (!fbc) {
#ifdef ZEND_ENGINE_3
		if (!EG(exception)) {
			zend_throw_error(NULL, "Call to undefined method %s::%s()",
				ZSTR_VAL(ce->name), Z_STRVAL_P(method));
		}
		FREE_OP(free_op2);
		FREE_OP_IF_VAR(free_op1);
		return ZEND_USER_OPCODE_CONTINUE;
#else
		zend_error(E_ERROR, "Call to undefined method %s::%s()", ce->name, Z_STRVAL_P(method));
#endif
	}

	Z_TRY_ADDREF_P(obj);
	fbc = scalar_objects_get_indirection_func(ce, fbc, method, obj);

#ifdef ZEND_ENGINE_3
	{
		zend_execute_data *call = zend_vm_stack_push_call_frame(
			ZEND_CALL_NESTED_FUNCTION, fbc, opline->extended_value, ce, NULL);
		call->prev_execute_data = EX(call);
		EX(call) = call;
	}
#elif defined(ZEND_ENGINE_2_5)
	execute_data->call = execute_data->call_slots + opline->result.num;
	execute_data->call->fbc = fbc;

	execute_data->call->called_scope = ce;
	execute_data->call->object = obj;
	execute_data->call->is_ctor_call = 0;
# ifdef ZEND_ENGINE_2_6
	execute_data->call->num_additional_args = 0;
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
#ifdef ZEND_ENGINE_3
		return IS_TRUE;
#else
		return IS_BOOL;
#endif
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
	strlen_t type_str_len;
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
#ifdef ZEND_ENGINE_3
	if (type == IS_TRUE) {
		SCALAR_OBJECTS_G(handlers)[IS_FALSE] = ce;
	}
#endif
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

