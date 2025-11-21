<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser;

class ParserConfig
{
    private bool $disableNetwork = false;
    private bool $disableExternalChecks = false;
    private string $branch = '';
    private string $baseURL = '';

    public function isNetworkDisabled(): bool
    {
        return $this->disableNetwork;
    }

    public function setDisableNetwork(bool $disableNetwork): self
    {
        $this->disableNetwork = $disableNetwork;

        return $this;
    }

    public function areExternalChecksDisabled(): bool
    {
        return $this->disableExternalChecks;
    }

    public function setDisableExternalChecks(bool $disableExternalChecks): self
    {
        $this->disableExternalChecks = $disableExternalChecks;

        return $this;
    }

    public function getBranch(): string
    {
        return $this->branch;
    }

    public function setBranch(string $branch): self
    {
        $this->branch = $branch;

        return $this;
    }

    public function getBaseURL(): string
    {
        return $this->baseURL;
    }

    public function setBaseURL(string $url): self
    {
        $this->baseURL = $url;

        return $this;
    }
}
