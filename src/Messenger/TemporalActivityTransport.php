<?php

declare(strict_types=1);

namespace Kiboko\TemporalBundle\Messenger;

use Kiboko\Temporal\Messenger\ActivityTask;
use Kiboko\Temporal\Transport\TransportInterface as TemporalTransportInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Transport Messenger pour les tâches d'activité Temporal.
 *
 * En production : get() poll via TemporalTransportInterface, ack() appelle RespondActivityTaskCompleted.
 * Pour le PoC : utilise injectTask() pour simuler la réception.
 */
final class TemporalActivityTransport implements TransportInterface
{
    /** @var array<int, array{envelope: Envelope, id: string}> */
    private array $pending = [];
    private int $nextId = 0;

    public function __construct(
        private readonly string $taskQueue = 'default',
        private readonly ?TemporalTransportInterface $transport = null,
        private readonly ?float $pollTimeoutSeconds = null,
    ) {
    }

    /**
     * Injecte une tâche pour les tests (simule la réception depuis Temporal).
     */
    public function injectTask(ActivityTask $task): void
    {
        $id = (string) $this->nextId++;
        $this->pending[$id] = [
            'envelope' => new Envelope($task),
            'id' => $id,
        ];
    }

    public function get(): iterable
    {
        if ($this->transport !== null) {
            $task = $this->transport->pollActivityTaskQueue($this->taskQueue, $this->pollTimeoutSeconds);
            if ($task !== null) {
                yield new Envelope(new ActivityTask(
                    activityType: $task['activityType'],
                    input: $task['input'],
                    taskToken: $task['taskToken'],
                    workflowId: $task['workflowId'],
                    runId: $task['runId'],
                ));
            }
            return;
        }

        if ($this->pending === []) {
            return;
        }

        $item = array_shift($this->pending);
        yield $item['envelope'];
    }

    public function ack(Envelope $envelope): void
    {
        $stamp = $envelope->last(ActivityResultStamp::class);
        $task = $envelope->getMessage();
        if ($task instanceof ActivityTask && $stamp !== null && $this->transport !== null) {
            $this->transport->respondActivityTaskCompleted($task->taskToken, $stamp->result);
        }
        // Sinon : mode stub, rien à notifier
    }

    public function reject(Envelope $envelope): void
    {
        $task = $envelope->getMessage();
        if ($task instanceof ActivityTask && $this->transport !== null) {
            $this->transport->respondActivityTaskFailed($task->taskToken, 'Rejected');
        }
    }

    public function send(Envelope $envelope): Envelope
    {
        // Les activités sont reçues de Temporal, pas envoyées via ce transport
        return $envelope;
    }
}
