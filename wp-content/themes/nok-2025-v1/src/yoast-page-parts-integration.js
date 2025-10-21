/**
 * Yoast SEO Page Parts Integration
 *
 * Architecture:
 * - Waits for all page part iframes to load and extract their semantic content
 * - Stores content in window.nokPagePartData keyed by part ID
 * - Registers with Yoast only after all expected parts have loaded
 * - Provides aggregated content synchronously via modifyContent()
 * - Visual editor mode only (shows notice in code mode)
 *
 * @package NOK2025\V1\SEO
 */

import {select, dispatch} from '@wordpress/data';
import {__} from '@wordpress/i18n';

(function () {
    'use strict';

    /**
     * Main Yoast integration class
     */
    class YoastPagePartsIntegration {
        constructor() {
            this.pluginName = 'nok-page-parts-analysis';
            this.isRegistered = false;
            this.currentMode = null;

            // Global store for iframe content (populated by iframes)
            window.nokPagePartData = window.nokPagePartData || {};

            this.init();
        }

        /**
         * Initialize integration
         *
         * Checks editor mode and either:
         * - Visual mode: Wait for iframes and register with Yoast
         * - Code mode: Show notice and exit
         */
        init() {
            if (typeof wp === 'undefined' || typeof wp.data === 'undefined') {
                console.warn('[Yoast Page Parts] WordPress data module not available');
                return;
            }

            // Check editor mode
            if (!this.isVisualMode()) {
                this.showCodeModeNotice();
                this.watchForModeSwitch();
                return;
            }

            this.currentMode = 'visual';

            // Wait for Yoast SEO to be ready
            if (typeof YoastSEO !== 'undefined' && typeof YoastSEO.app !== 'undefined') {
                this.waitForIframesAndRegister();
            } else {
                jQuery(window).on('YoastSEO:ready', () => this.waitForIframesAndRegister());
            }

            this.watchForModeSwitch();
        }

        /**
         * Check if editor is in visual mode
         *
         * @returns {boolean} True if visual mode, false if code mode
         */
        isVisualMode() {
            const editorMode = select('core/edit-post')?.getEditorMode();
            return editorMode === 'visual';
        }

        /**
         * Show editor notice when in code mode
         *
         * Creates dismissible info notice explaining SEO analysis
         * is disabled in code editor mode.
         */
        showCodeModeNotice() {
            dispatch('core/notices').createInfoNotice(
                __('Page Part SEO analysis is disabled in code editor mode. Switch to visual mode for full SEO analysis.', 'nok-2025-v1'),
                {
                    id: 'nok-yoast-code-mode',
                    isDismissible: true
                }
            );
        }

        /**
         * Watch for mode switches between visual and code
         *
         * Registers/unregisters Yoast integration as user switches modes
         */
        watchForModeSwitch() {
            let lastMode = this.currentMode;

            wp.data.subscribe(() => {
                const newMode = this.isVisualMode() ? 'visual' : 'code';

                if (newMode !== lastMode) {
                    lastMode = newMode;
                    this.currentMode = newMode;

                    if (newMode === 'visual' && !this.isRegistered) {
                        // Switched TO visual: initialize
                        dispatch('core/notices').removeNotice('nok-yoast-code-mode');
                        this.waitForIframesAndRegister();
                    } else if (newMode === 'code') {
                        // Switched TO code: show notice (whether registered or not)
                        this.showCodeModeNotice();  // ADD THIS LINE
                    }
                }
            });
        }

        /**
         * Wait for all expected iframes to load, then register with Yoast
         *
         * Polls window.nokPagePartData until all expected parts are present,
         * with 10-second timeout. Then registers plugin with Yoast.
         */
        async waitForIframesAndRegister() {
            if (typeof YoastSEO === 'undefined' || typeof YoastSEO.app === 'undefined') {
                console.warn('[Yoast Page Parts] YoastSEO.app not available');
                return;
            }

            // Register immediately as 'loading'
            YoastSEO.app.registerPlugin(this.pluginName, {status: 'loading'});

            const expectedParts = window.nokYoastIntegration?.expectedParts || [];

            if (nokYoastIntegration.debug) {
                console.log('[Yoast Page Parts] Waiting for iframes:', expectedParts);
            }

            // If no parts expected, mark as ready immediately
            if (expectedParts.length === 0) {
                YoastSEO.app.pluginReady(this.pluginName);
                this.completeRegistration();
                return;
            }

            // Wait for all iframes to populate nokPagePartData
            const maxWait = 10000;
            const checkInterval = 100;
            let elapsed = 0;

            const checkLoaded = () => {
                const loadedIds = Object.keys(window.nokPagePartData).map(id => parseInt(id));
                const allLoaded = expectedParts.every(id => loadedIds.includes(id));

                if (allLoaded) {
                    if (nokYoastIntegration.debug) {
                        console.log('[Yoast Page Parts] All iframes loaded');
                    }
                    YoastSEO.app.pluginReady(this.pluginName);
                    this.completeRegistration();
                    return true;
                }

                return false;
            };

            // Immediate check
            if (checkLoaded()) {
                return;
            }

            // Poll until loaded or timeout
            const interval = setInterval(() => {
                elapsed += checkInterval;

                if (checkLoaded()) {
                    clearInterval(interval);
                } else if (elapsed >= maxWait) {
                    clearInterval(interval);
                    console.warn('[Yoast Page Parts] Timeout waiting for iframes, marking as ready with partial data');
                    YoastSEO.app.pluginReady(this.pluginName);
                    this.completeRegistration();
                }
            }, checkInterval);
        }

        /**
         * Complete registration after iframes are loaded
         *
         * Registers content modification callback and starts watching
         * for block structure changes.
         */
        completeRegistration() {
            if (this.isRegistered) {
                return;
            }

            YoastSEO.app.registerModification(
                'content',
                this.modifyContent.bind(this),
                this.pluginName,
                10
            );

            this.isRegistered = true;

            if (nokYoastIntegration.debug) {
                console.log('[Yoast Page Parts] Plugin registered with Yoast');
            }

            // Initial analysis
            this.refreshYoastAnalysis();

            // Watch for block changes
            this.watchForBlockChanges();
        }

        /**
         * Content modification callback
         *
         * Called by Yoast during analysis. Reads from window.nokPagePartData
         * and appends all page part content to the page's base content.
         *
         * MUST be synchronous - data is pre-loaded from iframes.
         *
         * @param {string} data Current page content
         * @returns {string} Content with page parts appended
         */
        modifyContent(data) {
            const blocks = select('core/block-editor')?.getBlocks() || [];
            const pagePartBlocks = this.findPagePartBlocks(blocks)
                .filter(block => !block.attributes?.excludeFromSeo); // Respect exclusion

            let aggregated = '';

            pagePartBlocks.forEach(block => {
                const partId = block.attributes?.postId;
                if (partId && window.nokPagePartData[partId]) {
                    aggregated += '\n\n' + window.nokPagePartData[partId];
                }
            });

            if (nokYoastIntegration.debug && aggregated) {
                console.log(`[Yoast Page Parts] Adding ${aggregated.length} characters from ${pagePartBlocks.length} parts`);
            }

            return data + aggregated;
        }

        /**
         * Find all page part blocks recursively
         *
         * @param {Array} blocks Block array from getBlocks()
         * @returns {Array} Array of page part block objects
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
         * Watch for block structure changes
         *
         * Monitors block additions, removals, and exclusion toggle changes.
         * Triggers Yoast refresh when structure changes.
         */
        watchForBlockChanges() {
            let prevState = this.getBlockState();

            wp.data.subscribe(() => {
                const currentState = this.getBlockState();

                if (JSON.stringify(currentState) !== JSON.stringify(prevState)) {
                    prevState = currentState;

                    if (nokYoastIntegration.debug) {
                        console.log('[Yoast Page Parts] Block structure changed, refreshing analysis');
                    }

                    this.refreshYoastAnalysis();
                }
            });
        }

        /**
         * Get current block state for change detection
         *
         * Creates serializable state object with part IDs and exclusion status.
         *
         * @returns {Array} Array of {id, excluded} objects
         */
        getBlockState() {
            const blocks = select('core/block-editor')?.getBlocks() || [];
            return this.findPagePartBlocks(blocks).map(block => ({
                id: block.attributes?.postId || 0,
                excluded: block.attributes?.excludeFromSeo || false
            }));
        }

        /**
         * Trigger Yoast analysis refresh
         *
         * Tells Yoast to re-run content analysis with current content.
         */
        refreshYoastAnalysis() {
            if (typeof YoastSEO !== 'undefined' &&
                typeof YoastSEO.app !== 'undefined' &&
                typeof YoastSEO.app.refresh === 'function') {
                YoastSEO.app.refresh();
            }
        }

    }

    // Initialize when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            new YoastPagePartsIntegration();
        });
    } else {
        new YoastPagePartsIntegration();
    }

})();