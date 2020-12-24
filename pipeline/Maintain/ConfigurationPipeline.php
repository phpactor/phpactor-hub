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
use PhpactorHub\Pipeline\Task\GithubWorkflowUpdateTask;

class ConfigurationPipeline extends BasePipeline
{
    const MESSAGE = 'Maestro updates minimum PHP to 7.3';

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
                    'php' => $repository->vars()->get('phpMin'),
                ],
            ),
            new ComposerTask(
                require: [
                    'ergebnis/composer-normalize' => '^2.0',
                    'friendsofphp/php-cs-fixer' => '^2.17',
                ],
                dev: true,
                update: $repository->name() === 'phpactor',
            ),
            //new PhpProcessTask(
            //    cmd: '/usr/local/bin/composer normalize'
            //),
            new ConditionalTask(
                predicate: fn () => $repository->name() === 'phpactor', // the extension manager conflicts with v2 plugins
                task: new ComposerTask(
                    remove: [
                        'ergebnis/composer-normalize'
                    ],
                    dev: true,
                    update: true
                )
            ),
            new GitDiffTask(),
            new ProcessTask(
                cmd: 'git checkout -b ' . $repository->vars()->get('branch')
            ),
            new GitCommitTask(
                paths: (function (array $paths) use ($repository) {
                    if ($repository->vars()->get('composerLock')) {
                        $paths[] = 'composer.lock';
                    }

                    return $paths;
                })([
                    '.github',
                    'composer.json',
                ]),
                message: self::MESSAGE
            ),
            new ProcessTask(
                cmd: 'git push origin HEAD -f',
            ),
            new ProcessTask(
                cmd: sprintf('gh pr create --fill -t "%s"', self::MESSAGE),
                allowFailure: true
            ),
        ]);
    }
}
