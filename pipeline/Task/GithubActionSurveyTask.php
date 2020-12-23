<?php

namespace PhpactorHub\Pipeline\Task;

use Maestro\Core\Task\DelegateTask;
use Maestro\Core\Task\JsonApiSurveyTask;
use Maestro\Core\Task\Task;
use Stringable;

class GithubActionSurveyTask implements DelegateTask, Stringable
{
    public function __construct(
        private string $repo,
        private string $defaultBranch,
        private string $githubUsername,
        private string $githubAuthToken
    )
    {
    }

    public function task(): Task
    {
        return new JsonApiSurveyTask(
            url: sprintf(
                'https://api.github.com/repos/%s/actions/runs?branch=%s',
                $this->repo,
                $this->defaultBranch
            ),
            headers: [
                'Accept' => 'application/vnd.github.v3+json',
                'Authorization' => sprintf('Basic %s', base64_encode(sprintf(
                    '%s:%s',
                    $this->githubUsername,
                    $this->githubAuthToken
                )))
            ],
            extract: function (array $data) {
                $run = $data['workflow_runs'][0] ?? [];

                if ([] === $run) {
                    return [
                        'gha.#' => 'n/a',
                    ];
                }

                return [
                    'gha.#' => $run['run_number'],
                    'gha.sta' => $run['status'],
                    'gha.con' => (function (string $conclusion) {
                        return sprintf(
                            '<%s>%s</>',
                            $conclusion === 'n/a' ? 'comment' : ($conclusion === 'failure' ? 'error' : 'info'),
                            $conclusion
                        );
                    })($run['conclusion'] ?? 'n/a'),
                    'gha.url' => $run['html_url']
                ];
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return sprintf('Getting github action status for "%s"', $this->repo);
    }
}
