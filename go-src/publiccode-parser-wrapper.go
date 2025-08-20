package main

/*
#include <stdint.h>
#include <stdlib.h>
#include <stdbool.h>

struct ParserConfig {
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

typedef uintptr_t ParserHandle;
*/
import "C"
import (
	"encoding/json"
	"errors"
	"runtime/cgo"
	"strings"
	"unsafe"

	"github.com/italia/publiccode-parser-go/v4"
)

//export NewParser
func NewParser(disableNetwork C.bool, branch *C.char, baseURL *C.char) C.ParserHandle {
	config := publiccode.ParserConfig{
		DisableNetwork: bool(disableNetwork),
		Branch:         C.GoString(branch),
		BaseURL:        C.GoString(baseURL),
	}

	p, err := publiccode.NewParser(config)
	if err != nil {
		return 0
	}

	return C.ParserHandle(cgo.NewHandle(p))
}

//export ParseString
func ParseString(handle C.ParserHandle, content *C.char) *C.struct_ParseResult {
	result := (*C.struct_ParseResult)(C.calloc(1, C.size_t(C.sizeof_struct_ParseResult)))
	result.Error = nil
	result.Errors = nil
	result.ErrorCount = 0

	parser, err := toGoParser(handle)
	if err != nil {
		result.Error = C.CString("Failed create a Parser: " + err.Error())

		return result
	}

	goString := C.GoString(content)

	pc, err := parser.ParseStream(strings.NewReader(goString))

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

func toGoParser(handle C.ParserHandle) (*publiccode.Parser, error) {
	if handle == 0 {
		return nil, errors.New("nil handle")
	}

	v := cgo.Handle(handle).Value()
	p, ok := v.(*publiccode.Parser)
	if !ok || p == nil {
		return nil, errors.New("invalid handle")
	}

	return p, nil
}

func main() {
	// Required for building shared library
}
