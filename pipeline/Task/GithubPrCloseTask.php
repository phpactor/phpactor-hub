<?php

namespace PhpactorHub\Pipeline\Task;

use Maestro\Core\Task\DelegateTask;
use Maestro\Core\Task\ProcessTask;
use Maestro\Core\Task\Task;

class GithubPrCloseTask implements DelegateTask
{
    public function __construct(private string $branch)
    {
    }
    public function task(): Task
    {
        return new ProcessTask(
            cmd: [
                'gh',
                'pr',
                'close',
                $this->branch,
                '-d',
            ],
            allowFailure: true
        );
    }
}
