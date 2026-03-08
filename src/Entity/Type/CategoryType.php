<?php

declare(strict_types=1);

namespace App\Entity\Type;

class CategoryType
{
    public const int KARAHOD = 10;
    public const int DANCE = 20;
    public const int QUADRILLE = 21;
    public const int DANCE_GAME = 25;
    public const int CHORUSES = 26;
    public const int MELODY = 27;
    public const int DANCE_MOVEMENTS = 28;
    public const int SONGS = 30;
    public const int POEMS = 31;
    public const int CEREMONY = 40;
    public const int GAME = 50;
    public const int STORY = 80;
    public const int ABOUT_DANCES = 81;
    public const int PAREMIA = 82;
    public const int FAIRY_TALE = 83;
    public const int LULLABY = 84;
    public const int RIDDLE = 85;
    public const int PARABLE = 86;
    public const int SPELL = 87;
    public const int ABOUT_RECORD = 90;
    public const int ABOUT_INFORMANT = 91;
    public const int ABOUT_OTHER_INFORMANTS = 92;
    public const int CHANGE_INFORMANTS = 93;
    public const int STAGE_ACTION = 97;
    public const int FILM = 98;
    public const int OTHER = 99;
    // new types need add to database

    public const array TYPES = [
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

    public const array TYPES_MANY = [
        self::KARAHOD => 'карагоды',
        self::DANCE => 'танцы',
        self::QUADRILLE => 'кадрылі',
        self::DANCE_GAME => 'танцы-гульні',
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

    private const array VARIANTS_SAME = [
        self::DANCE_MOVEMENTS => ['рух танца', 'рухі танцаў'],
        self::QUADRILLE => ['кадрыль', 'кадрылі'],
        self::MELODY => ['гукарад', 'сігнал', 'марш', 'гучанне'],
        self::CHORUSES => ['прыпеўка', 'прыпевы'],
        self::SONGS => ['песень', 'песьня', 'песьні', 'галашэнне'],
        self::STORY => ['аповеды пра', 'аповед пра', 'аповед', 'размова', 'расказ', 'апавяданні'],
        self::ABOUT_DANCES => [
            'згадваньне пра танцы', 'як танцавалі', 'пра танец', 'каманда ў танцах', 'каманды ў танцах'
        ],
        self::FAIRY_TALE => ['казка пра'],
        self::PARABLE => ['прытча'],
        self::SPELL => ['загавор', 'нагавор', 'шэпт', 'загаворы', 'нагаворы', 'шэпты'],
        self::PAREMIA => [
            'прыкмета', 'прыкметы', 'праклён', 'вітанне', 'вітання', 'афарызм', 'прымаўка', 'прымаўкі',
            'пажаданне', 'пажаданні', 'каламбур', 'тост', 'лічылка', 'лічылкі', 'прыказка', 'прыказкі',
            'прыгавор', 'прыгаворы', 'дражнілка',
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
        self::CHANGE_INFORMANTS => [
            'змена інфарманта',
            'зьмена інфарманта',
            'зьмена інфармантаў',
            'змена інфарматара',
            'замена гарманіста',
            'змена гарманіста',
        ],
    ];

    private const array VARIANTS_OTHER = [
        self::SONGS => [
            'сьпявае', 'спявае', 'напеў', 'пятроўская', 'валачобная', 'восеньская', 'купальская', 'масленкавая',
            'сьпявалі', 'масьленіца', 'бяседная', 'масьленка', 'жніўная', 'любоўная', 'вясельная',
            'лірычная', 'салдацкая', 'талочная', 'балада', 'паставая', 'сямейна-бытавая', 'жартоўная',
            'вялікодная', 'піліпаўская', 'калядная', 'раманс', 'масьленыя', 'вясельныя', 'турэмная', 'рамансы',
            'веснавыя', 'лірычныя', 'на сене', 'провады ў армію', 'лірыка', 'хрэсьбінная',
            'у любы час', 'партызанская', 'карагодная', 'рэкруцкая', 'хрэсьбінская', 'летняя', 'касарская',
            'траецкая', 'сенакосная', 'веснавая', 'свадзьбальная', 'свадзебная', 'жытняя', 'маёвая',
            'аўтарская', 'застольная', 'шчадроўка', 'казацкая', 'царкоўная', 'сацыяльна-бытавая',
        ],
        self::STORY => [
            'апавядае', 'анекдот', 'былічка',
        ]
    ];

    private const array VARIANTS_SONG_CEREMONY = [
        'каляды', 'хрэсьбіны', 'жніво', 'вяселле', 'вясельле', 'купальле', 'юр\'я', 'дажынкі',
    ];

    public const array TEXT_JOIN = [
        self::OTHER => ['цікавыя словы, дыялекты', 'дыялекты', 'цікавыя выразы'],
    ];

    private const array VARIANTS_GROUPED = [
        self::CHORUSES => [
            ['прыпеўка да '],
        ],
        self::STORY => [
            ['што', 'такое'],
            ['як', 'гралі'],
            ['як', 'рабілі'],
            ['як', 'святкавалі'],
            ['як', 'спявалі'],
            ['што', 'рабілі'],
            ['як', 'запрашалі'],
            ['калі', 'гралі'],
            ['як', 'разводзяць'],
        ],
        self::ABOUT_DANCES => [
            ['як', 'танцавалі'],
            ['пра', 'танцы'],
            ['якія', 'былі', 'танцы'],
            ['якія', 'гулялі', 'танцы'],
        ],
        self::MELODY => [
            ['на', 'язык'],
        ],
        self::SONGS => [
            ['узгадка', 'песні'],
        ],
        self::OTHER => [
            ['праверка', 'апаратуры'],
        ],
    ];

    private const array SYSTEM_TYPES = [
        self::ABOUT_RECORD,
        self::ABOUT_INFORMANT,
        self::ABOUT_OTHER_INFORMANTS,
        self::CHANGE_INFORMANTS,
    ];

    public const array TYPES_WITH_DANCES = [
        self::DANCE,
        self::MELODY,
        self::ABOUT_DANCES,
        self::DANCE_MOVEMENTS,
        self::CHORUSES,
    ];

    private const array NOT_IMPORTANT_TYPES = [
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

    public const array TYPES_BY_TAGS = [
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
            'народная драма', 'легенды', 'легенда', 'жарты', 'фальклорная проза', 'страшылкі', 'байкі',
            'анекдот',
        ],
        self::ABOUT_DANCES =>  ['згадванне пра танцы'],
        self::PAREMIA => [
            'прыкметы', 'праклёны', 'вітання', 'афарызмы', 'прымаўкі', 'выслоўі', 'прыгаворы',
            'пажаданні', 'каламбуры', 'тосты', 'лічылкі', 'прыказкі', 'забаўлянкі', 'дражнілкі',
            'слоўныя прыгаворы',
        ], // малыя жанры
        self::FAIRY_TALE => ['казка'],
        self::LULLABY => ['калыханкі'],
        self::RIDDLE => ['загадкі'],
        self::PARABLE => ['прытчы'],
        self::SPELL => ['замовы', 'загавор', 'нагавор', 'шэпт'],
        self::OTHER => [],
    ];

    public const array TAGS = [
        self::KARAHOD => ['#традыцыйнытанец', '#беларускінароднытанец', '#карагод', '#гуртавытанец', '#абрад', '#фальклорБеларусі', '#МіколаКозенка', '#традиционныйтанец', '#белорусскийнародныйтанец', '#хоровод', '#обряд', '#народнаякультурабелорусов', '#фольклорБеларуси', '#НиколайКозенко', '#traditionalculture', '#traditionaldance', '#socialdancing', '#Belarusianfolklore', '#archiukozenka'],
        self::DANCE => ['#традыцыйнытанец', '#беларускінароднытанец', '#побытавытанец', '#фальклорБеларусі', '#МіколаКозенка', '#традиционныйтанец', '#белорусскийнародныйтанец', '#бытовойтанец', '#народнаякультурабелорусов', '#фольклорБеларуси', '#НиколайКозенко', '#traditionaldance', '#socialdancing', '#folkmusik', '#Belarusianfolklore', '#archiukozenka'],
        self::QUADRILLE => ['#традыцыйнытанец', '#беларускінароднытанец', '#побытавытанец', '#кадрыля', '#парнагуртавытанец', '#фальклорБеларусі', '#МіколаКозенка', '#традиционныйтанец', '#белорусскийнародныйтанец', '#бытовойтанец', '#кадриль', '#народнаякультурабелорусов', '#фольклорБеларуси', '#НиколайКозенко', '#traditionaldance', '#socialdancing', '#Belarusianfolklore', '#archiukozenka'],
        self::DANCE_GAME => ['#традыцыйнытанец', '#беларускінароднытанец', '#побытавытанец', '#танецгульня', '#беларускіянародныягульні', '#фальклорБеларусі', '#МіколаКозенка', '#традиционныйтанец', '#белорусскийнародныйтанец', '#бытовойтанец', '#белорусскиенародныеигры', '#народнаякультурабелорусов', '#фольклорБеларуси', '#НиколайКозенко', '#traditionaldance', '#socialdancing', '#Belarusianfolklore', '#archiukozenka'],
        self::CHORUSES => ['#традыцыйнытанец', '#беларускінароднытанец', '#побытавытанец', '#прыпеўкі', '#фальклорБеларусі', '#МіколаКозенка', '#традиционныйтанец', '#белорусскийнародныйтанец', '#бытовойтанец', '#припевки', '#частушки', '#народнаякультурабелорусов', '#фольклорБеларуси', '#НиколайКозенко', '#traditionaldance', '#socialdancing', '#folkmusik', '#traditionalsinging', '#Belarusianfolklore', '#archiukozenka'],
        self::MELODY => ['#традыцыйнытанец', '#беларускінароднытанец', '#побытавытанец', '#вясковыямузыкі', '#танцавальнынайгрыш', '#фальклорБеларусі', '#МіколаКозенка', '#традиционныйтанец', '#белорусскийнародныйтанец', '#бытовойтанец', '#народнаятанцевальнаямузыка', '#деревенскиемузыканты', '#фольклорБеларуси', '#народнаякультурабелорусов', '#НиколайКозенко', '#traditionaldance', '#socialdancing', '#folkmusik', '#traditionalmusik', '#Belarusianfolklore', '#archiukozenka'],
        self::DANCE_MOVEMENTS => ['#традыцыйнытанец', '#беларускінароднытанец', '#побытавытанец', '#фальклорБеларусі', '#МіколаКозенка', '#традиционныйтанец', '#белорусскийнародныйтанец', '#бытовойтанец', '#фольклорБеларуси', '#народнаякультурабелорусов', '#НиколайКозенко', '#traditionaldance', '#socialdancing', '#Belarusianfolklore', '#archiukozenka'],
        self::SONGS => ['#песенныфальклор', '#народнаяпесня', '#фальклорБеларусі', '#традыцыйнаякультура', '#МіколаКозенка', '#песенныйфольклор', '#традиционнаякультура', '#народнаякультурабелорусов', '#фольклорБеларуси', '#НиколайКозенко', '#traditionalsinging', '#folkmusik', '#Belarusianfolklore', '#archiukozenka'],
        self::CEREMONY => ['#беларускіяабрады', '#абрад', '#фальклорБеларусі', '#МіколаКозенка', '#белорусскиеобряды', '#обряд', '#традиционнаякультура', '#народнаякультурабелорусов', '#фольклорБеларуси', '#НиколайКозенко', '#traditionalritual', '#Belarusianfolklore', '#archiukozenka'],
        self::GAME => ['#беларускіянародныягульні', '#фальклорБеларусі', '#традыцыйнаякультура', '#МіколаКозенка', '#белорусскиенародныеигры', '#традиционнаякультура', '#народнаякультурабелорусов', '#фольклорБеларуси', '#НиколайКозенко', '#traditionalculture', '#Belarusianfolklore', '#archiukozenka'],
        self::STORY => ['#традыцыйнаякультура', '#фальклорБеларусі', '#МіколаКозенка', '#традиционнаякультура', '#народнаякультурабелорусов', '#фольклорБеларуси', '#НиколайКозенко', '#traditionalculture', '#Belarusianfolklore', '#archiukozenka'],
        self::STAGE_ACTION => ['#традыцыйнаякультура', '#фальклорБеларусі', '#МіколаКозенка', '#традиционнаякультура', '#народнаякультурабелорусов', '#фольклорБеларуси', '#НиколайКозенко', '#traditionalculture', '#Belarusianfolklore', '#archiukozenka'],
        self::FILM => ['#традыцыйнаякультура', '#фальклорБеларусі', '#МіколаКозенка', '#традиционнаякультура', '#народнаякультурабелорусов', '#фольклорБеларуси', '#НиколайКозенко', '#traditionalculture', '#Belarusianfolklore', '#archiukozenka'],
        self::OTHER => ['#традыцыйнаякультура', '#фальклорБеларусі', '#МіколаКозенка', '#традиционнаякультура', '#народнаякультурабелорусов', '#фольклорБеларуси', '#НиколайКозенко', '#traditionalculture', '#Belarusianfolklore', '#archiukozenka'],
    ];

    private const array DANCE_TYPES = [
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
            if (false !== mb_strstr($text, $name) && $key !== self::FAIRY_TALE) {
                if (
                    empty(preg_grep('/(' . $name . '([а-я]|і|ў)|([а-я]|і|ў)' . $name . ')/u', [$text]))
                    && empty(preg_grep('/( на ' . $name . ')|(пра ' . $name . ')/u', [$text])) // прапускаць, напрыклад, "на танец ..."
                ) {
                    return $key;
                }
            }
        }

        foreach (self::VARIANTS_SAME as $key => $variants) {
            foreach ($variants as $variant) {
                if (
                    !$isAll
                    && in_array($key, [self::ABOUT_RECORD, self::ABOUT_INFORMANT, self::ABOUT_OTHER_INFORMANTS])
                ) {
                    continue;
                }
                if (false !== mb_strstr($text, $variant)) {
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

        return array_find_key(self::TYPES_BY_TAGS, fn($tags) => in_array($tag, $tags, true));
    }

    public static function detectCategory(string $content, ?string $notes = null, ?int $default = null): ?int
    {
        $category = self::findId($content, '', true);
        if ($category === null && !empty($notes)) {
            $category = self::findId($notes, '') ?? $default;
        }

        return $category;
    }

    public static function detectCategoryByName(string $content): ?int
    {
        $category = null;
        foreach (self::TYPES_MANY as $key => $name) {
            if (null === $name) {
                continue;
            }
            if (mb_strpos($content, $name) !== false) {
                if (null !== $category && $category !== $key) {
                    return null;
                }
                $category = $key;
            }
        }

        return $category;
    }

    public static function asDanceType(int $category): bool
    {
        return in_array($category, self::DANCE_TYPES, true);
    }

    public static function getDanceMovementName(array $words): string
    {
        $texts = [];
        foreach ($words as $word) {
            $texts = array_merge($texts, explode(' ', $word));
        }

        foreach ($texts as $key => $word) {
            if (mb_substr($word, -1) === 'ы' || mb_substr($word, -1) === 'і') {
                $texts[$key] = mb_substr($word, 0, -1) . 'ага';
            }
        }
        $text = implode(' ', $texts);
        if (!empty($text)) {
            $text .= ' ';
        }

        return 'рухі ' . $text . 'танца';
    }

    public static function getTags(int $category): array
    {
        return self::TAGS[$category] ?? [];
    }
}
