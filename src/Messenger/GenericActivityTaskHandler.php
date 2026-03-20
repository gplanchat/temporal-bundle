<?php

declare(strict_types=1);

namespace Kiboko\TemporalBundle\Messenger;

use Kiboko\Temporal\Messenger\ActivityTask;
use Kiboko\Temporal\Messenger\ActivityTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Pont Symfony Messenger → {@see ActivityTaskHandler}.
 */
#[AsMessageHandler]
final class GenericActivityTaskHandler
{
    public function __construct(
        private readonly ActivityTaskHandler $handler,
    ) {
    }

    public function __invoke(ActivityTask $task): mixed
    {
        return ($this->handler)($task);
    }
}
