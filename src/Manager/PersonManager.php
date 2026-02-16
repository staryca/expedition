<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\GeoPoint;
use App\Entity\Informant;
use App\Repository\OrganizationInformantRepository;
use App\Repository\ReportBlockRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class PersonManager
{
    public function __construct(
        private OrganizationInformantRepository $organizationInformantRepository,
        private ReportBlockRepository $reportBlockRepository,
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function mergeDuplicates(Informant $informant1, Informant $informant2): array
    {
        $changed = $this->mergeInformants($informant1, $informant2);
        $changed['#id'] = $informant1->getId();
        $changed['organizations'] = [];
        $changed['reports'] = [];
        $changed['tasks'] = [];

        $orgIds = [];
        $items = $this->organizationInformantRepository->findBy(['informant' => $informant1]);
        foreach ($items as $item) {
            $orgIds[] = $item->getOrganization()->getId();
        }

        $items = $this->organizationInformantRepository->findBy(['informant' => $informant2]);
        foreach ($items as $item) {
            if (!in_array($item->getOrganization()->getId(), $orgIds)) {
                $changed['organizations'][] = 'moved ' . $item->getOrganization()->getName();
                $item->setInformant($informant1);
            } else {
                $this->entityManager->remove($item);
            }
        }

        $blocks = $this->reportBlockRepository->findByInformant($informant2);
        foreach ($blocks as $block) {
            $has1 = false;
            foreach ($block->getInformants() as $informant) {
                if ($informant->getId() === $informant2->getId()) {
                    $block->removeInformant($informant);
                }
                if ($informant->getId() === $informant1->getId()) {
                    $has1 = true;
                }
            }
            if (!$has1) {
                $block->addInformant($informant1);
            }
            $changed['reports'][] =
                $block->getReport() . ', ' . $block->getReport()->getMiddleGeoPlace() . ' #' . $block->getId();
        }

        $items = $this->taskRepository->findBy(['informant' => $informant2]);
        foreach ($items as $item) {
            $changed['tasks'][] = (string) $item;
            $item->setInformant($informant1);
        }

        $this->entityManager->remove($informant2);

        $this->entityManager->flush();

        return $changed;
    }

    public function mergeInformants(Informant &$informant1, Informant $informant2): array
    {
        try {
            $result = $this->mergeFullInformants($informant1, $informant2);
        } catch (\Exception $e) {
            throw new \Exception(
                $e->getMessage() . ': #' . $informant1->getId() . ' -> #' . $informant2->getId(),
                $e->getCode(),
                $e
            );
        }

        return $result;
    }
    private function mergeFullInformants(Informant &$informant1, Informant $informant2): array
    {
        $result = [];

        if (
            $informant2->getGeoPointBirth()
            && $informant1->getGeoPointBirth() !== $informant2->getGeoPointBirth()
        ) {
            if ($informant1->getGeoPointBirth()) {
                throw new \Exception('Bad geoPointBirth');
            }

            $result['geoPointBirth'] =
                $informant1->getGeoPointBirth()?->getMiddleBeName() . ' -> ' . $informant2->getGeoPointBirth()->getMiddleBeName();
            $informant1->setGeoPointBirth($informant2->getGeoPointBirth());
        }

        if (
            $informant2->getGeoPointCurrent()
            && $informant1->getGeoPointCurrent() !== $informant2->getGeoPointCurrent()
        ) {
            if ($informant1->getGeoPointCurrent()) {
                throw new \Exception('Bad getGeoPointCurrent');
            }

            $result['geoPointCurrent'] =
                $informant1->getGeoPointCurrent()?->getMiddleBeName() . ' -> ' . $informant2->getGeoPointCurrent()->getMiddleBeName();
            $informant1->setGeoPointCurrent($informant2->getGeoPointCurrent());
        }

        $name1 = $informant1->getFirstName();
        $name2 = $informant2->getFirstName();
        if ($name1 !== $name2 && mb_strlen($name1) < mb_strlen($name2)) {
            $result['name'] = $name1 . ' -> ' . $name2;
            $informant1->setFirstName($name1);
        }

        if ($informant2->hasGender() && $informant1->getGender() !== $informant2->getGender()) {
            if ($informant1->hasGender()) {
                throw new \Exception('Bad getGender');
            }

            $result['gender'] = $informant1->getGender() . ' -> ' . $informant2->getGender();
            $informant1->setGender($informant2->getGender());
        }

        $newValue = $this->checkInt($informant1->getYearBirth(), $informant2->getYearBirth(), 'getYearBirth');
        if (null !== $newValue) {
            $result['yearBirth'] = ($informant1->getYearBirth() ?? '?') . ' -> ' . $newValue;
            $informant1->setYearBirth($newValue);
        }

        if ($informant2->getDayBirth() && $informant1->getDayBirth() !== $informant2->getDayBirth()) {
            if ($informant1->getDayBirth()) {
                throw new \Exception('Bad getDayBirth');
            }

            $result['dayBirth'] =
                $informant1->getDayBirth()->format('Y-m-d') . ' -> ' . $informant2->getDayBirth()->format('Y-m-d');
            $informant1->setDayBirth($informant2->getDayBirth());
        }

        $newValue = $this->checkInt($informant1->getYearDied(), $informant2->getYearDied(), 'getYearDied');
        if (null !== $newValue) {
            $result['yearDied'] = ($informant1->getYearDied() ?? '?') . ' -> ' . $newValue;
            $informant1->setYearDied($newValue);
        }

        if (!$informant1->isDied() && $informant2->isDied()) {
            $result['isDied'] = 'no -> yes';
            $informant1->setDied(true);
        }

        if (
            !empty($informant2->getNotes())
            && $informant1->getNotes() !== $informant2->getNotes()
            && !str_contains($informant1->getNotes(), $informant2->getNotes())
        ) {
            $notes = $informant1->getNotes();
            if (empty($notes) || str_contains($informant2->getNotes(), $notes)) {
                $notes = $informant2->getNotes();
            } else {
                $notes .= '; ' . $informant2->getNotes();
            }
            $result['notes'] = $informant1->getNotes() . ' -> ' . $notes;
            $informant1->setNotes($notes);
        }

        $newValue = $this->checkInt($informant1->getYearDied(), $informant2->getYearDied(), 'getYearDied');
        if (null !== $newValue) {
            $result['yearDied'] = ($informant1->getYearDied() ?? '?') . ' -> ' . $newValue;
            $informant1->setYearDied($newValue);
        }

        $newValue = $this->checkString($informant1->getPlaceBirth(), $informant2->getPlaceBirth(), 'getPlaceBirth');
        if (null !== $newValue) {
            $result['placeBirth'] = $informant1->getPlaceBirth() . ' -> ' . $newValue;
            $informant1->setPlaceBirth($newValue);
        }

        $newValue = $this->checkString($informant1->getPlaceCurrent(), $informant2->getPlaceCurrent(), 'getPlaceCurrent');
        if (null !== $newValue) {
            $result['placeCurrent'] = $informant1->getPlaceCurrent() . ' -> ' . $newValue;
            $informant1->setPlaceCurrent($newValue);
        }

        $newValue = $this->checkString($informant1->getPhone(), $informant2->getPhone(), 'getPhone');
        if (null !== $newValue) {
            $result['phone'] = $informant1->getPhone() . ' -> ' . $newValue;
            $informant1->setPhone($newValue);
        }

        $newValue = $this->checkString($informant1->getAddress(), $informant2->getAddress(), 'getAddress');
        if (null !== $newValue) {
            $result['address'] = $informant1->getAddress() . ' -> ' . $newValue;
            $informant1->setAddress($newValue);
        }

        $newValue = $this->checkInt($informant1->getYearTransfer(), $informant2->getYearTransfer(), 'getYearTransfer');
        if (null !== $newValue) {
            $result['yearTransfer'] = ($informant1->getYearTransfer() ?? '?') . ' -> ' . $newValue;
            $informant1->setYearTransfer($newValue);
        }

        $newValue = $this->checkString($informant1->getConfession(), $informant2->getConfession(), 'getConfession');
        if (null !== $newValue) {
            $result['confession'] = $informant1->getConfession() . ' -> ' . $newValue;
            $informant1->setConfession($newValue);
        }

        $newValue = $this->checkString($informant1->getPathPhoto(), $informant2->getPathPhoto(), 'getPathPhoto');
        if (null !== $newValue) {
            $result['pathPhoto'] = $informant1->getPathPhoto() . ' -> ' . $newValue;
            $informant1->setPathPhoto($newValue);
        }

        $newValue = $this->checkString($informant1->getUrlPhoto(), $informant2->getUrlPhoto(), 'getUrlPhoto');
        if (null !== $newValue) {
            $result['urlPhoto'] = $informant1->getUrlPhoto() . ' -> ' . $newValue;
            $informant1->setUrlPhoto($newValue);
        }

        if (
            null !== $informant1->isMusician()
            && null !== $informant2->isMusician()
            && $informant1->isMusician() !== $informant2->isMusician()
        ) {
            throw new \Exception('Bad isMusician');
        }
        if (null === $informant1->isMusician() && null !== $informant2->isMusician()) {
            $result['musician'] = '? -> ' . $informant2->isMusician();
            $informant1->setIsMusician($informant2->isMusician());
        }

        return $result;
    }

    private function checkString(?string $text1, ?string $text2, string $name): ?string
    {
        if (empty($text2) || $text1 === $text2 || str_contains((string) $text1, $text2)) {
            return null;
        }

        if (empty($text1) || str_contains($text2, $text1)) {
            return $text2;
        } else {
            throw new \Exception('Bad ' . $name . ' (' . $text1 . ' -> ' . $text2 . ')');
        }
    }

    private function checkInt(?int $value1, ?int $value2, string $name): ?int
    {
        if (null === $value2 || $value1 === $value2) {
            return null;
        }

        if (null === $value1) {
            return $value2;
        } else {
            throw new \Exception('Bad ' . $name . ' (' . $value1 . ' -> ' . $value2 . ')');
        }
    }

    private function checkGeoPoint(?GeoPoint $geoPoint1, ?GeoPoint $geoPoint2, string $name): ?GeoPoint
    {
        if (null === $geoPoint2 || $geoPoint1?->getId() === $geoPoint2->getId()) {
            return null;
        }

        if (null === $geoPoint1) {
            return $geoPoint2;
        } else {
            throw new \Exception(
                'Bad ' . $name . ' (' . $geoPoint1->getMiddleBeName() . ' -> ' . $geoPoint2->getMiddleBeName() . ')'
            );
        }
    }
}
