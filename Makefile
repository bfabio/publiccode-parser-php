LIBRARY_NAME=libpubliccode-parser

LIB_DIR=lib

.PHONY: all build-all build-linux-amd64 build-linux-arm64 build-darwin-arm64 clean

all: build-all

# Build all platform variants
build-all: build-linux-amd64 build-linux-arm64 build-darwin-arm64
	@echo "All platforms built successfully"

build-linux-amd64:
	mkdir -p $(LIB_DIR)
	cd go-src && GOOS=linux GOARCH=amd64 CGO_ENABLED=1 go build -buildmode=c-shared -o ../$(LIB_DIR)/$(LIBRARY_NAME)-linux-amd64.so publiccode-parser-wrapper.go
	@echo "Built Linux AMD64"

build-linux-arm64:
	mkdir -p $(LIB_DIR)
	cd go-src && GOOS=linux GOARCH=arm64 CGO_ENABLED=1 CC=aarch64-linux-gnu-gcc go build -buildmode=c-shared -o ../$(LIB_DIR)/$(LIBRARY_NAME)-linux-arm64.so publiccode-parser-wrapper.go
	@echo "Built Linux ARM64"

build-darwin-arm64:
	mkdir -p $(LIB_DIR)
	cd go-src && GOOS=darwin GOARCH=arm64 CGO_ENABLED=1 go build -buildmode=c-shared -o ../$(LIB_DIR)/$(LIBRARY_NAME)-darwin-arm64.dylib publiccode-parser-wrapper.go
	@echo "Built macOS ARM64"

clean:
	go clean
	rm -rf $(LIB_DIR)
