<?php

namespace PhpactorHub\Pipeline\Survey;

use Maestro\Composer\Fact\ComposerFact;
use Maestro\Composer\Task\ComposerTask;
use Maestro\Core\Inventory\MainNode;
use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Pipeline\Pipeline;
use Maestro\Core\Report\TaskReportPublisher;
use Maestro\Core\Task\ClosureTask;
use Maestro\Core\Task\Context;
use Maestro\Core\Task\GitSurveyTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use PhpactorHub\Pipeline\BasePipeline;

class GitSurvey extends BasePipeline
{
    protected function buildRepository(RepositoryNode $repository): Task
    {
        return new SequentialTask([
            new ComposerTask(),
            new ClosureTask(function (Context $context) use ($repository) {
                $composer = $context->fact(ComposerFact::class);
                assert($composer instanceof ComposerFact);
                $context->service(TaskReportPublisher::class)->publishTableRow([
                    'alias' => implode(',', $context->fact(ComposerFact::class)->json()->branchAliases()),
                    'next' => $repository->vars()->get('version')
                ]);

                return $context;
            }),
            new GitSurveyTask()
        ]);
    }
}
