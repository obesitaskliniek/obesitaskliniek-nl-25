/**
 * Main editor component — sidebar summary panel + full-width modal for the vragenlijst CPT.
 *
 * Reads _vl_config from post meta as JSON string, parses it into a config
 * object. The sidebar shows a count summary and a button to open the
 * full editor modal. Changes flow to the WordPress store immediately
 * via editPost({meta: {_vl_config: JSON.stringify(config)}}).
 */
import {PluginDocumentSettingPanel} from '@wordpress/editor';
import {useSelect, useDispatch, select as wpSelect} from '@wordpress/data';
import {useMemo, useCallback, useState} from '@wordpress/element';
import {Notice, Button} from '@wordpress/components';

import FullEditorModal from './FullEditorModal';

/**
 * Parse _vl_config JSON safely.
 *
 * @param {string} raw - JSON string from meta
 * @returns {{config: Object, parseError: boolean}}
 */
function parseConfig(raw) {
    if (!raw) return {config: {}, parseError: false};
    try {
        return {config: JSON.parse(raw), parseError: false};
    } catch {
        return {config: {}, parseError: true};
    }
}

const VragenlijstEditor = () => {
    const {meta, postType} = useSelect((select) => ({
        meta: select('core/editor').getEditedPostAttribute('meta') || {},
        postType: select('core/editor').getCurrentPostType(),
    }));

    const {editPost} = useDispatch('core/editor');

    // Parse config from JSON string. Recalculate only when the raw string changes.
    const {config, parseError} = useMemo(
        () => parseConfig(meta._vl_config),
        [meta._vl_config]
    );

    // Write a partial config update back to meta.
    // Reads current state from the store to avoid stale closure issues
    // when multiple panels update in rapid succession.
    const updateConfig = useCallback((partial) => {
        const currentRaw = wpSelect('core/editor').getEditedPostAttribute('meta')?._vl_config;
        const {config: current} = parseConfig(currentRaw);
        const newConfig = {...current, ...partial};
        editPost({meta: {_vl_config: JSON.stringify(newConfig)}});
    }, [editPost]);

    // Only render for vragenlijst CPT
    if (postType !== 'vragenlijst') {
        return null;
    }

    if (parseError) {
        return (
            <PluginDocumentSettingPanel
                name="nok-vragenlijst-error"
                title="Vragenlijst"
                icon="warning"
            >
                <Notice status="error" isDismissible={false}>
                    De configuratie bevat ongeldige JSON. Herstel dit via het
                    veld &ldquo;Aangepaste velden&rdquo; onderaan de editor.
                </Notice>
            </PluginDocumentSettingPanel>
        );
    }

    const questions = config.questions || [];
    const results = config.results || [];
    const settings = config.settings || {};
    const hasDefault = results.some(r => r.condition === 'default');

    const [modalOpen, setModalOpen] = useState(false);

    return (
        <>
            <PluginDocumentSettingPanel
                name="nok-vragenlijst-editor"
                title="Vragenlijst"
                icon="editor-ol"
            >
                <div style={{fontSize: '13px', lineHeight: '1.6'}}>
                    <div>{questions.length} vragen</div>
                    <div>
                        {results.length} resultaten
                        {results.length > 0 && !hasDefault && (
                            <span style={{color: '#cc1818', marginLeft: '6px'}}>
                                (geen fallback)
                            </span>
                        )}
                    </div>
                </div>
                <Button
                    variant="secondary"
                    onClick={() => setModalOpen(true)}
                    style={{width: '100%', justifyContent: 'center', marginTop: '12px'}}
                >
                    Vragenlijst bewerken
                </Button>
            </PluginDocumentSettingPanel>

            {modalOpen && (
                <FullEditorModal
                    config={config}
                    questions={questions}
                    results={results}
                    settings={settings}
                    updateConfig={updateConfig}
                    onClose={() => setModalOpen(false)}
                />
            )}
        </>
    );
};

export default VragenlijstEditor;
