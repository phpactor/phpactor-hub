<?php

namespace PhpactorHub\Pipeline\Task;

use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Queue\TaskRunner;
use Maestro\Core\Task\ClosureTask;
use Maestro\Core\Task\Context;
use Maestro\Core\Task\DelegateTask;
use Maestro\Core\Task\JsonApiSurveyTask;
use Maestro\Core\Task\JsonApiTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use Maestro\Core\Task\TaskContext;
use Stringable;
use function Amp\call;

class RerunTask implements DelegateTask, Stringable
{
    public function __construct(
        private RepositoryNode $repository
    )
    {
    }

    public function task(): Task
    {
        return new SequentialTask([
            new JsonApiTask(
                url: sprintf(
                    'https://api.github.com/repos/%s/actions/runs?branch=%s',
                    'phpactor/' . $this->repository->name(),
                    $this->repository->vars()->get('branch'),
                ),
                headers: [
                    'Accept' => 'application/vnd.github.v3+json',
                    'Authorization' => sprintf('Basic %s', base64_encode(sprintf(
                        '%s:%s',
                        $this->repository->vars()->get('secret.githubUsername'),
                        $this->repository->vars()->get('secret.githubAuthToken'),
                    )))
                ],
            ),
            new ClosureTask(function (Context $context) {

                $data = $context->result();
                $lastRun = $data['workflow_runs'][0];

                if ($lastRun['conclusion'] === 'success') {
                    return $context;
                }

                $data = yield $context->runTask(
                    new JsonApiTask(
                        url: $lastRun['rerun_url'],
                        method: 'POST',
                        headers: [
                            'Accept' => 'application/vnd.github.v3+json',
                            'Authorization' => sprintf('Basic %s', base64_encode(sprintf(
                                '%s:%s',
                                $this->repository->vars()->get('secret.githubUsername'),
                                $this->repository->vars()->get('secret.githubAuthToken'),
                            )))
                        ],
                    )
                );

                return $context;
    });
}
);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return sprintf('Re-running CI for "%s"', $this->repository->name());
    }
}
