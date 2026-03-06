/**
 * Popup Link — RichText Format Type
 *
 * Adds a "Popup Link" toolbar button to the block editor's RichText toolbar.
 * Editors select text, click the button, pick a popup target from a dropdown,
 * and the text is wrapped in a <span class="nok-popup-link" data-popup="...">.
 *
 * PHP (BlockRenderers::expand_popup_links) converts the <span> to a proper
 * <a> with toggler data-attributes at render time.
 *
 * Uses <span> instead of <a> to avoid collision with core's link format.
 */

import { registerFormatType, toggleFormat, applyFormat, removeFormat, getActiveFormat } from '@wordpress/rich-text';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import { Popover, SelectControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const FORMAT_NAME = 'nok/popup-link';

/**
 * Get available popup targets from localized PHP data.
 *
 * @returns {Array<{id: string, label: string}>}
 */
function getPopupTargets() {
    return window.nokPopupTargets || [];
}

/**
 * Toolbar button component for the popup link format.
 */
function PopupLinkButton({ isActive, value, onChange, contentRef }) {
    const [showPopover, setShowPopover] = useState(false);

    const targets = getPopupTargets();
    const activeFormat = getActiveFormat(value, FORMAT_NAME);
    const currentTarget = activeFormat?.attributes?.popupTarget || '';

    function onToggle() {
        if (isActive) {
            onChange(removeFormat(value, FORMAT_NAME));
            setShowPopover(false);
        } else {
            setShowPopover(true);
        }
    }

    function onSelectTarget(popupId) {
        if (!popupId) return;

        onChange(applyFormat(value, {
            type: FORMAT_NAME,
            attributes: {
                popupTarget: popupId,
            },
        }));
        setShowPopover(false);
    }

    return (
        <>
            <RichTextToolbarButton
                icon="external"
                title={ __('Popup Link', 'nok') }
                onClick={ onToggle }
                isActive={ isActive }
            />
            { showPopover && (
                <Popover
                    anchor={ contentRef?.current }
                    placement="bottom-start"
                    onClose={ () => setShowPopover(false) }
                    focusOnMount="firstElement"
                >
                    <div style={{ padding: '12px', minWidth: '200px' }}>
                        <SelectControl
                            label={ __('Open popup', 'nok') }
                            value={ currentTarget }
                            options={ [
                                { value: '', label: __('Kies popup...', 'nok') },
                                ...targets.map(t => ({
                                    value: t.id,
                                    label: t.label,
                                })),
                            ] }
                            onChange={ onSelectTarget }
                        />
                    </div>
                </Popover>
            ) }
        </>
    );
}

registerFormatType(FORMAT_NAME, {
    title: __('Popup Link', 'nok'),
    tagName: 'span',
    className: 'nok-popup-link',
    attributes: {
        popupTarget: 'data-popup',
    },
    edit: PopupLinkButton,
});
