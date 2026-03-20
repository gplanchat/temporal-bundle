<?php

declare(strict_types=1);

namespace Kiboko\TemporalBundle\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Stamp contenant le résultat d'une activité pour le transport.
 * Le middleware l'ajoute avant ack() pour que le transport puisse
 * appeler RespondActivityTaskCompleted avec le bon résultat.
 */
final readonly class ActivityResultStamp implements StampInterface
{
    public function __construct(
        public mixed $result,
    ) {
    }
}
