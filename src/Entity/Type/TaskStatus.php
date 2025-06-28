<?php

declare(strict_types=1);

namespace App\Entity\Type;

class TaskStatus
{
    public const NEW = 0;
    public const TIP = 1;
    public const QUESTION = 2;

    public const DONE = 8;
    public const CANCELLED = 9;

    public const STATUSES = [
        self::NEW => 'Планы/дзеяньне',
        self::TIP => 'Наводка',
        self::QUESTION => 'Пытаньне',
    ];

    private const ICONS = [
        self::NEW => 'bi-envelope-open-heart',
        self::TIP => 'bi-truck',
        self::QUESTION => 'bi-question-square',
        self::DONE => 'bi-check',
        self::CANCELLED => 'bi-close',
    ];

    public static function getIcon(int $status): ?string
    {
        return self::ICONS[$status] ?? null;
    }
}
