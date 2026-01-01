<?php

declare(strict_types=1);

namespace App\Entity\Additional;

class FileMarkerAdditional
{
    // Columns
    public const LOCAL_NAME = 'localName';
    public const BASE_NAME = 'baseName';
    public const YOUTUBE = 'youtube';
    public const DANCE_TYPE = 'danceType';
    public const IMPROVISATION = 'improvisation';
    public const RITUAL = 'ritual';
    public const TRADITION = 'tradition';
    public const SOURCE = 'source';
    public const DATE_ACTION_NOTES = 'dateActionNotes';
    public const TMKB = 'tmkb';

    // Values
    public const TRADITION_LATE = 'познетрадыцыйны';
    public const TRADITION_ARCHAIC = 'архаіка';
    public const TRADITION_EARLY = 'раннетрадыцыйны';

    public const IMPROVISATION_VALUE = 'імправізацыйны';
    public const IMPROVISATION_COMMANDS = 'з камандамі';
    public const IMPROVISATION_MIKITA_CASE = 'тыпу Мікіта';
    public const IMPROVISATION_QUADRILLE = 'кадрыльнага тыпу';

    // status fields
    public const STATUS_UPDATED = 'statusUpdated';
    public const STATUS_ACTIVE = 'statusActive';

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
