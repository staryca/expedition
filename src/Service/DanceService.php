<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Dance;
use App\Repository\DanceRepository;

class DanceService
{
    private const array REPLACES = [
        'Падэспань' => ['Падыспань', 'Падыспан', 'Падыспанец',],
        'Сербіянка' => ['Цыганачка'],
        'Полька' => ['Полечка', 'Мазур-полька'],
        'Вянгерка' => ['Вангерка'],
        'Абэрак' => ['Абэрачак', 'Абэрка',],
        'Карапет' => ['Тустэп'],
        'Каробачка' => ['Каробушка'],
        'Лезгінка' => ['Люзбінка'],
        'Гапак' => ['Гапачок'],
        'Камарынская' => ['Камарыцкага', 'Камарынскі'],
        'Васадулі' => ['Ва саду лі'],
        'Нарэчанька' => ['на рэчаньку'],
        'Ойра' => ['Войра'],
        'Вальс' => ['Вальсок'],
    ];

    private const array OTHER_NAMES = [
        'Перапляс', 'Дасада', 'Траян', 'Джанджоха', 'Бычок', 'Малдавеняска',
    ];

    /**
     * @var array<string, Dance>|null
     */
    private ?array $dances = null;

    public function __construct(
        private readonly DanceRepository $danceRepository,
    ) {
    }

    private function getDances(): array
    {
        $dances = [];

        $objects = $this->danceRepository->findAll();
        foreach ($objects as $object) {
            $dances[mb_strtolower($object->getName())] = $object;
        }

        return $dances;
    }

    public function isDance(string $text): bool
    {
        $dance = $this->detectDance($text);
        if (null !== $dance) {
            return true;
        }

        foreach (self::OTHER_NAMES as $dance) {
            if (mb_strpos($text, mb_strtolower($dance)) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectDance(string $text): ?Dance
    {
        $text = mb_strtolower($text);

        if (null === $this->dances) {
            $this->dances = $this->getDances();
        }

        foreach ($this->dances as $dance => $object) {
            if (mb_strpos($text, $dance) !== false) {
                if (empty(preg_grep('/(.*)(' . $dance . '([а-я]|і|ў)|([а-я]|і|ў)' . $dance . ')(.*)/u', [$text]))) {
                    return $object;
                }
            }
        }

        foreach (self::REPLACES as $dance => $variants) {
            foreach ($variants as $variant) {
                if (mb_strpos($text, mb_strtolower($variant)) !== false) {
                    $dance = mb_strtolower($dance);
                    if (isset($this->dances[$dance])) {
                        return $this->dances[$dance];
                    }
                }
            }
        }

        return null;
    }

    public function getAllNames(): array
    {
        $result = [];

        if (null === $this->dances) {
            $this->dances = $this->getDances();
        }

        foreach ($this->dances as $object) {
            $result[$object->getId()] = $object->getName();
        }

        return $result;
    }
}
