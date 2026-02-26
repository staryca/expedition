<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\ImefDto;
use App\Dto\UserDto;
use App\Entity\Additional\Month;
use App\Entity\Type\CategoryType;
use App\Helper\TextHelper;
use App\Service\LocationService;
use App\Service\PersonService;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

class ImefParser
{
    public const array BAD_WORDS = [
        'сталін', 'крым', 'немцы', 'немец', 'гітлер', 'берлін', 'брыгадзір', 'фашыст', 'мінск', 'эсэс', 'паліцай', 'фрыц',
        'вайна', 'партызан', 'ленін', 'нямецк', 'штаб', 'кацюша', 'эшалон', 'германск', 'фронт',
    ];
    public const array BAD_TAGS = ['ваенная песня', 'салдацкая песня', 'рэвалюцыйная песня'];

    public function __construct(
        private readonly LocationService $locationService,
        private readonly PersonService $personService,
    ) {
    }

    /**
     * @param string $content
     * @return array<string>
     */
    public function parseCatalog(string $content): array
    {
        $result = [];
        $crawler = new Crawler($content);

        $crawler->filter('.row ul li a')->each(function (Crawler $node) use (&$result) {
            $result[] = $node->attr('href');
        });

        return $result;
    }

    /**
     * @param bool $previousDateDayMonth
     * @param string $content
     * @param string $folder
     * @return array<ImefDto>
     * @throws \Exception
     */
    public function parseItem(bool &$previousDateDayMonth, string $content, string $folder = ''): array
    {
        $result = [];
        $crawler = new Crawler($content);

        $nodeTable = $crawler->filter('.row table')->first();
        if ($nodeTable->count() > 0) {
            $nodeTable->filter('tr')->each(function (Crawler $node) use ($previousDateDayMonth, $folder, &$result) {
                $columns = $node->children();
                $dto = new ImefDto();
                $dto->content = $node->outerHtml();
                $dto->folder = $folder;

                $date = TextHelper::cleanManySpaces($columns->eq(0)->text());
                if ($date === 'Год') {
                    return;
                }
                if (($pos = mb_strpos($date, '-')) !== false) { // 10-20 mmm YYYY
                    $date = trim(mb_substr($date, $pos + 1));
                }
                if ((int) $date >= 1 && (int) $date <= 31) {
                    $parts = explode(' ', $date);
                    if (count($parts) >= 2) {
                        $day = (int) $parts[0];
                        $month = Month::getMonth($parts[1]);
                        $year = (int) $parts[1];
                        if (($year < 1800 || $year > 2020) && isset($parts[2])) {
                            $year = (int) $parts[2];
                        }
                        $dto->date = Carbon::createFromDate($year, $month, $day);
                    }
                } elseif ((int) $date < 1900) {
                    $parts = explode(' ', $date);
                    if (count($parts) >= 2) {
                        $month = Month::getMonth($parts[0]);
                        $day = 15;
                        $year = (int) $parts[1];
                        $dto->date = Carbon::createFromDate($year, $month, $day);
                    }
                } elseif (mb_strlen($date) > 5 && $date[4] === '.') {
                    // Y.m.d or Y.d.m
                    $parts = explode('.', $date);
                    if (count($parts) > 2) {
                        $year = (int) $parts[0];
                        $part1 = (int) $parts[1];
                        $part2 = (int) $parts[2];
                        if ($part1 > 12) {
                            $previousDateDayMonth = true;
                        }
                        if ($part2 > 12) {
                            $previousDateDayMonth = false;
                        }
                        // if part1 and part2 <= 12 then we see form previous item
                        $month = $previousDateDayMonth ? $part2 : $part1;
                        $day = $previousDateDayMonth ? $part1 : $part2;
                        $dto->date = Carbon::createFromDate($year, $month, $day);
                    }
                } elseif (str_contains($date, ',')) {
                    $year = (int) $date;
                    $date = trim(mb_substr($date, 5));
                    $day = (int) $date;
                    $date = trim(mb_substr($date, 2));
                    $month = Month::getMonth($date);
                    $dto->date = Carbon::createFromDate($year, $month, $day);
                } else {
                    $dto->date = Carbon::createFromDate((int) $date, 1, 1);
                }

                $users = $columns->eq(1)->text();
                foreach (explode(';', $users) as $user) {
                    $userDto = new UserDto($user);
                    $dto->users[] = $userDto;
                }

                $place = $columns->eq(2)->text();
                $place = str_replace(' - ', ' ' . LocationService::DISTRICT . ', ', $place);
                $location = $this->locationService->detectLocationByFullPlace($place);
                if ($location !== null) {
                    $dto->geoPoint = $location;
                } else {
                    $dto->place = $place;
                }

                $informants = $columns->eq(3)->text();
                $informants = str_replace(['|','(',')'], ['; ','',''], $informants);
                $dto->informants = $this->personService->getInformants(
                    $informants,
                    '',
                    null,
                    $dto->date?->year,
                    '|'
                );

                $name = $columns->eq(4)->text();
                $dto->name = trim($name, " ,.;:\t\n\r\0\x0B");

                $tags = $columns->eq(5)->text();
                foreach (explode('#', $tags) as $tag) {
                    if (!empty($tag)) {
                        $dto->tags[] = trim($tag);
                    }
                }

                foreach (array_reverse($dto->tags) as $tag) {
                    $category = CategoryType::getCategoryByTags($tag);
                    if ($category !== null) {
                        break;
                    }
                }
                $dto->category = $category ?? CategoryType::OTHER;

                $key = $folder . md5(var_export($dto, true));
                $result[$key] = $dto;
            });
        }

        return $result;
    }
}
