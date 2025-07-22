import { registerBlockType } from '@wordpress/blocks';
import { SelectControl, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

const textDomain = 'nok-2025-v1';
const blockName  = 'nok2025/embed-nok-page-part';

registerBlockType( blockName, {
    edit: ( { attributes, setAttributes } ) => {
        const { postId } = attributes;
        const parts = useSelect(
            select => select( 'core' ).getEntityRecords( 'postType', 'page_part', { per_page: -1 } ),
            []
        ) || [];

        // Build the iframe src
        const src = postId ? `/wp-json/nok-2025-v1/v1/embed-page-part/${postId}` : '';

        return (
            <>
                <PanelBody title={ __( 'Settings', textDomain ) } initialOpen>
                    <SelectControl
                        label={ __( 'Welk blok?', textDomain ) }
                        value={ postId }
                        options={ [
                            { label: __( 'Selecteer een blok…', textDomain ), value: 0 },
                            ...parts.map( p => ( { label: p.title.rendered, value: p.id } ) ),
                        ] }
                        onChange={ val => setAttributes( { postId: parseInt( val, 10 ) } ) }
                    />
                </PanelBody>

                { postId ? (
                    <iframe
                        title={ __( 'Embedded NOK Page Part', textDomain ) }
                        src={ src }
                        style={ { width: '100%', height: '500px', border: 0 } }
                        sandbox="allow-scripts allow-same-origin"
                    />
                ) : (
                    <p>{ __( 'Selecteer een blok om te bekijken…', textDomain ) }</p>
                ) }
            </>
        );
    },
    save: () => null, // fully dynamic
} );