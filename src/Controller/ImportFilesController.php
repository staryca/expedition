<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Expedition;
use App\Repository\ExpeditionRepository;
use App\Service\FilesServer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportFilesController extends AbstractController
{
    private const EXPEDITION_ID = 311;

    public function __construct(
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly FilesServer          $filesServer,
    ) {
    }

    #[Route('/import/files/check', name: 'app_import_files_check')]
    public function check(): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            return new Response('The expedition is not found', Response::HTTP_NOT_FOUND);
        }

        $folders = $this->filesServer->getFolders($this->getParameter('media_folder'));

        $foldersInfo = $this->filesServer->getFolderInfo($folders, $expedition->getReports());

        return $this->render('import/show.diff.folders.html.twig', [
            'foldersInfo' => $foldersInfo,
        ]);
    }
}
