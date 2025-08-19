LIBRARY_NAME=libpubliccode-parser

LIB_DIR=lib

LIBRARY=$(LIB_DIR)/$(LIBRARY_NAME).so

.PHONY: all build clean deps

all: build

build:
	mkdir -p $(LIB_DIR)
	cd go-src && CGO_ENABLED=1 go build -buildmode=c-shared -o ../$(LIBRARY) publiccode-parser-wrapper.go

clean:
	go clean
	rm -rf $(LIB_DIR)

install: build
	cp $(LIBRARY) /usr/local/lib/
