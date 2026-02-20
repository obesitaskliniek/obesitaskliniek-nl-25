import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps, BlockIcon } from '@wordpress/block-editor';
import { SelectControl, PanelBody, TextControl, TextareaControl, ToggleControl, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { video } from '@wordpress/icons';
import ColorSelector from '../../components/ColorSelector';

const textDomain = 'nok-2025-v1';

// Autoplay options for self-hosted videos
const AUTOPLAY_OPTIONS = [
    { label: __('Automatisch', textDomain), value: 'visibility' },
    { label: __('Klik om af te spelen', textDomain), value: 'click' },
    { label: __('Klik om fullscreen af te spelen', textDomain), value: 'off' },
];

registerBlockType('nok2025/nok-video-section', {
    edit: ({ attributes, setAttributes }) => {
        const {
            videoUrl,
            videoType,
            videoHq,
            videoPoster,
            videoStart,
            autoplay,
            fullSection,
            backgroundColor,
            textColor,
            narrowSection,
            sectionTitle,
            sectionDescription
        } = attributes;

        const blockProps = useBlockProps({
            className: 'nok-video-section-editor',
            style: {
                width: '100%',
                maxWidth: '100%',
                padding: '15px 3vw',
                boxSizing: 'border-box',
            },
        });

        // Generate preview embed
        const getPreviewEmbed = () => {
            if (!videoUrl) {
                return (
                    <Placeholder
                        icon={<BlockIcon icon={video} />}
                        label={__('NOK Video Sectie', textDomain)}
                        instructions={__('Voer een video URL in', textDomain)}
                        className="nok-video-section__placeholder"
                    >
                        <div style={{ width: '100%', maxWidth: '400px' }}>
                            <SelectControl
                                label={__('Video Type', textDomain)}
                                value={videoType}
                                options={[
                                    { label: 'YouTube', value: 'youtube' },
                                    { label: 'Vimeo', value: 'vimeo' },
                                    { label: __('Zelf-gehost', textDomain), value: 'self' }
                                ]}
                                onChange={(value) => setAttributes({ videoType: value })}
                            />
                            <TextControl
                                label={__('Video URL', textDomain)}
                                value={videoUrl}
                                onChange={(value) => setAttributes({ videoUrl: value })}
                                placeholder={
                                    videoType === 'youtube' ? 'https://www.youtube.com/watch?v=...' :
                                    videoType === 'vimeo' ? 'https://vimeo.com/...' :
                                    'https://...'
                                }
                            />
                        </div>
                    </Placeholder>
                );
            }

            // Extract video ID for preview
            let embedUrl = '';
            if (videoType === 'youtube') {
                const youtubeMatch = videoUrl.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/);
                if (youtubeMatch) {
                    embedUrl = `https://www.youtube.com/embed/${youtubeMatch[1]}`;
                } else if (/^[a-zA-Z0-9_-]{11}$/.test(videoUrl)) {
                    embedUrl = `https://www.youtube.com/embed/${videoUrl}`;
                }
            } else if (videoType === 'vimeo') {
                const vimeoMatch = videoUrl.match(/vimeo\.com\/(\d+)/);
                if (vimeoMatch) {
                    embedUrl = `https://player.vimeo.com/video/${vimeoMatch[1]}`;
                }
            } else if (videoType === 'self') {
                return (
                    <video
                        controls
                        src={videoUrl}
                        poster={videoPoster || undefined}
                        preload="metadata"
                        style={{ width: '100%', maxHeight: '400px', objectFit: 'contain' }}
                    />
                );
            }

            if (embedUrl) {
                return (
                    <div style={{ position: 'relative', paddingBottom: '56.25%', height: 0 }}>
                        <iframe
                            src={embedUrl}
                            frameBorder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowFullScreen
                            style={{ position: 'absolute', top: 0, left: 0, width: '100%', height: '100%' }}
                        />
                    </div>
                );
            }

            return <p>{__('Video kon niet worden geladen', textDomain)}</p>;
        };

        // Get display label for current settings
        const palettes = window.PagePartDesignSettings?.colorPalettes || {};
        const bgPalette = palettes['backgrounds-simple'] || [];
        const bgLabel = bgPalette.find(opt => opt.value === backgroundColor)?.label || backgroundColor || __('Transparant', textDomain);

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Video Instellingen', textDomain)} initialOpen={true}>
                        <SelectControl
                            label={__('Video Type', textDomain)}
                            value={videoType}
                            options={[
                                { label: 'YouTube', value: 'youtube' },
                                { label: 'Vimeo', value: 'vimeo' },
                                { label: __('Zelf-gehost', textDomain), value: 'self' }
                            ]}
                            onChange={(value) => setAttributes({ videoType: value })}
                        />
                        <TextControl
                            label={__('Video URL', textDomain)}
                            value={videoUrl}
                            onChange={(value) => setAttributes({ videoUrl: value })}
                            placeholder={
                                videoType === 'youtube' ? 'https://www.youtube.com/watch?v=...' :
                                videoType === 'vimeo' ? 'https://vimeo.com/...' :
                                'https://...'
                            }
                        />

                        {videoType === 'self' && (
                            <>
                                <TextControl
                                    label={__('Video HQ URL (optioneel)', textDomain)}
                                    value={videoHq}
                                    onChange={(value) => setAttributes({ videoHq: value })}
                                    help={__('Hogere kwaliteit video voor fullscreen weergave', textDomain)}
                                />
                                <TextControl
                                    label={__('Poster afbeelding URL (optioneel)', textDomain)}
                                    value={videoPoster}
                                    onChange={(value) => setAttributes({ videoPoster: value })}
                                    help={__('Afbeelding die wordt getoond voordat de video start', textDomain)}
                                />
                                <TextControl
                                    label={__('Starttijd in seconden (optioneel)', textDomain)}
                                    value={videoStart}
                                    onChange={(value) => setAttributes({ videoStart: value })}
                                    placeholder="0"
                                    help={__('Bijv. 2.5 voor start op 2,5 seconden', textDomain)}
                                />
                                <SelectControl
                                    label={__('Autoplay gedrag', textDomain)}
                                    value={autoplay}
                                    options={AUTOPLAY_OPTIONS}
                                    onChange={(value) => setAttributes({ autoplay: value })}
                                    help={__('Automatisch: speelt af wanneer zichtbaar. Klik: toont overlay, speelt 1x af bij klik. Uit: alleen poster.', textDomain)}
                                />
                            </>
                        )}
                    </PanelBody>

                    <PanelBody title={__('Sectie Instellingen', textDomain)} initialOpen={true}>
                        <ToggleControl
                            label={__('Volledige sectie', textDomain)}
                            help={__('Bedek de hele sectie tot max 90% van de viewport hoogte', textDomain)}
                            checked={fullSection}
                            onChange={(value) => setAttributes({ fullSection: value })}
                        />

                        <label style={{ display: 'block', fontSize: '11px', fontWeight: '500', textTransform: 'uppercase', marginBottom: '4px' }}>
                            {__('Achtergrondkleur', textDomain)}
                        </label>
                        <ColorSelector
                            value={backgroundColor}
                            palette="backgrounds-simple"
                            onChange={(value) => setAttributes({ backgroundColor: value })}
                        />

                        {!fullSection && (
                            <>
                                <TextControl
                                    label={__('Sectie titel', textDomain)}
                                    value={sectionTitle}
                                    onChange={(value) => setAttributes({ sectionTitle: value })}
                                    help={__('Optioneel: wordt als h2 boven de video getoond', textDomain)}
                                    placeholder={__('Geen titel', textDomain)}
                                />
                                <TextareaControl
                                    label={__('Beschrijving', textDomain)}
                                    value={sectionDescription}
                                    onChange={(value) => setAttributes({ sectionDescription: value })}
                                    help={__('Optioneel: wordt als paragraaf onder de titel getoond', textDomain)}
                                    rows={3}
                                />

                                <label style={{ display: 'block', fontSize: '11px', fontWeight: '500', textTransform: 'uppercase', marginBottom: '4px' }}>
                                    {__('Tekstkleur', textDomain)}
                                </label>
                                <ColorSelector
                                    value={textColor}
                                    palette="text-extended"
                                    onChange={(value) => setAttributes({ textColor: value })}
                                />

                                <ToggleControl
                                    label={__('Smalle sectie', textDomain)}
                                    help={__('Maakt de sectie smaller (max-width beperkt)', textDomain)}
                                    checked={narrowSection}
                                    onChange={(value) => setAttributes({ narrowSection: value })}
                                />
                            </>
                        )}
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div className="nok-video-section-editor__header" style={{
                        padding: '12px 20px',
                        background: '#f0f0f1',
                        border: '1px solid #c3c4c7',
                        borderBottom: 'none',
                        borderRadius: '4px 4px 0 0',
                        textAlign: 'center'
                    }}>
                        <strong>{__('NOK Video Sectie', textDomain)}</strong>
                        <div style={{ fontSize: '12px', marginTop: '4px', color: '#666' }}>
                            {fullSection ? __('Volledige sectie', textDomain) : __('Inline video', textDomain)} |&nbsp;
                            {__('Achtergrond:', textDomain)} {bgLabel}
                        </div>
                    </div>
                    <div style={{
                        padding: '20px',
                        background: '#fff',
                        border: '1px solid #c3c4c7',
                        borderRadius: '0 0 4px 4px'
                    }}>
                        {getPreviewEmbed()}
                    </div>
                </div>
            </>
        );
    },

    save: () => {
        // Server-side rendering via render.php
        return null;
    },
});
