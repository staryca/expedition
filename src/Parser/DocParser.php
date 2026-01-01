<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\ReportBlockDataDto;
use App\Dto\ReportDataDto;
use App\Entity\Type\CategoryType;
use App\Entity\Type\InformationType;
use App\Entity\Type\ReportBlockType;
use App\Parser\Type\DocBlockType;
use App\Service\LocationService;
use App\Service\PersonService;
use App\Service\ReportService;
use App\Service\UserService;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

readonly class DocParser
{
    public function __construct(
        private LocationService $locationService,
        private PersonService $personService,
        private UserService $userService,
        private ReportService $reportService,
    ) {
    }

    /**
     * @param string $html
     * @return array<ReportDataDto>
     * @throws \Exception
     */
    public function parseDoc(string $html): array
    {
        /** @var array<ReportDataDto> $reports */
        $reports = [];
        $reportKey = -1;
        $blockKey = -1;
        $expeditionDate = null;
        $users = [];
        $previousType = null;

        $crawler = new Crawler($html);

        // Find all blocks
        $content = [];
        $crawler->filter('para')->each(function (Crawler $node) use (&$blockKey, &$reportKey, &$reports, &$content, &$previousType, &$expeditionDate, &$users) {
            $text = htmlspecialchars_decode(trim($node->text()));

            $currentType = null;
            $types = null === $previousType ? [DocBlockType::BLOCK_SEC_TITLE] : DocBlockType::NEXT[$previousType];
            foreach ($types as $type) {
                if (isset(DocBlockType::CONTAINS[$type]) && false !== mb_strstr($text, DocBlockType::CONTAINS[$type])) {
                    $currentType = $type;
                    break;
                }
            }
            if (null === $currentType) {
                foreach ($types as $type) {
                    if (!isset(DocBlockType::CONTAINS[$type])) {
                        $currentType = $type;
                        break;
                    }
                }
            }

            if (null === $currentType && mb_strlen($text) > 1) {
                throw new \Exception('Type for "' . $text . '" not found. Prev: ' . $previousType);
            }

            if (null !== $currentType) {
                // Detect text
                if (DocBlockType::BLOCK_DATE_EXP === $currentType) {
                    $expeditionDate = Carbon::parse(mb_substr($text, 0, 10));
                }

                if (DocBlockType::BLOCK_USERS_DATA === $currentType) {
                    $users = $this->userService->getUsers($text);
                }

                if ((DocBlockType::BLOCK_CONTENT_DATA !== $currentType) && count($content) > 0) {
                    $episodes = $this->reportService->getEpisodes(
                        $content,
                        $reports[$reportKey]->blocks[$blockKey]->type === ReportBlockType::TYPE_CONVERSATION
                            ? CategoryType::STORY
                            : CategoryType::OTHER
                    );
                    $reports[$reportKey]->blocks[$blockKey]->setEpisodes($episodes);
                    $content = [];
                }

                if (DocBlockType::BLOCK_LOCATION_TITLE === $currentType) {
                    $reportKey++;
                    $reports[$reportKey] = new ReportDataDto();
                    $reports[$reportKey]->dateAction = $expeditionDate;
                    $reports[$reportKey]->userRoles = $users;
                    //$reports[$reportKey]->code = mb_substr($text, mb_strlen(DocBlockType::CONTAINS[$currentType]) + 1, 2);
                    $blockKey = -1;
                }

                if (DocBlockType::BLOCK_LOCATION_DATA === $currentType) {
                    $geoPoint = $this->locationService->detectLocationByFullPlace($text);
                    if (null !== $geoPoint) {
                        $reports[$reportKey]->geoPoint = $geoPoint;
                    } else {
                        $reports[$reportKey]->place = $text;
                    }
                }

                if (DocBlockType::BLOCK_NUMBER === $currentType) {
                    $blockKey++;
                    $reports[$reportKey]->blocks[$blockKey] = new ReportBlockDataDto();
                    $reports[$reportKey]->blocks[$blockKey]->additional['code'] =
                        mb_substr($text, mb_strlen(DocBlockType::CONTAINS[$currentType]) + 1, 2);
                }

                if (DocBlockType::BLOCK_TYPE_DATA === $currentType) {
                    $reports[$reportKey]->blocks[$blockKey]->type = ReportBlockType::getType($text);
                }

                if (DocBlockType::BLOCK_INFORMATION_DATA === $currentType && !empty($text)) {
                    $key = DocBlockType::CONTAINS[$previousType] ?? false;
                    if (false !== $key) {
                        $type = InformationType::getType($key);
                        if (null !== $type) {
                            $reports[$reportKey]->blocks[$blockKey]->additional[$type] = $text;
                        }
                    }
                }

                if (DocBlockType::BLOCK_INFORMANTS_DATA === $currentType && mb_strlen($text) > 2) {
                    $informants = $this->personService->getInformants($text);
                    foreach ($informants as $informant) {
                        $location = current($informant->locations);
                        if (!empty($informant->locations)) {
                            $geoPoint = $this->locationService->detectLocationByFullPlace($location);
                            if (null !== $geoPoint) {
                                $informant->geoPoint = $geoPoint;
                            } elseif (null !== $reports[$reportKey]->geoPoint) {
                                // As location of report
                                if (mb_stristr($location, $reports[$reportKey]->geoPoint->getName()) !== false) {
                                    $informant->geoPoint = $reports[$reportKey]->geoPoint;
                                } else {
                                    // As location in the same district
                                    $geoPoint = $this->locationService->detectLocationByFullPlace(
                                        $location,
                                        $reports[$reportKey]->geoPoint->getDistrict()
                                    );
                                    if (null !== $geoPoint) {
                                        $informant->geoPoint = $geoPoint;
                                    }
                                }
                            }
                            if (null === $informant->geoPoint) {
                                $informant->place = $location;
                            }
                        }
                        $informant->locations = [];
                    }
                    $reports[$reportKey]->blocks[$blockKey]->addInformants(...$informants);
                }

                if ((DocBlockType::BLOCK_CONTENT_DATA === $currentType)) {
                    $content[] = $text;
                }

                if (DocBlockType::BLOCK_TIPS_DATA === $currentType && '' !== $text) {
                    $reports[$reportKey]->tips[] = $text;
                }

                if (DocBlockType::BLOCK_PLAN_DATA === $currentType && '' !== $text && !is_numeric($text)) {
                    $reports[$reportKey]->tasks[] = $text;
                }

                $previousType = $currentType;
                // End detect text
            }
        });

        return $reports;
    }
}
