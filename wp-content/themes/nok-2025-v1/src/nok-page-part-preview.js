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

    /**
     * Update the preview iframe with current content and design_slug
     */
    const updateFrame = () => {
        const postId = wp.data.select('core/editor').getCurrentPostId();
        const meta = wp.data.select('core/editor').getEditedPostAttribute('meta') || {};
        const currentDesignSlug = meta.design_slug || '';

        hnlLogger.info(NAME, `About to autosave with design_slug: ${currentDesignSlug}`);

        // Store the meta value via AJAX
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'store_preview_meta',
                post_id: postId,
                design_slug: currentDesignSlug
            })
        })
            .then(response => response.json())
            .then(data => {
                hnlLogger.info(NAME, 'Meta stored:');
                hnlLogger.info(NAME, data);

                // Perform autosave (for content changes)
                return wp.data.dispatch('core/editor').autosave();
            })
            .then(() => {
                hnlLogger.info(NAME, 'Autosave completed');

                // Get the preview URL and update iframe
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

    // Expose updateFrame globally so React component can use it
    window.nokUpdatePreview = updateFrame;

    // Button click handler
    if (button) {
        button.addEventListener('click', updateFrame);
    }

    // Auto-update on window resize
    window.addEventListener('resize', debounceThis((e) => {
        updateFrame();
    }));

    // Watch for design_slug changes in the editor store
    let lastSlug = select("core/editor").getEditedPostAttribute("meta")?.design_slug;
    subscribe(() => {
        const meta = select("core/editor").getEditedPostAttribute("meta") || {};
        const currentSlug = meta.design_slug;
        if (currentSlug !== lastSlug) {
            lastSlug = currentSlug;
            hnlLogger.info(NAME, `Design slug changed to: ${currentSlug}`);
            updateFrame();
        }
    });
});