<?php

declare(strict_types=1);

namespace Wexample\PhpRemoteGit\Tests\Unit\Class;

use DateTimeImmutable;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Wexample\PhpRemoteGit\Class\GitLabClient;
use Wexample\PhpRemoteGit\Class\GitRepository;

class GitLabClientTest extends TestCase
{
    private MockHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new MockHandler();
    }

    public function testEveryPageIsRead(): void
    {
        $this->handler->append(
            new Response(200, ['X-Next-Page' => '2'], json_encode([
                ['id' => 1, 'path' => 'one', 'web_url' => 'https://gitlab.test/acme/one'],
            ])),
            new Response(200, ['X-Next-Page' => ''], json_encode([
                ['id' => 2, 'path' => 'fork', 'web_url' => 'https://gitlab.test/acme/fork', 'forked_from_project' => ['id' => 9]],
                ['id' => 3, 'path' => 'three', 'web_url' => 'https://gitlab.test/acme/three'],
            ])),
        );

        $this->assertEquals([
            new GitRepository('1', 'one', 'https://gitlab.test/acme/one'),
            new GitRepository('3', 'three', 'https://gitlab.test/acme/three'),
        ], $this->client()->getRepositories('acme'));
    }

    public function testTheCommitsAreCountedByTheDayTheyWereCommitted(): void
    {
        $this->handler->append(new Response(200, [], json_encode([
            ['committed_date' => '2026-10-06T23:30:00.000+02:00'],
            ['committed_date' => '2026-10-06T08:00:00.000+00:00'],
            ['committed_date' => '2026-10-04T12:00:00.000+00:00'],
        ])));

        $this->assertSame(
            ['2026-10-04' => 1, '2026-10-06' => 2],
            $this->client()->getDailyCommits(new GitRepository('1', 'one', ''), new DateTimeImmutable('2026-10-01'))
        );
    }

    private function client(): GitLabClient
    {
        return new GitLabClient('https://gitlab.test/api/v4', null, new GuzzleClient([
            'base_uri' => 'https://gitlab.test/api/v4/',
            'handler' => HandlerStack::create($this->handler),
        ]));
    }
}
