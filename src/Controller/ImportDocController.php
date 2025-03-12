<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Expedition;
use App\Entity\Type\ReportBlockType;
use App\Entity\Type\UserRoleType;
use App\Manager\ReportManager;
use App\Parser\DocParser;
use App\Repository\ExpeditionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportDocController extends AbstractController
{
    private const EXPEDITION_ID = 321;

    public function __construct(
        private readonly DocParser              $parser,
        private readonly ExpeditionRepository   $expeditionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ReportManager          $reportManager,
    ) {
    }

    #[Route('/import/doc/check', name: 'app_import_doc_check')]
    public function check(): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            return new Response('The expedition is not found', Response::HTTP_NOT_FOUND);
        }

        $filename = '../var/data/doc/report.xml';
        $content = file_get_contents($filename);

        $reportsData = $this->parser->parseDoc($content);

        $data = [];
        $episodes = [];
        foreach ($reportsData as $reportKey => $reportData) {
            if (null === $reportData->geoPoint) {
                $data['errors_geo_point'][] = $reportData->geoNotes;
            }

            $hasLeader = false;
            foreach ($reportData->userRoles as $userRole) {
                if (null === $userRole->user) {
                    $data['errors_user_empty'][] = implode(', ', $userRole->roles);
                } else {
                    $data['users_ok'][] = $userRole->user->getLastName();
                }
                if (empty($userRole->roles)) {
                    $data['errors_roles_empty'][] = $userRole->user?->getLastName();
                }
                if (in_array(UserRoleType::ROLE_LEADER, $userRole->roles)) {
                    $hasLeader = true;
                }
            }
            if (!$hasLeader) {
                $data['errors_roles_leader'] = 'Leader roles not found';
            }

            foreach ($reportData->blocks as $blockKey => $block) {
                if (ReportBlockType::TYPE_UNDEFINED === $block->type) {
                    $data['errors_block_type'][] = $block->additional;
                } else {
                    $data['types_ok'][] = ReportBlockType::TYPES[$block->type];
                }
                foreach ($block->informants as $informant) {
                    if (null === $informant->geoPoint && !empty($informant->place)) {
                        $data['errors_informant_place'][] = $informant->place;
                    }
                }
                foreach ($block->episodes as $episode) {
                    $episodes[$reportKey . "_" . $blockKey][] = $episode->toArray();
                }
            }
        }
        $data['episodes'] = $episodes;
        $data['reports'] = $reportsData;

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/import/doc/save', name: 'app_import_doc_save')]
    public function save(): Response
    {
        /** @var Expedition|null $expedition */
        $expedition = $this->expeditionRepository->find(self::EXPEDITION_ID);
        if (!$expedition) {
            return new Response('The expedition is not found', Response::HTTP_NOT_FOUND);
        }

        $filename = '../var/data/doc/report.xml';
        $content = file_get_contents($filename);

        $reportsData = $this->parser->parseDoc($content);

        $this->reportManager->createReports($expedition, $reportsData, [], [], []);
        $this->entityManager->flush();

        return $this->render('import/show.json.result.html.twig', [
            'data' => $reportsData,
        ]);
    }
}
