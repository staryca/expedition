<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\EpisodeDto;
use App\Entity\Type\CategoryType;

class ReportService
{
    /**
     * @param array<int, string> $contents
     * @param int $defaultCategory
     * @return array<int, EpisodeDto>
     */
    public function getEpisodes(array $contents, int $defaultCategory): array
    {
        $episodes = [];

        $episode = new EpisodeDto($defaultCategory, '');

        foreach ($contents as $index => $content) {
            if (mb_strlen($content) < 2) {
                $episodes[] = $episode;
                $episode = new EpisodeDto($defaultCategory, '');

                continue;
            }

            $pos = mb_strpos($content, ':');
            if (false !== $pos) {
                $episodes[] = $episode;
                $categoryContent = mb_substr($content, 0, $pos);
                $contentThis = trim(mb_substr($content, $pos + 1));
                $categoryThis = CategoryType::getId($categoryContent, $contentThis);
                if ($categoryThis) {
                    $episode = new EpisodeDto($categoryThis, $content);
                    continue;
                }
            }

            $text = $episode->getText() . "\n" . $content;
            $episode->setText($text);
        }

        $episodes[] = $episode;

        if ($episodes[0]->getText() === '') {
            array_shift($episodes);
        }

        return $episodes;
    }
}
