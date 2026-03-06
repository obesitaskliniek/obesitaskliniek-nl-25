/**
 * FullEditorModal — full-width modal for editing the entire vragenlijst.
 *
 * Uses a custom tab bar that renders all three panels simultaneously,
 * hiding inactive ones with display:none to preserve expand/collapse state.
 * Changes flow to the WordPress store immediately (write-through).
 */
import {useState} from '@wordpress/element';
import {Modal, Icon} from '@wordpress/components';

import QuestionsPanel from './QuestionsPanel';
import ResultsPanel from './ResultsPanel';
import SettingsPanel from './SettingsPanel';

const TABS = [
    {key: 'questions', label: 'Vragen', icon: 'editor-ol'},
    {key: 'results', label: 'Uitkomsten', icon: 'clipboard'},
    {key: 'settings', label: 'Instellingen', icon: 'admin-generic'},
];

const tabBarStyle = {
    display: 'flex',
    gap: '0',
    borderBottom: '1px solid #ddd',
    marginBottom: '16px',
};

const tabStyle = (active) => ({
    padding: '10px 20px',
    border: 'none',
    borderBottom: active ? '2px solid #007cba' : '2px solid transparent',
    background: 'none',
    cursor: 'pointer',
    fontSize: '13px',
    fontWeight: active ? 600 : 400,
    color: active ? '#007cba' : '#555',
});

const FullEditorModal = ({config, questions, results, settings, updateConfig, onClose}) => {
    const [activeTab, setActiveTab] = useState('questions');

    return (
        <Modal
            title="Vragenlijst bewerken"
            onRequestClose={onClose}
            className="nok-vragenlijst-modal"
            isFullScreen={false}
        >
            {/* Tab bar */}
            <div style={tabBarStyle}>
                {TABS.map((tab) => (
                    <button
                        key={tab.key}
                        type="button"
                        style={tabStyle(activeTab === tab.key)}
                        onClick={() => setActiveTab(tab.key)}
                    >
                        <Icon icon={tab.icon} size={18} style={{marginRight: '6px', verticalAlign: 'middle'}} />
                        {tab.label}
                        {tab.key === 'questions' && ` (${questions.length})`}
                        {tab.key === 'results' && ` (${results.length})`}
                    </button>
                ))}
            </div>

            {/* All panels rendered, inactive hidden via display:none */}
            <div style={{display: activeTab === 'questions' ? 'block' : 'none'}}>
                <QuestionsPanel
                    questions={questions}
                    results={results}
                    onChange={(q) => updateConfig({questions: q})}
                    onBatchChange={(partial) => updateConfig(partial)}
                />
            </div>
            <div style={{display: activeTab === 'results' ? 'block' : 'none'}}>
                <ResultsPanel
                    results={results}
                    questions={questions}
                    onChange={(r) => updateConfig({results: r})}
                />
            </div>
            <div style={{display: activeTab === 'settings' ? 'block' : 'none'}}>
                <SettingsPanel
                    settings={settings}
                    onChange={(s) => updateConfig({settings: s})}
                />
            </div>
        </Modal>
    );
};

export default FullEditorModal;
