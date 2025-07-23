import { registerPlugin }               from '@wordpress/plugins';
import { PluginDocumentSettingPanel }   from '@wordpress/edit-post';
import { SelectControl }                from '@wordpress/components';
import { useEntityProp }                from '@wordpress/core-data';
import { jsx }                          from '@wordpress/element';

function DesignSlugPanel() {
    const [ meta, setMeta ] = useEntityProp( 'postType', 'page_part', 'meta' );

    // pull in your PHP‑localized registry
    const registry = window.PagePartDesignSettings?.registry || {};

    // turn it into WP‑style options
    const options = [
        { label: '— Select —', value: '' },
        ...Object.entries( registry ).map( ( [ slug, data ] ) => ( {
            label: data.name,
            value: slug,
        } ) ),
    ];

    return (
        <PluginDocumentSettingPanel
            name="page-part-design"
            title="Page Part Design"
            className="page-part-design-panel"
        >
            <SelectControl
                label="Design template"
                value={ meta.design_slug || '' }
                options={ options }
                onChange={ ( value ) =>
                    setMeta( { ...meta, design_slug: value } )
                }
            />
        </PluginDocumentSettingPanel>
    );
}

registerPlugin( 'page-part-design-slug', { render: DesignSlugPanel } );
