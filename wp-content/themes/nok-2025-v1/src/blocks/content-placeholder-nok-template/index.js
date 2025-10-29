import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

const blockName = 'nok2025/content-placeholder-nok-template';

registerBlockType(blockName, {
    edit: () => {
        // Check if another instance already exists
        const hasMultipleInstances = useSelect(
            select => {
                const blocks = select('core/block-editor').getBlocks();
                let count = 0;

                const countBlocks = (blockList) => {
                    blockList.forEach(block => {
                        if (block.name === blockName) {
                            count++;
                        }
                        if (block.innerBlocks && block.innerBlocks.length > 0) {
                            countBlocks(block.innerBlocks);
                        }
                    });
                };

                countBlocks(blocks);
                return count > 1;
            },
            []
        );

        if (hasMultipleInstances) {
            return (
                <div {...useBlockProps()}>
                    <div style={{
                        padding: '20px',
                        background: '#f0f0f1',
                        border: '1px solid #c3c4c7',
                        borderRadius: '4px',
                        textAlign: 'center'
                    }}>
                        <p style={{
                            margin: 0,
                            color: '#d63638',
                            fontWeight: 'bold'
                        }}>
                            ⚠️ Let op: Er mag maar één inhoud placeholder zijn per template
                        </p>
                        <p style={{ margin: '8px 0 0 0', fontSize: '13px', color: '#50575e' }}>
                            Verwijder dit blok - er is al een inhoud placeholder aanwezig.
                        </p>
                    </div>
                </div>
            );
        }

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Inhoud Placeholder" initialOpen={true}>
                        <p style={{ fontSize: '13px', lineHeight: '1.6' }}>
                            Dit blok toont de hoofdinhoud van de post op de juiste plek
                            binnen het template layout. Het blok kan slechts één keer
                            per template worden gebruikt.
                        </p>
                    </PanelBody>
                </InspectorControls>

                <div {...useBlockProps()}>
                    <div style={{
                        padding: '40px 20px',
                        background: 'linear-gradient(135deg, #ffd41f 0%, #00b0e4 100%)',
                        borderRadius: '8px',
                        textAlign: 'center',
                        color: '#00132f',
                        border: '2px dashed #54b085'
                    }}>
                        <div style={{
                            fontSize: '48px',
                            marginBottom: '16px',
                            opacity: 0.9
                        }}>
                            📄
                        </div>
                        <h3 style={{
                            margin: '0 0 12px 0',
                            fontSize: '18px',
                            fontWeight: '600',
                            color: '#00132f'
                        }}>
                            Inhoud Placeholder
                        </h3>
                        <p style={{
                            margin: 0,
                            fontSize: '14px',
                            opacity: 0.9,
                            maxWidth: '400px',
                            marginLeft: 'auto',
                            marginRight: 'auto',
                            lineHeight: '1.6'
                        }}>
                            De inhoud van het artikel zal hier worden getoond. Je kunt dit blok verschuiven als dat nodig is.
                        </p>
                    </div>
                </div>
            </>
        );
    },

    save: () => {
        // Dynamic block - server-side rendering
        return null;
    }
});