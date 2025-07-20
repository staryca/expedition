<?php

declare(strict_types=1);

namespace App\Message;

class ImefMessage
{
    public function __construct(
        private readonly string $folder,
    ) {
    }

    public function getFolder(): string
    {
        return $this->folder;
    }
}
