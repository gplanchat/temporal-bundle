<?php

declare(strict_types=1);

namespace Kiboko\TemporalBundle\Messenger;

use Kiboko\Temporal\Messenger\ActivityTask;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Middleware qui capture le résultat du handler et l'ajoute comme ActivityResultStamp.
 * Le TemporalActivityTransport lit ce stamp dans ack() pour RespondActivityTaskCompleted.
 */
final class ActivityResultMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $envelope = $stack->next()->handle($envelope, $stack);

        $message = $envelope->getMessage();
        if (!$message instanceof ActivityTask) {
            return $envelope;
        }

        $handled = $envelope->last(HandledStamp::class);
        if ($handled === null) {
            return $envelope;
        }

        return $envelope->with(new ActivityResultStamp($handled->getResult()));
    }
}
