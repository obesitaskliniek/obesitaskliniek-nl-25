import '@wordpress/edit-post';          // ensure wp-editor is registered
import domReady from '@wordpress/dom-ready';
import {render, createElement, useRef, useState, useEffect} from '@wordpress/element';
import {select, dispatch, subscribe} from '@wordpress/data';
import {debounceThis} from "../assets/js/modules/hnl.debounce.mjs";
import {hnlLogger} from '../assets/js/modules/hnl.logger.mjs';

const NAME = 'nok-page-part-preview';
const prefix = `nok-page-part-preview`;

function IframePreview() {
    const iframeRef = useRef(null);
    const [height, setHeight] = useState(400);

    // Initial content for the iframe
    const placeholder = `
      <div style="
        display:flex;
        align-items:center;
        justify-content:center;
        height: 90vh;
        color:#777;
        font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',sans-serif;
      ">
        Klik "Refresh Preview" om de preview te laden.
      </div>
    `;

    useEffect(() => {
        const iframe = iframeRef.current;
        if (!iframe) {
            return;
        }

        const updateHeight = () => {
            try {
                const doc = iframe.contentDocument || iframe.contentWindow.document;
                const newHeight = Math.min(
                    doc.documentElement.scrollHeight,
                    doc.body.scrollHeight
                );
                setHeight(newHeight);
            } catch (e) {
                // ignore if not ready or cross‑origin
            }
        };

        const onLoad = () => {
            // don't touch height when it's just the srcdoc placeholder
            if (iframe.srcdoc) {
                return;
            }
            updateHeight();
        };

        iframe.addEventListener('load', onLoad);

        return () => {
            iframe.removeEventListener('load', onLoad);
        };
    }, []);

    return createElement('iframe', {
        ref: iframeRef,
        id: `${prefix}-iframe`,
        srcdoc: placeholder,
        style: {width: '100%', height: `${height}px`, border: 'none'},
    });
}

domReady(() => {
    // Mount the iframe
    const root = document.getElementById(`${prefix}-root`);
    if (root) {
        render(createElement(IframePreview), root);
    }

    // Get elements
    const button = document.getElementById(`${prefix}-button`);
    const iframe = document.getElementById(`${prefix}-iframe`);

    if (!iframe) {
        hnlLogger.error(NAME, 'Failed to load iframe');
        return;
    }
    if (!button) {
        hnlLogger.warn(NAME, 'Update button not found, continuing with frame only');
    }

    // Add initialization state tracking
    let isInitializing = true;
    let userInitiatedChange = false;
    let hasLoadedInitialPreview = false;

    // Function to load initial preview
    const loadInitialPreview = () => {
        if (!hasLoadedInitialPreview) {
            hnlLogger.info(NAME, 'Loading initial preview');

            const postId = wp.data.select('core/editor').getCurrentPostId();
            const previewLink = wp.data.select('core/editor').getEditedPostPreviewLink();

            if (previewLink && !previewLink.includes('auto-draft')) {
                iframe.removeAttribute('srcdoc');
                iframe.src = `${previewLink}&hide_adminbar=1`;
                hasLoadedInitialPreview = true;
            }
        }
    };

    // Enhanced updateFrame function that tracks user vs system changes
    const enhancedUpdateFrame = (isUserInitiated = false) => {
        if (isUserInitiated) {
            userInitiatedChange = true;
            hnlLogger.info(NAME, 'User-initiated preview update');
        }

        // Call the original updateFrame logic
        const postId = wp.data.select('core/editor').getCurrentPostId();

        /*
        const meta = wp.data.select('core/editor').getEditedPostAttribute('meta') || {};
        const currentDesignSlug = meta.design_slug || '';

        hnlLogger.info(NAME, `About to autosave with design_slug: ${currentDesignSlug}`);
        hnlLogger.info(NAME, `All meta:`);
        hnlLogger.info(NAME, meta);

        // Prepare all meta fields for storage
        const formData = new URLSearchParams({
            action: 'store_preview_meta',
            post_id: postId,
            design_slug: currentDesignSlug
        });

        // Add all meta fields
        formData.append('all_meta', JSON.stringify(meta));
         */

        // Collect complete editor state
        const completeEditorState = {
            title: wp.data.select('core/editor').getEditedPostAttribute('title') || '',
            content: wp.data.select('core/editor').getEditedPostAttribute('content') || '',
            excerpt: wp.data.select('core/editor').getEditedPostAttribute('excerpt') || '',
            meta: wp.data.select('core/editor').getEditedPostAttribute('meta') || {}
        };

        // Prepare for storage
        const formData = new URLSearchParams({
            action: 'store_preview_state',
            post_id: postId,
            nonce: window.PagePartDesignSettings.nonce  // Add this line
        });

        // Add complete state
        formData.append('editor_state', JSON.stringify(completeEditorState));


        // Store the meta value via AJAX
        fetch(ajaxurl, {
            method: 'POST', headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }, body: formData
        })
            .then(response => response.json())
            .then(data => {
                hnlLogger.info(NAME, 'Meta stored:');
                hnlLogger.info(NAME, data);

                // Perform autosave (for content changes) - RETURN the promise
                return wp.data.dispatch('core/editor').autosave();
            })
            .then(autosaveResult => {
                hnlLogger.info(NAME, 'Autosave completed:', autosaveResult);

                const previewLink = wp.data
                    .select('core/editor')
                    .getEditedPostPreviewLink();

                iframe.removeAttribute('srcdoc');
                iframe.src = `${previewLink}&hide_adminbar=1`;
            })
            .catch(error => {
                hnlLogger.error(NAME, `Preview update failed: ${error}`);
            });
    };

    // Expose enhanced function globally
    window.nokUpdatePreview = enhancedUpdateFrame;

    // Button click handler uses the enhanced function
    if (button) {
        button.addEventListener('click', () => enhancedUpdateFrame(true)); // Mark button clicks as user-initiated
    }

    // Auto-update on window resize
    window.addEventListener('resize', debounceThis((e) => {
        enhancedUpdateFrame(true); // Mark resize as user-initiated
    }));

    setTimeout(() => {
        isInitializing = false;
        loadInitialPreview(); // Load preview after initialization
        hnlLogger.info(NAME, 'Preview system initialized, updates now enabled');
    }, 2000);

    // Enhanced subscribe function with autosave detection
    let lastSlug = select("core/editor").getEditedPostAttribute("meta")?.design_slug;
    let lastMeta = select("core/editor").getEditedPostAttribute("meta") || {};

    subscribe(() => {
        // Skip all updates during initialization
        if (isInitializing) {
            return;
        }

        const meta = select("core/editor").getEditedPostAttribute("meta") || {};
        const currentSlug = meta.design_slug;

        // Check if this is likely an autosave (meta changed but no user action in last 2 seconds)
        const isLikelyAutosave = !userInitiatedChange;

        // Reset the user flag after checking
        if (userInitiatedChange) {
            // Keep the flag for 2 seconds, then reset
            setTimeout(() => {
                userInitiatedChange = false;
            }, 2000);
        }

        // Check if design_slug changed
        if (currentSlug !== lastSlug) {
            lastSlug = currentSlug;

            if (!isLikelyAutosave) {
                hnlLogger.info(NAME, `Design slug changed to: ${currentSlug}`);
                enhancedUpdateFrame();
            } else {
                hnlLogger.info(NAME, `Design slug changed via autosave, skipping preview update`);
            }
            return;
        }

        // Check if any custom fields changed
        const hasMetaChanged = JSON.stringify(meta) !== JSON.stringify(lastMeta);
        if (hasMetaChanged) {
            lastMeta = {...meta};

            if (!isLikelyAutosave) {
                hnlLogger.info(NAME, `Custom fields changed, updating preview`);
                enhancedUpdateFrame();
            } else {
                hnlLogger.info(NAME, `Custom fields changed via autosave, skipping preview update`);
            }
        }
    });

});