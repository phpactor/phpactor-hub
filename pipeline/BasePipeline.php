<?php

namespace PhpactorHub\Pipeline;

use Maestro\Core\Fact\PhpFact;
use Maestro\Core\Inventory\MainNode;
use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Pipeline\Pipeline;
use Maestro\Core\Task\ComposerTask;
use Maestro\Core\Task\FactTask;
use Maestro\Core\Task\FileTask;
use Maestro\Core\Task\GitRepositoryTask;
use Maestro\Core\Task\NullTask;
use Maestro\Core\Task\ParallelTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\SetDirectoryTask;
use Maestro\Core\Task\SetReportingGroupTask;
use Maestro\Core\Task\Task;
use Maestro\Rector\Task\RectorInstallTask;

/**
 * This pipeline creates a build directory and checks out
 * all the _selected_ repositories then delegates the
 * building of repository pipeline to an extending class.
 *
 * Use this for task as a basis for pipelines which require the repository to
 * be checked out.
 */
class BasePipeline implements Pipeline
{
    public function build(MainNode $mainNode): Task
    {
        return new SequentialTask([
            // Setting this will cause all PHP tasks to this bin
            new PhpFact($mainNode->vars()->get('php.bin')),

            // Ensrue the directory doesn't exist (start from scratch)
            new FileTask(
                type: 'directory',
                path: 'build',
                exists: false
            ),

            // ... and create it again
            new FileTask(
                type: 'directory',
                path: 'build',
                exists: true
            ),

            // Start checking out and running all the repository pipelines in
            // parallel.
            new ParallelTask(array_map(function (RepositoryNode $repositoryNode) {
                return new SequentialTask([

                    // Set the reporting group (i.e. the group in which reports
                    // will be published). It makes sense here for all
                    // subsequent reports to be grouped under the repository
                    // name.
                    new SetReportingGroupTask($repositoryNode->name()),

                    // Change the working directory
                    new SetDirectoryTask('build'),

                    // Checkout the git repository
                    new GitRepositoryTask(
                        url: $repositoryNode->url(),
                        path: $repositoryNode->name()
                    ),

                    // Change the working directory to the repository
                    new SetDirectoryTask('build/'.$repositoryNode->name()),

                    // Delegate
                    $this->buildRepository($repositoryNode)
                ]);
            }, $mainNode->selectedRepositories()))
        ]);
    }

    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new NullTask();
    }
}
