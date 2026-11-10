<?php

namespace App\Docs;

use Closure;

/**
 * The docs home (README.md), stored as an option and shown on the /docs/ archive.
 */
class DocsIndex
{
    public const OPTION = 'featherdash_docs_index';

    /**
     * @return array{title: string, excerpt: string, content: string, hash?: string}
     */
    public static function get(): array
    {
        return (
            get_option(self::OPTION, []) + ['title' => __('Documentation', 'radicle'), 'excerpt' => '', 'content' => '']
        );
    }

    public static function exists(): bool
    {
        return (bool) get_option(self::OPTION);
    }

    public static function isCurrent(string $hash): bool
    {
        return (get_option(self::OPTION, [])['hash'] ?? null) === $hash;
    }

    /**
     * @param  array{title: string, excerpt: string, content: string}  $converted
     */
    public static function save(array $converted, string $hash): void
    {
        update_option(self::OPTION, [...$converted, 'hash' => $hash], false);
    }

    /**
     * Store the converted index when its Markdown changed.
     *
     * @param  Closure(string): array{title: string, excerpt: string, content: string}  $convert
     * @param  Closure(string): string  $hash
     */
    public static function sync(string $markdown, Closure $convert, Closure $hash, SyncOption ...$options): string
    {
        if ($markdown === '') {
            return 'skipped';
        }

        $digest = $hash($markdown);

        if (! in_array(SyncOption::Force, $options, true) && self::isCurrent($digest)) {
            return 'unchanged';
        }

        $status = self::exists() ? 'updated' : 'created';

        if (! in_array(SyncOption::DryRun, $options, true)) {
            self::save($convert($markdown), $digest);
        }

        return $status;
    }
}
