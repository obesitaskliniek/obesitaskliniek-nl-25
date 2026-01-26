/**
 * NOK Video Block Handler
 * Handles play button visibility for embed-nok-video block (YouTube, Vimeo, self-hosted)
 */

export const NAME = 'videoBlock';

/**
 * Handle YouTube iframe API
 */
let youtubeReady = false;
let youtubeQueue = [];

function initYouTube() {
    if (window.YT && window.YT.Player) {
        youtubeReady = true;
        youtubeQueue.forEach(fn => fn());
        youtubeQueue = [];
        return;
    }

    if (!document.querySelector('script[src*="youtube.com/iframe_api"]')) {
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        document.head.appendChild(tag);
    }

    window.onYouTubeIframeAPIReady = () => {
        youtubeReady = true;
        youtubeQueue.forEach(fn => fn());
        youtubeQueue = [];
    };
}

function setupYouTubePlayer(iframe, wrapper) {
    const setup = () => {
        try {
            const player = new window.YT.Player(iframe, {
                events: {
                    onStateChange: (event) => {
                        // YT.PlayerState: -1 (unstarted), 0 (ended), 1 (playing), 2 (paused), 3 (buffering), 5 (cued)
                        if (event.data === 1) {
                            wrapper.classList.add('is-playing');
                        } else {
                            wrapper.classList.remove('is-playing');
                        }
                    }
                }
            });
        } catch (e) {
            console.error('Error setting up YouTube player:', e);
        }
    };

    if (youtubeReady) {
        setup();
    } else {
        youtubeQueue.push(setup);
        initYouTube();
    }
}

/**
 * Handle Vimeo Player API
 */
let vimeoReady = false;

function initVimeo() {
    return new Promise((resolve) => {
        if (window.Vimeo && window.Vimeo.Player) {
            vimeoReady = true;
            resolve();
            return;
        }

        if (!document.querySelector('script[src*="player.vimeo.com/api/player.js"]')) {
            const script = document.createElement('script');
            script.src = 'https://player.vimeo.com/api/player.js';
            script.onload = () => {
                vimeoReady = true;
                resolve();
            };
            document.head.appendChild(script);
        }
    });
}

async function setupVimeoPlayer(iframe, wrapper) {
    await initVimeo();

    try {
        const player = new window.Vimeo.Player(iframe);

        player.on('play', () => {
            wrapper.classList.add('is-playing');
        });

        player.on('pause', () => {
            wrapper.classList.remove('is-playing');
        });

        player.on('ended', () => {
            wrapper.classList.remove('is-playing');
        });
    } catch (e) {
        console.error('Error setting up Vimeo player:', e);
    }
}

/**
 * Initialize video playback handlers
 * @param {HTMLElement[]} elements - Container elements
 */
export function init(elements) {
    if (!Array.isArray(elements)) return;

    elements.forEach(element => {
        if (!(element instanceof Element)) return;

        const videoWrappers = element.querySelectorAll('.nok-video-block__video-wrapper');

        videoWrappers.forEach(wrapper => {
            // Self-hosted video
            const video = wrapper.querySelector('video');
            if (video) {
                video.addEventListener('play', () => wrapper.classList.add('is-playing'));
                video.addEventListener('pause', () => wrapper.classList.remove('is-playing'));
                video.addEventListener('ended', () => wrapper.classList.remove('is-playing'));
                return;
            }

            // YouTube or Vimeo iframe
            const iframe = wrapper.querySelector('iframe');
            if (!iframe) return;

            const src = iframe.src || '';

            if (src.includes('youtube.com') || src.includes('youtu.be')) {
                // Ensure iframe has enablejsapi parameter
                if (!src.includes('enablejsapi=1')) {
                    const separator = src.includes('?') ? '&' : '?';
                    iframe.src = src + separator + 'enablejsapi=1';
                }
                setupYouTubePlayer(iframe, wrapper);
            } else if (src.includes('vimeo.com')) {
                setupVimeoPlayer(iframe, wrapper);
            }
        });
    });
}

/**
 * Cleanup
 */
export function cleanup(elements) {
    // Event listeners are automatically cleaned up
}

/**
 * Destroy
 */
export function destroy() {
    youtubeQueue = [];
}
