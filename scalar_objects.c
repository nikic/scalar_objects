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

typedef size_t strlen_t;
#if PHP_VERSION_ID >= 70300
# define EX_LITERAL(opline, op) RT_CONSTANT(opline, op)
#else
# define EX_LITERAL(opline, op) EX_CONSTANT(op)
#endif
#define SO_THIS (Z_OBJ(EX(This)) ? &EX(This) : NULL)
#define FREE_OP(should_free) \
	if (should_free) { \
		zval_ptr_dtor_nogc(should_free); \
	}
#define FREE_OP_IF_VAR(should_free) FREE_OP(should_free)

#define SO_EX_CV(i)     (*EX_CV_NUM(execute_data, i))
#define SO_EX_T(offset) (*EX_TMP_VAR(execute_data, offset))


static zval *get_zval_ptr_safe(
	const zend_op *opline, int op_type, const znode_op *node,
	const zend_execute_data *execute_data
) {
	switch (op_type) {
		case IS_CONST:
			return EX_LITERAL(opline, *node);
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
}

static zval *get_object_zval_ptr_safe(
	const zend_op *opline, int op_type, const znode_op *node, zend_execute_data *execute_data
) {
	if (op_type == IS_UNUSED) {
		return SO_THIS;
	} else {
		return get_zval_ptr_safe(opline, op_type, node, execute_data);
	}
}

static zval *get_zval_ptr_real(
	const zend_op *opline, int op_type, const znode_op *node,
	const zend_execute_data *execute_data, zend_free_op *should_free, int type
) {
#if PHP_VERSION_ID >= 70300
	zval *zv = zend_get_zval_ptr(opline, op_type, node, execute_data, should_free, type);
#else
	zval *zv = zend_get_zval_ptr(op_type, node, execute_data, should_free, type);
#endif
	ZVAL_DEREF(zv);
	return zv;
}

static zval *get_object_zval_ptr_real(
	const zend_op *opline, int op_type, const znode_op *node, zend_execute_data *execute_data,
	zend_free_op *should_free, int type
) {
	if (op_type == IS_UNUSED) {
		if (!SO_THIS) {
			zend_error(E_ERROR, "Using $this when not in object context");
		}

		should_free = NULL;
		return SO_THIS;
	} else {
		return get_zval_ptr_real(opline, op_type, node, execute_data, should_free, type);
	}
}

typedef struct _indirection_function {
	zend_internal_function fn;
	zend_function *fbc;        /* Handler that needs to be invoked */
	zval obj;
} indirection_function;

static ZEND_NAMED_FUNCTION(scalar_objects_indirection_func)
{
	indirection_function *ind = (indirection_function *) execute_data->func;
	zval *obj = &ind->obj;
	zval *params = safe_emalloc(sizeof(zval), ZEND_NUM_ARGS() + 1, 0);
	zval result;
	zend_class_entry *ce = ind->fn.scope;
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;

	fci.size = sizeof(fci);
#if PHP_VERSION_ID < 70100
	fci.symbol_table = NULL;
#endif
	fci.param_count = ZEND_NUM_ARGS() + 1;
	fci.params = params;
	fci.no_separation = 1;

#if PHP_VERSION_ID < 70300
	fcc.initialized = 1;
#endif
	fcc.calling_scope = ce;
	fcc.function_handler = ind->fbc;

	zend_get_parameters_array_ex(ZEND_NUM_ARGS(), &params[1]);

	ZVAL_COPY_VALUE(&params[0], obj);
	ZVAL_STR(&fci.function_name, ind->fn.function_name);
	fci.retval = &result;
	fci.object = NULL;

	fcc.object = NULL;
	fcc.called_scope = zend_get_called_scope(execute_data);

	if (zend_call_function(&fci, &fcc) == SUCCESS && !Z_ISUNDEF(result)) {
		ZVAL_COPY_VALUE(return_value, &result);
	}
	zval_ptr_dtor(obj);
	execute_data->func = NULL;

	zval_ptr_dtor(&fci.function_name);
	efree(params);
	efree(ind);
}

static zend_function *scalar_objects_get_indirection_func(
	zend_class_entry *ce, zend_function *fbc, zval *method, zval *obj
) {
	indirection_function *ind = emalloc(sizeof(indirection_function));
	zend_function *fn = (zend_function *) &ind->fn;
	long keep_flags = ZEND_ACC_RETURN_REFERENCE | ZEND_ACC_VARIADIC;

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

	ind->fn.function_name = zend_string_copy(Z_STR_P(method));
	zend_set_function_arg_flags(fn);
	ZVAL_COPY_VALUE(&ind->obj, obj);

	return fn;
}

static int scalar_objects_method_call_handler(zend_execute_data *execute_data)
{
	const zend_op *opline = execute_data->opline;
	zend_free_op free_op1, free_op2;
	zval *obj, *method;
	zend_class_entry *ce;
	zend_function *fbc;

	/* First we fetch the ops without refcount changes or errors. Then we check whether we want
	 * to handle this opcode ourselves or fall back to the original opcode. Only once we know for
	 * certain that we will not fall back the ops are fetched for real. */
	obj = get_object_zval_ptr_safe(opline, opline->op1_type, &opline->op1, execute_data);
	method = get_zval_ptr_safe(opline, opline->op2_type, &opline->op2, execute_data);

	if (!obj || Z_TYPE_P(obj) == IS_OBJECT || Z_TYPE_P(method) != IS_STRING) {
		return ZEND_USER_OPCODE_DISPATCH;
	}

	ce = SCALAR_OBJECTS_G(handlers)[Z_TYPE_P(obj)];
	if (!ce) {
		return ZEND_USER_OPCODE_DISPATCH;
	}

	if (ce->get_static_method) {
		fbc = ce->get_static_method(ce, Z_STR_P(method));
	} else {
		fbc = zend_std_get_static_method(
			ce, Z_STR_P(method),
			opline->op2_type == IS_CONST ? EX_LITERAL(opline, opline->op2) + 1 : NULL
		);
	}

	method = get_zval_ptr_real(
		opline, opline->op2_type, &opline->op2, execute_data, &free_op2, BP_VAR_R
	);
	obj = get_object_zval_ptr_real(
		opline, opline->op1_type, &opline->op1, execute_data, &free_op1, BP_VAR_R
	);

	if (!fbc) {
		if (!EG(exception)) {
			zend_throw_error(NULL, "Call to undefined method %s::%s()",
				ZSTR_VAL(ce->name), Z_STRVAL_P(method));
		}
		FREE_OP(free_op2);
		FREE_OP_IF_VAR(free_op1);
		return ZEND_USER_OPCODE_CONTINUE;
	}

	Z_TRY_ADDREF_P(obj);
	fbc = scalar_objects_get_indirection_func(ce, fbc, method, obj);

	{
#if PHP_VERSION_ID >= 70400
		zend_execute_data *call = zend_vm_stack_push_call_frame(
			ZEND_CALL_NESTED_FUNCTION, fbc, opline->extended_value, ce);
#else
		zend_execute_data *call = zend_vm_stack_push_call_frame(
			ZEND_CALL_NESTED_FUNCTION, fbc, opline->extended_value, ce, NULL);
#endif
		call->prev_execute_data = EX(call);
		EX(call) = call;
	}

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
		return IS_TRUE;
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

	if (zend_parse_parameters(ZEND_NUM_ARGS(), "sC", &type_str, &type_str_len, &ce) == FAILURE) {
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
	if (type == IS_TRUE) {
		SCALAR_OBJECTS_G(handlers)[IS_FALSE] = ce;
	}
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

