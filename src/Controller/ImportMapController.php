<?php

declare(strict_types=1);

namespace App\Controller;

use App\Manager\ExpeditionManager;
use App\Manager\FileManager;
use App\Manager\ReportManager;
use App\Parser\MapParser;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportMapController extends AbstractController
{
    private const ACTION_CHECK = 'check';
    private const ACTION_IMPORT = 'import';

    public function __construct(
        private readonly ExpeditionManager $expeditionManager,
        private readonly MapParser $mapParser,
        private readonly FileManager $fileManager,
        private readonly ReportManager $reportManager,
    ) {
    }

    #[Route('/import/map/check', name: 'app_import_map_check', methods: ['GET'])]
    public function check(): Response
    {
        $filename = '../var/data/map/test.csv';

        return $this->checkFile($filename);
    }

    private function checkFile(string $filename): Response
    {
        $data = [];

        $content = file_get_contents($filename);

        $files = $this->mapParser->parse($content);

        $colors = [];
        $amount = 0;
        foreach ($files as $file) {
            foreach ($file->markers as $marker) {
                $amount++;
                $color = $marker->additional['color'] ?? null;
                if ($color) {
                    if (!isset($colors[$color])) {
                        $colors[$color] = 1;
                    } else {
                        $colors[$color]++;
                    }
                }
            }
        }
        $data['colors'] = $colors;
        $data['amount_colors'] = array_sum($colors);
        $data['amount_markers'] = $amount;

        $reports = $this->fileManager->createReports($files, Carbon::now());
        $data['amount_locations'] = count($reports);

        $data['location_errors'] = [];
        foreach ($reports as $report) {
            if (null !== $report->place) {
                $data['location_errors'][] = $report;
            }
        }

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/import/map/save', name: 'app_import_map_save', methods: ['GET'])]
    public function save(): Response
    {
        $filename = '../var/data/map/test.csv';

        return $this->importFile($filename);
    }

    private function importFile(string $filename): Response
    {
        $data = [];

        $content = file_get_contents($filename);

        $files = $this->mapParser->parse($content);

        $reports = $this->fileManager->createReports($files, Carbon::now());
        $data['locations'] = count($reports);

        $data['location_errors'] = [];
        foreach ($reports as $report) {
            if (null !== $report->place) {
                $data['location_errors'][] = $report;
            }
        }

        $expedition = $this->expeditionManager->getNextExpedition();
        $this->reportManager->saveVopisReports(
            $expedition,
            $reports,
            $files,
        );
        $data['saved'] = true;

        return $this->render('import/show.json.result.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/import/map/select', name: 'app_import_map_select', methods: ['GET', 'POST'])]
    public function select(Request $request): Response
    {
        $form = $this->createFormBuilder()
            ->add('file', FileType::class, ['label' => 'Выбярыце csv-файл з дадзенымі'])
            ->add('action', ChoiceType::class, [
                'choices' => ['Праверыць' => self::ACTION_CHECK, 'Імпарт' => self::ACTION_IMPORT],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('send', SubmitType::class, ['label' => 'Адправіць'])
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $action = $data['action'];
            /** @var UploadedFile $file */
            $file = $data['file'];

            if ($action === self::ACTION_IMPORT) {
                return $this->importFile($file->getRealPath());
            } else {
                return $this->checkFile($file->getRealPath());
            }
        }

        return $this->render('import/map.html.twig', [
            'form' => $form,
        ]);
    }
}
