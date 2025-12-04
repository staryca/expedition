<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Expedition;
use App\Repository\ExpeditionRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class ExpeditionManager
{
    public function __construct(
        private ExpeditionRepository $expeditionRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getNextExpedition(): Expedition
    {
        $last = $this->expeditionRepository->findOneBy([], ['id' => 'DESC']);

        $id = $last ? $last->getId() + 1 : 1;
        $expedition = new Expedition();
        $expedition->setId($id);
        $expedition->setName('Name ' . $id);

        $this->entityManager->persist($expedition);
        $this->entityManager->flush();

        return $expedition;
    }
}
