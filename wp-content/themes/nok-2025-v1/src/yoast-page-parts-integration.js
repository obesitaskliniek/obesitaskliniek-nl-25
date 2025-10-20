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
        async registerPlugin() {
            if (typeof YoastSEO === 'undefined' || typeof YoastSEO.app === 'undefined') {
                console.warn('[Yoast Page Parts] YoastSEO.app not available');
                return;
            }

            // Fetch content FIRST
            console.log('[Yoast Page Parts] Pre-fetching content before registration...');
            await this.fetchAggregatedContent();

            // NOW register with content ready
            YoastSEO.app.registerPlugin(this.pluginName, { status: 'ready' });

            YoastSEO.app.registerModification(
                'content',
                this.modifyContent.bind(this),
                this.pluginName,
                10
            );

            console.log('[Yoast Page Parts] Plugin registered with preloaded content');

            // Trigger initial analysis
            this.refreshYoastAnalysis();

            // Watch for future changes
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
            //const stack = new Error().stack;
            console.log(`[Yoast] ⏰ modifyContent called at ${new Date().toISOString()}`);
            //console.log('[Yoast] Call stack:', stack);
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
            if (this.isFetching) {
                return this.fetchPromise;
            }

            const postId = this.getCurrentPostId();
            if (!postId) {
                console.warn('[Yoast Page Parts] No post ID available');
                return;
            }

            // Get current part IDs from editor
            const blocks = select('core/block-editor')?.getBlocks() || [];
            const pagePartBlocks = this.findPagePartBlocks(blocks);
            const partIds = pagePartBlocks
                .map(b => b.attributes?.postId)
                .filter(Boolean);

            console.log('[Yoast Page Parts] Fetching with part IDs:', partIds);

            this.isFetching = true;

            this.fetchPromise = fetch(
                `/wp-json/nok-2025-v1/v1/seo-content/${postId}`,
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpApiSettings?.nonce || ''
                    },
                    body: JSON.stringify({ part_ids: partIds })
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
         * Find all page part blocks recursively
         * @param {Array} blocks Block array
         * @returns {Array} Page part blocks
         */
        findPagePartBlocks(blocks) {
            let found = [];

            for (const block of blocks) {
                if (block.name === 'nok2025/embed-nok-page-part') {
                    found.push(block);
                }

                if (block.innerBlocks?.length > 0) {
                    found = found.concat(this.findPagePartBlocks(block.innerBlocks));
                }
            }

            return found;
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
            let previousPartIds = [];
            let verificationTimer = null;

            wp.data.subscribe(() => {
                clearTimeout(verificationTimer);

                verificationTimer = setTimeout(() => {
                    const blocks = select('core/block-editor')?.getBlocks() || [];
                    const pagePartBlocks = this.findPagePartBlocks(blocks);
                    const currentPartIds = pagePartBlocks
                        .map(b => b.attributes?.postId)
                        .filter(Boolean)
                        .sort();

                    const idsChanged = JSON.stringify(currentPartIds)
                        !== JSON.stringify(previousPartIds);

                    if (idsChanged) {
                        console.log('[Yoast Page Parts] Part IDs changed:', {
                            from: previousPartIds,
                            to: currentPartIds
                        });

                        // Verify state is stable before fetching
                        setTimeout(() => {
                            const verifyBlocks = select('core/block-editor')?.getBlocks() || [];
                            const verifyParts = this.findPagePartBlocks(verifyBlocks);
                            const verifyIds = verifyParts
                                .map(b => b.attributes?.postId)
                                .filter(Boolean)
                                .sort();

                            if (JSON.stringify(verifyIds) === JSON.stringify(currentPartIds)) {
                                previousPartIds = currentPartIds;
                                console.log('[Yoast Page Parts] State stable, fetching content');
                                this.fetchAggregatedContent();
                            } else {
                                console.log('[Yoast Page Parts] State changed during verification, will retry');
                            }
                        }, 300);
                    }
                }, 800);
            });
        }

        /**
         * Find all page part blocks recursively
         *
         * @param {Array} blocks Block array
         * @returns {Array} Array of page part blocks
         */
        findPagePartBlocks(blocks) {
            let pagePartBlocks = [];

            for (const block of blocks) {
                if (block.name === 'nok2025/embed-nok-page-part') {
                    pagePartBlocks.push(block);
                }

                if (block.innerBlocks && block.innerBlocks.length > 0) {
                    pagePartBlocks = pagePartBlocks.concat(
                        this.findPagePartBlocks(block.innerBlocks)
                    );
                }
            }

            return pagePartBlocks;
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