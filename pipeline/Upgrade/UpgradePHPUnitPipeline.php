<?php

namespace PhpactorHub\Pipeline\Upgrade;

use Maestro\Core\Task\ComposerTask;
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
                    'phpunit/phpunit' => '^8.0',
                ],
                dev: true,
                update: false
            ),
            new ProcessTask(
                cmd: [
                    'bash',
                    '-c',
                    <<<'EOT'
find tests -type f -name '*.php' -print0 | xargs -0 sed -i '' -e 's/public function setUp()/protected function setUp(): void/g'
EOT
                ],
                allowFailure: true
            ),
            new GitDiffTask(),
            new ProcessTask(
                cmd: 'git checkout -b phpunit-8-upgrade'
            ),
            new GitCommitTask(
                paths: [
                    'tests',
                    'composer.json',
                ],
                message: 'Maestro updates to PHPUnit 8.0'
            ),
            new ProcessTask(
                cmd: 'git push origin HEAD -f',
            ),
            new ProcessTask(
                cmd: 'gh pr create --fill -t "Maestro updates to PHPUnit 8.0"',
                allowFailure: true
            ),
        ]);
    }
}
