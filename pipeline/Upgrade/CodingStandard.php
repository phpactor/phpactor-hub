<?php

namespace PhpactorHub\Pipeline\Upgrade;

use Maestro\Composer\Task\ComposerTask;
use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Inventory\Vars;
use Maestro\Core\Task\GitDiffTask;
use Maestro\Core\Task\PhpProcessTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use Maestro\Core\Task\TemplateTask;
use PhpactorHub\Pipeline\BasePipeline;
use PhpactorHub\Pipeline\Task\CommitAndPrTask;
use PhpactorHub\Pipeline\Task\GithubWorkflowUpdateTask;
use PhpactorHub\Pipeline\Task\PhpstanBaselineTask;

class CodingStandard extends BasePipeline
{
    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new SequentialTask([
            new ComposerTask(
                update: true
            ),
            // CS fixer config
            new TemplateTask(
                template: 'php-cs-fixer/php_cs.dist',
                target: '.php_cs.dist',
                overwrite: true,
                vars: [
                    'ignore' => $repository->vars()->get('php_cs_fixer.ignore'),
                ]
            ),
            new GithubWorkflowUpdateTask($repository),
            new PhpProcessTask(
                './vendor/bin/php-cs-fixer fix --allow-risky=yes'
            ),
            new PhpstanBaselineTask(),
            //new GitDiffTask(),
            new CommitAndPrTask($repository, [
                'phpstan-baseline.neon',
                'phpstan.neon',
                '.php_cs.dist',
                '.github',
                'lib',
                'tests'
            ], 'Update coding standard'),
        ]);
    }

}
