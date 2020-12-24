<?php

namespace PhpactorHub\Pipeline\Survey;

use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Task\Task;
use PhpactorHub\Pipeline\BasePipeline;
use PhpactorHub\Pipeline\Task\ComposerPublishVersionsTask;

class PackageVersions extends BasePipeline
{
    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new ComposerPublishVersionsTask(
            $repository->name(),
            [
                'phpunit/phpunit',
                'friendsofphp/php-cs-fixer',
                'phpstan/phpstan',
            ]
        );
    }
}
