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

        $category = $defaultCategory;
        foreach ($contents as $index => $content) {
            if (mb_strlen($content) < 2) {
                $category = $defaultCategory;
                continue;
            }

            $pos = mb_strpos($content, ':');
            if (false !== $pos) {
                $categoryContent = mb_substr($content, 0, $pos);
                $contentThis = trim(mb_substr($content, $pos + 1));
                $categoryThis = CategoryType::getId($categoryContent, $contentThis);
                if ($categoryThis) {
                    if (empty($contentThis)) {
                        $category = $categoryThis;
                        if ($category !== CategoryType::getIdForOther($categoryContent, $contentThis)) {
                            continue;
                        }
                    } else {
                        if ($categoryThis === CategoryType::getIdForOther($categoryContent, $contentThis)) {
                            $contentThis = $content;
                        }
                        $episodes[$index] = new EpisodeDto($categoryThis, $contentThis);
                        continue;
                    }
                }
            }
            $episodes[$index] = new EpisodeDto($category, $content);
        }

        return $episodes;
    }
}
