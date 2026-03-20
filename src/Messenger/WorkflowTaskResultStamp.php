<?php

declare(strict_types=1);

namespace Kiboko\TemporalBundle\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;
use Temporal\Api\Workflowservice\V1\RespondWorkflowTaskCompletedRequest;

final readonly class WorkflowTaskResultStamp implements StampInterface
{
    public function __construct(
        public RespondWorkflowTaskCompletedRequest $request,
    ) {
    }
}
