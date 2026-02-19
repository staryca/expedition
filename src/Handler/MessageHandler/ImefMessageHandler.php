<?php

declare(strict_types=1);

namespace App\Handler\MessageHandler;

use App\Handler\ImefHandler;
use App\Message\ImefMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ImefMessageHandler
{
    private bool $previousDateDayMonth = true;

    public function __construct(
        private readonly ImefHandler $handler,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ImefMessage $message): void
    {
        $dtos = $this->handler->parsingOneFolder($this->previousDateDayMonth, $message->getFolder());

        $this->logger->info(sprintf('For %s count dto: %d', $message->getFolder(), count($dtos)));

        $reports = $this->handler->saveDtos($dtos);

        $this->logger->info('Reports saved: ' . count($reports['reports']));
    }
}
