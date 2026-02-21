<?php

declare(strict_types=1);

namespace App\Manager;

use App\Dto\ContentDto;
use App\Dto\EpisodeDto;
use App\Dto\FileDto;
use App\Dto\InformantDto;
use App\Dto\OrganizationDto;
use App\Dto\ReportDataDto;
use App\Dto\SubjectDto;
use App\Dto\YearsDto;
use App\Entity\Expedition;
use App\Entity\File;
use App\Entity\FileMarker;
use App\Entity\Informant;
use App\Entity\Organization;
use App\Entity\OrganizationInformant;
use App\Entity\Report;
use App\Entity\ReportBlock;
use App\Entity\Subject;
use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\Type\CategoryType;
use App\Entity\Type\FileType;
use App\Entity\Type\OrganizationType;
use App\Entity\Type\ReportBlockType;
use App\Entity\Type\TaskStatus;
use App\Entity\UserReport;
use App\Repository\TagRepository;
use App\Service\LocationService;
use App\Service\SubjectService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

class ReportManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TagRepository $tagRepository,
        private readonly LocationService $locationService,
        private readonly SubjectService $subjectService,
    ) {
    }

    /**
     * @param Expedition $expedition
     * @param array<ReportDataDto> $reportsData
     * @param array<string, array<string> $reportCodeGroups
     * @param array<OrganizationDto> $organizations
     * @param array<InformantDto> $informants
     * @return void
     * @throws Exception
     */
    public function saveBsuReports(
        Expedition $expedition,
        array $reportsData,
        array $reportCodeGroups,
        array $organizations,
        array $informants,
    ): void {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $reportBlocks = $this->createReportsByCodes($expedition, $reportsData, $reportCodeGroups);
            $informantsDb = $this->saveInformants($informants, $reportsData, $reportBlocks);
            $this->saveOrganizations($organizations, $informantsDb, $reportBlocks);

            $this->saveTags($reportsData, $reportBlocks);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            if ($this->entityManager->isOpen()) {
                $this->entityManager->getConnection()->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @param Expedition $expedition
     * @param array<ReportDataDto> $reports
     * @param array<ContentDto> $contents
     * @param array<OrganizationDto> $organizations
     * @param array<InformantDto> $informants
     * @param array<int, array<string>> $tags
     * @return void
     * @throws Exception
     */
    public function saveKoboReports(
        Expedition $expedition,
        array $reports,
        array $contents,
        array $organizations,
        array $informants,
        array $tags,
    ): void {

        foreach ($contents as $contentIndex => $content) {
            if (isset($reports[$content->reportIndex])) {
                $episode = new EpisodeDto(
                    $reports[$content->reportIndex]->blocks[0]->type === ReportBlockType::TYPE_CONVERSATION
                        ? CategoryType::STORY
                        : CategoryType::OTHER,
                    $content->notes
                );
                if (isset($tags[$contentIndex])) {
                    $episode->tags = $tags[$contentIndex];
                }
                $reports[$content->reportIndex]->blocks[0]->addEpisode((string) $contentIndex, $episode);
            }
        }

        foreach ($informants as $informant) {
            if (1 === count($informant->codeReports) && isset($reports[$informant->codeReports[0]])) {
                $reports[$informant->codeReports[0]]->blocks[0]->informants[] = $informant;
            }
        }

        foreach ($organizations as $organization) {
            if (1 === count($organization->codeReports) && isset($reports[$organization->codeReports[0]])) {
                $reports[$organization->codeReports[0]]->blocks[0]->organization = $organization;
                // Add informants of block to organization
                //   only equal, because report has only 1 block and organization only is in 1 block
                if (!empty($reports[$organization->codeReports[0]]->blocks[0]->informants)) {
                    $reports[$organization->codeReports[0]]->blocks[0]->organization->informants =
                        $reports[$organization->codeReports[0]]->blocks[0]->informants;
                }
            }
            $organization->codeReports = [];
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->createReports($expedition, $reports, [], []);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            if ($this->entityManager->isOpen()) {
                $this->entityManager->getConnection()->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @param array<InformantDto> $informants
     * @param array<ReportDataDto> $reportsData
     * @param array<ReportBlock> $reportBlocks
     * @return array<Informant>
     */
    public function saveInformants(array $informants, array $reportsData, array $reportBlocks): array
    {
        $informantsDb = [];
        foreach ($informants as $key => $informant) {
            $informantDb = new Informant();
            $informantDb->setDateCreated(new \DateTime());
            $informantDb->setFirstName($informant->name);
            $informantDb->setYearBirth($informant->birth);
            $informantDb->setDayBirth($informant->birthDay);
            if ($informant->died === YearsDto::DIED_IS_UNKNOWN) {
                $informantDb->setDied(true);
            } else {
                $informantDb->setYearDied($informant->died);
            }

            $informantDb->setGeoPointCurrent($informant->geoPoint);
            $informantDb->setGender($informant->gender);
            if (count($informant->locations) < 2) {
                $informantDb->setPlaceCurrent($informant->place);
            }
            if ($informant->birthPlace !== null) {
                $informantDb->setGeoPointBirth($informant->birthPlace->geoPoint);
                $informantDb->setPlaceBirth($informant->birthPlace->place);
            }
            $informantDb->setIsMusician($informant->isMusician);
            $informantDb->setNotes($informant->notes);

            $this->entityManager->persist($informantDb);
            $informantsDb[$key] = $informantDb;
        }

        foreach ($reportsData as $code => $reportData) {
            foreach ($reportData->blocks as $block) {
                foreach ($block->informantKeys as $informantKey) {
                    $reportBlocks[$code]->addInformant($informantsDb[$informantKey]);

                    $created = $informantsDb[$informantKey]->getDateCreated();
                    if (null !== $created && null !== $reportData->dateCreated && $created > $reportData->dateCreated) {
                        $informantsDb[$informantKey]->setDateCreated($reportData->dateCreated);
                    }
                }
            }
        }

        return $informantsDb;
    }

    /**
     * @param array<OrganizationDto> $organizations
     * @param array<Informant> $informantsDb
     * @param array<ReportBlock> $reportBlocksDb
     * @return array<Organization>
     */
    public function saveOrganizations(
        array $organizations,
        array $informantsDb,
        array $reportBlocksDb
    ): array {
        $organizationsDb = [];
        foreach ($organizations as $key => $organization) {
            $organizationDb = new Organization();
            $organizationDb->setName($organization->name);
            $organizationDb->setType(OrganizationType::COLLECTIVE);
            $organizationDb->setNotes($organization->notes);
            $organizationDb->setDateCreated($organization->dateAdded ?? Carbon::now());
            $organizationDb->setGeoPoint($organization->geoPoint);
            $organizationDb->setAddress($organization->place);

            foreach ($organization->informantKeys as $informantKey) {
                $orgInformantDb = new OrganizationInformant();
                $orgInformantDb->setInformant($informantsDb[$informantKey]);

                $organizationDb->addOrganizationInformant($orgInformantDb);
                $this->entityManager->persist($orgInformantDb);
            }

            foreach ($organization->codeReports as $codeReport) {
                $reportBlocksDb[$codeReport]->setOrganization($organizationDb);
                $this->entityManager->persist($reportBlocksDb[$codeReport]);
            }

            $this->entityManager->persist($organizationDb);
            $organizationsDb[$key] = $organizationDb;
        }

        return $organizationsDb;
    }

    /**
     * @param Expedition $expedition
     * @param array<ReportDataDto> $reportsData
     * @param array<string, array<string> $reportCodeGroups
     * @return array<ReportBlock>
     */
    public function createReportsByCodes(Expedition $expedition, array $reportsData, array $reportCodeGroups): array
    {
        $reportBlocksDb = [];

        foreach ($reportCodeGroups as $reportBlockCodes) {
            $report = new Report($expedition);
            $report->setDateCreated($reportsData[$reportBlockCodes['0']]->dateCreated);
            if (null !== $reportsData[$reportBlockCodes['0']]->dateAction) {
                $report->setDateAction($reportsData[$reportBlockCodes['0']]->dateAction);
            }
            $report->setGeoPoint($reportsData[$reportBlockCodes['0']]->geoPoint);
            if (null === $reportsData[$reportBlockCodes['0']]->geoPoint) {
                $report->setGeoNotes($reportsData[$reportBlockCodes['0']]->place);
            }

            foreach ($reportBlockCodes as $code) {
                foreach ($reportsData[$code]->blocks as $block) {
                    $reportBlock = new ReportBlock();
                    $reportBlock->setDateCreated($reportsData[$code]->dateCreated);
                    $reportBlock->setType(ReportBlockType::TYPE_UNDEFINED);
                    $reportBlock->setDescription($block->description);
                    $reportBlock->setAdditional($block->additional);

                    $report->addBlock($reportBlock);

                    foreach ($block->files as $fileData) {
                        $file = new File();
                        $file->setType(FileType::TYPES_BSU_CONVERTER[$fileData['type']]);
                        $file->setSizeText($fileData['size']);
                        $file->setProcessed(true);

                        $pos = mb_strrpos($fileData['full_path'], '/');
                        $file->setFilename(
                            null !== $pos ? mb_substr($fileData['full_path'], $pos + 1) : $fileData['full_path']
                        );
                        $file->setUrl(null !== $pos ? mb_substr($fileData['full_path'], 0, $pos) : null);

                        $reportBlock->addFile($file);
                        $this->entityManager->persist($file);
                    }

                    $this->entityManager->persist($reportBlock);
                    $reportBlocksDb[$code] = $reportBlock;
                }
            }

            $expedition->addReport($report);
            $this->entityManager->persist($report);
        }

        return $reportBlocksDb;
    }

    /**
     * @param array<ReportDataDto> $reportsData
     * @param array<ReportBlock> $reportBlocks
     * @return void
     */
    public function saveTags(array $reportsData, array $reportBlocks): void
    {
        $allTags = $this->tagRepository->getAllTags();

        foreach ($reportsData as $code => $reportData) {
            foreach ($reportData->blocks as $block) {
                $tags = $block->tags;
                foreach ($tags as $tag) {
                    if (empty(trim($tag))) {
                        continue;
                    }
                    if (isset($allTags[mb_strtolower($tag)])) {
                        $reportBlocks[$code]->addTag($allTags[mb_strtolower($tag)]);
                    } else {
                        $tagDb = new Tag();
                        $tagDb->setName($tag);
                        $tagDb->setBase(false);
                        $tagDb->setSortOrder(157);

                        $allTags[mb_strtolower($tag)] = $tagDb;

                        $this->entityManager->persist($tagDb);

                        $reportBlocks[$code]->addTag($tagDb);
                    }
                }
            }
        }
    }

    /**
     * @param Expedition $expedition
     * @param array<ReportDataDto> $reportsData
     * @param array<FileDto> $filesData
     * @return void
     * @throws Exception
     */
    public function saveVopisReports(
        Expedition $expedition,
        array $reportsData,
        array $filesData,
    ): void {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $reports = $this->createReports($expedition, $reportsData, [], []);

            $reportBlocks = [];
            foreach ($reports as $reportKey => $report) {
                foreach ($report->getBlocks() as $blockKey => $block) {
                    $reportBlocks[$reportKey][$blockKey] = $block;
                }
            }

            $subjectsData = $this->subjectService->getSubjects($filesData, true);
            $this->createSubjects($expedition, $subjectsData, $reportBlocks);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            if ($this->entityManager->isOpen()) {
                $this->entityManager->getConnection()->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @param Expedition $expedition
     * @param array<ReportDataDto> $reportsData
     * @param array<Informant> $informantsDb
     * @param array<Organization> $organizationsDb
     * @return array<Report>
     * @throws \Exception
     */
    public function createReports(
        Expedition $expedition,
        array $reportsData,
        array $informantsDb,
        array $organizationsDb,
    ): array {
        $allTags = $this->getTagsFromEpisodes($reportsData);

        $reportsDb = [];
        foreach ($reportsData as $reportKey => $reportData) {
            $report = new Report($expedition);
            $report->setDateCreated($reportData->dateCreated ?? $reportData->dateAction);
            if (null !== $reportData->dateAction) {
                $report->setDateAction($reportData->dateAction);
            }
            $report->setGeoPoint($reportData->geoPoint);
            if (null === $reportData->geoPoint) {
                $report->setGeoNotes($reportData->place);
            }
            $report->setLat($reportData->lat);
            $report->setLon($reportData->lon);
            if (!empty($reportData->temp)) {
                $report->setTemp($reportData->temp);
            }

            $this->entityManager->persist($report);
            $expedition->addReport($report);
            $reportsDb[$reportKey] = $report;

            foreach ($reportData->tasks as $textTask) {
                $task = new Task();
                $task->setContent($textTask);
                $task->setStatus(TaskStatus::NEW);

                $report->addTask($task);
                $this->entityManager->persist($task);
            }
            foreach ($reportData->tips as $textTask) {
                $task = new Task();
                $task->setContent($textTask);
                $task->setStatus(TaskStatus::TIP);

                $report->addTask($task);
                $this->entityManager->persist($task);
            }

            foreach ($reportData->blocks as $block) {
                $reportBlock = new ReportBlock();
                $reportBlock->setCode($block->code);
                $reportBlock->setDateCreated($reportData->dateCreated ?? $reportData->dateAction);
                $reportBlock->setType($block->type);
                $reportBlock->setDescription($block->description);
                $reportBlock->setAdditional($block->additional);
                $reportBlock->setVideoNotes($block->videoNotes);
                $reportBlock->setPhotoNotes($block->photoNotes);
                $reportBlock->setUserNotes($block->userNotes);

                if ($block->organizationKey !== null && isset($organizationsDb[$block->organizationKey])) {
                    $reportBlock->setOrganization($organizationsDb[$block->organizationKey]);
                }
                foreach ($block->informantKeys as $informantKey) {
                    if (isset($informantsDb[$informantKey])) {
                        $reportBlock->addInformant($informantsDb[$informantKey]);
                    }
                }

                $report->addBlock($reportBlock);

                if (count($block->getEpisodes()) > 0) {
                    $file = new File();
                    $file->setType(FileType::TYPE_VIRTUAL_CONTENT_LIST);
                    $file->setProcessed(true);

                    $reportBlock->addFile($file);
                    $this->entityManager->persist($file);

                    foreach ($block->getEpisodes() as $episode) {
                        $fileMarker = FileMarker::makeFromEpisode($episode);

                        foreach ($episode->tags as $tag) {
                            $tag = mb_strtolower($tag);
                            if (!isset($allTags[$tag])) {
                                throw new \Exception("Unknown tag in episode: $tag");
                            }
                            $fileMarker->addTag($allTags[$tag]);
                        }

                        $this->entityManager->persist($fileMarker);
                        $file->addFileMarker($fileMarker);
                    }
                }

                $this->entityManager->persist($reportBlock);

                // Save url to scan
//            $report->setPhoto($reportData->photo);
//            $report->setPhotoUrl($reportData->photoUrl);
                if ($reportData->photoUrl && !empty(trim($reportData->photoUrl))) {
                    $file = new File();
                    $file->setType(FileType::TYPE_SCAN_NOTES);
                    $file->setProcessed(false);
                    $file->setUrl($reportData->photoUrl);
                    $file->setFilename($reportData->photo);

                    $reportBlock->addFile($file);
                    $this->entityManager->persist($file);
                }

                foreach ($block->informants as $informant) {
                    $informantDb = new Informant();
                    $informantDb->setDateCreated(
                        $informant->dateAdded ?? $reportData->dateCreated ?? $reportData->dateAction
                    );
                    $informantDb->setYearBirth($informant->birth);
                    $informantDb->setDayBirth($informant->birthDay);
                    $informantDb->setFirstName($informant->name);
                    $informantDb->setNotes($informant->notes);
                    $informantDb->setGender($informant->gender);
                    $informantDb->setConfession($informant->confession);
                    $informantDb->setPathPhoto($informant->photo);
                    $informantDb->setUrlPhoto($informant->photoUrl);
                    $informantDb->setIsMusician($informant->isMusician);

                    $informantDb->setGeoPointCurrent($reportData->geoPoint);
                    if (null === $reportData->geoPoint) {
                        $informantDb->setPlaceCurrent($reportData->place);
                    }

                    $informantDb->setGeoPointBirth($informant->geoPoint);
                    $informantDb->setPlaceBirth($informant->place);
                    if (!empty($informant->locations)) {
                        $location = $this->locationService->detectLocationByFullPlace($informant->locations[0]);
                        if (null !== $location) {
                            $informantDb->setGeoPointBirth($location);
                        } else {
                            $informantDb->setPlaceBirth($informant->locations[0]);
                        }
                    }

                    $this->entityManager->persist($informantDb);

                    $reportBlock->addInformant($informantDb);
                }

                if (null !== $block->organization) {
                    $organizationDb = new Organization();
                    $organizationDb->setName($block->organization->name);
                    $organizationDb->setType(OrganizationType::COLLECTIVE);
                    $organizationDb->setNotes($block->organization->notes);
                    $organizationDb->setDateCreated($block->organization->dateAdded ?? Carbon::now());
                    $organizationDb->setGeoPoint($block->organization->geoPoint);
                    $organizationDb->setAddress($block->organization->place);

                    $this->entityManager->persist($organizationDb);
                    $reportBlock->setOrganization($organizationDb);
                }
            }

            foreach ($reportData->userRoles as $userRole) {
                if (null !== $userRole->user) {
                    foreach ($userRole->roles as $role) {
                        $userReport = new UserReport($report, $userRole->user);
                        $userReport->setRole($role);

                        $report->addUserReport($userReport);
                        $this->entityManager->persist($userReport);
                    }
                }
            }
        }

        return $reportsDb;
    }

    /**
     * @param array<ReportDataDto> $reportsData
     * @return array<Tag>
     */
    private function getTagsFromEpisodes(array $reportsData): array
    {
        $allTags = $this->tagRepository->getAllTags();

        foreach ($reportsData as $reportData) {
            foreach ($reportData->blocks as $block) {
                foreach ($block->getEpisodes() as $episode) {
                    $amount = 0;
                    foreach ($episode->tags as $tag) {
                        $amount++;
                        $tag = mb_strtolower($tag);
                        if (!isset($allTags[$tag])) {
                            $tagDb = new Tag();
                            $tagDb->setName($tag);
                            $tagDb->setBase(false);
                            $tagDb->setSortOrder(70 + $amount);

                            $allTags[$tag] = $tagDb;

                            $this->entityManager->persist($tagDb);
                        }
                    }
                }
            }
        }

        return $allTags;
    }

    /**
     * @param array<FileDto> $filesData
     * @param array<int, array<int, ReportBlock>> $reportBlocks
     * @return array<File>
     */
    private function createFiles(array $filesData, array $reportBlocks): array
    {
        $files = [];

        foreach ($filesData as $fileDto) {
            $file = new File();
            $file->setType($fileDto->type);
            $file->setPath($fileDto->path);
            $file->setFilename($fileDto->name);
            $file->setComment($fileDto->notes);
            $file->setProcessed(true);

            $this->entityManager->persist($file);
            $files[] = $file;

            foreach ($fileDto->markers as $markerDto) {
                $fileMarker = new FileMarker();
                $fileMarker->setCategory($markerDto->category ?? CategoryType::OTHER);
                $fileMarker->setStartTime(
                    $markerDto->timeFrom
                    ? CarbonImmutable::createFromFormat('i:s.u', $markerDto->timeFrom)
                    : null
                );
                $fileMarker->setEndTime(
                    $markerDto->timeTo
                    ? CarbonImmutable::createFromFormat('i:s.u', $markerDto->timeTo)
                    : null
                );
                $fileMarker->setName($markerDto->name);
                $fileMarker->setNotes($markerDto->notes);
                $fileMarker->setDecoding($markerDto->decoding);
                $fileMarker->setRitual($markerDto->ritual);
                $fileMarker->setAdditional($markerDto->additional);

                $fileMarker->setReportBlock($reportBlocks[$markerDto->reportKey][$markerDto->blockKey]);

                $file->addFileMarker($fileMarker);
                $this->entityManager->persist($fileMarker);
            }
        }

        return $files;
    }

    /**
     * @param Expedition $expedition
     * @param array<SubjectDto> $subjectsData
     * @param array<int, array<int, ReportBlock>> $reportBlocks
     * @return void
     */
    private function createSubjects(Expedition $expedition, array $subjectsData, array $reportBlocks): void
    {
        foreach ($subjectsData as $subjectDto) {
            $subject = new Subject();
            $subject->setName($subjectDto->name);
            $subject->setType($subjectDto->type);
            $subject->setDigit(Subject::IS_DIGIT);
            if ($subjectDto->hasFileMarkers()) {
                $subject->setMarked(true);
            }

            $expedition->addSubject($subject);
            $this->entityManager->persist($subject);

            $files = $this->createFiles($subjectDto->files, $reportBlocks);
            foreach ($files as $file) {
                $subject->addFile($file);
            }
        }
    }

    /**
     * @param Expedition $expedition
     * @param array<InformantDto> $informants
     * @param array<OrganizationDto> $organizations
     * @param array<ReportDataDto> $reportsData
     * @param array<FileDto> $filesData
     * @return array<Report>
     * @throws Exception
     */
    public function saveVideoKozReports(
        Expedition $expedition,
        array $informants,
        array $organizations,
        array $reportsData,
        array $filesData,
    ): array {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $informantsDb = $this->saveInformants($informants, [], []);
            $organizationsDb = $this->saveOrganizations($organizations, $informantsDb, []);
            $reports = $this->createReports($expedition, $reportsData, $informantsDb, $organizationsDb);

            $reportBlocks = [];
            foreach ($reports as $reportKey => $report) {
                foreach ($report->getBlocks() as $blockKey => $block) {
                    $reportBlocks[$reportKey][$blockKey] = $block;
                }
            }

            $this->createFiles($filesData, $reportBlocks);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            if ($this->entityManager->isOpen()) {
                $this->entityManager->getConnection()->rollBack();
            }

            throw $e;
        }

        return $reports;
    }

    /**
     * @param Expedition $expedition
     * @param array<InformantDto> $informants
     * @param array<OrganizationDto> $organizations
     * @param array<ReportDataDto> $reportsData
     * @param array<SubjectDto> $subjectsData
     * @return array<Report>
     * @throws Exception
     */
    public function saveSubjects(
        Expedition $expedition,
        array $informants,
        array $organizations,
        array $reportsData,
        array $subjectsData,
    ): array {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $informantsDb = $this->saveInformants($informants, [], []);
            $organizationsDb = $this->saveOrganizations($organizations, $informantsDb, []);
            $reports = $this->createReports($expedition, $reportsData, $informantsDb, $organizationsDb);

            $reportBlocks = [];
            foreach ($reports as $reportKey => $report) {
                foreach ($report->getBlocks() as $blockKey => $block) {
                    $reportBlocks[$reportKey][$blockKey] = $block;
                }
            }

            $this->createSubjects($expedition, $subjectsData, $reportBlocks);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            if ($this->entityManager->isOpen()) {
                $this->entityManager->getConnection()->rollBack();
            }

            throw $e;
        }

        return $reports;
    }
}
