<?php

namespace PhpactorHub\Pipeline;

use Maestro\Composer\Task\ComposerTask;
use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Task\ConditionalTask;
use Maestro\Core\Task\FileTask;
use Maestro\Core\Task\GitCommitTask;
use Maestro\Core\Task\GitDiffTask;
use Maestro\Core\Task\JsonMergeTask;
use Maestro\Core\Task\PhpProcessTask;
use Maestro\Core\Task\ProcessTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use Maestro\Markdown\Task\MarkdownSectionTask;
use PhpactorHub\Pipeline\BasePipeline;
use PhpactorHub\Pipeline\Task\CommitAndPrTask;
use PhpactorHub\Pipeline\Task\GithubWorkflowUpdateTask;

/**
 * This is the main idempotent pipeline.
 */
class MainPipeline extends BasePipeline
{
    const MESSAGE = 'Maestro routine maintainence';

    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new SequentialTask([
            // Update the github workflow.
            new GithubWorkflowUpdateTask($repository),

            // Allow Phpactor to specify unreleased development versions but
            // prefer stable where possible.
            new JsonMergeTask(
                path: 'composer.json',
                data: [
                    'prefer-stable' => true,
                    'minimum-stability' => 'dev',
                ]
            ),

            // All packages must meet the minimum PHP requirement
            new ComposerTask(
                require: [
                    'php' => $repository->vars()->get('composer.require.php'),
                ],
            ),

            new MarkdownSectionTask(
                path: 'README.md',
                header: '## Contributing',
                template: 'readme/contributing.md.twig'
            ),

            new MarkdownSectionTask(
                path: 'README.md',
                header: '## Support',
                template: 'readme/support.md.twig'
            ),

            // Update any existing dependencies
            //
            // - Update Phpactor dependencies according to their version
            //   defined in the inventory.  
            // - Update any other dependencies which
            //   we want to support.
            //
            // If the dependencies are already satisfied (i.e. the existing
            // dependency covers the tagged one) then do nothing - unless told
            // not to (e.g. this is the main Phpactor application and we want
            // the latest deps).
            new ComposerTask(
                require: array_merge(
                    $repository->vars()->get('composer.require.intersection'),
                    array_combine(
                        array_map(
                            fn (RepositoryNode $n) => 'phpactor/' . $n->name(),
                            $repository->main()->repositories()->toArray()
                        ),
                        array_map(
                            fn (RepositoryNode $n) => '^' . $n->vars()->get('version'),
                            $repository->main()->repositories()->toArray()
                        )
                    )
                ),
                requireDev: $repository->vars()->get('composer.requireDev.intersection'),
                intersection: true,
                satisfactory: $repository->vars()->get('composer.satisfactory'),
            ),

            // If the package is configured with a composer.lock, update it
            new ConditionalTask(
                predicate: fn () => $repository->vars()->get('composer.lock'),
                task: new ComposerTask(update: true),
                message: 'Package does not have a composer.lock, not updating'
            ),

            // Commit changes and make a PR via. a custom delegate task.
            new CommitAndPrTask(
                repository: $repository,
                paths: (function (array $paths) use ($repository) {
                    if ($repository->vars()->get('composer.lock')) {
                        $paths[] = 'composer.lock';
                    }

                    return $paths;
                })([
                    'README.md',
                    '.github',
                    'composer.json',
                ]),
                message: $repository->vars()->getOrNull('commit.message')
            ),
        ]);
    }
}
