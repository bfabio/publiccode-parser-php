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
        int WarningCount;
        char **Warnings;
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
	result.Data = nil
	result.Error = nil
	result.ErrorCount = 0
	result.Errors = nil
	result.WarningCount = 0
	result.Warnings = nil

	parser, err := toGoParser(handle)
	if err != nil {
		result.Error = C.CString("Failed create a Parser: " + err.Error())

		return result
	}

	goString := C.GoString(content)

	pc, err := parser.ParseStream(strings.NewReader(goString))

	if err != nil {
		if validationRes, ok := err.(publiccode.ValidationResults); ok {
			for _, res := range validationRes {
				switch res.(type) {
				case publiccode.ValidationError:
					result.ErrorCount += 1
				case publiccode.ValidationWarning:
					result.WarningCount += 1
				}
			}

			var errorsSlice []*C.char
			cErrors := unsafe.Pointer(nil)
			if result.ErrorCount > 0 {
				cErrors = C.malloc(C.size_t(result.ErrorCount) * C.size_t(unsafe.Sizeof(uintptr(0))))
				errorsSlice = (*[1 << 28]*C.char)(cErrors)[:result.ErrorCount:result.ErrorCount]
			}

			var warningsSlice []*C.char
			cWarnings := unsafe.Pointer(nil)
			if result.WarningCount > 0 {
				cWarnings = C.malloc(C.size_t(result.WarningCount) * C.size_t(unsafe.Sizeof(uintptr(0))))
				warningsSlice = (*[1 << 28]*C.char)(cWarnings)[:result.WarningCount:result.WarningCount]
			}

			errIdx := 0
			warnIdx := 0
			for _, res := range validationRes {
				switch res.(type) {
				case publiccode.ValidationError:
					errorsSlice[errIdx] = C.CString(res.Error())
					errIdx += 1
				case publiccode.ValidationWarning:
					warningsSlice[warnIdx] = C.CString(res.Error())
					warnIdx += 1
				}
			}

			result.Errors = (**C.char)(cErrors)
			result.Warnings = (**C.char)(cWarnings)
		} else {
			result.Error = C.CString(err.Error())

			return result
		}

		if result.ErrorCount > 0 {
			return result
		}
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
	if result.Warnings != nil && result.WarningCount > 0 {
		warningsSlice := unsafe.Slice((**C.char)(result.Warnings), result.WarningCount)
		for i := 0; i < int(result.WarningCount); i++ {
			if warningsSlice[i] != nil {
				C.free(unsafe.Pointer(warningsSlice[i]))
				warningsSlice[i] = nil
			}
		}
		C.free(unsafe.Pointer(result.Warnings))
		result.Warnings = nil
		result.WarningCount = 0
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
