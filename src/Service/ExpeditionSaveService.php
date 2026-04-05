<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\FileMarkerRepository;
use App\Repository\FileRepository;
use App\Repository\InformantRepository;
use App\Repository\OrganizationInformantRepository;
use App\Repository\OrganizationRepository;
use App\Repository\ReportBlockRepository;
use App\Repository\ReportRepository;
use App\Repository\SubjectRepository;
use App\Repository\UserReportRepository;

readonly class ExpeditionSaveService
{
    public function __construct(
        private ReportRepository $reportRepository,
        private ReportBlockRepository $reportBlockRepository,
        private FileRepository $fileRepository,
        private FileMarkerRepository $fileMarkerRepository,
        private InformantRepository $informantRepository,
        private OrganizationRepository $organizationRepository,
        private OrganizationInformantRepository $organizationInformantRepository,
        private UserReportRepository $userReportRepository,
        private SubjectRepository $subjectRepository,
    ) {
    }

    public function getLastIds(): array
    {
        $result = [];

        $file = $this->fileRepository->findOneBy([], ['id' => 'DESC']);
        $result['last_file_id'] = $file?->getId();

        $fileMarker = $this->fileMarkerRepository->findOneBy([], ['id' => 'DESC']);
        $result['last_file_marker_id'] = $fileMarker?->getId();

        $informant = $this->informantRepository->findOneBy([], ['id' => 'DESC']);
        $result['last_informant_id'] = $informant?->getId();

        $organization = $this->organizationRepository->findOneBy([], ['id' => 'DESC']);
        $result['last_organization_id'] = $organization?->getId();

        $organizationInformant = $this->organizationInformantRepository->findOneBy([], ['id' => 'DESC']);
        $result['last_organization_informant_id'] = $organizationInformant?->getId();

        $report = $this->reportRepository->findOneBy([], ['id' => 'DESC']);
        $result['last_report_id'] = $report?->getId();

        $reportBlock = $this->reportBlockRepository->findOneBy([], ['id' => 'DESC']);
        $result['last_report_block_id'] = $reportBlock?->getId();

        $userReport = $this->userReportRepository->findOneBy([], ['id' => 'DESC']);
        $result['last_user_report_id'] = $userReport?->getId();

        $subject = $this->subjectRepository->findOneBy([], ['id' => 'DESC']);
        $result['last_subject'] = $subject?->getId();

        return $result;
    }
}
