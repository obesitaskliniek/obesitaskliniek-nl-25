import {useSelect, useDispatch} from '@wordpress/data';
import {registerPlugin} from '@wordpress/plugins';
import {PluginDocumentSettingPanel} from '@wordpress/edit-post';
import {SelectControl} from '@wordpress/components';
import {hnlLogger} from '../assets/js/modules/hnl.logger.mjs';

const NAME = 'nok-page-part-design-selector';

function DesignSlugPanel() {
    // 1) Grab the current post type
    const postType = useSelect((select) =>
        select('core/editor').getCurrentPostType(), []
    );
    // 2) If it isn't our CPT, render nothing
    if (postType !== 'page_part') {
        return null;
    }

    // 3) Now safely grab ID + meta
    const postId = useSelect((select) =>
        select('core/editor').getCurrentPostId(), []
    );
    const meta = useSelect((select) =>
        select('core/editor').getEditedPostAttribute('meta'), []
    );
    const {editPost} = useDispatch('core/editor');

    // 4) Build your dropdown options
    const registry = window.PagePartDesignSettings?.registry || {};
    const options = [
        {label: '— Select —', value: ''},
        ...Object.entries(registry).map(([slug, data]) => ({
            label: data.name,
            value: slug,
        })),
    ];

    // 5) Function to trigger preview update (same logic as HTML select)
    const triggerPreviewUpdate = (newSlug) => {
        // Store the meta value via AJAX (same as HTML version)
        fetch(window.PagePartDesignSettings.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'store_preview_meta',
                post_id: postId,
                design_slug: newSlug
            })
        })
            .then(response => response.json())
            .then(data => {
                hnlLogger.log(NAME, 'Meta stored via React:');
                hnlLogger.log(NAME, data);

                // Trigger autosave and iframe refresh
                return wp.data.dispatch('core/editor').autosave();
            })
            .then(() => {
                hnlLogger.log(NAME, 'Autosave completed via React');

                // Find and update the iframe (same as HTML version)
                const iframe = document.getElementById('nok-page-part-preview-iframe');
                if (iframe) {
                    const previewLink = wp.data
                        .select('core/editor')
                        .getEditedPostPreviewLink();

                    iframe.removeAttribute('srcdoc');
                    iframe.src = `${previewLink}&hide_adminbar=1`;
                }
            });
    };

    // 6) Console‑log so we can see what's happening
    hnlLogger.log(NAME, 'DesignSlugPanel meta:');
    hnlLogger.log(NAME, meta);

    return (
        <PluginDocumentSettingPanel
            name="page-part-design"
            title="Design template"
        >
            <SelectControl
                value={meta?.design_slug || ''}
                options={options}
                onChange={(newSlug) => {
                    hnlLogger.log(NAME, `→ setting design_slug to "${newSlug}"`);
                    // Update the editor meta
                    editPost({meta: {...meta, design_slug: newSlug}});
                    // Trigger preview update
                    triggerPreviewUpdate(newSlug);
                }}
            />
        </PluginDocumentSettingPanel>
    );
}

registerPlugin('page-part-design', {
    render: DesignSlugPanel,
});