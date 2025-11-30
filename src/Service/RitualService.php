<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Ritual;
use App\Entity\Type\RitualType;
use App\Repository\RitualRepository;

class RitualService
{
    private ?array $rituals = null;

    public function __construct(
        private readonly RitualRepository $ritualRepository,
    ) {
    }

    public static function getTreeFromList(array $list): array
    {
        $tree = [];

        foreach ($list as $item) {
            $item = trim($item, "#\t\n\r\0\x0B");
            $parts = explode('#', $item);

            $link = &$tree;
            foreach ($parts as $part) {
                if (!isset($link[$part])) {
                    $link[$part] = [];
                }
                $link = &$link[$part];
            }
        }

        return $tree;
    }

    /**
     * @return array<Ritual>
     */
    private function getRitualTree(): array
    {
        return $this->ritualRepository->findAll();
    }

    public function findRitual(string $ritualName): ?Ritual
    {
        if ($this->rituals === null) {
            $this->rituals = $this->getRitualTree();
        }

        $ritualName = trim($ritualName, "#\t\n\r\0\x0B");
        $parts = explode('#', $ritualName);
        $last = array_pop($parts);

        foreach ($this->rituals as $ritual) {
            if ($last === $ritual->getName()) {
                return $ritual;
            }
        }

        return null;
    }

    public function detectRitual(string $text): ?Ritual
    {
        $ritualName = RitualType::detectRitual($text);

        return $ritualName ? $this->findRitual($ritualName) : null;
    }
}
