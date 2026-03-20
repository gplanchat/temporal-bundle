<?php

declare(strict_types=1);

namespace Kiboko\TemporalBundle\Messenger;

use Temporal\Api\Command\V1\Command;
use Temporal\Api\History\V1\History;
use Temporal\Api\Workflowservice\V1\PollWorkflowTaskQueueResponse;

/**
 * Réponse minimale au poll workflow Temporal (PoC) : produit la liste de {@see Command} à renvoyer.
 */
interface WorkflowPollTaskResponderInterface
{
    public function supports(PollWorkflowTaskQueueResponse $poll): bool;

    /**
     * @return list<Command>
     */
    public function buildCommands(PollWorkflowTaskQueueResponse $poll, History $history): array;
}
