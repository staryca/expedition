<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Type\GenderType;
use App\Repository\InformantRepository;
use App\Service\InformantService;
use App\Service\PersonService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ToolsController extends AbstractController
{
    public function __construct(
        private readonly InformantRepository $informantRepository,
        private readonly PersonService $personService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/import/tools/list', name: 'app_import_tools_list')]
    public function list(): Response
    {
        return $this->render('tools/list.html.twig');
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
            'headers' => ['Імя до', 'Пол до', 'Імя пасля', 'Пол пасля'],
            'data' => $data,
        ]);
    }

    #[Route('/import/tools/duplicate_informant_names', name: 'app_import_tools_duplicate_informant_names')]
    public function duplicateInformantNames(): Response
    {
        $data = [];

        $informants = $this->informantRepository->findAll();
        $duplicates = (new PersonService())->getDuplicates($informants);
        $duplArray = [];
        foreach ($duplicates as $value) {
            $duplArray[] = $value[0];
            $duplArray[] = $value[1];
        }

        foreach ($duplArray as $informant) {
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
}
