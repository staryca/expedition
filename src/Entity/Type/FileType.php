<?php

declare(strict_types=1);

namespace App\Entity\Type;

class FileType
{
    public const TYPE_VIRTUAL_CONTENT_LIST = 0;
    public const TYPE_AUDIO = 1;
    public const TYPE_VIDEO = 2;
    public const TYPE_PHOTO = 3;
    public const TYPE_SCAN_NOTES = 4;
    public const TYPE_SCAN_ANY = 5;
    public const TYPE_WORD = 6;
    public const TYPE_XML = 7;
    public const TYPE_OTHER = 99;
    // For new types need add converter to SubjectType!

    public const AUDIO_MIME_TYPE = 'audio';
    public const PHOTO_MIME_TYPE = 'image';
    public const VIDEO_MIME_TYPE = 'video';

    private const MIME_TYPES = [
        self::TYPE_AUDIO => self::AUDIO_MIME_TYPE,
        self::TYPE_PHOTO => self::PHOTO_MIME_TYPE,
        self::TYPE_VIDEO => self::VIDEO_MIME_TYPE,
    ];

    public const TYPES_BSU_CONVERTER = [
        "MP3" => self::TYPE_AUDIO,
        "JPEG" => self::TYPE_SCAN_ANY,
        "Microsoft Word" => self::TYPE_WORD,
        "Microsoft Word XML" => self::TYPE_XML,
    ];

    public static function getTypeByMime(string $mimeContentType): ?int
    {
        [$mimeType] = explode('/', $mimeContentType, 1);

        $type = array_search($mimeType, self::MIME_TYPES);

        return $type === false ? null : $type;
    }
}
