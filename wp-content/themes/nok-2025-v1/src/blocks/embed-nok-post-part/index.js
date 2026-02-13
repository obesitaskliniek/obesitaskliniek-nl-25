/**
 * Embed NOK Post Part Block
 *
 * Allows editors to embed post-part templates (e.g., BMI calculator)
 * inline within post content via a simple dropdown selector.
 * Server-side rendered — no frontend save output.
 */

import {registerBlockType} from '@wordpress/blocks';
import {useBlockProps} from '@wordpress/block-editor';
import {SelectControl, PanelBody, Placeholder} from '@wordpress/components';
import {__} from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

const textDomain = 'nok-2025-v1';
const blockName = 'nok2025/embed-nok-post-part';

registerBlockType(blockName, {
    edit: ({attributes, setAttributes}) => {
        const {template} = attributes;

        const postParts = (typeof window !== 'undefined' && window.PagePartDesignSettings)
            ? window.PagePartDesignSettings.postParts || []
            : [];

        const options = [
            {label: __('— Selecteer een post part —', textDomain), value: ''},
            ...postParts.map(part => ({
                label: part.label,
                value: part.slug
            }))
        ];

        const blockProps = useBlockProps({
            style: {
                width: '100%',
                maxWidth: '100%',
                padding: '15px 3vw',
                boxSizing: 'border-box',
            },
        });

        return (
            <div {...blockProps}>
                <PanelBody title={__('NOK Post Part', textDomain)} initialOpen>
                    <SelectControl
                        label={__('Post Part template', textDomain)}
                        value={template}
                        options={options}
                        onChange={val => setAttributes({template: val})}
                    />

                    {!template ? (
                        <Placeholder
                            icon="screenoptions"
                            label={__('NOK Post Part', textDomain)}
                            instructions={__('Selecteer een post part template uit de lijst hierboven.', textDomain)}
                        />
                    ) : (
                        <ServerSideRender
                            block={blockName}
                            attributes={attributes}
                        />
                    )}
                </PanelBody>
            </div>
        );
    },

    save: () => null
});
