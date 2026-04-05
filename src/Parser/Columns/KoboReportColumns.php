<?php

declare(strict_types=1);

namespace App\Parser\Columns;

class KoboReportColumns
{
    public const string CODE = 'Код блока';
    public const string PLACE = 'Населены пункт';
    public const string PLACE_OTHER = 'Назва і сельсавет';
    public const string DISTRICT = 'Раён';
    public const string DISTRICT_OTHER = 'place_new_district_other';
    public const string LAT = '_Каардыната_latitude';
    public const string LON = '_Каардыната_longitude';
    public const string TYPE = 'Тып блока';
    public const string TYPE_OTHER = 'type_other';
    public const string COMMENTS = 'Заўвагі да ўсяго блока';
    public const string PHOTO = 'Фота старонак дзённіка';
    public const string PHOTO_URL = 'Фота старонак дзённіка_URL';
    public const string INFORMATION = 'Віды сабранай інфармацыі';
    public const string LEADER = 'Кіраўнік групы';
    public const string LEADER_OTHER = 'collectors_lead_other';
    public const string PERSON_NOTES = 'Нататкі';
    public const string PERSON_NOTES_OTHER = 'collectors_notes_other';
    public const string PERSON_AUDIO = 'Аўдыё';
    public const string PERSON_AUDIO_OTHER = 'collectors_audio_other';
    public const string PERSON_VIDEO = 'Відэааператар';
    public const string PERSON_VIDEO_OTHER = 'collectors_video_other';
    public const string VIDEO_NOTES = 'Змест відэа';
    public const string PERSON_PHOTO = 'Фатограф';
    public const string PERSON_PHOTO_OTHER = 'collectors_video_001_other';
    public const string PHOTO_NOTES = 'Змест фота';
    public const string PERSON_COMMENT = 'Дадатковыя заўвагі пра збіральнікаў і іх ролі';
    public const string INDEX = '_index';
    public const string DATE_ACTION = 'Дата';
    public const string DATE_CREATED = 'today';
}
