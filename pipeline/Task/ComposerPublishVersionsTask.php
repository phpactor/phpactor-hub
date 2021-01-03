<?php

namespace PhpactorHub\Pipeline\Task;

use Amp\Success;
use Maestro\Composer\ComposerPackage;
use Maestro\Composer\Fact\ComposerFact;
use Maestro\Composer\Task\ComposerTask;
use Maestro\Core\Report\TaskReportPublisher;
use Maestro\Core\Task\ClosureTask;
use Maestro\Core\Task\Context;
use Maestro\Core\Task\DelegateTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;

class ComposerPublishVersionsTask implements DelegateTask
{
    public function __construct(private string $name, private array $packages)
    {
    }

    public function task(): Task
    {
        return new SequentialTask([
            new ComposerTask(),
            new ClosureTask(function (Context $context) {
                $composer = $context->fact(ComposerFact::class);
                $publisher = $context->service(TaskReportPublisher::class);
                assert($composer instanceof ComposerFact);
                assert($publisher instanceof TaskReportPublisher);

                $packages = array_map(function (string $name) use ($composer) {
                    return $composer->json()->packages()->get($name);
                }, $this->packages);

                $publisher->publishTableRow(array_reduce($packages, function (array $row, ComposerPackage $package) {
                    $row[$package->name()] = $package->version();
                    return $row;
                }, []));
                return $context;
            })
        ]);
    }
}
