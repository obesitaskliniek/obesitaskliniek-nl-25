/**
 * Yoast SEO Page Parts Integration
 *
 * Integrates NOK page parts content into Yoast SEO analysis by fetching
 * aggregated content from all embedded page parts and providing it to
 * Yoast's analysis engine.
 *
 * @package NOK2025\V1\SEO
 */

import { select } from '@wordpress/data';

(function() {
    'use strict';

    /**
     * Main integration class
     */
    class YoastPagePartsIntegration {
        constructor() {
            this.pluginName = 'nok-page-parts-analysis';
            this.aggregatedContent = '';
            this.isFetching = false;
            this.fetchPromise = null;

            this.init();
        }

        /**
         * Initialize the integration
         */
        init() {
            // Check if we're in the block editor
            if (typeof wp === 'undefined' || typeof wp.data === 'undefined') {
                console.warn('[Yoast Page Parts] WordPress data module not available');
                return;
            }

            // Wait for Yoast SEO to be ready
            if (typeof YoastSEO !== 'undefined' && typeof YoastSEO.app !== 'undefined') {
                this.registerPlugin();
            } else {
                // Listen for Yoast ready event
                jQuery(window).on('YoastSEO:ready', () => this.registerPlugin());
            }
        }

        /**
         * Register with Yoast SEO
         */
        registerPlugin() {
            if (typeof YoastSEO === 'undefined' || typeof YoastSEO.app === 'undefined') {
                console.warn('[Yoast Page Parts] YoastSEO.app not available');
                return;
            }

            // Register our plugin
            YoastSEO.app.registerPlugin(this.pluginName, { status: 'ready' });

            // Register content modification
            YoastSEO.app.registerModification(
                'content',
                this.modifyContent.bind(this),
                this.pluginName,
                10
            );

            console.log('[Yoast Page Parts] Plugin registered successfully');

            // Pre-fetch content
            this.fetchAggregatedContent();

            // Listen for editor changes to refresh content
            this.watchForChanges();
        }

        /**
         * Content modification callback
         * Called by Yoast during analysis - must be synchronous
         *
         * @param {string} data Current content
         * @returns {string} Modified content with page parts included
         */
        modifyContent(data) {
            // Add timestamp to see WHEN this runs
            console.log(`[Yoast] ⏰ modifyContent called at ${new Date().toISOString()}`);
            console.log('[Yoast] Original length:', data.length);
            console.log('[Yoast] Aggregated length:', this.aggregatedContent.length);

            if (this.aggregatedContent) {
                return data + '\n\n' + this.aggregatedContent;
            }
            return data;
        }

        /**
         * Fetch aggregated content from REST API
         */
        async fetchAggregatedContent() {
            // Prevent duplicate fetches
            if (this.isFetching) {
                return this.fetchPromise;
            }

            const postId = this.getCurrentPostId();
            if (!postId) {
                console.warn('[Yoast Page Parts] No post ID available');
                return;
            }

            this.isFetching = true;

            this.fetchPromise = fetch(
                `/wp-json/nok-2025-v1/v1/seo-content/${postId}?use_cache=true`,
                {
                    credentials: 'same-origin',
                    headers: {
                        'X-WP-Nonce': wpApiSettings?.nonce || ''
                    }
                }
            )
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    this.aggregatedContent = data.content || '';
                    console.log(
                        `[Yoast Page Parts] Fetched ${data.content_length} characters from ${data.part_count} parts`
                    );

                    // Trigger Yoast refresh to reanalyze with new content
                    this.refreshYoastAnalysis();
                })
                .catch(error => {
                    console.error('[Yoast Page Parts] Failed to fetch aggregated content:', error);
                    this.aggregatedContent = '';
                })
                .finally(() => {
                    this.isFetching = false;
                    this.fetchPromise = null;
                });

            return this.fetchPromise;
        }

        /**
         * Get current post ID from WordPress data store
         *
         * @returns {number|null} Post ID or null
         */
        getCurrentPostId() {
            try {
                const postId = select('core/editor')?.getCurrentPostId();
                return postId || null;
            } catch (error) {
                console.error('[Yoast Page Parts] Error getting post ID:', error);
                return null;
            }
        }

        /**
         * Watch for changes that should trigger content refresh
         */
        watchForChanges() {
            let lastBlockCount = 0;
            let debounceTimer = null;

            // Subscribe to block changes
            wp.data.subscribe(() => {
                const blocks = select('core/block-editor')?.getBlocks() || [];
                const currentBlockCount = this.countPagePartBlocks(blocks);

                // If page part block count changed, refresh
                if (currentBlockCount !== lastBlockCount) {
                    lastBlockCount = currentBlockCount;

                    // Debounce the refresh (wait 1 second after last change)
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        console.log('[Yoast Page Parts] Block count changed, refreshing content...');
                        this.fetchAggregatedContent();
                    }, 1000);
                }
            });
        }

        /**
         * Count page part blocks recursively
         *
         * @param {Array} blocks Block array
         * @returns {number} Count of page part blocks
         */
        countPagePartBlocks(blocks) {
            let count = 0;

            for (const block of blocks) {
                if (block.name === 'nok2025/embed-nok-page-part') {
                    count++;
                }

                // Recursively count inner blocks
                if (block.innerBlocks && block.innerBlocks.length > 0) {
                    count += this.countPagePartBlocks(block.innerBlocks);
                }
            }

            return count;
        }

        /**
         * Trigger Yoast to refresh its analysis
         */
        refreshYoastAnalysis() {
            if (typeof YoastSEO !== 'undefined' && typeof YoastSEO.app !== 'undefined') {
                // Trigger a refresh of the analysis
                if (typeof YoastSEO.app.refresh === 'function') {
                    YoastSEO.app.refresh();
                }
            }
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            new YoastPagePartsIntegration();
        });
    } else {
        new YoastPagePartsIntegration();
    }

})();