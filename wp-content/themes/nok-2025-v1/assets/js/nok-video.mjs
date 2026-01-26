/**
 * NOK Video Module
 *
 * Handles background video autoplay and fullscreen HQ playback.
 *
 * Data attributes:
 * - data-video-lq: Low quality source (background, muted loop)
 * - data-video-hq: High quality source (fullscreen, with audio)
 *
 * Behavior:
 * - Background: muted loop, lazy-loaded, visibility-based play/pause
 * - Click play button: swap to HQ, unmute, enter fullscreen
 * - Exit fullscreen: swap back to LQ, mute, resume loop
 */
export const NAME = 'video';

const videoStates = new WeakMap();
const observers = new WeakMap();

/**
 * Initialize video containers and standalone triggers.
 * @param {HTMLElement[]} elements
 */
export function init(elements) {
    elements.forEach(container => {
        const video = container.querySelector('video');
        const playButton = container.querySelector('[data-video-play]');

        // Standalone trigger (no video element inside) - e.g., mobile inline trigger
        if (!video && container.dataset.videoHq) {
            container.addEventListener('click', (e) => {
                e.preventDefault();
                playFullscreenVideo(container.dataset.videoHq);
            });
            return;
        }

        if (!video) return;

        // Get sources from data attributes
        const lqSrc = container.dataset.videoLq || video.currentSrc || video.querySelector('source')?.src;
        const hqSrc = container.dataset.videoHq || lqSrc;

        // Store state
        videoStates.set(video, { lqSrc, hqSrc, currentTime: 0 });

        // Set up background playback
        video.muted = true;
        video.loop = true;
        video.controls = false;
        video.playsInline = true;

        // Lazy load with IntersectionObserver
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    video.play().catch(() => {});
                } else {
                    video.pause();
                }
            });
        }, { threshold: 0.3, rootMargin: '100px' });

        observer.observe(container);
        observers.set(container, observer);

        // Play button → fullscreen
        if (playButton) {
            playButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                enterFullscreen(video);
            });
        }

        // Fullscreen exit handler
        video.addEventListener('fullscreenchange', () => onFullscreenChange(video));
        video.addEventListener('webkitfullscreenchange', () => onFullscreenChange(video));
        video.addEventListener('webkitendfullscreen', () => exitFullscreen(video)); // iOS
    });
}

/**
 * Enter fullscreen with HQ video.
 */
async function enterFullscreen(video) {
    const state = videoStates.get(video);
    if (!state) return;

    state.currentTime = video.currentTime;

    // Swap to HQ if different
    if (state.hqSrc !== state.lqSrc) {
        video.src = state.hqSrc;
        video.load();
        await new Promise(r => video.addEventListener('loadeddata', r, { once: true }));
        video.currentTime = state.currentTime;
    }

    video.muted = false;
    video.loop = false;
    video.controls = true;

    // Request fullscreen
    try {
        await (video.requestFullscreen?.() || video.webkitRequestFullscreen?.() || video.webkitEnterFullscreen?.());
        video.play();
    } catch {
        // Fallback: play inline with controls
        video.play();
    }
}

/**
 * Handle fullscreen state change.
 */
function onFullscreenChange(video) {
    const isFullscreen = document.fullscreenElement === video || document.webkitFullscreenElement === video;
    if (!isFullscreen) exitFullscreen(video);
}

/**
 * Exit fullscreen, restore LQ background.
 */
function exitFullscreen(video) {
    const state = videoStates.get(video);
    if (!state) return;

    state.currentTime = video.currentTime;

    // Swap back to LQ
    if (state.hqSrc !== state.lqSrc) {
        video.src = state.lqSrc;
        video.load();
    }

    video.muted = true;
    video.loop = true;
    video.controls = false;

    video.addEventListener('loadeddata', () => {
        video.currentTime = (state.currentTime > video.duration - 1) ? 0 : state.currentTime;
        video.play().catch(() => {});
    }, { once: true });
}

/**
 * Play a video fullscreen without a background video element.
 * Used for standalone triggers (e.g., mobile inline trigger).
 * Creates a temporary video element, plays fullscreen, removes on close.
 */
async function playFullscreenVideo(src) {
    const video = document.createElement('video');
    video.src = src;
    video.controls = true;
    video.playsInline = true;
    video.autoplay = true;
    video.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;z-index:9999;background:#000;object-fit:contain;';

    document.body.appendChild(video);

    let closeBtn = null;

    const cleanup = () => {
        video.pause();
        video.remove();
        closeBtn?.remove();
    };

    // Fullscreen change handler
    const onFullscreenChange = () => {
        const isFullscreen = document.fullscreenElement === video || document.webkitFullscreenElement === video;
        if (!isFullscreen) cleanup();
    };

    video.addEventListener('fullscreenchange', onFullscreenChange);
    video.addEventListener('webkitfullscreenchange', onFullscreenChange);
    video.addEventListener('webkitendfullscreen', cleanup); // iOS
    video.addEventListener('ended', cleanup);

    // Try fullscreen, fallback to inline overlay
    try {
        await video.play();
        await (video.requestFullscreen?.() || video.webkitRequestFullscreen?.() || video.webkitEnterFullscreen?.());
    } catch {
        // Fallback: inline overlay with close button
        closeBtn = document.createElement('button');
        closeBtn.textContent = '×';
        closeBtn.setAttribute('aria-label', 'Video sluiten');
        closeBtn.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:10000;background:#fff;border:none;border-radius:50%;width:2.5rem;height:2.5rem;font-size:1.5rem;cursor:pointer;';
        closeBtn.onclick = cleanup;
        document.body.appendChild(closeBtn);
    }
}

/**
 * Cleanup.
 */
export function destroy(elements) {
    elements?.forEach(container => {
        observers.get(container)?.disconnect();
        observers.delete(container);
    });
}