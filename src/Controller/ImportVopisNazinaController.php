<?php

declare(strict_types=1);

namespace App\Controller;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use App\Entity\Type\CategoryType;
use App\Entity\Type\GenderType;
use App\Handler\VopisNazinaHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportVopisNazinaController extends AbstractController
{
    private const int EXPEDITION_ID = 14; // 14
    private const string FILENAME = '../var/data/vopis_nazina/vopis.csv';

    public function __construct(
        private readonly VopisNazinaHandler $handler,
    ) {
    }

    #[Route('/import/vopis_nazina/check', name: 'app_import_vopis_nazina_check')]
    public function check(): Response
    {
        $subjects = $this->handler->checkFile(self::FILENAME);

        $data = [];
        $data['errors_type'] = [];
        $data['errors_location'] = [];
        $data['errors_dance'] = [];
        $data['errors_filenames'] = [];
        foreach ($subjects as $subject) {
            foreach ($subject->files as $file) {
                if (empty($file->getFilename())) {
                    $data['errors_filenames'][] = $subject->name;
                }
                foreach ($file->markers as $marker) {
                    if ($marker->category === null) {
                        $data['errors_type'][] = $marker;
                    }
                    if (null === $marker->geoPoint) {
                        $data['errors_location'][] = $marker->place;
                    }
                    if (null === $marker->dance && $marker->category === CategoryType::DANCE) {
                        $data['errors_dance'][] = 'No dance for: ' . $marker->name;
                    }
                }
            }
        }
        $data['errors_location'] = array_unique($data['errors_location']);

        $organizations = [];
        $informants = [];
        $this->handler->detectOrganizationsAndInformants($subjects, $organizations, $informants);
        $data['organizations'] = $organizations;
        $data['informants'] = $informants;

        $data['errors_gender'] = [];
        foreach ($informants as $informant) {
            if ($informant->gender === GenderType::UNKNOWN) {
                $data['errors_gender'][] = $informant;
            }
        }

        $reportsData = $this->handler->createReportsData($subjects);
        $data['errors_date'] = [];
        foreach ($reportsData as $reportData) {
            if (null === $reportData->dateAction) {
                $data['errors_date'][] = $reportData->getPlaceHash();
            } elseif (1900 > (int) $reportData->dateAction->format('Y')) {
                $data['errors_date'][] = $reportData->getPlaceHash() . ' Year: ' . $reportData->dateAction->format('Y');
            }
        }
        $data['reports'] = $reportsData;

        $data['subjects'] = $subjects;
        $data['link'] = $this->generateUrl('app_import_vopis_nazina_save', [], UrlGeneratorInterface::ABS_URL);

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/import/vopis_nazina/save', name: 'app_import_vopis_nazina_save')]
    public function save(): Response
    {
        try {
            $subjects = $this->handler->checkFile(self::FILENAME);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_NOT_FOUND);
        }

        $reports = $this->handler->saveSubjects(self::EXPEDITION_ID, $subjects);

        return $this->render('import/show.json.result.html.twig', [
            'data' => $reports,
        ]);
    }
}
