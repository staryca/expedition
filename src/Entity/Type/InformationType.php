<?php

declare(strict_types=1);

namespace App\Entity\Type;

class InformationType
{
    public const AUDIO = 'Audio';
    public const VIDEO = 'Video';
    public const VIDEO_TAPES = 'VideoTapes';
    public const PHOTO = 'Photo';
    public const GPS = 'GPS';
    public const THINGS = 'Things';
    public const OBJECT = 'Object';
    public const DOCUMENT = 'Document';

    public const ALL = [
        self::AUDIO,
        self::VIDEO,
        self::VIDEO_TAPES,
        self::PHOTO,
        self::GPS,
        self::THINGS,
        self::OBJECT,
        self::DOCUMENT,
    ];

    public const VARIANTS = [
        self::AUDIO => ['аўдыё', 'аўдыя', 'аўдыа'],
        self::VIDEO => ['відэа'],
        self::VIDEO_TAPES => ['Video from tapes'],
        self::PHOTO => ['фота', 'фотаздымкі'],
        self::THINGS => ['прадметы'],
        self::OBJECT => ['Мат. аб’екты'],
        self::DOCUMENT => ['Дакум.', 'Дакументы', 'дакументы'],
    ];

    public static function getType(string $text): ?string
    {
        if (in_array($text, self::ALL)) {
            return $text;
        }

        foreach (self::VARIANTS as $key => $variants) {
            if (in_array($text, $variants)) {
                return $key;
            }
        }

        return null;
    }
}
