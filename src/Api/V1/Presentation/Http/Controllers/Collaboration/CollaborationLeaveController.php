<?php

namespace Queues\Api\V1\Presentation\Http\Controllers\Collaboration;

use Queues\Api\V1\Presentation\Http\Controllers\ApiController;
use Queues\Api\V1\Presentation\Http\Controllers\Collaboration\Requests\CollaborationLeaveRequest;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class CollaborationLeaveController extends ApiController
{
    public function __invoke(CollaborationLeaveRequest $request)
    {
        return $this->respond(
            $this
                ->user()
                ->getWorkspace($request->workspaceId)
                ->leave($this->userId())
        );
    }
}
