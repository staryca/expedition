<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\BsuDto;
use App\Dto\InformantDto;
use App\Dto\OrganizationDto;
use App\Dto\PersonBsuDto;
use App\Dto\PersonDto;
use App\Dto\ReportBlockDataDto;
use App\Dto\ReportDataDto;
use App\Dto\StudentDto;
use App\Entity\Expedition;
use App\Entity\Report;
use App\Helper\TextHelper;
use App\Service\LocationService;
use App\Service\PersonService;
use Symfony\Component\DomCrawler\Crawler;

class BsuParser
{
    public function __construct(
        private readonly LocationService $locationService,
        private readonly PersonService $personService,
    ) {
    }

    public function parseContent(string $content): BsuDto
    {
        $crawler = new Crawler($content);

        $dto = new BsuDto();

        // Title
        $tagText = $crawler->filter('title')->first()->text();
        $dto->title = mb_strpos($tagText, 'Электронная библиотека БГУ:') !== false
            ? mb_substr($tagText, 28)
            : $tagText;

        // Find meta DC
        $crawler->filter('meta')->each(function (Crawler $node) use ($dto) {
            if (str_starts_with($node->attr('name') ?? '', 'DC')) {
                $_content = str_replace('&quot;', '', $node->attr('content'));
                $dto->dc[$node->attr('name')] = $_content;
            }
            if ($node->attr('name') === 'DC.identifier') {
                $dto->id = (int) substr($node->attr('content'), -6);
            }
        });

        // Find table
        $crawler->filter('.itemDisplayTable tr')->each(function (Crawler $node) use ($dto) {
            $label = $node->filter('.metadataFieldLabel')->first();
            $labelClass = trim(str_replace('metadataFieldLabel', '', $label->attr('class')));
            if (empty($labelClass)) {
                $labelClass = $label->text();
            }

            $nodeValue = $node->filter('.metadataFieldValue')->first();
            $dto->values[$labelClass] = $nodeValue->text();

            if ($labelClass === 'Appears in Collections:' || $labelClass === 'Располагается в коллекциях:') {
                $a = $nodeValue->filter('a')->first();

                $dto->locationId = (int) substr($a->attr('href'), -6);
                $dto->locationText = html_entity_decode($a->text());
            }

            if ($labelClass === 'dc_identifier_uri' && $dto->id < 1) {
                $a = $nodeValue->filter('a')->first();

                $dto->id = (int) substr($a->attr('href'), -6);
            }

            if ($labelClass === 'dc_contributor') {
                $nodeValue->filter('a')->each(function (Crawler $nodeA, $key) use ($dto) {
                    $i = $nodeA->text() === 'невядомы' ? 500 : $key;
                    $dto->authors[$i] = html_entity_decode($nodeA->innerText());
                });
            }
        });

        // Find links
        $crawler->filter('h4.list-group-item-heading')->each(function (Crawler $node) use ($dto) {
            $nodeA = $node->filter('a')->first();
            $id = (int) mb_substr($nodeA->attr('href'), -6);
            $dto->links[$id] = trim($nodeA->text());
        });

        // Find children
        $crawler->filter('td.evenRowEvenCol strong a')->each(function (Crawler $node) use ($dto) {
            $id = (int) mb_substr($node->attr('href'), -6);
            $dto->children[$id] = html_entity_decode($node->text());
        });
        $crawler->filter('td.oddRowEvenCol strong a')->each(function (Crawler $node) use ($dto) {
            $id = (int) mb_substr($node->attr('href'), -6);
            $dto->children[$id] = html_entity_decode($node->text());
        });

        // Find amount of children
        $pos = mb_strpos($content, ': 1 по 20 из ');
        if ($pos !== false) {
            $dto->total = (int) mb_substr($content, $pos + 13, 5);
        } else {
            $pos = mb_strpos($content, ': 1 по ');
            if ($pos !== false) {
                $dto->total = (int) mb_substr($content, $pos + 7, 5);
            }
        }

        // Find files
        $crawler->filter('div.panel-info table.panel-body tr')->each(function (Crawler $node) use ($dto) {
            $filename = null;
            $size = '';
            $type = '';

            $node->filter('td')->each(function (Crawler $nodeTd) use (&$filename, &$size, &$type) {
                if ($nodeTd->attr('headers') === 't1') {
                    $filename = urldecode($nodeTd->filter('a')->first()->attr('href'));
                }
                if ($nodeTd->attr('headers') === 't3') {
                    $size = $nodeTd->text();
                }
                if ($nodeTd->attr('headers') === 't4') {
                    $type = $nodeTd->text();
                }
            });
            if ($filename) {
                $dto->files[] = ['filename' => $filename, 'size' => $size, 'type' => $type];
            }
        });

        return $dto;
    }

    public function createReport(BsuDto $dto, Expedition $expedition): Report
    {
        $report = new Report($expedition);
        $report->setCode((string)$dto->id);
        $report->setDateCreated(
            isset($dto->dc['DCTERMS.available'])
            ? new \DateTimeImmutable($dto->dc['DCTERMS.available'])
            : date_create()
        );
        if (isset($dto->dc['DCTERMS.created']) && (int)$dto->dc['DCTERMS.created'] > 1900) {
            $report->setDateAction((new \DateTimeImmutable())->setDate((int)$dto->dc['DCTERMS.created'], 1, 1));
        }
        $report->setTemp((array)$dto);
        $report->setNotes((string)$dto->total);

        $location = trim(($dto->dc['DCTERMS.spatial'] ?? '') . ' ' . $dto->locationText);
        $notes = empty($location) ? '- ' . $dto->title : $location;
        $report->setGeoNotes($notes);
        $report->setGeoPoint(
            $this->locationService->detectLocation($dto->dc['DCTERMS.spatial'] ?? '', $dto->locationText)
        );

        return $report;
    }

    public function createReportData(BsuDto $dto): ReportDataDto
    {
        $reportData = new ReportDataDto();

        $reportData->dateCreated = isset($dto->dc['DCTERMS.available'])
            ? new \DateTimeImmutable($dto->dc['DCTERMS.available'])
            : date_create();
        if (isset($dto->dc['DCTERMS.created']) && (int) $dto->dc['DCTERMS.created'] > 1900) {
            $reportData->dateAction = (new \DateTimeImmutable())->setDate((int) $dto->dc['DCTERMS.created'], 1, 1);
        }
        $code = $dto->id ? (string) $dto->id : null;
        $reportData->code = $code;

        $location = trim(($dto->dc['DCTERMS.spatial'] ?? '') . ' ' . $dto->locationText);
        $notes = empty($location) ? '- ' . $dto->title : $location;
        $reportData->place = $notes;
        $reportData->geoPoint =
            $this->locationService->detectLocation($dto->dc['DCTERMS.spatial'] ?? '', $dto->locationText);

        if (null !== $code) {
            $reportBlockDataDto = new ReportBlockDataDto();
            $reportBlockDataDto->tags = isset($dto->dc['DC.subject'])
                ? $this->getTags($dto->dc['DC.subject'])
                : [];
            $reportBlockDataDto->description = isset($dto->dc['DC.title']) ? trim($dto->dc['DC.title']) : '';
            $reportBlockDataDto->additional['code'] = $dto->id;
            $reportBlockDataDto->additional['source_url'] = 'https://elib.bsu.by/handle/123456789/' . $dto->id;
            $reportBlockDataDto->files = $dto->files ?? [];
            foreach ($reportBlockDataDto->files as $key => $file) {
                $reportBlockDataDto->files[$key]['full_path'] = 'https://elib.bsu.by' . $file['filename'];
            }

            $reportData->blocks[$code] = $reportBlockDataDto;
        }

        return $reportData;
    }

    public function updateGeo(Report $report): bool
    {
        $data = $report->getTemp();

        $report->setDateCreated(
            isset($data['dc']['DCTERMS.available'])
            ? new \DateTimeImmutable($data['dc']['DCTERMS.available'])
            : date_create()
        );
        if (isset($data['dc']['DCTERMS.created']) && (int) $data['dc']['DCTERMS.created'] > 1900) {
            $report->setDateAction((new \DateTimeImmutable())->setDate((int)$data['dc']['DCTERMS.created'], 1, 1));
        }

        $geoPoint = $this->locationService->detectLocation($data['dc']['DCTERMS.spatial'], $data['locationText']);
        if ($geoPoint) {
            $report->setGeoPoint($geoPoint);
            return true;
        }

        return false;
    }

    /**
     * @param array<string> $authors
     * @param int $yearAction
     * @return array<PersonDto>
     */
    public function getPersonsByAuthors(array $authors, int $yearAction): array
    {
        $persons = [];

        foreach ($authors as $author) {
            $person = new PersonDto();
            $author = TextHelper::replaceLetters($author);
            if (
                str_contains($author, 'рупа жанчын') /* група */
                || $author === 'тое ж'
                || $author === 'дзіцё'
                || str_contains($author, 'пявякоў') /* спявякоў */
            ) {
                $author = 'невядомы';
            }
            if (
                str_contains($author, 'тудэнт') || str_contains($author, 'студ.') /* студэнт */
                || str_contains($author, 'збіральнік')
            ) {
                $person->isStudent = true;
            }
            $person->isOrganization = str_contains($author, 'ансамбль') || str_contains($author, 'хор даярак')
                || str_contains($author, 'калектыў') || str_contains($author, '"')
                || str_contains($author, 'фальклорн') || str_contains($author, 'Ансабль');
            $person->isUnknown = $author === 'невядомы';

            $pos = mb_strpos($author, '(');
            $additional = $pos > 0
                ? mb_substr($author, $pos + 1)
                : '';
            $name = $pos > 0
                ? trim(mb_substr($author, 0, $pos))
                : $author;
            $name = str_replace(',', '', $name);
            if (mb_substr($name, -2, 1) === ' ' && mb_substr($name, -1, 1) !== ' ') {
                $name .= '.';
            }
            $person->name = $name;

            $pos = mb_strpos($additional, 'гадоў');
            $pos = $pos > 0 ? $pos : mb_strpos($additional, 'гады');
            $year = null;
            if ($pos > 0 && $yearAction > 1900) {
                $year = $yearAction - (int) $additional;
            }
            if ($pos === false && ($posY = mb_strpos($additional, '1')) !== false) {
                $year = (int) mb_substr($additional, $posY, 4);
            }
            $person->birth = $year;

            $persons[] = $person;
        }

        return $persons;
    }

    /**
     * @param Report $report
     * @return array<PersonDto>
     */
    public function getPersons(Report $report): array
    {
        $data = $report->getTemp();
        $authors = $data['authors'];
        return $this->getPersonsByAuthors($authors, ((int) $report->getDateAction()?->format('Y')));
    }

    /**
     * @param array<Report> $reports
     * @return array<PersonBsuDto>
     */
    public function getBsuPersons(array $reports): array
    {
        $result = [];
        foreach ($reports as $report) {
            $persons = $this->getPersons($report);

            foreach ($persons as $person) {
                if ($person->isUnknown) {
                    continue;
                }
                $personBsu = (new PersonBsuDto())->make($person);
                $personBsu->geoPoint = $report->getGeoPoint();
                $personBsu->place = $report->getGeoNotes();
                $personBsu->codeReport = $report->getCode();

                $result[] = $personBsu;
            }
        }

        return $result;
    }

    /**
     * @param array<string> $authors
     * @param ReportDataDto $reportData
     * @return array<PersonBsuDto>
     */
    public function getBsuPersonsFromAuthors(array $authors, ReportDataDto $reportData): array
    {
        $result = [];

        $persons = $this->getPersonsByAuthors($authors, (int) $reportData->dateAction?->format('Y'));
        foreach ($persons as $person) {
            if ($person->isUnknown) {
                continue;
            }
            $personBsu = (new PersonBsuDto())->make($person);
            $personBsu->geoPoint = $reportData->geoPoint;
            $personBsu->place = $reportData->place;
            $personBsu->codeReport = $reportData->code;

            $result[] = $personBsu;
        }

        return $result;
    }

    /**
     * @param array<PersonBsuDto> $personsBsu
     * @param array<OrganizationDto> $organizations
     * @return void
     */
    public function getOrganizations(array &$personsBsu, array &$organizations): void
    {
        foreach ($personsBsu as $key => $personBsu) {
            if ($personBsu->isOrganization) {
                $isSame = false;
                foreach ($organizations as $organization) {
                    if ($organization->isSame($personBsu)) {
                        $organization->addCodeReport($personBsu->codeReport);
                        $isSame = true;
                        break;
                    }
                }
                if (!$isSame) {
                    $organizations[] = (new OrganizationDto())->make($personBsu);
                }
                unset($personsBsu[$key]);
            }
        }
    }

    /**
     * @param array<OrganizationDto> $organizations
     * @param array<InformantDto> $informants
     * @return void
     */
    public function getInformantsFromOrganizations(array &$organizations, array &$informants): void
    {
        foreach ($organizations as $organization) {
            $this->personService->parseOrganization($organization);
            if ($organization->informantText) {
                $informant = new InformantDto();
                $informant->name = $organization->informantText;
                $informant->addLocation($organization->place);
                $informant->addCodeReports($organization->codeReports);
                $informants[] = $informant;
            }
        }
    }

    /**
     * @param array<PersonBsuDto> $personsBsu
     * @param array<StudentDto> $students
     * @return void
     */
    public function getStudents(array &$personsBsu, array &$students): void
    {
        foreach ($personsBsu as $key => $personBsu) {
            if ($personBsu->isStudent) {
                foreach ($this->personService->detectStudents($personBsu) as $student) {
                    $isSame = false;
                    foreach ($students as $_student) {
                        if ($student->isSame($_student)) {
                            $isSame = true;
                            break;
                        }
                    }
                    if (!$isSame) {
                        $students[] = $student;
                    }
                }
                unset($personsBsu[$key]);
            }
        }
    }

    /**
     * @param array<PersonBsuDto> $personsBsu
     * @param array<InformantDto> $informants
     * @return void
     */
    public function getInformants(array &$personsBsu, array &$informants): void
    {
        /** @var array<InformantDto> $informants */
        foreach ($personsBsu as $key => $personBsu) {
            $informant = $this->personService->parseInformant($personBsu);

            $isSame = false;
            foreach ($informants as $_informant) {
                if ($informant->isSame($_informant)) {
                    $_informant->addCodeReport($personBsu->codeReport);
                    if ($_informant->birth !== $informant->birth) {
                        $_informant->birth = null;
                    }
                    $isSame = true;
                    break;
                }
            }
            if (!$isSame) {
                $informant->addCodeReport($personBsu->codeReport);
                $informants[] = $informant;
            }

            unset($personsBsu[$key]);
        }
    }

    /**
     * @param array<OrganizationDto> $organizations
     * @param array<InformantDto> $informants
     * @param array<StudentDto> $students
     * @return array<int, array<int>>
     */
    public function detectInformantsInOrganizations(array &$organizations, array &$informants, array &$students): array
    {
        $result = [];
        foreach ($organizations as $orgKey => $organization) {
            $inOrganization = [];
            foreach ($informants as $informantKey => $informant) {
                $codes = array_intersect($organization->codeReports, $informant->codeReports);
                if (count($codes) === count($organization->codeReports)) {
                    $inOrganization[] = $informantKey;
                }
            }
            if (count($inOrganization) === 0) {
                continue;
            }

            if (
                count($inOrganization) === 1
                && !$this->personService->isSameNames(
                    (string) $organization->informantText,
                    $informants[$inOrganization[0]]->name
                )
            ) {
                $this->personService->informantToStudent($informants[$inOrganization[0]], $students);
                unset($informants[$inOrganization[0]]);
                continue;
            }
            $result[$orgKey] = $inOrganization;
            $organization->informantKeys = $inOrganization;
        }

        return $result;
    }

    /**
     * @param array<InformantDto> $informants
     * @return array<array<string>>
     */
    public function mergeSameInformants(array &$informants): array
    {
        $result = [];
        for ($i = 0; $i < 2; $i++) { // merge 2 times
            $informants = array_values($informants);
            $key = 0;
            $count = count($informants);
            while ($key < $count - 2) {
                if (
                    false !== $informants[$key]->isSameBirth($informants[$key + 1])
                    && $this->personService->isSameNames($informants[$key]->name, $informants[$key + 1]->name)
                    && $informants[$key]->isSameLocation($informants[$key + 1]->locations)
                ) {
                    $mergedInformant = [$informants[$key]->name, $informants[$key + 1]->name];

                    $informants[$key]->mergeInformant($informants[$key + 1]);
                    $informants[$key]->name =
                        $this->personService->getFullName($informants[$key]->name, $informants[$key + 1]->name);

                    unset($informants[$key + 1]);

                    $mergedInformant['name'] = $informants[$key]->name;
                    $result[] = $mergedInformant;
                    $key++;
                }

                $key++;
            }
        }

        return $result;
    }

    /**
     * @param array<InformantDto> $informants
     * @param array<StudentDto> $students
     * @return array<InformantDto>
     */
    public function compareInformantsAndStudents(array &$informants, array &$students): array
    {
        /** @var array<InformantDto> $result */
        $result = [];
        foreach ($informants as $key => $informant) {
            foreach ($students as $student) {
                if (
                    $informant->isSameLocation($student->locations)
                    && $this->personService->isSameNames($informant->name, $student->name)
                ) {
                    $student->addLocations($informant->locations);
                    $result[] = $informant;
                    unset($informants[$key]);
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<InformantDto> $informants
     * @param array<StudentDto> $students
     * @return array<InformantDto>
     */
    public function detectStudentsByLocations(array &$informants, array &$students): array
    {
        $informants = array_values($informants);

        $result = [];
        $key = 0;
        while ($key < count($informants) - 2) {
            // Find many same informants
            $keyGroup = $key + 1;
            $isSame = true;
            $name = $informants[$key]->name;
            $isAllEmptyBirth = null === $informants[$key]->birth;
            $countLocations = count($informants[$key]->locations);
            while ($keyGroup <= count($informants) - 1 && $isSame) {
                if (
                    false !== $informants[$key]->isSameBirth($informants[$keyGroup])
                    && $this->personService->isSameNames($name, $informants[$keyGroup]->name)
                ) {
                    $name = $this->personService->getFullName($name, $informants[$keyGroup]->name);
                    $isAllEmptyBirth = $isAllEmptyBirth && null === $informants[$keyGroup]->birth;
                    $countLocations += count($informants[$keyGroup]->locations);
                    $keyGroup++;
                } else {
                    $isSame = false;
                }
            }
            $keyGroup--;

            // Many informants without birthday => student
            if ($isAllEmptyBirth && $countLocations >= 3) {
                for ($i = $key + 1; $i <= $keyGroup; $i++) {
                    $informants[$key]->addLocations($informants[$i]->locations);
                    unset($informants[$i]);
                }
                $this->personService->informantToStudent($informants[$key], $students, $name);
                unset($informants[$key]);
            }

            // Many informants with birthday => merge
            if (!$isAllEmptyBirth && $countLocations >= 3) {
                $isEqualBirth = true;
                $birth = $informants[$key]->birth;
                for ($i = $key + 1; $i <= $keyGroup; $i++) {
                    if ($birth !== $informants[$i]->birth) {
                        $isEqualBirth = false;
                    }
                }

                if ($isEqualBirth) {
                    $informants[$key]->name = $name;
                    for ($i = $key + 1; $i <= $keyGroup; $i++) {
                        $informants[$key]->addLocations($informants[$i]->locations);
                        $informants[$key]->addCodeReports($informants[$i]->codeReports);
                        unset($informants[$i]);
                    }
                    $result[] = $informants[$key];
                }
            }

            $key = $keyGroup + 1;
        }

        return $result;
    }

    /**
     * @param array<InformantDto> $informants
     * @param array<StudentDto> $students
     * @return array<string, array<InformantDto>>
     */
    public function detectStudentsByNames(array &$informants, array &$students): array
    {
        $result = [];
        foreach ($informants as $key => $informant) {
            if (null !== $informant->birth) {
                continue;
            }
            foreach ($students as $student) {
                if ($this->personService->isSameNames($informant->name, $student->name)) {
                    $result[$student->name][] = $informant;
                    $student->addLocations($informant->locations);
                    unset($informants[$key]);
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<OrganizationDto> $organizations
     * @param array<InformantDto> $informants
     * @return array<ReportBlockDataDto>
     */
    public function getReportBlocks(array $organizations, array $informants): array
    {
        $result = [];
        foreach ($organizations as $organizationKey => $organization) {
            foreach ($organization->codeReports as $codeReport) {
                if (!isset($result[$codeReport])) {
                    $result[$codeReport] = new ReportBlockDataDto();
                }
                $result[$codeReport]->organizationKey = $organizationKey;

                foreach ($organization->informantKeys as $informantKey) {
                    $informants[$informantKey]->codeReports = [];
                }
            }
            $organization->codeReports = [];
        }
        foreach ($informants as $informantKey => $informant) {
            foreach ($informant->codeReports as $codeReport) {
                if (!isset($result[$codeReport])) {
                    $result[$codeReport] = new ReportBlockDataDto();
                }
                $result[$codeReport]->informantKeys[] = $informantKey;
            }
            $informant->codeReports = [];
        }

        return $result;
    }

    /**
     * @param array<ReportDataDto> $reports
     * @param array<ReportBlockDataDto> $reportBlocks
     * @return void
     */
    public function mergeReportBlocks(array $reports, array $reportBlocks): void
    {
        foreach ($reports as $report) {
            foreach ($report->blocks as $code => $block) {
                if (isset($reportBlocks[$code])) {
                    $report->blocks[$code]->merge($reportBlocks[$code]);
                }
            }
        }
    }

    /**
     * @param string $subject
     * @return array<string>
     */
    public function getTags(string $subject): array
    {
        $results = [];

        $_tags = explode('::', $subject);
        foreach ($_tags as $tag) {
            $tag = trim($tag);
            if (!in_array($tag, ['', 'ЭБ БГУ', 'Беларускі фальклор'])) {
                $results[] = $tag;
            }
        }

        return $results;
    }
}
