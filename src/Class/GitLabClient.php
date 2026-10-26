<?php

declare(strict_types=1);

namespace Wexample\PhpRemoteGit\Class;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Wexample\PhpApi\Common\Client;
use Wexample\PhpRemoteGit\Interface\GitHostClientInterface;

/**
 * GitLab's REST API (`https://gitlab.com/api/v4`, or an instance's own).
 * Without a token it lists the public projects; a token (the client's API
 * key, read as a bearer token) adds those it may read.
 */
class GitLabClient extends Client implements GitHostClientInterface
{
    public const string USER_AGENT = 'wexample-php-remote-git';

    public const string PING_PATH = 'projects?per_page=1';

    /** The most a page lists. */
    private const int PAGE_SIZE = 100;

    /**
     * An owner is a group, its subgroups included; without one, every
     * project the client sees.
     */
    public function getRepositories(?string $owner): array
    {
        $path = null === $owner ? 'projects' : 'groups/'.rawurlencode($owner).'/projects';
        $query = null === $owner ? [] : ['include_subgroups' => 'true'];
        $repositories = [];

        foreach ($this->pages($path, $query) as $item) {
            if (! isset($item['forked_from_project'])) {
                $repositories[] = new GitRepository((string) $item['id'], $item['path'], $item['web_url']);
            }
        }

        return $repositories;
    }

    /**
     * Counted from the commits themselves, a page of them at a time: GitLab
     * keeps no statistics per day. Those of the default branch, by the day
     * they were committed.
     */
    public function getDailyCommits(GitRepository $repository, DateTimeImmutable $since): ?array
    {
        $days = [];
        $utc = new DateTimeZone('UTC');

        foreach ($this->pages('projects/'.$repository->id.'/repository/commits', [
            'since' => $since->format(DateTimeInterface::ATOM),
        ]) as $commit) {
            $day = new DateTimeImmutable($commit['committed_date'])->setTimezone($utc)->format('Y-m-d');
            $days[$day] = ($days[$day] ?? 0) + 1;
        }

        ksort($days);

        return $days;
    }

    /**
     * Every item of a listing, page after page, as long as GitLab names a
     * next one.
     *
     * @return iterable<array<string, mixed>>
     */
    private function pages(string $path, array $query): iterable
    {
        for ($page = 1; $page; ) {
            $response = $this->get($path, ['query' => $query + ['per_page' => self::PAGE_SIZE, 'page' => $page]]);

            yield from json_decode((string) $response->getBody(), true) ?: [];

            $page = (int) $response->getHeaderLine('X-Next-Page');
        }
    }
}
