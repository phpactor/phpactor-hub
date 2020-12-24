<?php

namespace PhpactorHub\Pipeline\Task;

use Maestro\Composer\Fact\ComposerJsonFact;
use Maestro\Composer\Task\ComposerTask;
use Maestro\Core\Task\ConditionalTask;
use Maestro\Core\Task\Context;
use Maestro\Core\Task\DelegateTask;
use Maestro\Core\Task\Task;

class ComposerBumpVersionIfPresentTask implements DelegateTask
{
    public function __construct(private string $package, private string $version, private bool $dev)
    {
    }

    public function task(): Task
    {
        return new ConditionalTask(
            predicate: function (Context $context) {
                $packages = $context->fact(ComposerJsonFact::class)->packages();
                return 
                    $packages->has($this->package) &&
                    $packages->get($this->package)->dev() === $this->dev;
            },
            task: new ComposerTask(
                require: [
                    $this->package => $this->version,
                ],
                update: false,
                dev: $this->dev
            )
        );
    }
}
