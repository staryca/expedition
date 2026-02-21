<?php

declare(strict_types=1);

namespace App\Controller;

use App\Handler\ImefHandler;
use App\Message\ImefMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class ImportImefController extends AbstractController
{
    public function __construct(
        private readonly ImefHandler $imefHandler,
    ) {
    }

    #[Route('/import/imef/check', name: 'app_import_imef_check')]
    public function index(): Response
    {
        $dtos = $this->imefHandler->check();
        $data = $this->imefHandler->getViewData($dtos);

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/import/imef/queue', name: 'app_import_imef_queue')]
    public function queue(MessageBusInterface $bus): Response
    {
        $folders = $this->imefHandler->getNewFolders();

        foreach ($folders as $folder) {
            $bus->dispatch(new ImefMessage($folder));
        }

        return $this->render('import/show.json.result.html.twig', [
            'data' => $folders,
        ]);
    }

    #[Route('/import/imef/save', name: 'app_import_imef_save')]
    public function save(): Response
    {
        $dtos = $this->imefHandler->check();
        $result = $this->imefHandler->saveDtos($dtos);

        return $this->render('import/show.json.result.html.twig', [
            'data' => $result,
        ]);
    }
}
