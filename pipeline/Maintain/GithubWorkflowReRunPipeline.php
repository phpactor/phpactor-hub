<?php

namespace PhpactorHub\Pipeline\Maintain;

use Maestro\Core\Inventory\MainNode;
use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Pipeline\Pipeline;
use Maestro\Core\Task\ParallelTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\SetReportingGroupTask;
use Maestro\Core\Task\Task;
use PhpactorHub\Pipeline\BasePipeline;
use PhpactorHub\Pipeline\Task\RerunTask;

class GithubWorkflowReRunPipeline implements Pipeline
{
    public function build(MainNode $mainNode): Task
    {
        return new ParallelTask(array_merge([
        ], array_map(function (RepositoryNode $repositoryNode) {
            return new SequentialTask(array_merge([
                new SetReportingGroupTask($repositoryNode->name())
            ], [
                new RerunTask(
                    $repositoryNode
                )
            ]));
        }, $mainNode->selectedRepositories())));
    }
}
