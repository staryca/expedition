<?php

declare(strict_types=1);

namespace App\Entity\Type;

class CategoryType
{
    public const KARAHOD = 10;
    public const DANCE = 20;
    public const QUADRILLE = 21;
    public const DANCE_GAME = 25;
    public const CHORUSES = 26;
    public const MELODY = 27;
    public const DANCE_MOVEMENTS = 28;
    public const SONGS = 30;
    public const POEMS = 31;
    public const CEREMONY = 40;
    public const GAME = 50;
    public const STORY = 80;
    public const ABOUT_DANCES = 81;
    public const PAREMIA = 82;
    public const FAIRY_TALE = 83;
    public const LULLABY = 84;
    public const RIDDLE = 85;
    public const PARABLE = 86;
    public const SPELL = 87;
    public const ABOUT_RECORD = 90;
    public const ABOUT_INFORMANT = 91;
    public const ABOUT_OTHER_INFORMANTS = 92;
    public const CHANGE_INFORMANTS = 93;
    public const STAGE_ACTION = 97;
    public const FILM = 98;
    public const OTHER = 99;
    // new types need add to database

    public const TYPES = [
        self::KARAHOD => 'карагод',
        self::DANCE => 'танец',
        self::QUADRILLE => 'кадрыля',
        self::DANCE_GAME => 'танец-гульня',
        self::CHORUSES => 'прыпеўкі',
        self::MELODY => 'найгрыш',
        self::DANCE_MOVEMENTS => 'рухі танца',
        self::SONGS => 'песня',
        self::POEMS => 'верш',
        self::CEREMONY => 'абрад',
        self::GAME => 'гульня',
        self::STORY => 'аповед',
        self::ABOUT_DANCES => 'згадванне пра танцы',
        self::PAREMIA => 'паремія', // малыя жанры
        self::FAIRY_TALE => 'казка',
        self::LULLABY => 'калыханка',
        self::RIDDLE => 'загадка', // таксама малы жанр
        self::PARABLE => 'прыпавесць',
        self::SPELL => 'замова',
        self::ABOUT_RECORD => 'звесткі пра запіс',
        self::ABOUT_INFORMANT => 'звесткі пра інфармантаў',
        self::ABOUT_OTHER_INFORMANTS => 'звесткі пра іншых інфармантаў',
        self::CHANGE_INFORMANTS => 'змена інфармантаў',
        self::STAGE_ACTION => 'сцэнічная дзея',
        self::FILM => 'фільм',
        self::OTHER => 'іншае',
    ];

    public const TYPES_MANY = [
        self::KARAHOD => 'карагоды',
        self::DANCE => 'танцы',
        self::QUADRILLE => 'кадрылі',
        self::DANCE_GAME => 'танец-гульні',
        self::CHORUSES => null,
        self::MELODY => 'найгрышы',
        self::DANCE_MOVEMENTS => null,
        self::SONGS => 'песні',
        self::POEMS => 'вершы',
        self::CEREMONY => 'абрады',
        self::GAME => 'гульні',
        self::STORY => 'аповеды',
        self::ABOUT_DANCES => null,
        self::PAREMIA => 'пареміі', // малыя жанры
        self::FAIRY_TALE => 'казкі',
        self::LULLABY => 'калыханкі',
        self::RIDDLE => 'загадкі', // таксама малы жанр
        self::PARABLE => 'прыпавесці',
        self::SPELL => 'замовы',
        self::ABOUT_RECORD => null,
        self::ABOUT_INFORMANT => null,
        self::ABOUT_OTHER_INFORMANTS => null,
        self::CHANGE_INFORMANTS => null,
        self::STAGE_ACTION => 'сцэнічныя дзеі',
        self::FILM => 'фільмы',
        self::OTHER => null,
    ];

    private const VARIANTS_SAME = [
        self::DANCE_MOVEMENTS => ['рух танца', 'рухі танцаў'],
        self::QUADRILLE => ['кадрыль'],
        self::CHORUSES => ['прыпеўка'],
        self::SONGS => ['песень', 'песьня', 'песьні'],
        self::STORY => ['аповеды пра', 'аповед пра', 'аповед', 'размова', 'расказ'],
        self::ABOUT_DANCES => [
            'згадваньне пра танцы', 'як танцавалі', 'пра танец', 'каманда ў танцах', 'каманды ў танцах'
        ],
        self::FAIRY_TALE => ['казка пра'],
        self::PARABLE => ['прытча'],
        self::SPELL => ['загавор', 'нагавор', 'шэпт', 'загаворы', 'нагаворы', 'шэпты'],
        self::PAREMIA => [
            'прыкмета', 'прыкметы', 'праклён', 'вітанне', 'вітання', 'афарызм', 'прымаўка', 'прымаўкі',
            'пажаданне', 'пажаданні', 'каламбур', 'тост', 'лічылка', 'лічылкі', 'прыказка', 'прыказкі',
        ],
        self::ABOUT_RECORD => [
            'зьвесткі пра запіс',
            'звесткі пра перадачу',
        ],
        self::ABOUT_INFORMANT => [
            'звесткі пра інфарманта',
            'звесткі пра інфармантку',
            'звесткі пра інфармантак',
            'звесткі пра інфармантаў',
            'звесткі пра інфарматара',
            'звесткі пра гурт-інфармант',
            'звесткі пра гарманіста',
            'зьвесткі пра інфарманта',
            'зьвесткі пра інфарматара',
        ],
        self::ABOUT_OTHER_INFORMANTS => ['зьвесткі пра іншых інфармантаў'],
        self::CHANGE_INFORMANTS => ['змена інфарманта', 'зьмена інфарманта', 'зьмена інфармантаў'],
    ];

    private const VARIANTS_OTHER = [
        self::SONGS => [
            'сьпявае', 'спявае', 'напеў', 'пятроўская', 'валачобная', 'восеньская', 'купальская', 'масленкавая',
            'сьпявалі', 'масьленіца', 'бяседная', 'масьленка', 'жніўная', 'любоўная', 'вясельная',
            'лірычная', 'салдацкая', 'талочная', 'балада', 'паставая', 'сямейна-бытавая', 'жартоўная',
            'вялікодная', 'піліпаўская', 'калядная', 'раманс', 'масьленыя', 'вясельныя', 'турэмная', 'рамансы',
            'веснавыя', 'лірычныя', 'на сене', 'провады ў армію', 'лірыка', 'хрэсьбінная',
            'у любы час', 'партызанская', 'карагодная', 'рэкруцкая', 'хрэсьбінская', 'летняя', 'касарская',
            'траецкая', 'сенакосная', 'пазаабрадавая', 'веснавая', 'свадзьбальная', 'жытняя', 'маёвая',
            'аўтарская',
        ],
        self::STORY => [
            'апавядае',
        ]
    ];

    private const VARIANTS_SONG_CEREMONY = [
        'каляды', 'хрэсьбіны', 'жніво', 'вяселле', 'вясельле', 'купальле', 'юр\'я', 'дажынкі',
    ];

    public const TEXT_JOIN = [
        self::OTHER => ['цікавыя словы, дыялекты', 'дыялекты', 'цікавыя выразы'],
    ];

    private const VARIANTS_GROUPED = [
        self::STORY => [
            ['што', 'такое'],
            ['як', 'гралі'],
            ['як', 'рабілі'],
            ['як', 'святкавалі'],
            ['як', 'спявалі'],
            ['што', 'рабілі'],
        ],
        self::ABOUT_DANCES => [
            ['як', 'танцавалі'],
            ['пра', 'танцы'],
        ],
    ];

    public const SYSTEM_TYPES = [
        self::ABOUT_RECORD,
        self::ABOUT_INFORMANT,
        self::ABOUT_OTHER_INFORMANTS,
        self::CHANGE_INFORMANTS,
    ];

    private const NOT_IMPORTANT_TYPES = [
        self::POEMS,
        self::PAREMIA,
        self::FAIRY_TALE,
        self::LULLABY,
        self::PARABLE,
        self::SPELL,
        self::RIDDLE,
        self::ABOUT_DANCES,
        self::OTHER,
    ];

    public const TYPES_BY_TAGS = [
        self::KARAHOD => ['карагоды', 'веснавыя карагоды'],
        self::DANCE => ['танцы'],
        self::QUADRILLE =>  ['кадрыля'],
        self::DANCE_GAME =>  ['танец-гульня'],
        self::CHORUSES => ['прыпеўкі'],
        self::MELODY =>  ['найгрыш'],
        self::DANCE_MOVEMENTS =>  ['рухі танца'],
        self::SONGS => [
            'лірычныя песні', 'балады', 'савецкая песня', 'жартоўная песня', 'раманс', 'песні суседзяў',
            'касецкая песня (сенакосная)', 'восеньская песня', 'талака і талочныя песні', 'аўтарскія песні',
        ],
        self::POEMS => ['народныя вершы'],
        self::CEREMONY =>  ['абрад'],
        self::GAME => ['гульні'],
        self::STORY => [
            'размова', 'звычаі', 'апісанні розныя', 'народная проза', 'рэлігійная паэзія', 'сатырычныя',
            'каляндарная абраднасць і паэзія', 'сямейная абраднасць і паэзія', 'дзіцячы фальклор',
            'народная драма',
        ],
        self::ABOUT_DANCES =>  ['згадванне пра танцы'],
        self::PAREMIA => [
            'прыкметы', 'праклёны', 'вітання', 'афарызмы', 'прымаўкі', 'выслоўі', 'прыгаворы',
            'пажаданні', 'каламбуры', 'тосты', 'лічылкі', 'прыказкі', 'забаўлянкі',
        ], // малыя жанры
        self::FAIRY_TALE => ['казка'],
        self::LULLABY => ['калыханкі'],
        self::RIDDLE => ['загадкі'],
        self::PARABLE => ['прытчы'],
        self::SPELL => ['замовы', 'загавор', 'нагавор', 'шэпт'],
        self::OTHER => [],
    ];

    private const DANCE_TYPES = [
        self::KARAHOD,
        self::DANCE,
        self::QUADRILLE,
        self::DANCE_GAME,
        self::CHORUSES,
        self::MELODY,
        self::DANCE_MOVEMENTS,
        self::ABOUT_DANCES,
    ];

    public static function getSingleName(int $type): ?string
    {
        return isset(self::TYPES[$type]) ? mb_ucfirst(self::TYPES[$type]) : null;
    }

    public static function getManyOrSingleName(int $type): ?string
    {
        if (isset(self::TYPES_MANY[$type])) {
            return mb_ucfirst(self::TYPES_MANY[$type]);
        }

        return self::getSingleName($type);
    }

    public static function getSingleManyNames(): array
    {
        $types = [];
        foreach (self::TYPES as $key => $type) {
            $types[$key] = mb_ucfirst($type) . (null !== self::TYPES_MANY[$key] ? '/' . self::TYPES_MANY[$key] : '');
        }

        return $types;
    }

    public static function getManyNames(bool $withSystem = true): array
    {
        $types = [];
        foreach (self::TYPES as $key => $type) {
            if (!$withSystem && self::isSystemType($key)) {
                continue;
            }
            $types[$key] = self::getManyOrSingleName($key);
        }

        return $types;
    }

    public static function isTypeNextBlock(int $type): bool
    {
        return in_array($type, [self::ABOUT_RECORD, self::CHANGE_INFORMANTS], true);
    }

    public static function isSystemType(int $type): bool
    {
        return in_array($type, [
            self::ABOUT_RECORD, self::CHANGE_INFORMANTS, self::ABOUT_INFORMANT, self::ABOUT_OTHER_INFORMANTS
        ], true);
    }

    public static function isImportantType(int $type): bool
    {
        return !in_array($type, self::NOT_IMPORTANT_TYPES, true);
    }

    public static function findId(string $text, string $textNext, bool $isAll = true): ?int
    {
        $text = mb_strtolower($text);

        foreach (self::TYPES as $key => $name) {
            if ($text === $name) {
                return $key;
            }
        }

        foreach (self::TYPES_MANY as $key => $name) {
            if ($text === $name) {
                return $key;
            }
        }

        foreach (self::TYPES as $key => $name) {
            if (!$isAll && !in_array($key, [self::ABOUT_RECORD, self::ABOUT_INFORMANT, self::ABOUT_OTHER_INFORMANTS])) {
                continue;
            }
            if (false !== mb_strstr($text, $name)) {
                return $key;
            }
        }

        foreach (self::VARIANTS_SAME as $key => $variants) {
            foreach ($variants as $variant) {
                if (
                    !$isAll
                    && !in_array($key, [self::ABOUT_RECORD, self::ABOUT_INFORMANT, self::ABOUT_OTHER_INFORMANTS])
                ) {
                    continue;
                }
                if (false !== mb_strstr($text, $variant)) {
                    return $key;
                }
            }
        }

        foreach (self::VARIANTS_GROUPED as $key => $variants) {
            foreach ($variants as $words) {
                $hasAll = true;
                foreach ($words as $word) {
                    if (false === mb_strstr($text, $word)) {
                        $hasAll = false;
                        break;
                    }
                }

                if ($hasAll) {
                    return $key;
                }
            }
        }

        return self::findOtherVariantsId($text, $textNext);
    }

    public static function findOtherVariantsId(string $text, string $textNext): ?int
    {
        $text = mb_strtolower($text);

        foreach (self::VARIANTS_OTHER as $key => $variants) {
            foreach ($variants as $variant) {
                if (false !== mb_strstr($text, $variant)) {
                    return $key;
                }
            }
        }

        foreach (self::VARIANTS_SONG_CEREMONY as $variant) {
            if (false !== mb_strstr($text, $variant)) {
                $char = mb_substr($textNext, 0, 1);

                return in_array($char, ['"', '']) ? self::SONGS : self::CEREMONY;
            }
        }

        return null;
    }

    public static function getId(string $text, string $textNext): ?int
    {
        $text = mb_strtolower($text);

        foreach (self::TYPES as $key => $name) {
            if ($text === $name) {
                return $key;
            }
        }

        foreach (self::TYPES_MANY as $key => $name) {
            if ($text === $name) {
                return $key;
            }
        }

        foreach (self::VARIANTS_SAME as $key => $variants) {
            if (in_array($text, $variants, true)) {
                return $key;
            }
        }

        return self::getIdForOther($text, $textNext);
    }

    public static function getIdForOther(string $text, string $textNext): ?int
    {
        if (in_array(mb_strtolower($text), self::TEXT_JOIN[self::OTHER], true)) {
            return self::OTHER;
        }

        foreach (self::VARIANTS_OTHER as $key => $variants) {
            if (in_array($text, $variants, true)) {
                return $key;
            }
        }

        if (in_array($text, self::VARIANTS_SONG_CEREMONY, true)) {
            $char = mb_substr($textNext, 0, 1);

            return in_array($char, ['"', '']) ? self::SONGS : self::CEREMONY;
        }

        return null;
    }

    public static function getCategoryByTags(string $tag): ?int
    {
        $tag = mb_strtolower($tag);

        foreach (self::TYPES_BY_TAGS as $type => $tags) {
            if (in_array($tag, $tags, true)) {
                return $type;
            }
        }

        return null;
    }

    public static function detectCategory(string $content, ?string $notes = null, ?int $default = null): ?int
    {
        $pos = mb_strpos($content, ') ');
        if ($pos !== false && $pos < 3) {
            $content = trim(mb_substr($content, $pos + 1));
        }

        $category = self::findId($content, '', false);
        if ($category === null) {
            $category = self::findId($notes, '') ?? $default;
        }

        return $category;
    }

    public static function asDanceType(int $category): bool
    {
        return in_array($category, self::DANCE_TYPES, true);
    }
}
