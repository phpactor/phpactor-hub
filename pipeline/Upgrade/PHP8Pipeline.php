<?php

namespace PhpactorHub\Pipeline\Upgrade;

use Maestro\Composer\Task\ComposerTask;
use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Task\Context;
use Maestro\Core\Task\GitDiffTask;
use Maestro\Core\Task\ParallelTask;
use Maestro\Core\Task\PhpProcessTask;
use Maestro\Core\Task\ProcessTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use PhpactorHub\Pipeline\BasePipeline;
use PhpactorHub\Pipeline\Task\CommitAndPrTask;
use PhpactorHub\Pipeline\Task\ComposerBumpVersionIfPresentTask;
use PhpactorHub\Pipeline\Task\GithubActionsTask;
use PhpactorHub\Pipeline\Task\GithubWorkflowUpdateTask;

class PHP8Pipeline extends BasePipeline
{
    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new SequentialTask([
            new ComposerTask(
                remove:
                require: [
                    'php' => '^7.3 || ^8.0'
                ],
            ),
            new ComposerTask(
                require: [
                    'phpbench/phpbench': '^1.0.0-alpha3',
                    'webmozart/glob' => 'dev-feature/support-php8',
                ],
            ),
            new ComposerBumpVersionIfPresentTask(
                'phpbench/phpbench',
                '^1.0.0-alpha3'
            ),
            new ComposerBumpVersionIfPresentTask(
                'webmozart/glob',
                'dev-feature/support-php8',
                'git@github.com:Th3Mouk/glob'
            ),
            new ComposerBumpVersionIfPresentTask(
                'phly/phly-event-dispatcher',
                'dev-php8',
                'git@github.com:dantleech/phly-event-dispatcher'
            ),
            new ParallelTask(array_map(function (RepositoryNode $phpactorRepo) use ($repository) {
                return new ComposerBumpVersionIfPresentTask(
                    'phpactor/' . $phpactorRepo->name(),
                    'dev-' . $repository->vars()->get('branch')
                );
            },  iterator_to_array($repository->main()->repositories()))),
            new GithubWorkflowUpdateTask($repository),
            new GitDiffTask(),
            //new ComposerTask(
            //    update: true
            //),
            //new PhpProcessTask(
            //    cmd: './vendor/bin/phpunit'
            //),
            new ProcessTask(
                cmd: 'git checkout -b ' . $repository->vars()->get('branch')
            ),
            new CommitAndPrTask(
                repository: $repository,
                message: 'Maestro adds support for PHP 8.0',
                paths: [
                    'composer.json',
                    '.github',
                ]
            )
        ]);
    }
}
