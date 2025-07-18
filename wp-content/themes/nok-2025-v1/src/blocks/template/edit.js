import { useSelect } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
    const parts = useSelect( ( select ) =>
        select( 'core' ).getEntityRecords( 'postType', 'part', { per_page: -1 } )
    ) || [];

    return (
        <SelectControl
            label={ __( 'Select Part', 'nok-2025-v1' ) }
            value={ attributes.partId }
            options={ [
                { label: __( '— Select —', 'nok-2025-v1' ), value: 0 },
                ...parts.map( (p) => ( { label: p.title.rendered, value: p.id } ) ),
            ] }
            onChange={ ( partId ) => setAttributes( { partId: parseInt( partId ) } ) }
        />
    );
}
