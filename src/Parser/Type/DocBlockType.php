<?php

declare(strict_types=1);

namespace App\Parser\Type;

class DocBlockType
{
    public const BLOCK_SEC_TITLE = 1;
    public const BLOCK_SEC_NAME = 2;
    public const BLOCK_EXP_NAME = 3;
    public const BLOCK_REPORT_TITLE = 4;
    public const BLOCK_DATE_TITLE = 10;
    public const BLOCK_DATE_EXP = 11;
    public const BLOCK_USERS_TITLE = 12;
    public const BLOCK_USERS_DATA = 13;
    public const BLOCK_LOCATION_TITLE = 20;
    public const BLOCK_LOCATION_DATA = 21;
    public const BLOCK_NUMBER = 30;
    public const BLOCK_TYPE_TITLE = 31;
    public const BLOCK_TYPE_DATA = 32;
    public const BLOCK_INFORMATION_TITLE = 33;
    public const BLOCK_INFORMATION_AUDIO = 34;
    public const BLOCK_INFORMATION_PHOTO = 35;
    public const BLOCK_INFORMATION_VIDEO = 36;
    public const BLOCK_INFORMATION_VIDEO_TAPES = 37;
    public const BLOCK_INFORMATION_OBJECT = 38;
    public const BLOCK_INFORMATION_DOC = 39;
    public const BLOCK_INFORMATION_GPS = 40;
    public const BLOCK_INFORMATION_DATA = 41;
    public const BLOCK_INFORMANTS_TITLE = 50;
    public const BLOCK_INFORMANTS_DESCRIPTION = 51;
    public const BLOCK_INFORMANTS_DATA = 52;
    public const BLOCK_CONTENT_TITLE = 60;
    public const BLOCK_CONTENT_DESCRIPTION = 61;
    public const BLOCK_CONTENT_DATA = 62;
    public const BLOCK_TIPS_TITLE = 70;
    public const BLOCK_TIPS_DATA = 71;
    public const BLOCK_PLAN_TITLE = 80;
    public const BLOCK_PLAN_DATA = 81;
    public const BLOCK_PAGE = 90;

    public const NEXT = [
        self::BLOCK_SEC_TITLE => [self::BLOCK_SEC_NAME],
        self::BLOCK_SEC_NAME => [self::BLOCK_EXP_NAME],
        self::BLOCK_EXP_NAME => [self::BLOCK_REPORT_TITLE],
        self::BLOCK_REPORT_TITLE => [self::BLOCK_DATE_TITLE],
        self::BLOCK_DATE_TITLE => [self::BLOCK_DATE_EXP],
        self::BLOCK_DATE_EXP => [self::BLOCK_USERS_TITLE],
        self::BLOCK_USERS_TITLE => [self::BLOCK_USERS_DATA],
        self::BLOCK_USERS_DATA => [self::BLOCK_LOCATION_TITLE],
        self::BLOCK_LOCATION_TITLE => [self::BLOCK_LOCATION_DATA],
        self::BLOCK_LOCATION_DATA => [self::BLOCK_NUMBER],
        self::BLOCK_NUMBER => [self::BLOCK_TYPE_TITLE],
        self::BLOCK_TYPE_TITLE => [self::BLOCK_TYPE_DATA],
        self::BLOCK_TYPE_DATA => [self::BLOCK_INFORMATION_TITLE],
        self::BLOCK_INFORMATION_TITLE => [self::BLOCK_INFORMATION_AUDIO],
        self::BLOCK_INFORMATION_AUDIO => [self::BLOCK_INFORMATION_DATA],
        self::BLOCK_INFORMATION_PHOTO => [self::BLOCK_INFORMATION_DATA],
        self::BLOCK_INFORMATION_VIDEO => [self::BLOCK_INFORMATION_DATA],
        self::BLOCK_INFORMATION_VIDEO_TAPES => [self::BLOCK_INFORMATION_DATA],
        self::BLOCK_INFORMATION_OBJECT => [self::BLOCK_INFORMATION_DATA],
        self::BLOCK_INFORMATION_DOC => [self::BLOCK_INFORMATION_DATA],
        self::BLOCK_INFORMATION_GPS => [self::BLOCK_INFORMATION_DATA],
        self::BLOCK_INFORMATION_DATA => [
            self::BLOCK_INFORMATION_PHOTO, self::BLOCK_INFORMATION_VIDEO, self::BLOCK_INFORMATION_VIDEO_TAPES,
            self::BLOCK_INFORMATION_OBJECT, self::BLOCK_INFORMATION_DOC, self::BLOCK_INFORMATION_GPS,
            self::BLOCK_INFORMANTS_TITLE,
        ],
        self::BLOCK_INFORMANTS_TITLE => [self::BLOCK_INFORMANTS_DESCRIPTION, self::BLOCK_INFORMANTS_DATA],
        self::BLOCK_INFORMANTS_DESCRIPTION => [self::BLOCK_INFORMANTS_DATA],
        self::BLOCK_INFORMANTS_DATA => [self::BLOCK_CONTENT_TITLE, self::BLOCK_INFORMANTS_DATA],
        self::BLOCK_CONTENT_TITLE => [self::BLOCK_CONTENT_DESCRIPTION, self::BLOCK_CONTENT_DATA],
        self::BLOCK_CONTENT_DESCRIPTION => [self::BLOCK_CONTENT_DATA],
        self::BLOCK_CONTENT_DATA => [
            self::BLOCK_CONTENT_DATA, self::BLOCK_LOCATION_TITLE, self::BLOCK_NUMBER, self::BLOCK_TIPS_TITLE
        ],
        self::BLOCK_TIPS_TITLE => [self::BLOCK_TIPS_DATA],
        self::BLOCK_TIPS_DATA => [self::BLOCK_TIPS_DATA, self::BLOCK_PLAN_TITLE],
        self::BLOCK_PLAN_TITLE => [self::BLOCK_PLAN_DATA],
        self::BLOCK_PLAN_DATA => [self::BLOCK_PLAN_DATA, self::BLOCK_PAGE],
    ];

    public const CONTAINS = [
        self::BLOCK_SEC_TITLE => 'СЭТ,',
        self::BLOCK_SEC_NAME => 'Студэнцкае этнаграфічнае таварыства',
        self::BLOCK_EXP_NAME => 'Экспедыцыя',
        self::BLOCK_REPORT_TITLE => 'Справаздача пра экспедыцыйны дзень',
        self::BLOCK_DATE_TITLE => 'Год-месяц-дзень',
        self::BLOCK_USERS_TITLE => 'Шыфр кіраўніка і склад групы',
        self::BLOCK_LOCATION_TITLE => 'Населены пункт',
        self::BLOCK_NUMBER => 'Блок даследавання',
        self::BLOCK_TYPE_TITLE => 'Тып (',
        self::BLOCK_INFORMATION_TITLE => 'Віды сабранай інфармацыі',
        self::BLOCK_INFORMATION_AUDIO => 'Audio',
        self::BLOCK_INFORMATION_PHOTO => 'Photo',
        self::BLOCK_INFORMATION_VIDEO => 'Video',
        self::BLOCK_INFORMATION_VIDEO_TAPES => 'Video from tapes',
        self::BLOCK_INFORMATION_OBJECT => 'Мат. аб’екты',
        self::BLOCK_INFORMATION_DOC => 'Дакум.',
        self::BLOCK_INFORMATION_GPS => 'GPS',
        self::BLOCK_INFORMANTS_TITLE => 'Інфарматар(ы)',
        self::BLOCK_INFORMANTS_DESCRIPTION => 'Фармат: Прозвішча',
        self::BLOCK_CONTENT_TITLE => 'Кароткі змест',
        self::BLOCK_CONTENT_DESCRIPTION => 'Размяркоўвайце сабраныя звесткі па тэмах у',
        self::BLOCK_TIPS_TITLE => 'Наводкі:',
        self::BLOCK_PLAN_TITLE => 'Планы:',
    ];
}
