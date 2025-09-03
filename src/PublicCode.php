<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser;

use JsonSerializable;

class PublicCode implements JsonSerializable
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getPubliccodeYmlVersion(): string
    {
        return $this->data['publiccodeYmlVersion'];
    }

    public function getName(): string
    {
        return $this->data['name'];
    }

    public function getApplicationSuite(): ?string
    {
        return $this->data['applicationSuite'] ?? null;
    }

    public function getUrl(): string
    {
        return $this->data['url'];
    }

    public function getLandingUrl(): ?string
    {
        return $this->data['landingURL'] ?? null;
    }

    /**
     * @return string[]
     */
    public function getIsBasedOn(): array
    {
        if ($this->data['isBasedOn'] === null) {
            return [];
        }
        if (is_string($this->data['isBasedOn'])) {
            return [$this->data['isBasedOn']];
        }

        return $this->data['isBasedOn'];
    }

    public function getSoftwareVersion(): ?string
    {
        return $this->data['softwareVersion'] ?? null;
    }

    public function getLogo(): ?string
    {
        return $this->data['logo'] ?? null;
    }

    public function getDescription(string $language = 'en'): ?string
    {
        return $this->data['description'][$language]['shortDescription'] ?? null;
    }

    public function getLongDescription(string $language = 'en'): ?string
    {
        return $this->data['description'][$language]['longDescription'] ?? null;
    }

    public function getAllDescriptions(): array
    {
        return $this->data['description'];
    }

    public function getFeatures(string $language = 'en'): array
    {
        return $this->data['description'][$language]['features'] ?? [];
    }

    public function getMaintenance(): array
    {
        return $this->data['maintenance'];
    }

    public function getLicense(): ?string
    {
        return $this->data['legal']['license'];
    }

    public function getRepoOwner(): ?string
    {
        return $this->data['legal']['repoOwner'] ?? null;
    }

    public function getCategories(): array
    {
        return $this->data['categories'];
    }

    public function getRoadmap(): ?string
    {
        return $this->data['roadmap'] ?? null;
    }

    public function getPlatforms(): array
    {
        return $this->data['platforms'];
    }

    public function getReleaseDate(): ?\DateTimeInterface
    {
        if (!isset($this->data['releaseDate'])) {
            return null;
        }

        return new \DateTime($this->data['releaseDate']);
    }

    public function getDevelopmentStatus(): string
    {
        return $this->data['developmentStatus'];
    }

    public function getSoftwareType(): string
    {
        return $this->data['softwareType'];
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->data, $options);
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
