<?php

declare(strict_types=1);

namespace App\Entity\Additional;

class FileMarkerAdditional
{
    // Columns
    public const LOCAL_NAME = 'localName';
    public const BASE_NAME = 'baseName';
    public const DANCE_TYPE = 'danceType';
    public const IMPROVISATION = 'improvisation';
    public const RITUAL = 'ritual';
    public const TRADITION = 'tradition';
    public const DATE_ACTION_NOTES = 'dateActionNotes';
    public const TMKB = 'tmkb';

    // Values
    public const TRADITION_LATE = 'Познетрадыцыйны';
    public const TRADITION_ARCHAIC = 'Архаіка';

    public const IMPROVISATION_VALUE = 'імправізацыйны';
    public const IMPROVISATION_COMMANDS = 'з камандамі';
    public const IMPROVISATION_MIKITA = 'тып Мікіта';
    public const IMPROVISATION_QUADRILLE = 'кадрыльнага тыпу';
}
