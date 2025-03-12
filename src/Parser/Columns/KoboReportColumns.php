<?php

declare(strict_types=1);

namespace App\Parser\Columns;

class KoboReportColumns
{
    public const CODE = 'Код блока';
    public const PLACE = 'Населены пункт';
    public const PLACE_OTHER = 'Назва і сельсавет';
    public const DISTRICT = 'Раён';
    public const DISTRICT_OTHER = 'place_new_district_other';
    public const LAT = '_Каардыната_latitude';
    public const LON = '_Каардыната_longitude';
    public const TYPE = 'Тып блока';
    public const TYPE_OTHER = 'type_other';
    public const COMMENTS = 'Заўвагі да ўсяго блока';
    public const PHOTO = 'Фота старонак дзённіка';
    public const PHOTO_URL = 'Фота старонак дзённіка_URL';
    public const INFORMATION = 'Віды сабранай інфармацыі';
    public const LEADER = 'Кіраўнік групы';
    public const LEADER_OTHER = 'collectors_lead_other';
    public const PERSON_NOTES = 'Нататкі';
    public const PERSON_NOTES_OTHER = 'collectors_notes_other';
    public const PERSON_AUDIO = 'Аўдыё';
    public const PERSON_AUDIO_OTHER = 'collectors_audio_other';
    public const PERSON_VIDEO = 'Відэааператар';
    public const PERSON_VIDEO_OTHER = 'collectors_video_other';
    public const VIDEO_NOTES = 'Змест відэа';
    public const PERSON_PHOTO = 'Фатограф';
    public const PERSON_PHOTO_OTHER = 'collectors_video_001_other';
    public const PHOTO_NOTES = 'Змест фота';
    public const PERSON_COMMENT = 'Дадатковыя заўвагі пра збіральнікаў і іх ролі';
    public const INDEX = '_index';
    public const DATE_ACTION = 'Дата';
    public const DATE_CREATED = 'today';
}
