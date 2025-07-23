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
        wp.data.dispatch('core/editor').autosave().then(() => {
            // 2) once saved, grab the exact preview URL
            const previewLink = wp.data
                .select('core/editor')
                .getEditedPostPreviewLink();

            // 3) update our iframe
            iframe.removeAttribute('srcdoc')
            iframe.src = `${previewLink}&hide_adminbar=1`;
        });
    }

    if (button) {
        button.addEventListener('click', updateFrame);
    }

    window.addEventListener('resize', debounceThis((e)=> {
        updateFrame();
    }));

    // store last known slug so we can see if the select has actually changed.
    let lastSlug = select("core/editor").getEditedPostAttribute("meta")?.design_slug;
    subscribe(() => {
        const meta = select("core/editor").getEditedPostAttribute("meta") || {};
        const currentSlug = meta.design_slug;
        if (currentSlug !== lastSlug) {
            lastSlug = currentSlug;
            // trigger the same autosave + iframe refresh - this will auto kick-off on looad (bonus)
            updateFrame();
        }
    });
});