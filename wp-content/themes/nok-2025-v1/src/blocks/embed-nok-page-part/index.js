import { registerBlockType } from '@wordpress/blocks';
import { SelectControl, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

const textDomain = 'nok-2025-v1';
const blockName = 'nok2025/embed-nok-page-part';

registerBlockType( blockName, {
    edit: ( { attributes, setAttributes } ) => {
        const { postId } = attributes;

        // Load all 'page_part' posts for the dropdown:
        const parts = useSelect( ( select ) =>
                select( 'core' ).getEntityRecords( 'postType', 'page_part', { per_page: -1 } )
            , [] ) || [];

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
                        onChange={ ( val ) => setAttributes( { postId: parseInt( val, 10 ) } ) }
                    />
                </PanelBody>

                { postId ? (
                    <ServerSideRender
                        block='nok2025/embed-nok-page-part'
                        attributes={ { postId } }
                    />
                ) : (
                    <p>{ __( 'Selecteer een blok om te bekijken…', textDomain ) }</p>
                ) }
            </>
        );
    },
    save: () => {
        // entirely dynamic — PHP render callback handles output
        return null;
    }
} );
