<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Expedition;
use App\Handler\ImefHandler;
use App\Repository\ExpeditionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportImefController extends AbstractController
{
    private const EXPEDITION_ID = 241; // 10

    public function __construct(
        private readonly ExpeditionRepository $expeditionRepository,
        private readonly ImefHandler $imefHandler,
    ) {
    }

    #[Route('/import/imef/check', name: 'app_import_imef_check')]
    public function index(): Response
    {
        set_time_limit(2600);
        $baseUrl = $this->getParameter('imef_url');

        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            return new Response('The expedition is not found', Response::HTTP_NOT_FOUND);
        }

        $dtos = $this->imefHandler->check($baseUrl, $expedition);
        $data = $this->imefHandler->getViewData($dtos);

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/import/imef/save', name: 'app_import_imef_save')]
    public function save(): Response
    {
        set_time_limit(2600);
        $baseUrl = $this->getParameter('imef_url');

        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            return new Response('The expedition is not found', Response::HTTP_NOT_FOUND);
        }

        $dtos = $this->imefHandler->check($baseUrl, $expedition);
        $reports = $this->imefHandler->saveDtos($expedition, $dtos);

        return $this->render('import/show.json.result.html.twig', [
            'data' => $reports,
        ]);
    }
}
