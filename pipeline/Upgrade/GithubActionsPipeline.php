<?php

namespace PhpactorHub\Pipeline\Upgrade;

use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Task\CatTask;
use Maestro\Core\Task\ClosureTask;
use Maestro\Core\Task\FileTask;
use Maestro\Core\Task\GitCommitTask;
use Maestro\Core\Task\GitDiffTask;
use Maestro\Core\Task\ProcessTask;
use Maestro\Core\Task\ReplaceLineTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use Maestro\Core\Task\TemplateTask;
use Maestro\Core\Task\YamlTask;
use PhpactorHub\Pipeline\BasePipeline;

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
            new ReplaceLineTask(
                group: $repository->name(),
                path: 'README.md',
                regexp: '{Build Status.*travis}',
                line: sprintf('![CI](https://github.com/phpactor/%s/workflows/CI/badge.svg)', $repository->name()),
            ),
            new TemplateTask(
                template: 'github/workflow.yml.twig',
                target: '.github/workflows/ci.yml',
                overwrite: true,
                vars: [
                    'name' => 'CI',
                    'repo' => $repository,
                    'jobs' => $repository->vars()->get('jobs'),
                    'branches' => $repository->vars()->get('branches'),
                ]
            ),
            new CatTask(
                path: '.github/workflows/ci.yml'
            ),
            //new GitDiffTask(),
            new GitCommitTask(
                message: 'Maestro is adding Github Actions',
                paths: [
                    'README.md',
                    '.github/workflows/ci.yml',
                    //'.travis.yml',
                ],
            ),
            new ProcessTask(
                args: ['git', 'checkout', '-b', 'github-actions'],
            ),
            //new ProcessTask(
            //    args: ['git', 'push', 'origin', 'HEAD', '--force'],
            //),
            //new ProcessTask(
            //    args: ['gh', 'pr', 'create', '--fill', '--head', 'github-actions'],
            //    allowFailure: true
            //)
        ]);

    }
}
