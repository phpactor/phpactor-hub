<?php

namespace PhpactorHub\Pipeline\Maintain;

use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Task\GitDiffTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use PhpactorHub\Pipeline\BasePipeline;
use PhpactorHub\Pipeline\Task\GithubWorkflowUpdateTask;

class GithubWorkflowPipeline extends BasePipeline
{
    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new SequentialTask([
            new GithubWorkflowUpdateTask($repository),
            new GitDiffTask()
        ]);
    }
}
