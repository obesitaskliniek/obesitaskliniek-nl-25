import '@wordpress/edit-post';          // ensure wp-editor is registered
import domReady from '@wordpress/dom-ready';
import {render, createElement, useRef, useState, useEffect} from '@wordpress/element';
import {select, dispatch, subscribe} from '@wordpress/data';
import {Notice} from '@wordpress/components';
import {debounceThis} from "../assets/js/modules/hnl.debounce.mjs";

const prefix = `nok-page-part-preview`;

function IframePreview() {
    // we only need a ref for the iframe
    const iframeRef = useRef(null);
    // Refs & state for dynamic height
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
        Klik “Refresh Preview” om de preview te laden.
      </div>
    `;

    useEffect(() => {
        const iframe = iframeRef.current;
        if (!iframe) {
            return;
        }

        let mo; // mutation observer

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
            // don’t touch height when it’s just the srcdoc placeholder
            if (iframe.srcdoc) {
                return;
            }
            updateHeight();
        };

        iframe.addEventListener('load', onLoad);

        // cleanup
        return () => {
            iframe.removeEventListener('load', onLoad);
        };
    }, []);

    // render just an empty iframe — src will be set on button click
    return createElement('iframe', {
        ref: iframeRef,
        id: `${prefix}-iframe`,
        srcdoc: placeholder,
        style: {width: '100%', height: `${height}px`, border: 'none'},
    });
}

domReady(() => {
    // mount the iframe
    const root = document.getElementById(`${prefix}-root`);
    if (root) {
        render(createElement(IframePreview), root);
    }

    // get elements
    const button = document.getElementById(`${prefix}-button`);
    const iframe = document.getElementById(`${prefix}-iframe`);

    if (!iframe) {
        console.error('Failed to load iframe');
        return;
    }
    if (!button) {
        console.warn('Update button not found, continuing with frame only');
    }

    // single update function
    const updateFrame = () => {
        // 1) Get the current select value
        const designSelect = document.querySelector('select[name="page_part_design_slug"]');
        const currentDesignSlug = designSelect ? designSelect.value : '';
        console.log('About to autosave with design_slug:', currentDesignSlug);

        // 2) Store the meta value directly via AJAX
        const postId = wp.data.select('core/editor').getCurrentPostId();

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
                console.log('Meta stored:', data);

                // 3) Now perform autosave (for content changes)
                return wp.data.dispatch('core/editor').autosave();
            })
            .then(() => {
                console.log('Autosave completed');
                // 4) once saved, grab the exact preview URL
                const previewLink = wp.data
                    .select('core/editor')
                    .getEditedPostPreviewLink();

                // 5) update our iframe
                iframe.removeAttribute('srcdoc')
                iframe.src = `${previewLink}&hide_adminbar=1`;
            });
    }

    // Auto-update when design select changes
    const designSelect = document.querySelector('select[name="page_part_design_slug"]');
    if (designSelect) {
        designSelect.addEventListener('change', function() {
            console.log('Design slug changed to:', this.value);
            updateFrame();
        });
    } else {
        console.warn('Design select dropdown not found');
    }

    if (button) {
        button.addEventListener('click', updateFrame);
    }

    window.addEventListener('resize', debounceThis((e) => {
        updateFrame();
    }));
});