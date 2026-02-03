import { useState, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';

/**
 * ImageSelector - WordPress Media Library image picker component
 *
 * Displays a button to select an image from the media library.
 * Shows thumbnail preview when an image is selected.
 * Stores attachment ID (not URL) for proper WordPress integration.
 *
 * @param {Object} props Component props
 * @param {string|number} props.value Current selected attachment ID (or empty)
 * @param {Function} props.onChange Callback when selection changes (receives attachment ID as string)
 */
const ImageSelector = ({ value, onChange }) => {
    // Convert value to number for useSelect, handle empty strings
    const attachmentId = value ? parseInt(value, 10) : null;

    // Fetch image data using WordPress data store
    const imageData = useSelect(
        (select) => {
            if (!attachmentId) return null;
            return select('core').getMedia(attachmentId);
        },
        [attachmentId]
    );

    // Get appropriate thumbnail URL
    const thumbnailUrl = imageData
        ? (imageData.media_details?.sizes?.medium?.source_url
            || imageData.media_details?.sizes?.thumbnail?.source_url
            || imageData.source_url)
        : null;

    const handleSelect = (media) => {
        onChange(String(media.id));
    };

    const handleRemove = () => {
        onChange('');
    };

    return (
        <div style={{ marginBottom: '12px' }}>
            <MediaUploadCheck>
                <MediaUpload
                    onSelect={handleSelect}
                    allowedTypes={['image']}
                    value={attachmentId}
                    render={({ open }) => (
                        <div>
                            {/* Image preview */}
                            {thumbnailUrl && (
                                <div style={{
                                    marginBottom: '12px',
                                    border: '1px solid #ddd',
                                    borderRadius: '4px',
                                    overflow: 'hidden',
                                    maxWidth: '200px'
                                }}>
                                    <img
                                        src={thumbnailUrl}
                                        style={{
                                            width: '100%',
                                            height: 'auto',
                                            display: 'block'
                                        }}
                                        alt=""
                                    />
                                </div>
                            )}

                            {/* Buttons */}
                            <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
                                <Button
                                    variant="secondary"
                                    onClick={open}
                                >
                                    {thumbnailUrl
                                        ? 'Wijzig afbeelding'
                                        : 'Selecteer afbeelding'}
                                </Button>

                                {thumbnailUrl && (
                                    <Button
                                        variant="tertiary"
                                        isDestructive
                                        onClick={handleRemove}
                                    >
                                        Verwijder
                                    </Button>
                                )}
                            </div>
                        </div>
                    )}
                />
            </MediaUploadCheck>
        </div>
    );
};

export default ImageSelector;
