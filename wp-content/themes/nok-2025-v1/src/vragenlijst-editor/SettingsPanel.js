/**
 * SettingsPanel — global questionnaire settings.
 */
import {useCallback} from '@wordpress/element';
import {TextControl, ToggleControl} from '@wordpress/components';

const SettingsPanel = ({settings, onChange}) => {
    const update = useCallback((partial) => {
        onChange({...settings, ...partial});
    }, [settings, onChange]);

    const skipIntro = settings.skip_intro === true;

    return (
        <div style={{display: 'flex', flexDirection: 'column', gap: '12px'}}>
            <ToggleControl
                label="Introscherm overslaan"
                help="Start de vragenlijst direct, zonder startknop en introductietekst."
                checked={skipIntro}
                onChange={(skip_intro) => update({skip_intro: skip_intro || undefined})}
                __nextHasNoMarginBottom
            />

            <ToggleControl
                label="Voortgangsbalk tonen"
                checked={settings.show_progress !== false}
                onChange={(show_progress) => update({show_progress})}
                __nextHasNoMarginBottom
            />

            <ToggleControl
                label="Terug-knop toestaan"
                checked={settings.allow_back !== false}
                onChange={(allow_back) => update({allow_back})}
                __nextHasNoMarginBottom
            />

            {!skipIntro && (
                <div>
                    <TextControl
                        label="Startknop tekst"
                        value={settings.start_button_text || ''}
                        onChange={(start_button_text) => update({
                            start_button_text: start_button_text || undefined,
                        })}
                        placeholder="Start de vragenlijst"
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />
                </div>
            )}

            <div>
                <TextControl
                    label="Verzendknop tekst"
                    value={settings.submit_button_text || ''}
                    onChange={(submit_button_text) => update({
                        submit_button_text: submit_button_text || undefined,
                    })}
                    placeholder="Bekijk resultaat"
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                />
            </div>
        </div>
    );
};

export default SettingsPanel;
