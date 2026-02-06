<?php

declare(strict_types=1);

namespace App\Entity\Additional;

class FileMarkerAdditional
{
    // Columns
    public const string LOCAL_NAME = 'localName';
    public const string BASE_NAME = 'baseName';
    public const string YOUTUBE = 'youtube';
    public const string DANCE_TYPE = 'danceType';
    public const string IMPROVISATION = 'improvisation';
    public const string RITUAL = 'ritual';
    public const string TRADITION = 'tradition';
    public const string SOURCE = 'source';
    public const string DATE_ACTION_NOTES = 'dateActionNotes';
    public const string TMKB = 'tmkb';
    public const string NUMBER = 'number';

    // Temp
    public const string INFORMANTS_TEXT = 'informantsText';

    // Values
    public const string TRADITION_LATE = 'познетрадыцыйны';
    public const string TRADITION_ARCHAIC = 'архаіка';
    public const string TRADITION_EARLY = 'раннетрадыцыйны';

    public const string IMPROVISATION_VALUE = 'імправізацыйны';
    public const string IMPROVISATION_COMMANDS = 'з камандамі';
    public const string IMPROVISATION_MIKITA_CASE = 'тыпу Мікіта';
    public const string IMPROVISATION_QUADRILLE = 'кадрыльнага тыпу';

    // status fields
    public const string STATUS_UPDATED = 'statusUpdated';
    public const string STATUS_ACTIVE = 'statusActive';
    public const string STATUS_SHEDULED = 'statusScheduled';

    public static function getTradition(string $value): string
    {
        $value = mb_strtolower($value);

        if ($value === self::TRADITION_LATE) {
            return self::TRADITION_LATE;
        }

        if ($value === self::TRADITION_ARCHAIC || $value === self::TRADITION_EARLY) {
            return self::TRADITION_EARLY;
        }

        return '';
    }

    public static function getImprovisation(string $value): string
    {
        return str_replace('тып ', 'тыпу ', $value);
    }

    public static function getAllImprovisations(): array
    {
        return [
            1 => self::IMPROVISATION_VALUE,
            2 => self::IMPROVISATION_COMMANDS,
            3 => self::IMPROVISATION_MIKITA_CASE,
            4 => self::IMPROVISATION_QUADRILLE,
        ];
    }
}
