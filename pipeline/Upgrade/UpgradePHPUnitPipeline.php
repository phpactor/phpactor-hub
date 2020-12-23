<?php

namespace PhpactorHub\Pipeline\Upgrade;

use Maestro\Composer\Task\ComposerTask;
use Maestro\Core\Task\GitCommitTask;
use Maestro\Core\Task\GitDiffTask;
use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Task\PhpProcessTask;
use Maestro\Core\Task\ProcessTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use PhpactorHub\Pipeline\BasePipeline;

class UpgradePHPUnitPipeline extends BasePipeline
{
    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new SequentialTask([
            new ComposerTask(
                require: [
                    'phpunit/phpunit' => '^9.0',
                ],
                dev: true,
                update: false
            ),
            new GitDiffTask(),
            new ProcessTask(
                cmd: 'git checkout -b ' . $repository->vars()->get('branch')
            ),
            new GitCommitTask(
                paths: [
                    'tests',
                    'composer.json',
                ],
                message: 'Maestro updates PHPUnit'
            ),
            new ProcessTask(
                cmd: 'git push origin HEAD -f',
            ),
            new ProcessTask(
                cmd: 'gh pr create --fill -t "Maestro updates PHPUnit"',
                allowFailure: true
            ),
        ]);
    }
}
