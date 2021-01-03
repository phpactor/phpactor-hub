<?php

namespace PhpactorHub\Pipeline\Task;

use Maestro\Composer\Fact\ComposerFact;
use Maestro\Composer\Task\ComposerTask;
use Maestro\Core\Task\ConditionalTask;
use Maestro\Core\Task\Context;
use Maestro\Core\Task\DelegateTask;
use Maestro\Core\Task\JsonMergeTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;

class ComposerBumpVersionIfPresentTask implements DelegateTask
{
    public function __construct(private string $package, private string $version, private ?string $repository = null)
    {
    }

    public function task(): Task
    {
        $tasks = [];

        if ($this->repository !== null) {
            $tasks[] = new JsonMergeTask(
                'composer.json',
                [
                    'repositories' => [],
                ],
                filter: function (\stdClass $object) {
                    $object->repositories[] = [
                        'type' => 'vcs',
                        'url' => $this->repository
                    ];

                    return $object;
                }
            );
        }

        $tasks[] = $this->bumpIfPresent();

        return new SequentialTask($tasks);
    }

    private function bumpIfPresent(): SequentialTask
    {
        return new SequentialTask(array_map(fn (bool $dev) => new ConditionalTask(
            predicate: function (Context $context) use ($dev) {
                $packages = $context->fact(ComposerFact::class)->json()->packages();
                return 
                    $packages->has($this->package) &&
                    $packages->get($this->package)->dev() === $dev;
            },
            task: new ComposerTask(
                require: [
                    $this->package => $this->version,
                ],
                update: false,
                dev: $dev
            )
        ), [ true, false ]));
    }
}
