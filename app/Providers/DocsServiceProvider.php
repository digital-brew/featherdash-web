<?php

namespace App\Providers;

use App\Docs\DocsIndex;
use App\Docs\SyncedPosts;
use Illuminate\Support\ServiceProvider;

class DocsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * The /docs/ archive takes its SEO title and description from the docs index (README.md).
         */
        add_filter('rank_math/frontend/title', function (string $title): string {
            return is_post_type_archive('doc')
                ? sprintf('%s - %s', __('Documentation', 'radicle'), get_bloginfo('name'))
                : $title;
        });

        add_filter('rank_math/frontend/description', function (string $description): string {
            return is_post_type_archive('doc') ? (DocsIndex::get()['excerpt'] ?: $description) : $description;
        });

        /**
         * Synced content is overwritten by the next `docs:sync`; say so in the editor.
         */
        add_action('enqueue_block_editor_assets', function (): void {
            $source = get_post_meta(get_the_ID() ?: 0, SyncedPosts::SOURCE, true);

            if (! $source) {
                return;
            }

            wp_add_inline_script('wp-notices', sprintf(
                'wp.domReady(() => wp.data.dispatch("core/notices").createWarningNotice(%s, { isDismissible: false }));',
                wp_json_encode(sprintf(
                    /* translators: %s: Markdown file name */
                    __(
                        'This content is synced from %s in the Featherdash repository. Edit the Markdown file instead: changes made here are overwritten by the next docs sync.',
                        'radicle',
                    ),
                    $source,
                )),
            ));
            wp_enqueue_script('wp-dom-ready');
        });
    }
}
