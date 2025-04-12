<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Type\UserRoleType;
use App\Parser\Columns\KoboReportColumns;
use Carbon\Carbon;

class ReportDataDto extends PlaceDto
{
    public ?\DateTimeInterface $dateCreated = null;

    public ?\DateTimeInterface $dateAction = null;

    public ?string $code = null;

    /** @var array<ReportBlockDataDto> $blocks */
    public array $blocks = [];

    /** @var array<UserDto> $users */
    public array $users = [];

    /** @var array<UserRolesDto> $userRoles */
    public array $userRoles = [];

    /** @var array<string> $tips */
    public array $tips = [];
    /** @var array<string> $tasks */
    public array $tasks = [];
    public ?string $lat = null;
    public ?string $lon = null;

    public ?string $photo = null;

    public ?string $photoUrl = null;

    public static function fromKobo(array $data): self
    {
        $dto = new self();
        $dto->code = $data[KoboReportColumns::CODE] ?? null;
        if ($data[KoboReportColumns::PLACE] === 'няма ў спісе, дадаць населены пункт') {
            $place = $data[KoboReportColumns::PLACE_OTHER];
            if ($data[KoboReportColumns::DISTRICT] === 'other') {
                $place .= ', ' . $data[KoboReportColumns::DISTRICT_OTHER];
            } else {
                $place .= ', ' . $data[KoboReportColumns::DISTRICT];
            }
            $dto->place = $place;
        } else {
            $dto->place = $data[KoboReportColumns::PLACE];
        }
        $dto->lat = $data[KoboReportColumns::LAT] ? str_replace(',', '.', $data[KoboReportColumns::LAT]) : null;
        $dto->lon = $data[KoboReportColumns::LON] ? str_replace(',', '.', $data[KoboReportColumns::LON]) : null;

        $dto->dateAction = $data[KoboReportColumns::DATE_ACTION]
            ? Carbon::parse($data[KoboReportColumns::DATE_ACTION])
            : null;
        $dto->dateCreated = $data[KoboReportColumns::DATE_CREATED]
            ? Carbon::parse($data[KoboReportColumns::DATE_CREATED])
            : null;

        $leader = $data[KoboReportColumns::LEADER] ?? null;
        if ($leader && $leader !== 'other') {
            $dto->addUser($leader, UserRoleType::ROLE_LEADER);
        } else {
            $leader = $data[KoboReportColumns::LEADER_OTHER] ?? null;
            if ($leader) {
                $dto->addUser($leader, UserRoleType::ROLE_LEADER);
            }
        }

        $personNotes = $data[KoboReportColumns::PERSON_NOTES] ?? null;
        if ($personNotes && $personNotes !== 'other') {
            $dto->addUsersByNames($personNotes, UserRoleType::ROLE_NOTES);
        } else {
            $personNotes = $data[KoboReportColumns::PERSON_NOTES_OTHER] ?? null;
            if ($personNotes) {
                $dto->addUsersByNames($personNotes, UserRoleType::ROLE_NOTES);
            }
        }

        $personAudio = $data[KoboReportColumns::PERSON_AUDIO] ?? null;
        if ($personAudio && $personAudio !== 'other') {
            $dto->addUsersByNames($personAudio, UserRoleType::ROLE_AUDIO);
        } else {
            $personAudio = $data[KoboReportColumns::PERSON_AUDIO_OTHER] ?? null;
            if ($personAudio) {
                $dto->addUsersByNames($personAudio, UserRoleType::ROLE_AUDIO);
            }
        }

        $personVideo = $data[KoboReportColumns::PERSON_VIDEO] ?? null;
        if ($personVideo && $personVideo !== 'other') {
            $dto->addUsersByNames($personVideo, UserRoleType::ROLE_VIDEO);
        } else {
            $personVideo = $data[KoboReportColumns::PERSON_VIDEO_OTHER] ?? null;
            if ($personVideo) {
                $dto->addUsersByNames($personVideo, UserRoleType::ROLE_VIDEO);
            }
        }

        $personPhoto = $data[KoboReportColumns::PERSON_PHOTO] ?? null;
        if ($personPhoto && $personPhoto !== 'other') {
            $dto->addUsersByNames($personPhoto, UserRoleType::ROLE_PHOTO);
        } else {
            $personPhoto = $data[KoboReportColumns::PERSON_PHOTO_OTHER] ?? null;
            if ($personPhoto) {
                $dto->addUsersByNames($personPhoto, UserRoleType::ROLE_PHOTO);
            }
        }

        $dto->photo = $data[KoboReportColumns::PHOTO] ?? null;
        $dto->photoUrl = $data[KoboReportColumns::PHOTO_URL] ?? null;

        return $dto;
    }

    private function addUsersByNames(string $names, string $role): void
    {
        $names = str_replace('other', '', $names);
        $parts = explode(' ', trim($names));
        $name = '';
        foreach ($parts as $part) {
            if ('' === $name) {
                $name = $part;
            } else {
                $name .= ' ' . $part;
                $this->addUser($name, $role);
                $name = '';
            }
        }
        if ('' !== $name) {
            $this->addUser($name, $role);
        }
    }

    private function addUser(string $name, string $role): void
    {
        $isNew = true;
        foreach ($this->users as $user) {
            if ($user->name === $name) {
                $isNew = false;
                $user->roles[] = $role;
                break;
            }
        }

        if ($isNew) {
            $userDto = new UserDto();
            $userDto->name = $name;
            $userDto->roles[] = $role;
            $this->users[] = $userDto;
        }
    }
}
