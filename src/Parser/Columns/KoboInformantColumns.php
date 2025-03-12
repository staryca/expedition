<?php

declare(strict_types=1);

namespace App\Parser\Columns;

class KoboInformantColumns
{
    public const SEX = 'Пол';
    public const NAME = 'Поўнае імя';
    public const BIRTH_YEAR = 'Год народзінаў';
    public const CONFESSION = 'Канфесія';
    public const CONFESSION_OTHER = 'Іншая канфесія';
    public const BIRTH_PLACE = 'Месца народжання';
    public const BIRTH_PLACE_ADDITIONAL = 'Дадаць населены пункт (раён, сельсавет)';
    public const COMMENTS = 'Дадатковыя заўвагі пра інфарматара';
    public const PHOTO = 'Фотаздымак інфарматара';
    public const PHOTO_URL = 'Фотаздымак інфарматара_URL';
    public const INDEX = '_index';
    public const INDEX_REPORT = '_parent_index';
    public const DATE_ADDED = '_submission__submission_time';
}
