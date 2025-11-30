<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\ContentDto;
use App\Dto\InformantDto;
use App\Dto\OrganizationDto;
use App\Dto\ReportBlockDataDto;
use App\Dto\ReportDataDto;
use App\Dto\UserRolesDto;
use App\Parser\Columns\KoboInformantColumns;
use App\Parser\Columns\KoboOrganizationColumns;
use App\Parser\Columns\KoboReportColumns;
use App\Parser\Columns\KoboTagColumns;
use App\Service\LocationService;
use App\Service\UserService;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;
use League\Csv\SyntaxError;

readonly class KoboParser
{
    public function __construct(
        private LocationService $locationService,
        private UserService $userService,
    ) {
    }

    /**
     * @param string $content
     * @return array<InformantDto>
     * @throws Exception
     * @throws InvalidArgument
     * @throws SyntaxError
     */
    public function parseInformants(string $content): array
    {
        $informants = [];

        $csv = Reader::fromString($content);
        $csv->setDelimiter(';');
        $csv->setEnclosure('"');
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        foreach ($csv->getRecords($header) as $record) {
            $informants[$record[KoboInformantColumns::INDEX]] = InformantDto::fromKobo($record);
        }

        return $informants;
    }

    /**
     * @param string $content
     * @return array<OrganizationDto>
     * @throws Exception
     * @throws InvalidArgument
     * @throws SyntaxError
     */
    public function parseOrganizations(string $content): array
    {
        $organizations = [];

        $csv = Reader::fromString($content);
        $csv->setDelimiter(';');
        $csv->setEnclosure('"');
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        foreach ($csv->getRecords($header) as $record) {
            $organizations[$record[KoboOrganizationColumns::INDEX]] = OrganizationDto::fromKobo($record);
        }

        return $organizations;
    }

    /**
     * @param string $content
     * @return array<ReportDataDto>
     * @throws Exception
     * @throws InvalidArgument
     * @throws SyntaxError
     */
    public function parseReports(string $content): array
    {
        $reports = [];

        $csv = Reader::fromString($content);
        $csv->setDelimiter(';');
        $csv->setEnclosure('"');
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        foreach ($csv->getRecords($header) as $record) {
            $reportDto = ReportDataDto::fromKobo($record);

            $location = $this->locationService->detectLocationByFullPlace($reportDto->place);
            if ($location !== null) {
                $reportDto->geoPoint = $location;
                $reportDto->place = null;
            }

            foreach ($reportDto->users as $key => $userDto) {
                $user = $this->userService->findByFullName($userDto->name);
                if (null !== $user) {
                    $userRole = new UserRolesDto();
                    $userRole->user = $user;
                    $userRole->roles = $userDto->roles;
                    $reportDto->userRoles[] = $userRole;
                    unset($reportDto->users[$key]);
                } else {
                    $userDto->found = false;
                }
            }

            $reportBlockDto = ReportBlockDataDto::fromKobo($record);
            $reportDto->blocks[] = $reportBlockDto;

            $reports[$record[KoboReportColumns::INDEX]] = $reportDto;
        }

        return $reports;
    }

    /**
     * @param string $contentCsv
     * @return array<int, ContentDto>
     * @throws Exception
     * @throws InvalidArgument
     * @throws SyntaxError
     */
    public function parseContents(string $contentCsv): array
    {
        $contents = [];

        $csv = Reader::fromString($contentCsv);
        $csv->setDelimiter(';');
        $csv->setEnclosure('"');
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        foreach ($csv->getRecords($header) as $record) {
            $contents[$record[KoboReportColumns::INDEX]] = ContentDto::fromKobo($record);
        }

        return $contents;
    }

    /**
     * @param string $content
     * @return array<int, array<string>>
     * @throws Exception
     * @throws InvalidArgument
     * @throws SyntaxError
     */
    public function parseTags(string $content): array
    {
        $tags = [];

        $csv = Reader::fromString($content);
        $csv->setDelimiter(';');
        $csv->setEnclosure('"');
        $csv->setHeaderOffset(0);
        $header = $csv->getHeader();
        foreach ($csv->getRecords($header) as $record) {
            $indexContent = $record[KoboTagColumns::INDEX_CONTENT] ? (int) $record[KoboTagColumns::INDEX_CONTENT] : null;
            $tag = $record[KoboTagColumns::TAG] ?? null;
            if ($tag === 'other') {
                $tag = $record[KoboTagColumns::TAG_OTHER] ?? null;
            }

            $tags[$indexContent][] = $tag;
        }

        return $tags;
    }
}
