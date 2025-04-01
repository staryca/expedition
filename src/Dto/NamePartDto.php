<?php

declare(strict_types=1);

namespace App\Dto;

class NamePartDto
{
    /** @var array<string> $parts */
    public array $parts = [];

    /** @var array<bool> $isRepeats */
    public array $isRepeats = [];

    public function addPart(string $part, bool $isRepeat): void
    {
        $this->parts[] = $part;
        $this->isRepeats[] = $isRepeat;
    }

    public function getSameName(NamePartDto $other): ?string
    {
        if (count($this->parts) !== count($other->parts)) {
            return null;
        }

        $name = '';

        $isBase = true;
        foreach ($this->parts as $key => $part) {
            $isSame = $this->isRepeats[$key] || $part === $other->parts[$key];
            if ($isBase) {
                if ($isSame) {
                    $name .= $part;
                } else {
                    $isBase = false;
                }
            } elseif (!$isSame) {
                return null;
            }
        }

        return trim($name, "-=_., \t\n\r\0\x0B");
    }

    public function getDifferentPart(NamePartDto $other): string
    {
        if (count($this->parts) !== count($other->parts)) {
            return '';
        }

        $name = '';
        $parts = [];
        $keyPart = -1;
        $keyLast = null;

        $isDiff = false;
        foreach ($this->parts as $key => $part) {
            $isSame = $this->isRepeats[$key] || $part === $other->parts[$key];
            if ($isDiff) {
                $parts[++$keyPart] = $part;
                if (!$isSame) {
                    $keyLast = null;
                } elseif (null === $keyLast) {
                    $keyLast = $keyPart;
                }
            } elseif (!$isSame) {
                $isDiff = true;
                $parts[++$keyPart] = $part;
            }
        }

        foreach ($parts as $key => $part) {
            if ($key === $keyLast) {
                break;
            }
            $name .= $part;
        }

        return $name;
    }

    /**
     * @param array<string> $parts
     * @return bool
     */
    public function isEqualParts(array $parts): bool
    {
        if (count($parts) !== count($this->parts)) {
            return false;
        }

        $partsThis = array_values($this->parts);
        $partsOther = array_values($parts);

        foreach ($partsThis as $key => $part) {
            if ($part !== $partsOther[$key]) {
                return false;
            }
        }

        return true;
    }
}
