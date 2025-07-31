<?php

declare(strict_types=1);

namespace App\Handler;

use App\Entity\Expedition;
use App\Entity\Task;
use App\Repository\TaskRepository;

class ExpeditionHandler
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
    ) {
    }

    /**
     * @param Expedition $expedition
     * @return array<Task>
     */
    public function getTips(Expedition $expedition): array
    {
        $result = [];
        if ($expedition->getGeoPoint()) {
            $tips = $this->taskRepository->findTipsByInformantGeoPoint($expedition->getGeoPoint());
            foreach ($tips as $tip) {
                if ($tip->getReport()?->getExpedition()->getId() !== $expedition->getId()) {
                    $result[] = $tip;
                }
            }
        }

        return $result;
    }
}
