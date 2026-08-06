<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser;

class ValidationWarning
{
    private int $line;
    private int $column;
    private string $key;
    private string $description;

    public function __construct(int $line, int $column, string $key, string $description)
    {
        $this->line = $line;
        $this->column = $column;
        $this->key = $key;
        $this->description = $description;
    }

    public function getType(): string
    {
        return 'warning';
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getColumn(): int
    {
        return $this->column;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function __toString(): string
    {
        $key = $this->key !== '' ? $this->key . ': ' : '';

        return sprintf(
            'publiccode.yml:%d:%d: warning: %s%s',
            $this->line,
            $this->column,
            $key,
            $this->description,
        );
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['line'] ?? 0),
            (int) ($data['column'] ?? 0),
            (string) ($data['key'] ?? ''),
            (string) ($data['description'] ?? ''),
        );
    }

    public static function fromString(string $message): self
    {
        $matches = [];
        if (preg_match(
            '/^publiccode\.yml:(\d+):(\d+): warning: (?:(.*?): )?(.*)$/s',
            $message,
            $matches,
        ) === 1) {
            return new self(
                (int) $matches[1],
                (int) $matches[2],
                $matches[3],
                $matches[4],
            );
        }

        return new self(0, 0, '', $message);
    }
}
