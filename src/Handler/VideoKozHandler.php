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
use App\Entity\Expedition;
use App\Entity\Report;
use App\Entity\Type\CategoryType;
use App\Entity\Type\ReportBlockType;
use App\Entity\Type\UserRoleType;
use App\Manager\ReportManager;
use App\Parser\VideoKozParser;
use App\Repository\ExpeditionRepository;
use App\Repository\UserRepository;
use App\Service\PersonService;
use Carbon\CarbonImmutable;
use League\Csv\Exception;
use League\Csv\InvalidArgument;

class VideoKozHandler
{
    private const USER_LEADER_ID = 6; // Kozienka

    public function __construct(
        private readonly VideoKozParser       $parser,
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly PersonService        $personService,
        private readonly UserRepository       $userRepository,
        private readonly ReportManager        $reportManager,
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
                            || (!$report->geoPoint && !$videoItem->geoPoint && $report->geoNotes === $videoItem->place)
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
                    $reports[$reportKey]->geoNotes = $videoItem->place;
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
                $marker->name = $videoItem->localName . (empty($videoItem->baseName) ? '' : ' (' . $videoItem->baseName . ')');
                $notes = '';
                if ($videoItem->pack || !empty($videoItem->improvisation . $videoItem->ritual)) {
                    $notes = ($videoItem->category ? CategoryType::TYPES[$videoItem->category] : '')
                        . ($videoItem->pack ? ' ' . $videoItem->pack->getName() : '')
                        . (empty($videoItem->improvisation) ? '' : ', ' . $videoItem->improvisation)
                        . (empty($videoItem->ritual) ? '' : ', ' . $videoItem->ritual)
                        . '.';
                }
                $notes .= (empty($videoItem->notes) ? '' : "\r\n" . $videoItem->notes)
                    . (empty($videoItem->tmkb) ? '' : "\r\n" . $videoItem->tmkb);
                $marker->notes = $notes;
                $marker->decoding = empty(trim($videoItem->texts)) ? null : trim($videoItem->texts);

                $marker->reportKey = $videoItem->reportKey;
                $marker->blockKey = $videoItem->blockKey;

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
}
