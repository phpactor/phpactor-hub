<?php

namespace PhpactorHub\Pipeline\Maintain;

use Maestro\Composer\Task\ComposerTask;
use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Task\ConditionalTask;
use Maestro\Core\Task\GitCommitTask;
use Maestro\Core\Task\GitDiffTask;
use Maestro\Core\Task\JsonMergeTask;
use Maestro\Core\Task\PhpProcessTask;
use Maestro\Core\Task\ProcessTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use PhpactorHub\Pipeline\BasePipeline;
use PhpactorHub\Pipeline\Task\CommitAndPrTask;
use PhpactorHub\Pipeline\Task\GithubWorkflowUpdateTask;

class ConfigurationPipeline extends BasePipeline
{
    const MESSAGE = 'Maestro routine maintainence';

    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new SequentialTask([
            new GithubWorkflowUpdateTask($repository),
            new JsonMergeTask(
                path: 'composer.json',
                data: [
                    'prefer-stable' => true,
                    'minimum-stability' => 'dev',
                ]
            ),
            new ComposerTask(
                require: [
                    'php' => $repository->vars()->get('composer.require.php'),
                ],
            ),
            new ComposerTask(
                require: $repository->vars()->get('composer.requireDev.intersection'),
                intersection: true,
                dev: true,
                update: $repository->vars()->get('composer.lock')
            ),
            new ConditionalTask(
                predicate: fn () => $repository->vars()->get('composer.lock'),
                task: new ComposerTask(update: true)
            ),
            new CommitAndPrTask(
                repository: $repository,
                paths: (function (array $paths) use ($repository) {
                    if ($repository->vars()->get('composer.lock')) {
                        $paths[] = 'composer.lock';
                    }

                    return $paths;
                })([
                    '.github',
                    'composer.json',
                ]),
                message: $repository->vars()->getOrNull('commit.message')
            ),
        ]);
    }
}
