<?php

declare(strict_types=1);

namespace Kiboko\TemporalBundle\Messenger;

/**
 * Tâche workflow reçue via {@see \Kiboko\Temporal\Transport\TemporalWorkflowTransport} (payload = réponse protobuf sérialisée).
 */
final class WorkflowPollTask
{
    public function __construct(
        public readonly string $serializedPollWorkflowTaskQueueResponse,
    ) {
    }
}
