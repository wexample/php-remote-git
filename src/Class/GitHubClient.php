<?php

declare(strict_types=1);

namespace Wexample\PhpRemoteGit\Class;

use DateTimeImmutable;
use DateTimeZone;
use Wexample\PhpApi\Common\Client;
use Wexample\PhpRemoteGit\Interface\GitHostClientInterface;

/**
 * GitHub's REST API (https://api.github.com). Without a token it answers
 * sixty requests an hour, too few for an organization's statistics: a token
 * (the client's API key) raises it to five thousand.
 */
class GitHubClient extends Client implements GitHostClientInterface
{
    public const string USER_AGENT = 'wexample-php-remote-git';

    public const string PING_PATH = 'rate_limit';

    /** The most a page lists. */
    private const int PAGE_SIZE = 100;

    /** What GitHub answers while it computes statistics it had not. */
    private const int STATUS_COMPUTING = 202;

    public function getRepositories(?string $owner): array
    {
        $repositories = [];
        $path = null === $owner ? 'user/repos' : 'users/'.rawurlencode($owner).'/repos';

        for ($page = 1; ; ++$page) {
            $items = $this->requestJson('GET', $path, ['query' => ['per_page' => self::PAGE_SIZE, 'page' => $page]]);

            foreach ($items as $item) {
                if (! $item['fork']) {
                    $repositories[] = new GitRepository($item['full_name'], $item['name'], $item['html_url']);
                }
            }

            if (count($items) < self::PAGE_SIZE) {
                return $repositories;
            }
        }
    }

    /**
     * Read from the year of weekly statistics GitHub keeps per repository,
     * each week its seven days from Sunday: one request, whatever the
     * number of commits.
     */
    public function getDailyCommits(GitRepository $repository, DateTimeImmutable $since): ?array
    {
        $response = $this->get('repos/'.$repository->id.'/stats/commit_activity');

        if (self::STATUS_COMPUTING === $response->getStatusCode()) {
            return null;
        }

        $weeks = json_decode((string) $response->getBody(), true) ?: [];
        $from = $since->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d');
        $days = [];

        foreach ($weeks as $week) {
            foreach ($week['days'] as $offset => $commits) {
                $day = gmdate('Y-m-d', $week['week'] + $offset * 86400);

                if ($commits > 0 && $day >= $from) {
                    $days[$day] = $commits;
                }
            }
        }

        return $days;
    }
}
