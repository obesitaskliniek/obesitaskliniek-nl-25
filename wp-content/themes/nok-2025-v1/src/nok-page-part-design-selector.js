(() => {
    const {registerPlugin} = wp.plugins;
    const {PluginDocumentSettingPanel} = wp.editPost;
    const {SelectControl} = wp.components;
    const {useSelect, useDispatch, select, dispatch, subscribe} = wp.data;
    const apiFetch                       = wp.apiFetch;

    function DesignSlugPanel() {
        // ← no deps array here: subscribe to EVERY editor change
        const meta = useSelect(
            select => select('core/editor').getEditedPostAttribute('meta') || {}
        );

        const {editPost} = useDispatch('core/editor');
        const postId        = useSelect( ( select ) =>
            select( 'core/editor' ).getCurrentPostId()
        );

        // your localized PHP registry
        const registry = window.PagePartDesignSettings?.registry || {};

        const options = [
            {label: '— Select —', value: ''},
            ...Object.entries(registry).map(([slug, data]) => ({
                label: data.name,
                value: slug,
            })),
        ];

        return (
            <PluginDocumentSettingPanel name="page-part-design" title="Select a design template">
                <SelectControl
                    label="Design template"
                    value={meta.design_slug || ''}
                    options={options}
                    onChange={newSlug => {
                        // pushes into core/editor store – this is what Gutenberg saves
                        editPost({meta: {...meta, design_slug: newSlug}});
                        apiFetch({
                            path: `/wp/v2/page_part/${ postId }`,
                            method: 'POST',
                            data: { meta: { design_slug: newSlug } },
                        }).catch( ( err ) => {
                            console.error( 'Failed to save design_slug:', err );
                        });
                    }}
                />
            </PluginDocumentSettingPanel>
        );
    }
    registerPlugin('page-part-design-slug', {render: DesignSlugPanel});

})();
