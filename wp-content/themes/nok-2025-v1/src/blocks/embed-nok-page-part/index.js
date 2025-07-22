import {registerBlockType} from '@wordpress/blocks';
import {InspectorControls, BlockControls, useBlockProps} from '@wordpress/block-editor';
import {SelectControl, PanelBody} from '@wordpress/components';
import {useSelect} from '@wordpress/data';
import {__} from '@wordpress/i18n';
import {useRef, useState, useEffect} from '@wordpress/element';

const textDomain = 'nok-2025-v1';
const blockName = 'nok2025/embed-nok-page-part';

registerBlockType(blockName, {
    edit: ({attributes, setAttributes}) => {
        const {postId} = attributes;
        const parts = useSelect(
            select => select('core').getEntityRecords('postType', 'page_part', {per_page: -1}),
            []
        ) || [];

        // Build the iframe src
        const src = postId ? `/wp-json/nok-2025-v1/v1/embed-page-part/${postId}` : '';

        // Refs & state for dynamic height
        const iframeRef = useRef(null);
        const [height, setHeight] = useState(400);

        useEffect(() => {
            const iframe = iframeRef.current;
            if (!iframe) {
                return;
            }

            let mo; // mutation observer

            const updateHeight = () => {
                try {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    const newHeight = Math.max(
                        doc.documentElement.scrollHeight,
                        doc.body.scrollHeight
                    );
                    setHeight(newHeight);
                } catch (e) {
                    // ignore if not ready or cross‑origin
                }
            };

            const onLoad = () => {
                updateHeight();
                // watch for any DOM changes inside the iframe
                mo = new MutationObserver(updateHeight);
                mo.observe(iframe.contentDocument.body, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                });
            };

            iframe.addEventListener('load', onLoad);

            // cleanup
            return () => {
                iframe.removeEventListener('load', onLoad);
                if (mo) {
                    mo.disconnect();
                }
            };
        }, [postId]);

        const blockProps = useBlockProps({
            style: {
                position: 'relative',
                width: '100%',
                margin: 0,
                maxWidth: '100%',
            },
        });

        return (
            <>
                <BlockControls></BlockControls>
                <div {...useBlockProps()} style={{position: 'relative', width: '100%', margin: '0', maxWidth: '100%'}}>
                    <PanelBody title={__('NOK Page Part Blok', textDomain)} initialOpen>
                        <SelectControl
                            label={__('Selecteer een Page Part uit de lijst', textDomain)}
                            value={postId}
                            options={[
                                {label: __(' - Selecteer Page Part blok… - ', textDomain), value: 0},
                                ...parts.map(p => ({label: p.title.rendered, value: p.id})),
                            ]}
                            onChange={val => setAttributes({postId: parseInt(val, 10)})}
                        />
                    </PanelBody>

                    {postId ? (
                        <div style={{position: 'relative', width: '100%'}}>
                            <iframe
                                ref={iframeRef}
                                title={__('Embedded NOK Page Part', textDomain)}
                                src={src}
                                style={{
                                    width: '100%',
                                    height: `${height}px`,
                                    border: 0,
                                    //pointerEvents: 'none'
                                }}
                                sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox"
                            />
                        </div>
                    ) : (
                        <p>{__('Selecteer een blok om te bekijken…', textDomain)}</p>
                    )}
                </div>
            </>
        );
    },
    save: () => null, // fully dynamic
});