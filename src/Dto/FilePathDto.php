<?php

declare(strict_types=1);

namespace App\Dto;

class FilePathDto
{
    public ?string $path = null;
    public string $name = '';

    public function __construct(string $filename)
    {
        $pos = mb_strrpos($filename, '\\');
        $this->path = false === $pos ? null : mb_substr($filename, 0, $pos);
        $this->name = false === $pos ? $filename : mb_substr($filename, $pos + 1);
    }

    public function getNameWithoutType(): string
    {
        $pos = mb_strrpos($this->name, '.');
        $type = false === $pos ? '' : mb_substr($this->name, $pos + 1);
        if (mb_strlen($type) >= 2 && mb_strlen($type) <= 4) {
            return mb_substr($this->name, 0, $pos);
        }

        return $this->name;
    }

    public function getFilename(): string
    {
        if (null === $this->path) {
            return $this->name;
        }

        return sprintf('%s\\%s', $this->path, $this->name);
    }
}
