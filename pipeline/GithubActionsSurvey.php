<?php

namespace PhpactorHub\Pipeline;

use Maestro\Core\Inventory\MainNode;
use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Pipeline\Pipeline;
use Maestro\Core\Task\JsonApiSurveyTask;
use Maestro\Core\Task\ParallelTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\SetReportingGroupTask;
use Maestro\Core\Task\Task;
use PhpactorHub\Pipeline\Task\GithubActionSurveyTask;

class GithubActionsSurvey implements Pipeline
{
    public function build(MainNode $mainNode): Task
    {
        return new ParallelTask(array_merge([
        ], array_map(function (RepositoryNode $repositoryNode) {
            return new SequentialTask(array_merge([
                new SetReportingGroupTask($repositoryNode->name())
            ], [
                new GithubActionSurveyTask(
                    repo: 'phpactor/' . $repositoryNode->name(),
                    defaultBranch: $repositoryNode->vars()->get('branch'),
                    githubUsername: $repositoryNode->vars()->get('secret.githubUsername'),
                    githubAuthToken: $repositoryNode->vars()->get('secret.githubAuthToken'),
                )
            ]));
        }, $mainNode->selectedRepositories())));
    }
}
