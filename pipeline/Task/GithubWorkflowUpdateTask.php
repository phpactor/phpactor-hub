<?php

namespace PhpactorHub\Pipeline\Task;

use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Task\DelegateTask;
use Maestro\Core\Task\GitCommitTask;
use Maestro\Core\Task\GitDiffTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use Maestro\Core\Task\TemplateTask;

class GithubWorkflowUpdateTask implements DelegateTask
{
    public function __construct(
        private RepositoryNode $repository,
    )
    {
    }

    public function task(): Task
    {
        return new SequentialTask([
            new TemplateTask(
                template: 'github/workflow.yml.twig',
                target: '.github/workflows/ci.yml',
                overwrite: true,
                vars: [
                    'name' => 'CI',
                    'repo' => $this->repository,
                    'jobs' => $this->repository->vars()->get('jobs'),
                    'branches' => $this->repository->vars()->get('branches'),
                    'checkoutOptions' => $this->repository->vars()->get('checkoutOptions'),
                ]
            ),
        ]);
    }
}
