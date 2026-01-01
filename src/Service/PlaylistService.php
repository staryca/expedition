<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FileMarker;
use App\Repository\CategoryRepository;
use App\Repository\DanceRepository;
use App\Repository\ImprovisationRepository;
use App\Repository\PackRepository;

readonly class PlaylistService
{
    public function __construct(
        private DanceRepository $danceRepository,
        private ImprovisationRepository $improvisationRepository,
        private PackRepository $packRepository,
        private CategoryRepository $categoryRepository,
    ) {
    }

    public function getPlaylists(FileMarker $fileMarker): array
    {
        $playlists = [];

        $category = $fileMarker->getCategory();
        if ($category) {
            $categoryObject = $this->categoryRepository->find($category);
            $playlists[$fileMarker->getCategoryName()] = $categoryObject ? $categoryObject->getPlaylist() : null;
        }

        $baseName = $fileMarker->getAdditionalDance();
        if (!empty($baseName)) {
            $dance = $this->danceRepository->findOneBy(['name' => $baseName]);
            $playlists[$baseName] = $dance?->getPlaylist();
        }

        $improvisation = $fileMarker->getAdditionalImprovisation();
        if (!empty($improvisation)) {
            $improvisationObject = $this->improvisationRepository->findOneBy(['name' => $improvisation]);
            $playlists[$improvisation] = $improvisationObject?->getPlaylist();
        }

        $danceType = $fileMarker->getAdditionalPack();
        if (!empty($danceType)) {
            $pack = $this->packRepository->findOneBy(['name' => $danceType]);
            $playlists[$danceType] = $pack?->getPlaylist();
        }

        $ritual = $fileMarker->getRitual();
        if ($ritual) {
            $playlists[$ritual->getName()] = $ritual->getPlaylist();
        }

        return $playlists;
    }
}
