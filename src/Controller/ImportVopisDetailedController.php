<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\FileMarkerDto;
use App\Entity\Type\GenderType;
use App\Handler\VopisDetailedHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportVopisDetailedController extends AbstractController
{
    private const EXPEDITION_ID = 3; // 3
    private const FILENAME = '../var/data/vopis_detailed/vit_koz.csv';

    public function __construct(
        private readonly VopisDetailedHandler $handler,
    ) {
    }

    #[Route('/import/vopis_detailed/check', name: 'app_import_vopis_detailed_check')]
    public function check(): Response
    {
        try {
            $subjects = $this->handler->checkFile(self::FILENAME);
        } catch (\Exception $exception) {
            throw $exception;
        }

        $data = [];
        $data['errors_type'] = [];
        $data['errors_location'] = [];
        $data['errors_date'] = [];
        $data['errors_time'] = [];
        foreach ($subjects as $subject) {
            foreach ($subject->files as $file) {
                foreach ($file->markers as $marker) {
                    if ($marker->category === null) {
                        $data['errors_type'][] = $marker;
                    }
                    if (null === $marker->geoPoint) {
                        $data['errors_location'][] = $marker->place;
                    }
                    if (null === $marker->dateAction && !isset($marker->others[FileMarkerDto::OTHER_RECORD])) {
                        $data['errors_date'][] = $marker;
                    }
                    if (
                        ($marker->timeFrom !== null && !str_contains($marker->timeFrom, ':'))
                        || ($marker->timeTo !== null && !str_contains($marker->timeTo, ':'))
                    ) {
                        $data['errors_time'][] = $marker;
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
        $data['reports'] = $reportsData;

        $data['subjects'] = $subjects;

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/import/vopis_detailed/save', name: 'app_import_vopis_detailed_save')]
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
