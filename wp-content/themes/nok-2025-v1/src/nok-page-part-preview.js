import '@wordpress/editor';          // ensure wp-editor is registered
import domReady from '@wordpress/dom-ready';
import {render, createElement, useRef, useState, useEffect} from '@wordpress/element';
import {select, dispatch, subscribe} from '@wordpress/data';
import {debounceThis} from "../assets/js/domule/util.debounce.mjs";
import {logger} from '../assets/js/domule/core.log.mjs';

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
        <div class="loader" style="margin:15px;">
            <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g><circle cx="12" cy="2.5" r="1.5" opacity=".14"/><circle cx="16.75" cy="3.77" r="1.5" opacity=".29"/><circle cx="20.23" cy="7.25" r="1.5" opacity=".43"/><circle cx="21.50" cy="12.00" r="1.5" opacity=".57"/><circle cx="20.23" cy="16.75" r="1.5" opacity=".71"/><circle cx="16.75" cy="20.23" r="1.5" opacity=".86"/><circle cx="12" cy="21.5" r="1.5"/><animateTransform attributeName="transform" type="rotate" calcMode="discrete" dur="0.75s" values="0 12 12;30 12 12;60 12 12;90 12 12;120 12 12;150 12 12;180 12 12;210 12 12;240 12 12;270 12 12;300 12 12;330 12 12;360 12 12" repeatCount="indefinite"/></g></svg>
        </div>
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
        logger.error(NAME, 'Failed to load iframe');
        return;
    }
    if (!button) {
        logger.warn(NAME, 'Update button not found, continuing with frame only');
    }

    // Add initialization state tracking
    let isInitializing = true;
    let userInitiatedChange = false;
    let hasLoadedInitialPreview = false;

    // Function to load initial preview
    const loadInitialPreview = () => {
        if (!hasLoadedInitialPreview) {
            const postId = wp.data.select('core/editor').getCurrentPostId();
            const previewLink = wp.data.select('core/editor').getEditedPostPreviewLink();
            const designSlug = wp.data.select('core/editor').getEditedPostAttribute('meta')?.design_slug;

            logger.info(NAME, 'Loading initial preview');
            logger.info(NAME, `src: ${previewLink}`);

            // Only load if template selected and not auto-draft
            if (previewLink && !previewLink.includes('auto-draft') && designSlug) {
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
            logger.info(NAME, 'User-initiated preview update');
        }

        const postId = wp.data.select('core/editor').getCurrentPostId();

        // ONLY collect meta fields for transient - let Gutenberg handle title/content
        const metaFields = wp.data.select('core/editor').getEditedPostAttribute('meta') || {};

        // Prepare for storage
        const formData = new URLSearchParams({
            action: 'store_preview_state',
            post_id: postId,
            nonce: window.PagePartDesignSettings.nonce
        });

        // Only store meta fields
        formData.append('meta_fields', JSON.stringify(metaFields));

        // Store meta via AJAX
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                logger.info(NAME, 'Meta fields stored in transient');
                logger.info(NAME, data);

                // Gutenberg autosave handles title/content
                return wp.data.dispatch('core/editor').autosave();
            })
            .then(autosaveResult => {
                logger.info(NAME, 'Autosave completed.');
                wp.data.dispatch('core/notices').removeNotice('autosave-exists');

                const previewLink = wp.data
                    .select('core/editor')
                    .getEditedPostPreviewLink();

                iframe.removeAttribute('srcdoc');
                iframe.src = `${previewLink}&hide_adminbar=1`;
            })
            .catch(error => {
                logger.error(NAME, `Preview update failed: ${error}`);
            });
    };

    // Expose enhanced function globally
    window.nokUpdatePreview = enhancedUpdateFrame;

    // Button click handler uses the enhanced function
    if (button) {
        button.addEventListener('click', () => enhancedUpdateFrame(true)); // Mark button clicks as user-initiated
    }

    setTimeout(() => {
        isInitializing = false;
        loadInitialPreview(); // Load preview after initialization
        logger.info(NAME, 'Preview system initialized, updates now enabled');
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

        // Check if design_slug changed
        if (currentSlug !== lastSlug) {
            lastSlug = currentSlug;
                logger.info(NAME, `Design slug changed to: ${currentSlug}`);
                enhancedUpdateFrame();
            return;
        }

        // Check if any custom fields changed
        const hasMetaChanged = JSON.stringify(meta) !== JSON.stringify(lastMeta);
        if (hasMetaChanged) {
            lastMeta = {...meta};
                logger.info(NAME, `Custom fields changed, updating preview`);
                enhancedUpdateFrame();
        }
    });

});