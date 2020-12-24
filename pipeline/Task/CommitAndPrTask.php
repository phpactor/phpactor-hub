<?php

namespace PhpactorHub\Pipeline\Task;

use Maestro\Core\Task\DelegateTask;
use Maestro\Core\Task\GitCommitTask;
use Maestro\Core\Task\ProcessTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;

class CommitAndPrTask implements DelegateTask
{
    public function __construct(private array $paths, private string $message)
    {
    }

    public function task(): Task
    {
        return new SequentialTask([
            new GitCommitTask(
                paths: $this->paths,
                message: $this->message,
            ),
            new ProcessTask(
                cmd: 'git push origin HEAD -f',
            ),
            new ProcessTask(
                cmd: [
                    'gh',
                    'pr',
                    'create',
                    '--fill',
                    '-t',
                    $this->message
                ],
                allowFailure: true
            )
        ]);
    }
}
