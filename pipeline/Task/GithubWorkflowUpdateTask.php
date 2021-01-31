<?php

namespace PhpactorHub\Pipeline\Task;

use Maestro\Core\Inventory\RepositoryNode;
use Maestro\Core\Task\ConditionalTask;
use Maestro\Core\Task\DelegateTask;
use Maestro\Core\Task\GitCommitTask;
use Maestro\Core\Task\GitDiffTask;
use Maestro\Core\Task\SequentialTask;
use Maestro\Core\Task\Task;
use Maestro\Core\Task\TemplateTask;

class GithubWorkflowUpdateTask implements DelegateTask
{
    public function __construct(
        private RepositoryNode $repository,
    )
    {
    }

    public function task(): Task
    {
        return new SequentialTask([
            new ConditionalTask(
                predicate: fn() => $this->repository->vars()->get('phpbench.enable'),
                task: new TemplateTask(
                    template: 'phpbench/regression_test.sh.twig',
                    target: '.github/phpbench_regression_test.sh',
                    mode: 0755
                )
            ),
            new TemplateTask(
                template: 'github/workflow.yml.twig',
                target: '.github/workflows/ci.yml',
                overwrite: true,
                vars: [
                    'name' => 'CI',
                    'repo' => $this->repository,
                    'jobs' => (function (array $jobs) {
                        if ($this->repository->vars()->get('phpbench.enable')) {
                            $jobs[] = 'phpbench';
                        }
                        return $jobs;
                    })($this->repository->vars()->get('workflow.jobs')),
                    'branches' => $this->repository->vars()->get('workflow.branches'),
                    'checkoutOptions' => $this->repository->vars()->get('workflow.checkoutOptions'),
                    'phpMin' => (fn (array $versions) => reset($versions))($this->repository->vars()->get('workflow.matrix.php')),
                    'workflowMatrixPhp' => $this->repository->vars()->get('workflow.matrix.php'),
                ],
            ),
        ]);
    }
}
