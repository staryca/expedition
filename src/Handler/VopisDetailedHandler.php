<?php

declare(strict_types=1);

namespace App\Handler;

use App\Dto\FileMarkerDto;
use App\Dto\InformantDto;
use App\Dto\OrganizationDto;
use App\Dto\ReportBlockDataDto;
use App\Dto\ReportDataDto;
use App\Dto\SubjectDto;
use App\Dto\UserRolesDto;
use App\Entity\Expedition;
use App\Entity\Report;
use App\Entity\Type\CategoryType;
use App\Entity\Type\ReportBlockType;
use App\Entity\Type\UserRoleType;
use App\Manager\ReportManager;
use App\Parser\VopisDetailedParser;
use App\Repository\ExpeditionRepository;
use App\Repository\UserRepository;
use App\Service\PersonService;
use Carbon\CarbonImmutable;
use League\Csv\Exception;
use League\Csv\InvalidArgument;

class VopisDetailedHandler
{
    private const USER_LEADER_ID = 6; // 6 - Kozienka

    public function __construct(
        private readonly VopisDetailedParser $parser,
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly PersonService $personService,
        private readonly UserRepository $userRepository,
        private readonly ReportManager $reportManager,
    ) {
    }

    /**
     * @param string $filepath
     * @return array<SubjectDto>
     * @throws Exception
     * @throws InvalidArgument
     */
    public function checkFile(string $filepath): array
    {
        $content = file_get_contents($filepath);

        return $this->parser->parse($content);
    }

    /**
     * @param array<SubjectDto> $subjects
     * @return array<ReportDataDto>
     */
    public function createReportsData(array &$subjects): array
    {
        $user = $this->userRepository->find(self::USER_LEADER_ID);

        $hashReports = [];
        /** @var array<ReportDataDto> $reports */
        $reports = [];
        $reportKey = -1;
        $blockKey = -1;

        foreach ($subjects as $subject) {
            foreach ($subject->files as $file) {
                foreach ($file->markers as $markerKey => $marker) {
                    /* for Report */
                    if (count($hashReports) === 0 && $marker->isEmptyPlace()) {
                        throw new \Exception('First record without place!');
                    }
                    $hashReport = ($marker->dateAction?->format('Ymd'))
                        . '_'
                        . ($marker->isEmptyPlace() ? $reports[$reportKey]->getPlaceHash() : $marker->getPlaceHash());
                    if (isset($hashReports[$hashReport])) {
                        $reportKey = $hashReports[$hashReport];
                        if ($reportKey + 1 !== count($hashReports)) {
                            $blockKey = count($reports[$reportKey]->blocks) - 1;
                        }
                    } else {
                        $reportKey = count($hashReports);
                        $hashReports[$hashReport] = $reportKey;
                        $blockKey = -1;

                        $reports[$reportKey] = new ReportDataDto();
                        $reports[$reportKey]->geoPoint = $marker->geoPoint;
                        $reports[$reportKey]->place = $marker->place;
                        $reports[$reportKey]->dateCreated = CarbonImmutable::now();
                        $reports[$reportKey]->dateAction = $marker->dateAction;

                        if ($user) {
                            $userRole = new UserRolesDto();
                            $userRole->user = $user;
                            $userRole->roles = [UserRoleType::ROLE_LEADER];
                            $reports[$reportKey]->userRoles[] = $userRole;
                        }
                    }
                    $marker->reportKey = $reportKey;

                    /* for ReportBlock */
                    if ($blockKey > 0 && !isset($reports[$reportKey]->blocks[$blockKey])) {
                        throw new \Exception(
                            sprintf('Block key %s not found. Marker: %s', $blockKey, $marker->informantsText)
                        );
                    }
                    if (
                        $blockKey < 0
                        || $reports[$reportKey]->blocks[$blockKey]->organizationKey !== $marker->organizationKey
                        || $marker->category === CategoryType::CHANGE_INFORMANTS
                        || ($marker->category === CategoryType::ABOUT_RECORD && $markerKey > 0)
                    ) {
                        $blockKey++;
                        $reports[$reportKey]->blocks[$blockKey] = new ReportBlockDataDto();

                        $reports[$reportKey]->blocks[$blockKey]->type =
                            $marker->organizationKey ? ReportBlockType::TYPE_BAND_RECORD : ReportBlockType::TYPE_CONVERSATION;
                        $reports[$reportKey]->blocks[$blockKey]->organizationKey = $marker->organizationKey;
                        $reports[$reportKey]->blocks[$blockKey]->informantKeys = $marker->informantKeys;
                        if (isset($marker->others[FileMarkerDto::OTHER_RECORD])) {
                            $reports[$reportKey]->blocks[$blockKey]->description = 'Запіс: ' . $marker->others[FileMarkerDto::OTHER_RECORD];
                        }
                        if (isset($marker->others[FileMarkerDto::OTHER_MENTION])) {
                            $reports[$reportKey]->blocks[$blockKey]->additional['mention'] = $marker->others[FileMarkerDto::OTHER_MENTION];
                        }
                    } else {
                        // Add other informants in block
                        foreach ($marker->informantKeys as $informantKey) {
                            if (!in_array($informantKey, $reports[$reportKey]->blocks[$blockKey]->informantKeys)) {
                                $reports[$reportKey]->blocks[$blockKey]->informantKeys[] = $informantKey;
                            }
                        }
                    }
                    $marker->blockKey = $blockKey;
                }
            }
        }

        return $reports;
    }

    /**
     * @param array<SubjectDto> $subjects
     * @param array<OrganizationDto> $organizations
     * @param array<InformantDto> $informants
     */
    public function detectOrganizationsAndInformants(array &$subjects, array &$organizations, array &$informants): void
    {
        /** @var array<int, string> $hashes */
        $hashes = [];
        $keyNames = -1;
        $keys = [];
        /** @var array<int, FileMarkerDto> $markersForOrgs */
        $markersForOrgs = [];

        foreach ($subjects as $keySubject => $subject) {
            foreach ($subject->files as $keyFile => $file) {
                foreach ($file->markers as $keyMarker => $marker) {
                    if (!empty($marker->informantsText)) {
                        $hash = $marker->informantsText . '_' . $marker->getPlaceHash();
                        $key = array_search($hash, $hashes, true);
                        if ($key === false) {
                            $keyNames++;
                            $hashes[$keyNames] = $hash;

                            $organization = new OrganizationDto();
                            $organization->name = $marker->informantsText;
                            $organization->geoPoint = $marker->geoPoint;
                            $organization->place = $marker->place;
                            $organization->dateAdded = $marker->dateAction;
                            $organizations[$keyNames] = $organization;

                            $markersForOrgs[$keyNames] = $marker;
                            $key = $keyNames;
                        }
                        $keys[$keySubject][$keyFile][$keyMarker] = $key;
                    }
                }
            }
        }

        /* Create OrganizationDto from name */
        $keyInformant = count($informants) - 1;
        $informantHashes = [];
        foreach ($organizations as $keyNames => $organization) {
            $this->personService->parseOrganization($organization);

            foreach ($organization->informants as $informant) {
                $informant->geoPoint = $organization->geoPoint;
                $informant->place = $organization->place;
                $informant->birthPlace = $markersForOrgs[$keyNames]->getBirthPlace();

                $hash = $informant->getHash();
                $key = array_search($hash, $informantHashes, true);
                if (false === $key) {
                    $keyInformant++;

                    // Informant have no locations, there are notes
                    foreach ($informant->locations as $location) {
                        $informant->addNotes($location);
                    }
                    $informant->locations = [];

                    $informants[$keyInformant] = $informant;
                    $informantHashes[$keyInformant] = $hash;

                    $key = $keyInformant;
                }
                $organization->informantKeys[] = $key;
            }
        }
        unset($names);

        foreach ($keys as $keySubject => $keysFile) {
            foreach ($keysFile as $keyFile => $keysMarker) {
                foreach ($keysMarker as $keyMarker => $keyName) {
                    $marker = $subjects[$keySubject]->files[$keyFile]->markers[$keyMarker];
                    $organization = $organizations[$keyName];
                    $marker->informantKeys = $organization->informantKeys;
                    if (!empty($organization->name)) {
                        $marker->organizationKey = $keyName;
                    }
                }
            }
        }

        foreach ($organizations as $key => $organization) {
            if (empty($organization->name)) {
                unset($organizations[$key]);
            }
        }
    }

    /**
     * @param int $expeditionId
     * @param array<SubjectDto> $subjects
     * @return array<Report>
     * @throws \Doctrine\DBAL\Exception
     */
    public function saveSubjects(int $expeditionId, array $subjects): array
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find($expeditionId);
        if (!$expedition) {
            throw new \Exception('The expedition {$expeditionId} is not found');
        }

        $organizations = [];
        $informants = [];
        $this->detectOrganizationsAndInformants($subjects, $organizations, $informants);
        $reportsData = $this->createReportsData($subjects);

        return $this->reportManager->saveSubjects($expedition, $informants, $organizations, $reportsData, $subjects);
    }
}
