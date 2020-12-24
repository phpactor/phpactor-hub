<?php

namespace PhpactorHub\Pipeline\Upgrade;

use Maestro\Composer\Fact\ComposerJsonFact;
use Maestro\Composer\Task\ComposerTask;
use Maestro\Core\Task\ConditionalTask;
use Maestro\Core\Task\Context;
use Maestro\Core\Task\FileTask;
use Maestro\Core\Task\GitCommitTask;
use Maestro\Core\Task\GitDiffTask;
use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Task\PhpProcessTask;
use Maestro\Core\Task\ProcessTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use Maestro\Core\Task\TemplateTask;
use PhpactorHub\Pipeline\BasePipeline;
use PhpactorHub\Pipeline\Task\CommitAndPrTask;
use PhpactorHub\Pipeline\Task\ComposerBumpVersionIfPresentTask;
use Rector\Set\ValueObject\SetList;

class PHPUnitPipeline extends BasePipeline
{
    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new SequentialTask([
            new ComposerTask(),
            new ConditionalTask(
                fn (Context $context) => $context->fact(
                    ComposerJsonFact::class
                )->packages()->get('phpunit/phpunit')->version()->lessThan('^9.0'),
                $this->upgradePhpunit($repository)
            )
        ]);
    }

    private function upgradePhpunit(RepositoryNode $repository): Task
    {
        return new SequentialTask([
            new ComposerBumpVersionIfPresentTask('symfony/filesystem', '^4.2 || ^5.0', true),
            new ComposerBumpVersionIfPresentTask('symfony/filesystem', '^4.2 || ^5.0', false),
            new ComposerBumpVersionIfPresentTask('symfony/console', '^4.3 || ^5.1', true),
            new ComposerBumpVersionIfPresentTask('symfony/console', '^4.3 || ^5.1', false),
            new ProcessTask(
                cmd: 'git checkout -b ' . $repository->vars()->get('branch')
            ),
            new ComposerTask(
                require: [
                    'rector/rector' => '0.8.52',
                ],
                dev: true,
                update: true
            ),
            new ConditionalTask(
                fn (Context $context) => $context->fact(
                    ComposerJsonFact::class
                )->packages()->get('phpunit/phpunit')->version()->lessThan('^8.0'),
                $this->migrateToPhpUnit8()
            ),
            $this->migrateToPhpUnit9(),
            new PhpProcessTask(
                './vendor/bin/phpunit --migrate-configuration'
            ),
            new FileTask(
                exists: false,
                path: 'phpunit.xml.dist.bak'
            ),
            new FileTask(
                exists: false,
                path: 'rector.php'
            ),
            new ProcessTask([
                'bash',
                '-c',
                'echo ".phpunit.result.cache" >> .gitignore'
            ]),
            //new PhpProcessTask(
            //    './vendor/bin/phpunit'
            //),
            new ComposerTask(
                remove: [
                    'rector/rector',
                ],
                dev: true
            ),
            new GitDiffTask(),
            new CommitAndPrTask(
                message: 'Maestro updates to PHPUnit 9.0',
                paths: [
                    '.gitignore',
                    'composer.json',
                    'phpunit.xml.dist',
                    'tests'
                ]
            ),
        ]);
    }

    private function migrateToPhpUnit8(): Task
    {
        return new SequentialTask([
            new TemplateTask(
                template: 'rector/rector.php.twig',
                target: 'rector.php',
                vars: [
                    'setList' => [
                        'PHPUNIT_70',
                        'PHPUNIT_75',
                        'PHPUNIT_80',
                    ],
                    'rules' => [
                    ]
                ]
            ),
            new ProcessTask(
                cmd: [
                    'bash',
                    '-c',
                    'php7.3 ./vendor/bin/rector process -- tests'
                ],
                allowFailure: true
            ),
            new ComposerTask(
                require: [
                    'phpunit/phpunit' => '^8.0',
                ],
                dev: true,
                update: true
            ),
        ]);
    }

    private function migrateToPhpUnit9(): Task
    {
        return new SequentialTask([
            new TemplateTask(
                template: 'rector/rector.php.twig',
                target: 'rector.php',
                vars: [
                    'setList' => [
                        'PHPUNIT_90',
                        'PHPUNIT_91',
                    ],
                    'rules' => [
                    ]
                ]
            ),
            new ComposerTask(
                require: [
                    'phpunit/phpunit' => '^9.0',
                    'phpspec/prophecy-phpunit' => '^2.0',
                ],
                dev: true,
                update: true
            ),
            new ProcessTask(
                cmd: [
                    'bash',
                    '-c',
                    'php7.3 ./vendor/bin/rector process -- tests'
                ],
                allowFailure: true
            ),
        ]);
    }
}
