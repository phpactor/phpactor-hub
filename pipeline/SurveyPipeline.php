<?php

namespace PhpactorHub\Pipeline;
 Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Task\GitSurveyTask;
use Maestro\Core\Task\JsonApiSurveyTask;
use Maestro\Core\Task\ParallelTask;
use Maestro\Core\Task\Task;
use PhpactorHub\Pipeline\Task\RerunTask;

class SurveyPipeline extends BasePipeline
{
    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new ParallelTask([
            new GitSurveyTask(),
            new RerunTask(
                defaultBranch: $repository->vars()->get('branch'),
            ),
        ]);
    }
}
