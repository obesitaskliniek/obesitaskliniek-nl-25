/**
 * NOK Vestiging Agenda Carousel Block
 *
 * Displays upcoming voorlichtingen and events in a scrollable carousel.
 * Auto-detects vestiging on vestiging pages, or shows all locations.
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    SelectControl,
    RangeControl,
    TextControl,
    ToggleControl,
    Placeholder,
    Notice,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import ColorSelector from '../../components/ColorSelector';

const textDomain = 'nok-2025-v1';
const blockName = 'nok2025/nok-vestiging-voorlichtingen';

registerBlockType(blockName, {
    edit: ({ attributes, setAttributes }) => {
        const { vestigingId, limit, title, showAllLink, backgroundColor, textColor } = attributes;

        const vestigingen = useSelect((select) => {
            return select('core').getEntityRecords('postType', 'vestiging', {
                per_page: -1,
                status: 'publish',
                orderby: 'title',
                order: 'asc',
            }) || [];
        }, []);

        const vestigingOptions = [
            { label: __('Automatisch detecteren', textDomain), value: 0 },
            ...vestigingen.map((v) => ({
                label: v.title.rendered,
                value: v.id,
            })),
        ];

        const blockProps = useBlockProps({
            className: 'nok-vestiging-voorlichtingen-editor',
            style: {
                width: '100%',
                maxWidth: '100%',
                padding: '15px 3vw',
                boxSizing: 'border-box',
            },
        });

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Kleuren', textDomain)}>
                        <label style={{ display: 'block', marginBottom: '8px', fontWeight: '500' }}>
                            {__('Achtergrondkleur', textDomain)}
                        </label>
                        <ColorSelector
                            value={backgroundColor}
                            onChange={(value) => setAttributes({ backgroundColor: value })}
                            palette="backgrounds"
                        />

                        <label style={{ display: 'block', marginBottom: '8px', fontWeight: '500' }}>
                            {__('Tekstkleur', textDomain)}
                        </label>
                        <ColorSelector
                            value={textColor}
                            onChange={(value) => setAttributes({ textColor: value })}
                            palette="text"
                        />
                    </PanelBody>
                    <PanelBody title={__('Instellingen', textDomain)}>
                        <SelectControl
                            label={__('Vestiging', textDomain)}
                            value={vestigingId}
                            options={vestigingOptions}
                            onChange={(value) => setAttributes({ vestigingId: Number(value) })}
                            help={__('Kies een vestiging of laat automatisch detecteren.', textDomain)}
                        />
                        <RangeControl
                            label={__('Aantal agenda-items', textDomain)}
                            value={limit}
                            onChange={(value) => setAttributes({ limit: value })}
                            min={1}
                            max={12}
                        />
                        <TextControl
                            label={__('Titel', textDomain)}
                            value={title}
                            onChange={(value) => setAttributes({ title: value })}
                        />
                        <ToggleControl
                            label={__('Toon "Bekijk alle" link', textDomain)}
                            checked={showAllLink}
                            onChange={(value) => setAttributes({ showAllLink: value })}
                        />
                    </PanelBody>
                </InspectorControls>

                {vestigingId === 0 && (
                    <Notice status="info" isDismissible={false} style={{ margin: '0 0 12px' }}>
                        {__('Op vestiging-pagina\'s worden automatisch voorlichtingen en evenementen voor die vestiging getoond. Op andere pagina\'s worden alle aankomende agenda-items getoond.', textDomain)}
                    </Notice>
                )}

                <ServerSideRender
                    block={blockName}
                    attributes={attributes}
                    EmptyResponsePlaceholder={() => (
                        <Placeholder
                            icon="calendar-alt"
                            label={__('Agenda', textDomain)}
                            instructions={__('Er zijn geen aankomende agenda-items gevonden.', textDomain)}
                        />
                    )}
                />
            </div>
        );
    },

    save: () => null,
});
