<?php

declare(strict_types=1);

namespace Bfabio\PublicCodeParser;

class ParserOptions
{
    private bool $disableNetwork = true;
    private int $timeout = 60;

    public function isNetworkDisabled(): bool
    {
        return $this->disableNetwork;
    }

    public function setDisableNetwork(bool $disableNetwork): self
    {
        $this->disableNetwork = $disableNetwork;
        return $this;
    }
}
