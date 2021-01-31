<?php

namespace PhpactorHub\Pipeline\Upgrade;

use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Task\CatTask;
use Maestro\Core\Task\ClosureTask;
use Maestro\Core\Task\FileTask;
use Maestro\Core\Task\GitCommitTask;
use Maestro\Core\Task\GitDiffTask;
use Maestro\Core\Task\ProcessTask;
use Maestro\Core\Task\LineInFileTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use Maestro\Core\Task\TemplateTask;
use Maestro\Core\Task\YamlTask;
use PhpactorHub\Pipeline\BasePipeline;
use PhpactorHub\Pipeline\Task\CommitAndPrTask;
use PhpactorHub\Pipeline\Task\GithubWorkflowUpdateTask;

class GithubActionsPipeline extends BasePipeline
{
    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new SequentialTask([
            new TemplateTask(
                template: 'README.md.twig',
                target: "README.md",
                vars: [
                    'repo' => $repository,
                ]
            ),
            //new CatTask(
            //    path: '.travis.yml'
            //),
            new FileTask(
                path: '.travis.yml',
                exists: false
            ),
            new LineInFileTask(
                group: $repository->name(),
                path: 'README.md',
                regexp: '{Build Status.*travis}',
                line: sprintf('![CI](https://github.com/phpactor/%s/workflows/CI/badge.svg)', $repository->name()),
            ),
            new GithubWorkflowUpdateTask($repository),
            new CatTask(
                path: '.github/workflows/ci.yml'
            ),
            //new GitDiffTask(),
            new ProcessTask(
                cmd: ['git', 'checkout', '-b', 'github-actions'],
            ),
            new CommitAndPrTask(
                repository: $repository,
                paths: ['.'],
                message: 'Update to Github Actions'
            )
        ]);

    }
}
