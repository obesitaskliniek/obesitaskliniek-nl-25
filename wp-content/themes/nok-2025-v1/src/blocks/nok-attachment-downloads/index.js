/**
 * NOK Attachment Downloads Block
 *
 * Lists non-image attachments (PDFs, documents, etc.) uploaded to the current
 * page/post for download. Server-side rendered — no frontend save output.
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

const textDomain = 'nok-2025-v1';
const blockName = 'nok2025/nok-attachment-downloads';

registerBlockType(blockName, {
    edit: ({ attributes, setAttributes }) => {
        const { title, description } = attributes;

        const blockProps = useBlockProps({
            className: 'nok-attachment-downloads-editor',
            style: {
                width: '100%',
                maxWidth: '100%',
                padding: '15px 3vw',
                boxSizing: 'border-box',
            },
        });

        return (
            <div {...blockProps}>
                <div className="nok-attachment-downloads-editor__header" style={{
                    padding: '20px',
                    background: '#1a2744',
                    borderRadius: '6px 6px 0 0',
                    color: '#fff',
                }}>
                    <RichText
                        tagName="h2"
                        value={title}
                        onChange={(value) => setAttributes({ title: value })}
                        placeholder={__('Titel…', textDomain)}
                        allowedFormats={['core/bold', 'core/italic']}
                        style={{
                            margin: '0 0 8px',
                            fontSize: '1.5rem',
                            color: 'inherit',
                        }}
                    />
                    <RichText
                        tagName="p"
                        value={description}
                        onChange={(value) => setAttributes({ description: value })}
                        placeholder={__('Optionele beschrijving…', textDomain)}
                        allowedFormats={['core/bold', 'core/italic', 'core/link']}
                        style={{
                            margin: 0,
                            fontSize: '0.95rem',
                            opacity: 0.8,
                            color: 'inherit',
                        }}
                    />
                </div>
                <ServerSideRender
                    block={blockName}
                    attributes={attributes}
                    EmptyResponsePlaceholder={() => (
                        <Placeholder
                            icon="download"
                            label={__('Downloads', textDomain)}
                            instructions={__('Er zijn geen downloadbare bijlagen aan deze pagina gekoppeld. Upload bestanden (PDF, Word, etc.) via de Media Bibliotheek en koppel ze aan deze pagina.', textDomain)}
                        />
                    )}
                />
            </div>
        );
    },

    save: () => null,
});
