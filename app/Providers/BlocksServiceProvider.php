<?php

namespace App\Providers;

use App\Blocks\Core\Button;
use App\Blocks\Modal;
use App\Blocks\Section;
use Illuminate\Support\ServiceProvider;

class BlocksServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * Render `core/button` block with Blade template
         */
        add_filter('render_block_core/button', [new Button(), 'render'], 10, 2);

        /**
         * Group the landing page section blocks under their own inserter category
         */
        add_filter('block_categories_all', fn (array $categories): array => [
            ['slug' => 'featherdash', 'title' => __('Featherdash', 'radicle')],
            ...$categories,
        ]);

        /**
         * Register and render the `radicle/modal` block
         */
        add_action('init', function () {
            register_block_type('radicle/modal', [
                'api_version' => 3,
                'attributes' => [
                    'heading' => [
                        'type' => 'string',
                        'default' => 'Modal heading',
                    ],
                    'buttonText' => [
                        'type' => 'string',
                        'default' => 'Open modal',
                    ],
                ],
                'render_callback' => [new Modal(), 'render'],
            ]);

            /**
             * Register the `featherdash/*` landing page sections from their block.json files
             */
            foreach (glob(resource_path('js/editor/blocks/*/block.json')) as $metadata) {
                register_block_type(dirname($metadata), [
                    'render_callback' => [new Section(), 'render'],
                ]);
            }

            /**
             * One-click pattern with every section and the launch copy
             */
            register_block_pattern_category('featherdash', ['label' => __('Featherdash', 'radicle')]);

            register_block_pattern('featherdash/landing', [
                'title' => __('Featherdash landing page', 'radicle'),
                'categories' => ['featherdash'],
                'blockTypes' => ['core/post-content'],
                'content' => serialize_blocks(
                    collect(require resource_path('patterns/landing.php'))
                        ->map(fn (array $attributes, string $slug): array => [
                            'blockName' => "featherdash/{$slug}",
                            'attrs' => $attributes,
                            'innerBlocks' => [],
                            'innerHTML' => '',
                            'innerContent' => [],
                        ])
                        ->values()
                        ->all(),
                ),
            ]);
        });
    }
}
