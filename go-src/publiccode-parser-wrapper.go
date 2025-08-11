package main

/*
#include <stdlib.h>
#include <stdbool.h>

struct ParseOptions {
        bool DisableNetwork;
        char *Branch;
        char *BaseURL;
};

struct ParseResult {
        char *Data;
        char *Error;
        int ErrorCount;
        char **Errors;
};
*/
import "C"
import (
	"encoding/json"
	"github.com/italia/publiccode-parser-go/v4"
	"unsafe"
)

//export ParseString
func ParseString(content *C.char) *C.struct_ParseResult {
	parser, err := publiccode.NewDefaultParser()
	if err != nil {
		panic()
	}

	goContent := C.GoString(content)

	pc, err := parser.Parse([]byte(goContent))

	result := (*C.struct_ParseResult)(C.malloc(C.size_t(C.sizeof_struct_ParseResult)))

	if err != nil {
		result.Error = C.CString(err.Error())

		if validationErr, ok := err.(*publiccode.ValidationErrors); ok {
			errors := validationErr.Errors()
			errCount := len(errors)
			result.ErrorCount = C.int(errCount)

			if len(errors) > 0 {
				cErrors := C.malloc(C.size_t(errCount) * C.size_t(unsafe.Sizeof(uintptr(0))))
				errorsSlice := unsafe.Slice((**C.char)(cErrors), errCount)

				for i, e := range errors {
					errorsSlice[i] = C.CString(e.Error())
				}

				result.Errors = (**C.char)(cErrors)
			}
		}

		return result
	}

	jsonData, err := json.Marshal(pc)
	if err != nil {
		result.Error = C.CString("Failed to marshal result to JSON: " + err.Error())

		return result
	}

	result.Data = C.CString(string(jsonData))
	return result
}

//export FreeResult
func FreeResult(result *C.struct_ParseResult) {
	if result == nil {
		return
	}

	if result.Data != nil {
		C.free(unsafe.Pointer(result.Data))
	}

	if result.Error != nil {
		C.free(unsafe.Pointer(result.Error))
	}

	if result.Errors != nil && result.ErrorCount > 0 {
		errorsSlice := unsafe.Slice((**C.char)(cErrors), result.ErrorCount)
		for i := 0; i < int(result.ErrorCount); i++ {
			if errorsSlice[i] != nil {
				C.free(unsafe.Pointer(errorsSlice[i]))
			}
		}
		C.free(unsafe.Pointer(result.Errors))
	}
}

func main() {
	// Required for building shared library
}
