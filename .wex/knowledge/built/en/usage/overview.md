## What it is

The repositories a git host holds and the commits of each day, asked of GitHub and GitLab through their REST API. Each host is a php-api client (`GitHubClient`, `GitLabClient`) answering `GitHostClientInterface`; symfony-remote-git builds them from an application's configuration and keeps what they answer.

## Asking a host

```php
$github = new GitHubClient('https://api.github.com', $token);
$repositories = $github->getRepositories('wexample');      // list<GitRepository>, forks left out
$days = $github->getDailyCommits($repositories[0], new DateTimeImmutable('-365 days'));
// ['2026-10-04' => 1, '2026-10-06' => 2], or null while GitHub counts them
```

`getRepositories()` takes an owner — a GitHub user or organization, a GitLab group and its subgroups —, or null for every repository the client sees: the token's own on GitHub, every visible project on GitLab. A repository is known by its host's key (`id`: `owner/name` on GitHub, the project id on GitLab) and its short `name`, the one a mirror keeps on another host.

`getDailyCommits()` counts the commits of each day since a date, in UTC, a day without any left out.

- **GitHub** reads them from the weekly statistics it keeps per repository, its seven days each: one request, whatever the number of commits. On the first request it computes them and answers `202`: the method returns null, to be asked again later.
- **GitLab** keeps no such statistics: the commits of the default branch are read a page of a hundred at a time, and counted by the day they were committed.

## Tokens

Without a token GitHub answers sixty requests an hour, too few for an organization of a hundred repositories: a token raises it to five thousand. GitLab lists its public projects to anyone; a token, sent as a bearer token, adds those it may read.
