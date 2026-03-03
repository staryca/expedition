<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\DanceRepository;

class DanceService
{
    private const array REPLACES = [
        'Камарыцкага' => 'Камарынская',
        'Камарынскі' => 'Камарынская',
        'Ва саду лі' => 'Васадулі',
        'Перапляс' => '',
        'Гапачок' => 'Гапак',
        'Падыспань' => 'Падэспань',
        'Падыспан' => 'Падэспань',
        'Падыспанец' => 'Падэспань',
        'Дасада' => '',
        'Траян' => '',
        'Джанджоха' => '',
        'Цыганачка' => 'Сербіянка',
        'Каробушка' => 'Каробачка',
        'Тустэп' => 'Карапет',
        'Люзбінка' => 'Лезгінка',
        'Абэрачак' => 'Абэрак',
        'Абэрка' => 'Абэрак',
        'Вангерка' => 'Венгерка',
        'на рэчаньку' => 'Нарэчанька',
        'Полечка' => 'Полька',
    ];

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
            $dances[] = mb_strtolower($object->getName());
        }

        return $dances;
    }

    public function isDance(string $text): bool
    {
        $text = mb_strtolower($text);

        if (null === $this->dances) {
            $this->dances = $this->getDances();
        }

        foreach ($this->dances as $dance) {
            if (mb_strpos($text, $dance) !== false) {
                return true;
            }
        }

        foreach (self::REPLACES as $danceVariant => $dance) {
            if (mb_strpos($text, mb_strtolower($danceVariant)) !== false) {
                return true;
            }
        }

        return false;
    }
}
