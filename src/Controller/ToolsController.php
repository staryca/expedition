<?php

declare(strict_types=1);

namespace App\Controller;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use App\Entity\Additional\Musician;
use App\Entity\Type\CategoryType;
use App\Entity\Type\GenderType;
use App\Handler\GeoPointHandler;
use App\Manager\PersonManager;
use App\Parser\VopisDetailedParser;
use App\Repository\InformantRepository;
use App\Repository\ReportRepository;
use App\Service\CategoryService;
use App\Service\LocationService;
use App\Service\MarkerService;
use App\Service\PersonService;
use App\Service\RitualService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ToolsController extends AbstractController
{
    public function __construct(
        private readonly InformantRepository $informantRepository,
        private readonly ReportRepository $reportRepository,
        private readonly GeoPointHandler $geoPointHandler,
        private readonly PersonService $personService,
        private readonly LocationService $locationService,
        private readonly RitualService $ritualService,
        private readonly MarkerService $markerService,
        private readonly CategoryService $categoryService,
        private readonly PersonManager $personManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/import/tools/list', name: 'app_import_tools_list')]
    public function list(): Response
    {
        return $this->render(
            'tools/list.html.twig',
            [
                'vopis_detailed_columns' => VopisDetailedParser::getColumns(),
            ]
        );
    }

    #[Route('/import/tools/all_informants', name: 'app_import_tools_informants')]
    public function informants(): Response
    {
        $data = [];

        $informants = $this->informantRepository->findAll();
        foreach ($informants as $informant) {
            $dto = $informant->getNameAndGender();
            $item['before_gender'] = GenderType::TYPES_MIDDLE[$dto->gender];
            $item['before_name'] = $dto->getName();
            $dto->gender = GenderType::UNKNOWN;

            $middleNames = $this->personService->fixNameAndGender($dto);
            $item['after_gender'] = GenderType::TYPES_MIDDLE[$dto->gender];
            $item['after_name'] = $dto->getName();

            $item['compare'] =
                $item['before_gender'] !== GenderType::TYPES_MIDDLE[GenderType::UNKNOWN]
                && $item['after_gender'] !== $item['before_gender']
                && $item['after_gender'] !== GenderType::TYPES_MIDDLE[GenderType::UNKNOWN]
            ;

            $item['middle_names'] = implode(', ', $middleNames);

            if ($item['before_name'] !== $item['after_name'] || $item['after_gender'] !== $item['before_gender']) {
                $data[] = $item;
            }
        }

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['Імя до', 'Пол до', 'Імя пасля', 'Пол пасля', 'Супадзеньне пола', 'Імя па бацьку'],
            'data' => $data,
        ]);
    }

    #[Route('/import/tools/all_middle_names', name: 'app_import_tools_all_middle_names')]
    public function allMiddleNames(): Response
    {
        $names = [];
        $informants = $this->informantRepository->findAll();
        foreach ($informants as $informant) {
            $dto = $informant->getNameAndGender();

            $middleNames = $this->personService->fixNameAndGender($dto);
            if ($dto->gender === GenderType::MALE) {
                foreach ($middleNames as $middleName) {
                    $names[] = $middleName;
                }
            }
        }
        $names = array_unique($names);
        sort($names);

        $data = [];
        foreach ($names as $name) {
            $data[] = [
                'name' => $name,
                'in_list' => GenderType::isMaleMiddle($name) ? 'yes' : 'no',
            ];
        }

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['Імя па бацьку', 'Ёсьць у сьпісе'],
            'data' => $data,
        ]);
    }

    #[Route('/import/tools/update_name_informants', name: 'app_import_tools_update_name_informants')]
    public function updateInformantNames(): Response
    {
        $data = [];

        $informants = $this->informantRepository->findAll();
        foreach ($informants as $informant) {
            $dto = $informant->getNameAndGender();
            $oldName = $dto->getName();
            $oldGender = $dto->gender;
            $dto->gender = GenderType::UNKNOWN;

            $this->personService->fixNameAndGender($dto);
            if ($dto->gender === GenderType::UNKNOWN) {
                $dto->gender = $oldGender;
            }

            $isChanged = $informant->setNameAndGender($dto);
            if ($isChanged) {
                $item['before_gender'] = GenderType::TYPES_MIDDLE[$oldGender];
                $item['before_name'] = $oldName;
                $item['after_gender'] = GenderType::TYPES_MIDDLE[$dto->gender];
                $item['after_name'] = $dto->getName();

                $data[] = $item;
            }
        }

        $this->entityManager->flush();

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['Пол до', 'Імя до', 'Пол пасля', 'Імя пасля'],
            'data' => $data,
        ]);
    }

    #[Route('/import/tools/duplicate_informant_names', name: 'app_import_tools_duplicate_informant_names')]
    public function duplicateInformantNames(): Response
    {
        $data = [];

        $informants = $this->informantRepository->findSortedByName();
        $duplicates = $this->personService->getDuplicates($informants);

        foreach ($duplicates as $informants) {
            $informant = $informants[0];
            $item['name1'] = $this->renderView('part/informant.full.html.twig', ['informant' => $informant]);

            $informant = $informants[1];
            $item['name2'] = $this->renderView('part/informant.full.html.twig', ['informant' => $informant]);

            $hasShortName = str_contains($informant->getFirstName(), '.');
            $item['duplicate'] = ($item['name1'] === $item['name2'] && !$hasShortName) ? 'yes' : 'no';

            $link = $this->generateUrl(
                'app_import_tools_merge_informants',
                ['id1' => $informants[0]->getId(), 'id2' => $informants[1]->getId()],
                UrlGeneratorInterface::ABS_URL
            );
            $item['merge'] = '<a target="_blank" href="' . $link . '">Аб\'яднаць</a>';

            $data[] = $item;
        }

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['Інфармант 1', 'Інфармант 2', '=', "Аб'яднаць"],
            'data' => $data,
        ]);
    }

    #[Route('/import/tools/merge_duplicate_informants', name: 'app_import_tools_merge_duplicate_informants')]
    public function mergeDuplicateInformants(): Response
    {
        $result = [];

        $informants = $this->informantRepository->findSortedByName();
        $duplicates = $this->personService->getDuplicates($informants);

        foreach ($duplicates as $informants) {
            $informant1 = $informants[0];
            $html1 = $this->renderView('part/informant.full.html.twig', ['informant' => $informant1]);

            $informant2 = $informants[1];
            $html2 = $this->renderView('part/informant.full.html.twig', ['informant' => $informant2]);

            if ($html1 === $html2) {
                $result[] = $this->personManager->mergeDuplicates($informant1, $informant2);
            }
        }

        return $this->render('import/show.json.result.html.twig', [
            'data' => $result,
        ]);
    }

    #[Route('/import/tools/merge_informants/{id1}/{id2}', name: 'app_import_tools_merge_informants')]
    public function mergeTwoInformants(int $id1, int $id2): Response
    {
        $informant1 = $this->informantRepository->find($id1);
        $informant2 = $this->informantRepository->find($id2);
        $result = $this->personManager->mergeDuplicates($informant1, $informant2);

        return $this->render('import/show.json.result.html.twig', [
            'data' => $result,
        ]);
    }

    #[Route('/import/tools/update_all_musicians', name: 'app_import_tools_update_all_musicians')]
    public function updateAllMusicians(): Response
    {
        $data = [];
        $informants = $this->informantRepository->findAll();
        foreach ($informants as $informant) {
            $wasMusician = $informant->isMusician();
            $isMusician = Musician::isMusician($informant->getNotes());
            if ($wasMusician === $isMusician || is_null($isMusician)) {
                continue;
            }

            $informant->setIsMusician($isMusician);
            $data[] = [
                'id' => $informant->getId(),
                'name' => $informant->getFirstName(),
                'was' => $wasMusician === null ? '?' : ($wasMusician ? 'Муз.' : 'не'),
                'now' => $isMusician ? 'Муз.' : 'не',
                'notes' => $informant->getNotes(),
            ];
        }

        $this->entityManager->flush();

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['Id', 'Імя', 'Быў', 'Стаў', 'Заўвагі'],
            'data' => $data,
        ]);
    }

    #[Route('/import/tools/update_geo', name: 'app_import_tools_update_geo')]
    public function updateGeoTablePoints(): Response
    {
        $data = $this->geoPointHandler->setRegionsAndDistricts();

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['Id', 'Месца', 'Тып', 'К.', 'Рэгіёны', 'Раёны', 'Тыпы', 'Новае', '!'],
            'data' => $data,
        ]);
    }

    #[Route('/import/tools/detect_report_points', name: 'app_import_tools_detect_report_points')]
    public function detectReportPoints(): Response
    {
        $data = [];

        $reports = $this->reportRepository->findNotDetectedPoints();
        foreach ($reports as $report) {
            $reportPlace = $report->getGeoNotes();
            $dto = $this->locationService->getSearchDtoByFullPlace($reportPlace);
            if (null === $dto->district) {
                continue;
            }
            $reportPoint = $this->locationService->detectLocationByFullPlace($report->getGeoNotes());

            $item = [
                'id' => $report->getId(),
                'was' => $reportPlace,
                'now' => $reportPoint?->getFullBeName(true),
            ];

            if ($reportPoint !== null) {
                $report->setGeoPoint($reportPoint);
                $report->setGeoNotes(null);

                array_unshift($data, $item);
            } else {
                $data[] = $item;
            }
        }

        $this->entityManager->flush();

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['Справаздача', 'Лакацыя', 'Знойдзена'],
            'data' => $data,
        ]);
    }

    #[Route('/import/tools/detect_informant_points', name: 'app_import_tools_detect_informant_points')]
    public function detectInformantPoints(): Response
    {
        $data = [];
        $informants = $this->informantRepository->findNotDetectedPoints();
        foreach ($informants as $informant) {
            $place = $informant->getPlaceBirth();
            $dto = $this->locationService->getSearchDtoByFullPlace((string) $place);
            if (!empty($place) && $dto->district !== null && !$informant->getGeoPointBirth()) {
                $point = $this->locationService->detectLocationByFullPlace($place);

                $item = [
                    'id' => $informant->getFirstName(),
                    'was' => $place,
                    'now' => $point?->getFullBeName(true),
                ];

                if ($point !== null) {
                    $informant->setGeoPointBirth($point);
                    $informant->setPlaceBirth(null);

                    array_unshift($data, $item);
                } else {
                    $data[] = $item;
                }
            }

            $place = $informant->getPlaceCurrent();
            $dto = $this->locationService->getSearchDtoByFullPlace((string) $place);
            if (!empty($place) && $dto->district !== null && !$informant->getGeoPointCurrent()) {
                $point = $this->locationService->detectLocationByFullPlace($place);

                $item = [
                    'id' => $informant->getFirstName(),
                    'was' => $place,
                    'now' => $point?->getFullBeName(true),
                ];

                if ($point !== null) {
                    $informant->setGeoPointCurrent($point);
                    $informant->setPlaceCurrent(null);

                    array_unshift($data, $item);
                } else {
                    $data[] = $item;
                }
            }
        }

        $this->entityManager->flush();

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['Інфармант', 'Лакацыя', 'Знойдзена'],
            'data' => $data,
        ]);
    }

    #[Route('/import/tools/rituals_check', name: 'app_import_tools_rituals_check')]
    public function checkRituals(): Response
    {
        $data = [];
        $key = 10000;

        $markerGroups = $this->markerService->getGroupedMarkersInLocation();
        foreach ($markerGroups as $category => $markers) {
            if (CategoryType::isSystemType($category)) {
                continue;
            }

            foreach ($markers as $marker) {
                if (empty($marker->getNotes())) {
                    continue;
                }

                $item = [];
                $item['id'] = $marker->getId();
                $item['type'] = $marker->getCategoryName();
                $item['name'] = $marker->getName();
                $item['notes'] = $marker->getNotes();

                $item['ritual'] = ($this->ritualService->detectRitual($marker->getNotes()))?->getName();

                $category = $this->categoryService->detectCategory($marker->getName(), $marker->getNotes());
                $errorCategory = $category !== $marker->getCategory();
                $item['category'] = ($category ? CategoryType::getSingleName($category) : '???') .
                    ($errorCategory ? ' <i class="bi bi-exclamation-diamond-fill text-danger"></i>' : '');

                $key++;
                $data[$key + ($errorCategory ? 10000 : 0)] = $item;
            }
        }

        krsort($data);

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['Id', 'Тып', 'Назва', 'Заўвагі', 'Жанр', 'Тып 2'],
            'data' => $data,
        ]);
    }

    #[Route('/import/tools/correct_informant_places', name: 'app_import_tools_correct_informant_places')]
    public function correctInformantPlaces(): Response
    {
        $data = [];

        $informants = $this->informantRepository->findNotDetectedPoints();
        foreach ($informants as $informant) {
            $place = (string) $informant->getPlaceBirth();
            if (!empty($place) && $informant->getGeoPointBirth()) {
                $point = $this->locationService->detectLocationByFullPlace($place);
                if ($point !== null && $informant->getGeoPointBirth()->getId() === $point->getId()) {
                    $item = [
                        'id' => $informant->getId(),
                        'name' => $informant->getFirstName(),
                        'status' => 'ok',
                        'place' => $place,
                        'point' => $informant->getGeoPointBirth()->getFullBeName(true),
                    ];
                    $informant->setPlaceBirth(null);
                } else {
                    $item = [
                        'id' => $informant->getId(),
                        'name' => $informant->getFirstName(),
                        'status' => 'no',
                        'place' => $place,
                        'point' => $informant->getGeoPointBirth()->getFullBeName(true),
                    ];
                }
                $data[] = $item;

                $place = (string) $informant->getPlaceCurrent();
                if (!empty($place) && $informant->getGeoPointCurrent()) {
                    $point = $this->locationService->detectLocationByFullPlace($place);
                    if ($point !== null && $informant->getGeoPointCurrent()->getId() === $point->getId()) {
                        $item = [
                            'id' => $informant->getId(),
                            'name' => $informant->getFirstName(),
                            'status' => 'ok',
                            'place' => $place,
                            'point' => $informant->getGeoPointCurrent()->getFullBeName(true),
                        ];
                        $informant->setPlaceCurrent(null);
                    } else {
                        $item = [
                            'id' => $informant->getId(),
                            'name' => $informant->getFirstName(),
                            'status' => 'no',
                            'place' => $place,
                            'point' => $informant->getGeoPointCurrent()->getFullBeName(true),
                        ];
                    }
                    $data[] = $item;
                }
            }
        }

        $this->entityManager->flush();

        return $this->render('import/show.table.result.html.twig', [
            'headers' => ['ID', 'Інфармант', 'Status', 'Place', 'Point'],
            'data' => $data,
        ]);
    }
}
