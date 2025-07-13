<?php

declare(strict_types=1);

namespace App\Parser;

use App\Dto\ImefDto;
use App\Dto\UserDto;
use App\Entity\Type\CategoryType;
use App\Service\LocationService;
use App\Service\PersonService;
use App\Service\UserService;
use Carbon\Carbon;
use Symfony\Component\DomCrawler\Crawler;

class ImefParser
{
    public const BAD_WORDS = [
        'сталін', 'крым', 'немцы', 'немец', 'гітлер', 'берлін', 'брыгадзір', 'фашыст', 'мінск', 'эсэс', 'паліцай', 'фрыц',
        'вайна', 'партызан', 'ленін', 'нямецк', 'штаб', 'кацюша', 'эшалон', 'германск', 'фронт',
    ];
    public const BAD_TAGS = ['ваенная песня', 'салдацкая песня', 'рэвалюцыйная песня'];

    public function __construct(
        private readonly LocationService $locationService,
        private readonly PersonService $personService,
        private readonly UserService $userService,
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
     * @param string $content
     * @param string $folder
     * @return array<ImefDto>
     */
    public function parseItem(string $content, string $folder = ''): array
    {
        $result = [];
        $crawler = new Crawler($content);

        $nodeTable = $crawler->filter('.row table')->first();
        if ($nodeTable->count() > 0) {
            $nodeTable->filter('tr')->each(function (Crawler $node) use ($folder, &$result) {
                $columns = $node->children();
                $dto = new ImefDto();
                $dto->content = $node->outerHtml();

                $date = $columns->eq(0)->text();
                if ((int) $date < 1900) {
                    return;
                }
                $dto->date = Carbon::createFromDate((int) $date, 1, 1);

                $users = $columns->eq(1)->text();
                foreach (explode(';', $users) as $user) {
                    $dto->users[] = new UserDto($user);
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
                $dto->informants = $this->personService->getInformants($informants, '', null, $dto->date->year);

                $name = $columns->eq(4)->text();
                $dto->name = trim($name, " ,.;:\t\n\r\0\x0B");

                $tags = $columns->eq(5)->text();
                foreach (explode('#', $tags) as $tag) {
                    if (!empty($tag)) {
                        $dto->tags[] = $tag;
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
