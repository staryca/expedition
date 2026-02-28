<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\YoutubeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class YouTubeController extends AbstractController
{
    public function __construct(
        private readonly YoutubeService $youtubeService,
    ) {
    }

    #[Route('/videos/list', name: 'app_videos_list')]
    public function list(): Response
    {
        $youtube = $this->youtubeService->getYoutubeService();

        // by playlist (with api_key)
        //$list = $youtube->playlistItems->listPlaylistItems('snippet', ['playlistId' => 'PLcZbXijhBgIoxkJvHt_lbm9fp72mCjcvX']);

/*
        $service = new Google_Service_YouTube($client);
*/
        $queryParams = [
            'id' => 'UCF-ObqklvVQ1mo4OoCTVbFA'
        ];

        $list = $youtube->channels->listChannels('snippet,contentDetails,statistics', $queryParams);
/*
        $queryParams = [
            'forMine' => true,
            'maxResults' => 25,
            'type' => 'video'
        ];
        $list = $youtube->search->listSearch('id,snippet', $queryParams);
*/
        $data = [];
        foreach ($list->getItems() as $item) {
            $data[] = [$item->getSnippet()->getTitle()];
        }

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['title'],
            'data' => $data,
        ]);
    }
}
