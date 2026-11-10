<?php

namespace App\Blocks;

use Illuminate\Support\Str;
use WP_Block;

/**
 * Renders the landing page section blocks (`featherdash/*`) with `resources/views/blocks/{slug}.blade.php`.
 */
class Section
{
    public function render(array $attributes, string $content, WP_Block $block): string
    {
        $anchor = sanitize_title($attributes['anchor'] ?? '');
        $style = $this->colorVariables($attributes['colors'] ?? []);

        return view('blocks.' . Str::after($block->name, '/'), [
            ...$this->sanitize($attributes),
            'wrapper' => fn (string $class = ''): string => get_block_wrapper_attributes(array_filter([
                'class' => $class,
                'id' => $anchor,
                'style' => $style,
            ])),
        ])->render();
    }

    /**
     * A block's `colors` attribute (e.g. ['bg' => '#0a7aa2']) as CSS variables (`--c-bg: #0a7aa2`).
     * Templates read them with a fallback to the design default: `bg-[var(--c-bg,var(--color-brand))]`.
     */
    protected function colorVariables(array $colors): string
    {
        return collect($colors)
            ->map(fn ($color) => is_string($color) ? sanitize_hex_color($color) : null)
            ->filter()
            ->map(fn (string $color, string $key): string => '--c-' . Str::kebab($key) . ': ' . $color)
            ->implode('; ');
    }

    /**
     * Attribute strings come from RichText fields, so they may hold inline HTML.
     */
    protected function sanitize(array $attributes): array
    {
        array_walk_recursive($attributes, function (&$value): void {
            if (is_string($value)) {
                $value = wp_kses_post($value);
            }
        });

        return $attributes;
    }
}
