<?php

namespace PhpactorHub\Pipeline\Maintain;

use Maestro\Core\Inventory\MainNode;
use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Pipeline\Pipeline;
use Maestro\Core\Task\GitRepositoryTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\SetDirectoryTask;
use Maestro\Core\Task\Task;
use Maestro\Git\GitRepository;
use PhpactorHub\Pipeline\BasePipeline;
use PhpactorHub\Pipeline\Task\GithubPrCloseTask;
use PhpactorHub\Pipeline\Task\GithubPrMergeTask;

class MergePrPipeline extends BasePipeline
{
    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new SequentialTask([
            new GithubPrMergeTask($repository->vars()->get('branch'))
        ]);
    }
}
