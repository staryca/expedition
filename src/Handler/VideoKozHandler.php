<?php

declare(strict_types=1);

namespace App\Handler;

use App\Dto\FileDto;
use App\Dto\FileMarkerDto;
use App\Dto\InformantDto;
use App\Dto\OrganizationDto;
use App\Dto\ReportBlockDataDto;
use App\Dto\ReportDataDto;
use App\Dto\UserRolesDto;
use App\Entity\Additional\FileMarkerAdditional;
use App\Entity\Expedition;
use App\Entity\Report;
use App\Entity\Type\ReportBlockType;
use App\Entity\Type\UserRoleType;
use App\Manager\ReportManager;
use App\Parser\VideoKozParser;
use App\Repository\ExpeditionRepository;
use App\Repository\FileMarkerRepository;
use App\Repository\UserRepository;
use App\Service\PersonService;
use App\Service\RitualService;
use App\Service\YoutubeService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use League\Csv\Exception;
use League\Csv\InvalidArgument;

class VideoKozHandler
{
    private const int USER_LEADER_ID = 6; // Kozienka
    private const int AMOUNT_FIRST_VIDEOS = 100;
    private const int AMOUNT_PER_DAY = 5;
    private const string PRESENTATION_DATE = '2026-02-14';

    public function __construct(
        private readonly VideoKozParser $parser,
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly FileMarkerRepository $fileMarkerRepository,
        private readonly PersonService $personService,
        private readonly UserRepository $userRepository,
        private readonly ReportManager $reportManager,
        private readonly RitualService $ritualService,
        private readonly YoutubeService $youtubeService,
    ) {
    }

    /**
     * @param string $filepath
     * @return array<FileDto>
     * @throws Exception
     * @throws InvalidArgument
     */
    public function checkFile(string $filepath): array
    {
        $content = file_get_contents($filepath);

        return $this->parser->parse($content);
    }

    /**
     * @param array<FileDto> $files
     * @return array<OrganizationDto>
     */
    public function getOrganizations(array &$files): array
    {
        /** @var array<int, string> $names */
        $names = [];
        $keyNames = -1;
        $keys = [];
        foreach ($files as $keyFile => $file) {
            foreach ($file->videoItems as $keyVideo => $videoItem) {
                if (!empty($videoItem->organizationName)) {
                    $key = array_search($videoItem->organizationName, $names, true);
                    if ($key === false) {
                        $keyNames++;
                        $names[$keyNames] = $videoItem->organizationName;
                        $key = $keyNames;
                    }
                    $keys[$keyFile][$keyVideo] = $key;
                }
            }
        }

        /* Create OrganizationDto from name */
        /** @var array<OrganizationDto> $organizations */
        $organizations = [];
        foreach ($names as $keyName => $name) {
            $organization = new OrganizationDto();
            $organization->name = $name;
            $this->personService->parseOrganization($organization);
            $organizations[$keyName] = $organization;
        }
        unset($names);

        // Set informants and location for organizations
        foreach ($files as $keyFile => $file) {
            foreach ($file->videoItems as $keyVideo => $videoItem) {
                if (isset($keys[$keyFile][$keyVideo])) {
                    $keyName = $keys[$keyFile][$keyVideo];
                    $videoItem->organizationName = null;
                    if (isset($organizations[$keyName])) {
                        $organizations[$keyName]->geoPoint = $videoItem->geoPoint;
                        $organizations[$keyName]->place = $videoItem->place;

                        foreach ($videoItem->informantKeys as $informantKey) {
                            if (!in_array($informantKey, $organizations[$keyName]->informantKeys)) {
                                $organizations[$keyName]->informantKeys[] = $informantKey;
                            }
                        }
                        $videoItem->organizationKey = $keyName;
                    }
                }
            }
        }

        return $organizations;
    }

    /**
     * @param array<FileDto> $files
     * @return array<InformantDto>
     */
    public function getInformants(array &$files): array
    {
        /** @var array<int, InformantDto> $informants */
        $informants = [];
        /** @var array<int, string> $hashes */
        $hashes = [];

        foreach ($files as $file) {
            foreach ($file->videoItems as $videoItem) {
                foreach ($videoItem->informants as $informant) {
                    $hash = $informant->name . $videoItem->geoPoint?->getLongBeName() . $videoItem->place;
                    $key = array_search($hash, $hashes, true);
                    if ($key === false) {
                        $informant->geoPoint = $videoItem->geoPoint;
                        $informant->place = $videoItem->place;

                        $dto = $informant->getNameAndGender();
                        $this->personService->fixNameAndGender($dto);
                        $informant->name = $dto->getName();
                        $informant->gender = $dto->gender;

                        $key = count($informants);
                        $informants[$key] = $informant;
                        $hashes[$key] = $hash;
                    } else {
                        $informants[$key]->mergeInformant($informant);
                    }
                    $videoItem->informantKeys[] = $key;
                }
                // Informants will be saved by keys, not by Dto
                $videoItem->informants = [];
            }
        }

        return $informants;
    }

    /**
     * @param array<FileDto> $files
     * @return array<ReportDataDto>
     */
    public function createReportsData(array &$files): array
    {
        $user = $this->userRepository->find(self::USER_LEADER_ID);

        /** @var array<ReportDataDto> $reports */
        $reports = [];
        $reportKey = -1;
        foreach ($files as $file) {
            foreach ($file->videoItems as $videoItem) {
                $key = null;
                foreach ($reports as $keyForSearch => $report) {
                    if (
                        $report->dateAction?->format('Y-m-d') === $videoItem->dateAction?->format('Y-m-d')
                        && (
                            ($report->geoPoint && $videoItem->geoPoint && $report->geoPoint->getId() === $videoItem->geoPoint->getId())
                            || (!$report->geoPoint && !$videoItem->geoPoint && $report->place === $videoItem->place)
                        )
                    ) {
                        $key = $keyForSearch;
                        break;
                    }
                }

                if (null === $key) {
                    $reportKey++;
                    $reports[$reportKey] = new ReportDataDto();
                    $reports[$reportKey]->geoPoint = $videoItem->geoPoint;
                    $reports[$reportKey]->place = $videoItem->place;
                    $reports[$reportKey]->dateCreated = CarbonImmutable::now();
                    $reports[$reportKey]->dateAction = $videoItem->dateAction;

                    if ($user) {
                        $userRole = new UserRolesDto();
                        $userRole->user = $user;
                        $userRole->roles = [UserRoleType::ROLE_LEADER];
                        $reports[$reportKey]->userRoles[] = $userRole;
                    }

                    $key = $reportKey;
                }

                $videoItem->reportKey = $key;
            }
        }

        $blockKeys = [];
        foreach ($files as $file) {
            foreach ($file->videoItems as $videoItem) {
                $reportKey = $videoItem->reportKey;
                if (!isset($blockKeys[$reportKey])) {
                    $blockKeys[$reportKey] = [];
                }
                $hash = $videoItem->getHash();

                $blockKey = array_search($hash, $blockKeys[$reportKey], true);
                if (false === $blockKey) {
                    $blockKey = count($reports[$reportKey]->blocks);
                    $reports[$reportKey]->blocks[$blockKey] = new ReportBlockDataDto();
                    $reports[$reportKey]->blocks[$blockKey]->type =
                        $videoItem->organizationKey ? ReportBlockType::TYPE_BAND_RECORD : ReportBlockType::TYPE_CONVERSATION;
                    $reports[$reportKey]->blocks[$blockKey]->organizationKey = $videoItem->organizationKey;
                    $reports[$reportKey]->blocks[$blockKey]->informantKeys = $videoItem->informantKeys;
                }

                $videoItem->blockKey = $blockKey;
                $blockKeys[$reportKey][$blockKey] = $hash;
            }
        }

        return $reports;
    }

    /**
     * @param array<FileDto> $files
     */
    public function convertVideoItemsToFileMarkers(array &$files): void
    {
        foreach ($files as $file) {
            $file->markers = [];
            foreach ($file->videoItems as $videoItem) {
                $marker = new FileMarkerDto();
                $marker->category = $videoItem->category;
                $marker->name = $videoItem->localName;
                if (!empty($videoItem->baseName) && $videoItem->localName !== $videoItem->baseName) {
                    $marker->name .= ' (' . $videoItem->baseName . ')';
                }
                $marker->notes = empty(trim($videoItem->notes)) ? null : trim($videoItem->notes);
                $marker->decoding = empty(trim($videoItem->texts)) ? null : trim($videoItem->texts);

                $marker->reportKey = $videoItem->reportKey;
                $marker->blockKey = $videoItem->blockKey;

                if (!empty($videoItem->number)) {
                    $marker->additional[FileMarkerAdditional::NUMBER] = $videoItem->number;
                }
                if (!empty($videoItem->localName)) {
                    $marker->additional[FileMarkerAdditional::LOCAL_NAME] = $videoItem->localName;
                }
                if (!empty($videoItem->baseName)) {
                    $marker->additional[FileMarkerAdditional::BASE_NAME] = $videoItem->baseName;
                }
                if (!empty($videoItem->youTube)) {
                    $marker->additional[FileMarkerAdditional::YOUTUBE] = $videoItem->youTube;
                }
                if (!empty($videoItem->pack)) {
                    $marker->additional[FileMarkerAdditional::DANCE_TYPE] = $videoItem->pack->getName();
                }
                if (!empty($videoItem->improvisation)) {
                    $marker->additional[FileMarkerAdditional::IMPROVISATION] = $videoItem->improvisation;
                }

                $marker->ritual = $this->ritualService->findRitual($videoItem->ritual);
                if (!$marker->ritual && !empty($videoItem->ritual)) {
                    $marker->additional[FileMarkerAdditional::RITUAL] = $videoItem->ritual;
                }

                if (!empty($videoItem->tradition)) {
                    $marker->additional[FileMarkerAdditional::TRADITION] = $videoItem->tradition;
                }
                if (!empty($videoItem->source)) {
                    $marker->additional[FileMarkerAdditional::SOURCE] = $videoItem->source;
                }
                if (!empty($videoItem->dateActionNotes)) {
                    $marker->additional[FileMarkerAdditional::DATE_ACTION_NOTES] = $videoItem->dateActionNotes;
                }
                if (!empty($videoItem->tmkb)) {
                    $marker->additional[FileMarkerAdditional::TMKB] = $videoItem->tmkb;
                }
                // Temp
                if (!empty($videoItem->informantsText)) {
                    $marker->additional[FileMarkerAdditional::INFORMANTS_TEXT] = $videoItem->informantsText;
                }

                $file->markers[] = $marker;
            }
            $file->videoItems = [];
        }
    }

    /**
     * @param int $expeditionId
     * @param array<FileDto> $files
     * @return array<Report>
     * @throws \Doctrine\DBAL\Exception
     */
    public function saveFiles(int $expeditionId, array $files): array
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($expeditionId);
        if (!$expedition) {
            throw new \Exception('The expedition {$expeditionId} is not found');
        }

        $informants = $this->getInformants($files);
        $organizations = $this->getOrganizations($files);
        $reportsData = $this->createReportsData($files);

        $this->convertVideoItemsToFileMarkers($files);

        return $this->reportManager->saveVideoKozReports($expedition, $informants, $organizations, $reportsData, $files);
    }

    /**
     * @throws \Exception
     */
    public function getYoutubeList(int $expeditionId): array
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($expeditionId);
        if (!$expedition) {
            throw new \Exception('The expedition {$expeditionId} is not found');
        }

        $markers = $this->fileMarkerRepository->getMarkersWithFullObjects($expedition);

        $data = [];

        $updated = 0;
        $active = 0;
        $sheduled = 0;
        $all = 0;
        foreach ($markers as $fileMarker) {
            $all++;
            if ((int)$fileMarker->getAdditionalValue(FileMarkerAdditional::STATUS_UPDATED) > 0) {
                $updated++;
            }
            if ((int)$fileMarker->getAdditionalValue(FileMarkerAdditional::STATUS_ACTIVE) > 0) {
                $active++;
            }
            if ((int)$fileMarker->getAdditionalValue(FileMarkerAdditional::STATUS_SHEDULED) > 0) {
                $sheduled++;
            }
        }

        $item['stat'] = '<i class="bi bi-arrow-clockwise"></i> / <i class="bi bi-eye-fill"></i> / <i class="bi bi-stopwatch"></i> / all<br>'
            . $updated . ' / ' . $active . ' / ' . $sheduled . ' / ' . $all;
        $data[0] = $item;

        $keyWarningDesc = $keyWarningTitle = $keyOk = 1;
        foreach ($markers as $fileMarker) {
            $title = $this->youtubeService->getTitle($fileMarker);
            $titleNotes = mb_strlen($title) > YoutubeService::MAX_LENGTH_TITLE
                ? '<i class="bi bi-exclamation-diamond-fill text-danger" title="' . mb_strlen($title) . ' charters"></i> '
                : ''
            ;
            $description = $this->youtubeService->getDescription($fileMarker);
            $descriptionWarning = mb_strlen($description) > YoutubeService::MAX_LENGTH_DESCRIPTION
                ? '<i class="bi bi-exclamation-diamond-fill text-danger"></i> '
                : ''
            ;

            $key = match (true) {
                !empty($descriptionWarning) => $keyWarningDesc++,
                !empty($titleNotes) => YoutubeService::MAX_LENGTH_TITLE + $keyWarningTitle++,
                default => YoutubeService::MAX_LENGTH_DESCRIPTION + $keyOk++,
            };

            $statuses = [];
            $statuses[] = (int)$fileMarker->getAdditionalValue(FileMarkerAdditional::STATUS_UPDATED) > 0
                ? '<i class="bi bi-arrow-clockwise"></i>'
                : '-';
            $statuses[] = (int)$fileMarker->getAdditionalValue(FileMarkerAdditional::STATUS_ACTIVE) > 0
                ? '<i class="bi bi-eye-fill"></i>'
                : '-';
            $statuses[] = (int)$fileMarker->getAdditionalValue(FileMarkerAdditional::STATUS_SHEDULED) > 0
                ? '<i class="bi bi-stopwatch"></i>'
                : '-';

            $item = [];
            $item['id'] = $fileMarker->getId();
            $item['status'] = implode(' / ', $statuses);
            $item['number'] = $fileMarker->getAdditionalNumber();
            $item['file'] = $fileMarker->getFile()?->getFullFileName();
            $item['publish'] = $fileMarker->getPublishDateText();
            $item['youtube'] = $fileMarker->getAdditionalYoutube();
            $item['youtube_title'] = $titleNotes . $title;
            $item['youtube_description'] = $descriptionWarning . $description;

            $data[$key] = $item;
        }
        ksort($data);

        return $data;
    }

    public function setPublishDate(int $expeditionId): array
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($expeditionId);
        if (!$expedition) {
            throw new \Exception('The expedition {$expeditionId} is not found');
        }

        $count = 0;
        $date = Carbon::parse(self::PRESENTATION_DATE);
        $markers = $this->fileMarkerRepository->getMarkersWithFullObjects($expedition, [], true);
        foreach ($markers as $fileMarker) {
            if ($count < self::AMOUNT_FIRST_VIDEOS) {
                $fileMarker->setPublish(null);
            } else {
                $num = ($count - self::AMOUNT_FIRST_VIDEOS) % self::AMOUNT_PER_DAY;
                if ($num === 0) {
                    $date->addDay();
                }
                $fileMarker->setPublish($date->clone());
            }
            $count++;
        }

        return [
            'amount' => $count,
            'manual' => self::AMOUNT_FIRST_VIDEOS,
            'per_day' => self::AMOUNT_PER_DAY,
            'start' => self::PRESENTATION_DATE,
            'end' => $date->format('Y-m-d'),
        ];
    }
}
