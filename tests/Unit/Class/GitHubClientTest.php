<?php

declare(strict_types=1);

namespace Wexample\PhpRemoteGit\Tests\Unit\Class;

use DateTimeImmutable;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Wexample\PhpRemoteGit\Class\GitHubClient;
use Wexample\PhpRemoteGit\Class\GitRepository;

class GitHubClientTest extends TestCase
{
    private MockHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new MockHandler();
    }

    public function testAForkIsNoRepositoryOfTheOwner(): void
    {
        $this->handler->append(new Response(200, [], json_encode([
            ['full_name' => 'acme/one', 'name' => 'one', 'html_url' => 'https://github.com/acme/one', 'fork' => false],
            ['full_name' => 'acme/fork', 'name' => 'fork', 'html_url' => 'https://github.com/acme/fork', 'fork' => true],
        ])));

        $this->assertEquals(
            [new GitRepository('acme/one', 'one', 'https://github.com/acme/one')],
            $this->client()->getRepositories('acme')
        );
    }

    public function testTheWeeksAreReadDayByDay(): void
    {
        // Sunday 2026-10-04 00:00 UTC.
        $sunday = 1791072000;
        $this->handler->append(new Response(200, [], json_encode([
            ['week' => $sunday - 7 * 86400, 'days' => [0, 4, 0, 0, 0, 0, 0], 'total' => 4],
            ['week' => $sunday, 'days' => [1, 0, 2, 0, 0, 0, 0], 'total' => 3],
        ])));

        $this->assertSame(
            ['2026-10-04' => 1, '2026-10-06' => 2],
            $this->client()->getDailyCommits(new GitRepository('acme/one', 'one', ''), new DateTimeImmutable('2026-10-01'))
        );
    }

    public function testStatisticsStillComputedAreNotThereYet(): void
    {
        $this->handler->append(new Response(202, [], '{}'));

        $this->assertNull($this->client()->getDailyCommits(new GitRepository('acme/one', 'one', ''), new DateTimeImmutable('2026-01-01')));
    }

    private function client(): GitHubClient
    {
        return new GitHubClient('https://api.github.test', null, new GuzzleClient([
            'base_uri' => 'https://api.github.test/',
            'handler' => HandlerStack::create($this->handler),
        ]));
    }
}
