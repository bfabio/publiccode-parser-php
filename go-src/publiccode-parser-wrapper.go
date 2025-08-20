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
	"strings"
	"unsafe"

	"github.com/italia/publiccode-parser-go/v4"
)

//export ParseString
func ParseString(content *C.char) *C.struct_ParseResult {
	parser, err := publiccode.NewDefaultParser()
	if err != nil {
		// TODO: proper return
		panic("err on NewDefaultParser()")
	}

	goString := C.GoString(content)

	pc, err := parser.ParseStream(strings.NewReader(goString))

	result := (*C.struct_ParseResult)(C.calloc(1, C.size_t(C.sizeof_struct_ParseResult)))
	result.Error = nil
	result.Errors = nil
	result.ErrorCount = 0

	if err != nil {
		if validationRes, ok := err.(publiccode.ValidationResults); ok {
			var ve []publiccode.ValidationError
			for _, res := range validationRes {
				switch v := res.(type) {
				case publiccode.ValidationError:
					ve = append(ve, v)
				}
			}

			errCount := len(ve)
			result.ErrorCount = C.int(errCount)

			if errCount > 0 {
				result.Error = C.CString(err.Error())
				cErrors := C.malloc(C.size_t(errCount) * C.size_t(unsafe.Sizeof(uintptr(0))))
				errorsSlice := (*[1 << 28]*C.char)(cErrors)[:errCount:errCount]

				for i, e := range ve {
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
		errorsSlice := unsafe.Slice((**C.char)(result.Errors), result.ErrorCount)
		for i := 0; i < int(result.ErrorCount); i++ {
			if errorsSlice[i] != nil {
				C.free(unsafe.Pointer(errorsSlice[i]))
				errorsSlice[i] = nil
			}
		}
		C.free(unsafe.Pointer(result.Errors))
		result.Errors = nil
		result.ErrorCount = 0
	}
}

func main() {
	// Required for building shared library
}
