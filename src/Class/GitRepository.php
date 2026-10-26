<?php

declare(strict_types=1);

namespace Wexample\PhpRemoteGit\Class;

/**
 * A repository as a host lists it: what the host knows it by (`id`), its
 * name, where it is read.
 */
final readonly class GitRepository
{
    public function __construct(
        /** The host's own key for it: `owner/name` on GitHub, the project id on GitLab. */
        public string $id,
        /** Its short name, the same on every host mirroring it. */
        public string $name,
        public string $url,
    ) {
    }
}
