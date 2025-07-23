import '@wordpress/edit-post';          // ensure wp-editor is registered
import domReady           from '@wordpress/dom-ready';
import { render, createElement, useRef } from '@wordpress/element';
import { select }         from '@wordpress/data';
import { Notice } from '@wordpress/components';

const prefix = `nok-page-part-preview`;

function IframePreview() {
    // we only need a ref for the iframe
    const iframeRef = useRef( null );
    const placeholder = `
      <div style='
        display:flex;
        align-items:center;
        justify-content:center;
        height: 90vh;
        color:#777;
        font-family: -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Oxygen-Sans,Ubuntu,Cantarell,Helvetica Neue,sans-serif;
      ">
        Klik “Refresh Preview” om de preview te laden.
      </div>
    `;
    // render just an empty iframe — src will be set on button click
    return createElement( 'iframe', {
        ref:   iframeRef,
        id:    `${prefix}-iframe`,
        srcdoc: placeholder,
        style: { width: '100%', height: '400px', border: 'none' },
    } );
}

domReady( () => {
    // mount the iframe
    const root = document.getElementById( `${prefix}-root` );
    if ( root ) {
        render( createElement( IframePreview ), root );
    }

    // wire up the button
    const button = document.getElementById( `${prefix}-button` );
    const iframe  = document.getElementById( `${prefix}-iframe` );
    if ( button && iframe ) {
        button.addEventListener( 'click', () => {
            // 1) autosave the post
            wp.data.dispatch( 'core/editor' ).autosave().then( () => {
                // 2) once saved, grab the exact preview URL
                const previewLink = wp.data
                    .select( 'core/editor' )
                    .getEditedPostPreviewLink();

                // 3) update our iframe
                iframe.removeAttribute( 'srcdoc' )
                iframe.src = `${previewLink}&hide_adminbar=1`;
            } );
        } );
    }
} );