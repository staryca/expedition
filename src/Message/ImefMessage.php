<?php

declare(strict_types=1);

namespace App\Message;

readonly class ImefMessage
{
    public function __construct(
        private string $folder,
    ) {
    }

    public function getFolder(): string
    {
        return $this->folder;
    }
}
