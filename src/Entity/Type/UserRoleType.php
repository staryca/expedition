<?php

declare(strict_types=1);

namespace App\Entity\Type;

class UserRoleType
{
    public const ROLE_LEADER = 'Leader';
    public const ROLE_AUDIO = 'Audio';
    public const ROLE_NOTES = 'Notes';
    public const ROLE_VIDEO = 'Video';
    public const ROLE_PHOTO = 'Photo';
    public const ROLE_SKETCH = 'Sketch';
    public const ROLE_VIEWER = 'Viewer';
    public const ROLE_INTERVIEWER = 'Interviewer';

    public const VARIANTS = [
        self::ROLE_LEADER => ['кіраўнік', 'кіраўнік групы'],
        self::ROLE_INTERVIEWER => ['апытанне', 'гутарка'],
        self::ROLE_AUDIO => ['дыктафон', 'аўдыё', 'аўдыя'],
        self::ROLE_NOTES => ['нататкі', 'нататнік', 'дзённік'],
        self::ROLE_VIDEO => ['відэа'],
        self::ROLE_PHOTO => ['фота', 'фотаздымкі'],
        self::ROLE_SKETCH => ['замалёўкі'],
    ];

    public const ROLES = [
        self::ROLE_LEADER => 'Кіраўнік',
        self::ROLE_AUDIO => 'Аўдыё',
        self::ROLE_NOTES => 'Нататкі',
        self::ROLE_VIDEO => 'Відэа',
        self::ROLE_PHOTO => 'Фота',
        self::ROLE_SKETCH => 'Замалёўкі',
        self::ROLE_VIEWER => 'Удзельнік',
        self::ROLE_INTERVIEWER => 'Гутарка',
    ];
}
