package main

/*
#include <stdint.h>
#include <stdlib.h>
#include <stdbool.h>

struct ParserConfig {
        bool DisableNetwork;
        bool DisableExternalChecks;
        char *Branch;
        char *BaseURL;
        int64_t HTTPTimeout;
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
	"time"
	"unsafe"

	"github.com/italia/publiccode-parser-go/v5"
)

//export NewParser
func NewParser(disableNetwork C.bool, disableExternalChecks C.bool, branch *C.char, baseURL *C.char, httpTimeout C.int64_t) C.ParserHandle {
	config := publiccode.ParserConfig{
		DisableNetwork:        bool(disableNetwork),
		DisableExternalChecks: bool(disableExternalChecks),
		Branch:                C.GoString(branch),
		BaseURL:               C.GoString(baseURL),
		Timeout:               time.Duration(httpTimeout),
	}

	p, err := publiccode.NewParser(config)
	if err != nil {
		return 0
	}

	return C.ParserHandle(cgo.NewHandle(p))
}

//export ParseFile
func ParseFile(handle C.ParserHandle, uri *C.char) *C.struct_ParseResult {
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

	goString := C.GoString(uri)

	pc, err := parser.Parse(goString)

	if err != nil {
		var validationRes publiccode.ValidationResults
		if errors.As(err, &validationRes) {
			fillValidationResult(result, validationRes)
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
		var validationRes publiccode.ValidationResults
		if errors.As(err, &validationRes) {
			fillValidationResult(result, validationRes)
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

// fillValidationResult serializes validation issues as JSON so callers can
// access their fields without parsing the human-readable error message.
func fillValidationResult(result *C.struct_ParseResult, validationRes publiccode.ValidationResults) {
	for _, res := range validationRes {
		var validationError publiccode.ValidationError
		var validationWarning publiccode.ValidationWarning

		switch {
		case errors.As(res, &validationError):
			result.ErrorCount += 1
		case errors.As(res, &validationWarning):
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
		var validationError publiccode.ValidationError
		var validationWarning publiccode.ValidationWarning

		switch {
		case errors.As(res, &validationError):
			payload, _ := json.Marshal(validationError)
			errorsSlice[errIdx] = C.CString(string(payload))
			errIdx += 1
		case errors.As(res, &validationWarning):
			payload, _ := json.Marshal(validationWarning)
			warningsSlice[warnIdx] = C.CString(string(payload))
			warnIdx += 1
		}
	}

	result.Errors = (**C.char)(cErrors)
	result.Warnings = (**C.char)(cWarnings)
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

	C.free(unsafe.Pointer(result))

}

//export FreeParser
func FreeParser(handle C.ParserHandle) {
	h := cgo.Handle(handle)
	h.Delete()
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
