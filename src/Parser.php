<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser;

use Bfabio\PublicCodeParser\Exception\ParserException;
use Bfabio\PublicCodeParser\Exception\ValidationException;
use FFI;

class Parser
{
    private ParserConfig $config;
    private FFI $ffi;
    private static ?FFI $ffiInstance = null;
    private int $handle;

    public function __construct(?ParserConfig $config = null)
    {
        $this->config = $config ?? new ParserConfig();

        $this->ffi = $this->getFFI();
        /** @phpstan-ignore-next-line */
        $this->handle = $this->ffi->NewParser(
            $this->config->isNetworkDisabled(),
            $this->config->getBranch(),
            $this->config->getBaseURL(),
        );
        if ($this->handle == 0) {
            throw new ParserException('Failed to create parser');
        }
    }

    public function __destruct()
    {
        if ($this->handle != 0) {
            /** @phpstan-ignore-next-line */
            $this->ffi->FreeParser($this->handle);
            $this->handle = 0;
        }
    }

    /**
     * Parse publiccode.yml content
     *
     * @param string $content YAML content
     * @return PublicCode
     * @throws ParserException
     * @throws ValidationException
     */
    public function parse(string $content): PublicCode
    {
        /** @phpstan-ignore-next-line */
        $result = $this->ffi->ParseString($this->handle, $content);

        if ($result === null) {
            throw new ParserException('Failed to parse publiccode.yml content');
        }

        return $this->processResult($result);
    }

    /**
     * Parse a publiccode.yml file
     *
     * @param string $filePath Path to publiccode.yml
     * @return PublicCode
     * @throws ParserException
     * @throws ValidationException
     */
    public function parseFile(string $filePath): PublicCode
    {
        if (!file_exists($filePath)) {
            throw new ParserException("File not found: {$filePath}");
        }

        $fp = @fopen($filePath, 'rb');
        if ($fp === false) {
            throw new ParserException("Cannot open file: {$filePath}");
        }

        try {
            $content = stream_get_contents($fp);
            if ($content === false) {
                throw new ParserException('Failed to read from stream');
            }

            return $this->parse($content);
        } finally {
            fclose($fp);
        }
    }

    /**
     * Helper function to validate publiccode.yml file, with no error details
     *
     * @param string $filePath
     * @return bool
     */
    public function isValid(string $filePath): bool
    {
        try {
            $this->parseFile($filePath);
            return true;
        } catch (ParserException | ValidationException $e) {
            return false;
        }
    }

    /**
     * Get or create FFI instance
     *
     * @return FFI
     * @throws ParserException
     */
    private function getFFI(): FFI
    {
        if (self::$ffiInstance !== null) {
            return self::$ffiInstance;
        }

        $libraryPath = $this->findLibrary();

        $cdef = <<<'CDEF'
	typedef struct {
	    char* Data;
	    char* Error;
	    int ErrorCount;
	    char** Errors;
	    int WarningCount;
	    char** Warnings;
	} ParseResult;

	typedef uintptr_t ParserHandle;

	ParserHandle NewParser(bool disableNetwork, const char* branch, const char* baseURL);
	ParseResult* ParseString(ParserHandle handle, const char* content);
	void FreeResult(ParseResult* result);
	void FreeParser(ParserHandle handle);
	CDEF;

        try {
            self::$ffiInstance = FFI::cdef($cdef, $libraryPath);
            return self::$ffiInstance;
        } catch (\FFI\Exception $e) {
            throw new ParserException(
                'Failed to load publiccode-parser library: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Find the publiccode-parser shared library
     *
     * @return string
     * @throws ParserException
     */
    private function findLibrary(): string
    {
        $libraryName = 'libpubliccode-parser.so';
        $possiblePaths = [
            __DIR__ . '/',
            __DIR__ . '/../lib/',
            __DIR__ . '/../vendor/lib/',
            '/usr/local/lib/',
            '/usr/lib/',
        ];

        foreach ($possiblePaths as $path) {
            $filePath = $path . $libraryName;
            if (file_exists($filePath)) {
                return $filePath;
            }
        }

        throw new ParserException('libpubliccode-parser.so not found');
    }

    /**
     * Process FFI result and convert to PublicCode object
     *
     * @param mixed $result FFI ParseResult pointer
     * @return PublicCode
     * @throws ValidationException
     * @throws ParserException
     */
    private function processResult($result): PublicCode
    {
        if ($result->ErrorCount > 0) {
            /** @var list<non-empty-string> */
            $errors = [];

            if ($result->Errors !== null) {
                for ($i = 0; $i < $result->ErrorCount; $i++) {
                    $errors[] = FFI::string($result->Errors[$i]);
                }
            }

            /** @phpstan-ignore-next-line */
            $this->ffi->FreeResult($result);

            throw new ValidationException(implode("\n", $errors), $errors);
        }

        if ($result->Error !== null) {
            $message = FFI::string($result->Error);
            /** @phpstan-ignore-next-line */
            $this->ffi->FreeResult($result);

            throw new ParserException($message);
        }

        if ($result->Data === null) {
            /** @phpstan-ignore-next-line */
            $this->ffi->FreeResult($result);

            throw new ParserException('No data returned from parser');
        }

        $jsonData = FFI::string($result->Data);
        /** @phpstan-ignore-next-line */
        $this->ffi->FreeResult($result);

        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ParserException('Failed to decode JSON data: ' . json_last_error_msg());
        }

        return new PublicCode($data);
    }
}
