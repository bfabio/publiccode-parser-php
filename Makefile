LIBRARY_NAME=libpubliccode-parser

LIB_DIR=lib

# Detect current platform
GOOS := $(shell go env GOOS)
GOARCH := $(shell go env GOARCH)

# Determine file extension based on OS
ifeq ($(GOOS),darwin)
	EXT=dylib
else
	EXT=so
endif

# Current platform library
LIBRARY=$(LIB_DIR)/$(LIBRARY_NAME)-$(GOOS)-$(GOARCH).$(EXT)

# Legacy symlink for backward compatibility
LEGACY_LIBRARY=$(LIB_DIR)/$(LIBRARY_NAME).so

.PHONY: all build build-all build-linux-amd64 build-linux-arm64 build-darwin-amd64 build-darwin-arm64 clean install

all: build

# Build for current platform
build:
	mkdir -p $(LIB_DIR)
	cd go-src && CGO_ENABLED=1 go build -buildmode=c-shared -o ../$(LIBRARY) publiccode-parser-wrapper.go
	@echo "Built $(LIBRARY)"

# Build all platform variants
build-all: build-linux-amd64 build-linux-arm64 build-darwin-amd64 build-darwin-arm64
	@echo "All platforms built successfully"

build-linux-amd64:
	mkdir -p $(LIB_DIR)
	cd go-src && GOOS=linux GOARCH=amd64 CGO_ENABLED=1 go build -buildmode=c-shared -o ../$(LIB_DIR)/$(LIBRARY_NAME)-linux-amd64.so publiccode-parser-wrapper.go
	@echo "Built Linux AMD64"

build-linux-arm64:
	mkdir -p $(LIB_DIR)
	cd go-src && GOOS=linux GOARCH=arm64 CGO_ENABLED=1 CC=aarch64-linux-gnu-gcc go build -buildmode=c-shared -o ../$(LIB_DIR)/$(LIBRARY_NAME)-linux-arm64.so publiccode-parser-wrapper.go
	@echo "Built Linux ARM64"

build-darwin-amd64:
	mkdir -p $(LIB_DIR)
	cd go-src && GOOS=darwin GOARCH=amd64 CGO_ENABLED=1 go build -buildmode=c-shared -o ../$(LIB_DIR)/$(LIBRARY_NAME)-darwin-amd64.dylib publiccode-parser-wrapper.go
	@echo "Built macOS AMD64"

build-darwin-arm64:
	mkdir -p $(LIB_DIR)
	cd go-src && GOOS=darwin GOARCH=arm64 CGO_ENABLED=1 go build -buildmode=c-shared -o ../$(LIB_DIR)/$(LIBRARY_NAME)-darwin-arm64.dylib publiccode-parser-wrapper.go
	@echo "Built macOS ARM64"

clean:
	go clean
	rm -rf $(LIB_DIR)

install: build
	cp $(LIBRARY) /usr/local/lib/
