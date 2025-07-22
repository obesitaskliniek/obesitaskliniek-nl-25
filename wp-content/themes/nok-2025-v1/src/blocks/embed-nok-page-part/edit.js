import { useSelect } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const textDomain = 'nok-2025-v1';

export default function Edit( { attributes, setAttributes } ) {
    const parts = useSelect( ( select ) =>
        select( 'core' ).getEntityRecords( 'postType', 'part', { per_page: -1 } )
    ) || [];

    return (
        <SelectControl
            label={ __( 'Select Post', textDomain ) }
            value={ attributes.postId }
            options={ [
                { label: __( '— Selecteer vormgeving —', textDomain ), value: 0 },
                ...parts.map( (p) => ( { label: p.title.rendered, value: p.id } ) ),
            ] }
            onChange={ ( postId ) => setAttributes( { postId: parseInt( postId ) } ) }
        />
    );
}
