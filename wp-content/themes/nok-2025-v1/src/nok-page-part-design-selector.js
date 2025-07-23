import { useSelect, useDispatch } from '@wordpress/data';
import { registerPlugin }         from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { SelectControl }          from '@wordpress/components';

function DesignSlugPanel() {
    // 1) Grab the current post type
    const postType = useSelect( ( select ) =>
        select('core/editor').getCurrentPostType(), []
    );
    // 2) If it isn’t our CPT, render nothing
    if ( postType !== 'page_part' ) {
        return null;
    }

    // 3) Now safely grab ID + meta
    const postId = useSelect( ( select ) =>
        select('core/editor').getCurrentPostId(), []
    );
    const meta   = useSelect( ( select ) =>
        select('core/editor').getEditedPostAttribute('meta'), []
    );
    const { editPost } = useDispatch('core/editor');

    // 4) Build your dropdown options
    const registry = window.PagePartDesignSettings?.registry || {};
    const options = [
        { label: '— Select —', value: '' },
        ...Object.entries(registry).map(([ slug, data ]) => ({
            label: data.name,
            value: slug,
        })),
    ];

    // 5) Console‑log so we can see what’s happening
    console.log( 'DesignSlugPanel meta:', meta );

    return (
        <PluginDocumentSettingPanel
            name="page-part-design"
            title="Design template"
        >
            <SelectControl
                value={ meta?.design_slug || '' }
                options={ options }
                onChange={ ( newSlug ) => {
                    console.log( '→ setting design_slug to', newSlug );
                    editPost({ meta: { ...meta, design_slug: newSlug } });
                } }
            />
        </PluginDocumentSettingPanel>
    );
}

registerPlugin( 'page-part-design', {
    render: DesignSlugPanel,
} );
