<?php

declare(strict_types=1);

namespace App\Entity\Type;

class ReportBlockType
{
    public const TYPE_UNDEFINED = 0;
    public const TYPE_CONVERSATION = 1;
    public const TYPE_VILLAGE_TOUR = 2;
    public const TYPE_CEMETERY_TOUR = 3;
    public const TYPE_BAND_RECORD = 4;
    public const TYPE_PHOTO_OF_ITEMS = 5;

    public const TYPE_CHANGE_INFORMATION = 6;
    public const TYPE_PHOTO_MATERIALS = 7;
    public const TYPE_OLD_AUDIO = 8;
    public const TYPE_CEREMONY = 9;
    public const TYPE_OLD_PHOTO = 10;
    public const TYPE_PHOTO_OF_OBJECTS = 11;

    public const DETECT = [
        self::TYPE_UNDEFINED => ['іншае'],
        self::TYPE_CONVERSATION => ['гутарка', 'вечарына'],
        self::TYPE_VILLAGE_TOUR => ['агляд вёскі', 'здымкі вёскі'],
        self::TYPE_CEMETERY_TOUR => ['агляд могілак', 'здымкі могілак', 'здымка могілак', 'агляд магілак',],
        self::TYPE_BAND_RECORD => ['запіс гурта'],
        self::TYPE_PHOTO_OF_ITEMS => [
            'здымка фондаў, калекцый', 'здымка калекцый', 'здымкі фондаў, калекцый, рэчаў', 'здымкі калекцый',
            'здымка фондаў', 'агляд музея',
        ],
        self::TYPE_CHANGE_INFORMATION => ['абмен інфармацыяй'],
        self::TYPE_PHOTO_MATERIALS => ['адфотканныя матэрыялы'],
        self::TYPE_OLD_AUDIO => ['старыя аўдыёзапісы'],
        self::TYPE_CEREMONY => ['агляд абрада'],
        self::TYPE_OLD_PHOTO => ['старыя фотаздымкі'],
        self::TYPE_PHOTO_OF_OBJECTS => [
            'здымка могілак, аб’ектаў', 'здымка аб\'екта сакральнай тапаграфіі', 'агляд крыніцы',
            'агляд сакральнага аб\'екта', 'здымка аб\'ектаў',
        ],
    ];

    public const TYPES = [
        self::TYPE_CONVERSATION => 'гутарка',
        self::TYPE_VILLAGE_TOUR => 'агляд вёскі',
        self::TYPE_CEMETERY_TOUR => 'агляд могілак',
        self::TYPE_BAND_RECORD => 'запіс гурта',
        self::TYPE_PHOTO_OF_ITEMS => 'здымкі фондаў, калекцый, рэчаў',
        self::TYPE_CHANGE_INFORMATION => 'абмен інфармацыяй',
        self::TYPE_PHOTO_MATERIALS => 'адфотканныя матэрыялы',
        self::TYPE_OLD_AUDIO => 'старыя аўдыёзапісы',
        self::TYPE_CEREMONY => 'агляд абрада',
        self::TYPE_OLD_PHOTO => 'старыя фотаздымкі',
        self::TYPE_PHOTO_OF_OBJECTS => 'здымка розных аб\'екта, агляд крыніцы',
        self::TYPE_UNDEFINED => 'іншае',
    ];

    public static function getType(string $text): int
    {
        $text = mb_strtolower($text);

        foreach (self::DETECT as $type => $values) {
            if (in_array($text, $values, true)) {
                return $type;
            }
        }

        foreach (self::DETECT as $type => $values) {
            foreach ($values as $value) {
                if (0 === mb_strpos($text, $value)) {
                    return $type;
                }
            }
        }

        return self::TYPE_UNDEFINED;
    }
}
