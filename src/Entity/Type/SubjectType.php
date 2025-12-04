<?php

declare(strict_types=1);

namespace App\Entity\Type;

class SubjectType
{
    public const TYPE_REEL = 1;
    public const TYPE_AUDIO = 2;
    public const TYPE_VIDEO = 3;
    public const TYPE_VIDEO_TDK_VHS = 4;
    public const TYPE_AUDIO_MICRO = 5;
    public const TYPE_AUDIO_DAT = 6;
    public const TYPE_VIDEO_DVD = 7;
    public const TYPE_PHOTO = 8;
    public const TYPE_VIDEO_VHS_C = 9;
    public const TYPE_VIDEO_MINI_DV = 10;
    public const TYPE_BOOK = 11;
    public const TYPE_OTHER = 99;

    public const TYPES = [
        self::TYPE_REEL => 'Бабіна',
        self::TYPE_AUDIO => 'Аўдыакасета',
        self::TYPE_VIDEO => 'Відэакасета',
        self::TYPE_VIDEO_TDK_VHS => 'Відэакасета TDK VHS',
        self::TYPE_AUDIO_MICRO => 'Мікракасета (аўдыё)',
        self::TYPE_AUDIO_DAT => 'Аўдыакасета ДАТ-касета',
        self::TYPE_VIDEO_DVD => 'DVD-дыск',
        self::TYPE_PHOTO => 'Фотастужка',
        self::TYPE_VIDEO_VHS_C => 'Відэакасета VHS-C',
        self::TYPE_VIDEO_MINI_DV => 'Відэакасета MiniDV',
        self::TYPE_BOOK => 'Альбом, сшытак, кніга',
        self::TYPE_OTHER => '',
    ];

    public const CONVERTER_FILE_TYPES = [
        FileType::TYPE_VIRTUAL_CONTENT_LIST => null,
        FileType::TYPE_AUDIO => self::TYPE_AUDIO,
        FileType::TYPE_VIDEO => self::TYPE_VIDEO,
        FileType::TYPE_PHOTO => self::TYPE_PHOTO,
        FileType::TYPE_SCAN_NOTES => self::TYPE_BOOK,
        FileType::TYPE_SCAN_ANY => self::TYPE_BOOK,
        FileType::TYPE_WORD => self::TYPE_OTHER,
        FileType::TYPE_XML => self::TYPE_OTHER,
        FileType::TYPE_OTHER => self::TYPE_OTHER,
    ];

    public static function getName(int $type): ?string
    {
        if (!isset(self::TYPES[$type])) {
            return null;
        }

        return self::TYPES[$type];
    }

    public static function getTypeByFileType(?int $type): ?int
    {
        return self::CONVERTER_FILE_TYPES[$type] ?? null;
    }
}
