/**
 * NOK Attachment Downloads Block
 *
 * Lists non-image attachments (PDFs, documents, etc.) uploaded to the current
 * page/post for download. Server-side rendered — no frontend save output.
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

const textDomain = 'nok-2025-v1';
const blockName = 'nok2025/nok-attachment-downloads';

registerBlockType(blockName, {
    edit: ({ attributes }) => {
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
