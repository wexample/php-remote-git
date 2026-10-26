<?php

declare(strict_types=1);

namespace Wexample\PhpRemoteGit\Interface;

use DateTimeImmutable;
use Wexample\PhpApi\Exceptions\ApiException;
use Wexample\PhpRemoteGit\Class\GitRepository;

/**
 * A service hosting git repositories — GitHub, GitLab —, asked what it holds
 * and how much was committed there.
 */
interface GitHostClientInterface
{
    /**
     * The repositories of an owner — a user, an organization, a group —, or
     * every one the client may see without an owner. Forks are left out: they
     * are someone else's work.
     *
     * @return list<GitRepository>
     *
     * @throws ApiException
     */
    public function getRepositories(?string $owner): array;

    /**
     * The commits of each day since then, by `Y-m-d` (UTC); a day without
     * any left out. Null when the host has not counted them yet — GitHub
     * computes its statistics on the first request —: asked again later.
     *
     * @return array<string, int>|null
     *
     * @throws ApiException
     */
    public function getDailyCommits(GitRepository $repository, DateTimeImmutable $since): ?array;
}
