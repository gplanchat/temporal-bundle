<?php

declare(strict_types=1);

namespace Kiboko\TemporalBundle\Messenger;

use Kiboko\Temporal\Transport\WorkflowPollTransportInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Transport Messenger : poll {@see WorkflowPollTransportInterface} → {@see WorkflowPollTask}.
 */
final class TemporalWorkflowTransport implements TransportInterface
{
    public function __construct(
        private readonly string $taskQueue,
        private readonly WorkflowPollTransportInterface $workflowTransport,
        private readonly ?float $pollTimeoutSeconds = null,
    ) {
    }

    public function get(): iterable
    {
        $poll = $this->workflowTransport->pollWorkflowTaskQueue(
            $this->taskQueue,
            $this->pollTimeoutSeconds ?? 25.0,
        );
        if ($poll === null) {
            return;
        }

        yield new Envelope(new WorkflowPollTask($poll->serializeToString()));
    }

    public function ack(Envelope $envelope): void
    {
        $stamp = $envelope->last(WorkflowTaskResultStamp::class);
        if ($stamp !== null) {
            $this->workflowTransport->respondWorkflowTaskCompleted($stamp->request);
        }
    }

    public function reject(Envelope $envelope): void
    {
        // PoC : pas de RespondWorkflowTaskFailed ici
    }

    public function send(Envelope $envelope): Envelope
    {
        return $envelope;
    }
}
