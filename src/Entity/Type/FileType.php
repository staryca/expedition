<?php

declare(strict_types=1);

namespace App\Entity\Type;

class FileType
{
    public const int TYPE_VIRTUAL_CONTENT_LIST = 0;
    public const int TYPE_AUDIO = 1;
    public const int TYPE_VIDEO = 2;
    public const int TYPE_PHOTO = 3;
    public const int TYPE_SCAN_NOTES = 4;
    public const int TYPE_SCAN_ANY = 5;
    public const int TYPE_WORD = 6;
    public const int TYPE_XML = 7;
    public const int TYPE_OTHER = 99;
    // For new types need add converter to SubjectType!

    public const string AUDIO_MIME_TYPE = 'audio';
    public const string PHOTO_MIME_TYPE = 'image';
    public const string VIDEO_MIME_TYPE = 'video';

    private const array MIME_TYPES = [
        self::TYPE_AUDIO => self::AUDIO_MIME_TYPE,
        self::TYPE_PHOTO => self::PHOTO_MIME_TYPE,
        self::TYPE_VIDEO => self::VIDEO_MIME_TYPE,
    ];

    public const array TYPES_BSU_CONVERTER = [
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
