<?php

declare(strict_types=1);

namespace Kiboko\TemporalBundle\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Attache {@see WorkflowTaskResultStamp} à partir du résultat du handler (RespondWorkflowTaskCompletedRequest).
 */
final class WorkflowResultMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $envelope = $stack->next()->handle($envelope, $stack);

        $message = $envelope->getMessage();
        if (!$message instanceof WorkflowPollTask) {
            return $envelope;
        }

        $handled = $envelope->last(HandledStamp::class);
        if ($handled === null) {
            return $envelope;
        }

        $result = $handled->getResult();
        if (!$result instanceof \Temporal\Api\Workflowservice\V1\RespondWorkflowTaskCompletedRequest) {
            return $envelope;
        }

        return $envelope->with(new WorkflowTaskResultStamp($result));
    }
}
