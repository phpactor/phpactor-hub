<?php

namespace PhpactorHub\Pipeline\Task;

use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Process\ProcessResult;
use Maestro\Core\Task\ConditionalTask;
use Maestro\Core\Task\Context;
use Maestro\Core\Task\DelegateTask;
use Maestro\Core\Task\PhpProcessTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use Maestro\Core\Task\YamlTask;

class PhpstanBaselineTask implements DelegateTask
{
    public function task(): Task
    {
        return new SequentialTask([
            $this->phpstanTask(true),
            new ConditionalTask(
                predicate: function (Context $context) {
                    return $context->var('phpstan-exit') !== 0;
                },
                task: $this->generateBaselineTask(),
            )
        ]);
    }

    private function phpstanTask(bool $allowFailure = false)
    {
        return new PhpProcessTask(
            cmd: [
                './vendor/bin/phpstan',
                '--no-interaction',
                'analyse',
            ],
            allowFailure: $allowFailure,
            after: function (ProcessResult $result, Context $context) {
                return $context->withVar('phpstan-exit', $result->exitCode());
            },
        );
    }

    private function generateBaselineTask(): Task
    {
        return new SequentialTask([
            new PhpProcessTask(
                cmd: [
                    './vendor/bin/phpstan',
                    'analyse',
                    '--generate-baseline'
                ]
            ),
            new YamlTask(
                inline: 3,
                data: [
                    'includes' => [
                    ],
                ],
                path: 'phpstan.neon',
                filter: function (array $data) {
                    foreach ($data['includes'] as $include) {
                        if ($include === 'phpstan-baseline.neon') {
                            return;
                        }
                    }

                    $data['includes'][] = 'phpstan-baseline.neon';
                    return $data;
                },
            ),
            $this->phpstanTask(),
        ]);
    }
}
