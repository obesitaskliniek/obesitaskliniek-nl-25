import {registerBlockType} from '@wordpress/blocks';
import {InspectorControls, useBlockProps, RichText, BlockIcon} from '@wordpress/block-editor';
import {SelectControl, PanelBody, TextControl, TextareaControl, Button, Placeholder} from '@wordpress/components';
import {__} from '@wordpress/i18n';
import {video} from '@wordpress/icons';

const textDomain = 'nok-2025-v1';

registerBlockType('nok2025/embed-nok-video', {
    edit: ({attributes, setAttributes}) => {
        const {videoUrl, videoType, title, description} = attributes;
        const blockProps = useBlockProps({
            className: 'nok-video-section'
        });

        // Generate preview embed
        const getPreviewEmbed = () => {
            if (!videoUrl) {
                return (
                    <Placeholder
                        icon={<BlockIcon icon={video} />}
                        label="NOK Video"
                        instructions="Voer een video URL in"
                        className="nok-video-section__placeholder"
                    >
                        <div style={{width: '100%', maxWidth: '400px'}}>
                            <SelectControl
                                label={__('Video Type', textDomain)}
                                value={videoType}
                                options={[
                                    {label: 'YouTube', value: 'youtube'},
                                    {label: 'Vimeo', value: 'vimeo'},
                                    {label: 'Self-hosted', value: 'self'}
                                ]}
                                onChange={(value) => setAttributes({videoType: value})}
                            />
                            <TextControl
                                label={__('Video URL', textDomain)}
                                value={videoUrl}
                                onChange={(value) => setAttributes({videoUrl: value})}
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
                }
            } else if (videoType === 'vimeo') {
                const vimeoMatch = videoUrl.match(/vimeo\.com\/(\d+)/);
                if (vimeoMatch) {
                    embedUrl = `https://player.vimeo.com/video/${vimeoMatch[1]}`;
                }
            } else if (videoType === 'self') {
                // Self-hosted video
                return (
                    <video controls src={videoUrl} preload="metadata" style={{width: '100%', height: '100%'}} />
                );
            }

            if (embedUrl) {
                return (
                    <iframe
                        src={embedUrl}
                        frameBorder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowFullScreen
                        style={{position: 'absolute', top: 0, left: 0, width: '100%', height: '100%'}}
                    />
                );
            }

            return <p>Video kon niet worden geladen</p>;
        };

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Video Instellingen', textDomain)} initialOpen={true}>
                        <SelectControl
                            label={__('Video Type', textDomain)}
                            value={videoType}
                            options={[
                                {label: 'YouTube', value: 'youtube'},
                                {label: 'Vimeo', value: 'vimeo'},
                                {label: 'Self-hosted', value: 'self'}
                            ]}
                            onChange={(value) => setAttributes({videoType: value})}
                        />
                        <TextControl
                            label={__('Video URL', textDomain)}
                            value={videoUrl}
                            onChange={(value) => setAttributes({videoUrl: value})}
                            placeholder="https://www.youtube.com/watch?v=..."
                            help={videoType === 'youtube' ? 'YouTube URL (bijv. https://www.youtube.com/watch?v=...)' :
                                  videoType === 'vimeo' ? 'Vimeo URL (bijv. https://vimeo.com/...)' :
                                  'Directe URL naar video bestand'}
                        />
                    </PanelBody>
                    <PanelBody title={__('Tekst', textDomain)} initialOpen={false}>
                        <TextControl
                            label={__('Titel', textDomain)}
                            value={title}
                            onChange={(value) => setAttributes({title: value})}
                        />
                        <TextareaControl
                            label={__('Beschrijving', textDomain)}
                            value={description}
                            onChange={(value) => setAttributes({description: value})}
                            rows={4}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div className="nok-video-section__content">
                        {/* Video Preview */}
                        {!videoUrl ? (
                            // Show placeholder directly without wrapper to maintain proper sizing
                            getPreviewEmbed()
                        ) : (
                            <div className="nok-video-section__video-wrapper">
                                {getPreviewEmbed()}
                            </div>
                        )}

                        {/* Text Content */}
                        {(title || description) && (
                            <div className="nok-video-section__text">
                                {title && (
                                    <h2 className="nok-fs-giant">{title}</h2>
                                )}
                                {description && (
                                    <div className="nok-fs-body">
                                        <p>{description}</p>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </>
        );
    },

    save: () => {
        // Server-side rendering via render.php
        return null;
    }
});
