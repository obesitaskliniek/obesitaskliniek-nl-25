/**
 * NOK Video Module
 *
 * Handles background video autoplay and fullscreen HQ playback.
 * Only one background video plays at a time (most visible takes priority).
 *
 * Data attributes:
 * - data-video-lq: Low quality source (background, muted loop)
 * - data-video-hq: High quality source (fullscreen, with audio)
 * - data-video-start: Start time offset in seconds (e.g., "2.5")
 *
 * Behavior:
 * - Background: muted loop, lazy-loaded, visibility-based play/pause
 * - Only ONE background video plays at a time (most visible wins)
 * - Stability: playing video won't be interrupted unless another has 10%+ higher visibility
 * - Click play button: swap to HQ, unmute, enter fullscreen
 * - Exit fullscreen: swap back to LQ, mute, resume loop
 */
export const NAME = 'video';

const videoStates = new WeakMap();       // Per-video config (lqSrc, hqSrc, startTime)
const visibilityMap = new Map();         // video → intersection ratio
let activeVideo = null;                  // Currently playing background video
let fullscreenVideo = null;              // Video in fullscreen mode (takes priority)
let enteringFullscreen = false;          // Guard against premature exit during fullscreen entry
let sharedObserver = null;               // Single shared IntersectionObserver

const VISIBILITY_THRESHOLD = 0.3;
const HYSTERESIS = 0.1;                  // 10% higher visibility required to switch

/**
 * Get or create the shared IntersectionObserver.
 * Uses multiple thresholds for granular visibility updates.
 */
function getSharedObserver() {
    if (!sharedObserver) {
        sharedObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const video = entry.target.querySelector('video');
                if (video) {
                    visibilityMap.set(video, entry.intersectionRatio);
                }
            });
            updateActiveVideo();
        }, {
            threshold: [0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.75, 1.0],
            rootMargin: '100px'
        });
    }
    return sharedObserver;
}

/**
 * Determine which video should be playing and update playback state.
 * Implements stability: current video won't be interrupted unless
 * another video has significantly higher visibility (HYSTERESIS).
 */
function updateActiveVideo() {
    // Fullscreen video takes exclusive priority
    if (fullscreenVideo) return;

    // Find video with highest visibility above threshold
    // When tied, prefer videos OTHER than the current (give "new arrival" priority)
    let bestVideo = null;
    let bestRatio = VISIBILITY_THRESHOLD;

    for (const [video, ratio] of visibilityMap) {
        if (ratio >= VISIBILITY_THRESHOLD) {
            // Select if: higher visibility OR same visibility but not current video
            if (ratio > bestRatio || (ratio === bestRatio && video !== activeVideo)) {
                bestVideo = video;
                bestRatio = ratio;
            }
        }
    }

    const currentRatio = activeVideo ? visibilityMap.get(activeVideo) ?? 0 : 0;

    // If current video is still the best, keep it (but ensure it's playing)
    if (activeVideo && bestVideo === activeVideo) {
        activeVideo.play().catch(() => {}); // Resume if paused (e.g., after fullscreen exit)
        return;
    }

    // Best video is different from current
    // Apply hysteresis only when current video is NOT at full visibility
    // (if current is at 100%, let the new video take over immediately when it's also at 100%)
    if (activeVideo && currentRatio >= VISIBILITY_THRESHOLD && currentRatio < 1.0) {
        const difference = bestRatio - currentRatio;
        if (!bestVideo || difference <= HYSTERESIS) {
            activeVideo.play().catch(() => {}); // Resume if paused
            return; // Keep current video playing
        }
    }

    // Switch to new active video
    if (activeVideo && activeVideo !== bestVideo) {
        activeVideo.pause();
    }

    activeVideo = bestVideo;
    if (activeVideo) {
        activeVideo.play().catch(() => {});
    }
}

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

        // Get sources and start time from data attributes
        const lqSrc = container.dataset.videoLq || video.currentSrc || video.querySelector('source')?.src;
        const hqSrc = container.dataset.videoHq || lqSrc;
        const startTime = parseFloat(container.dataset.videoStart) || 0;

        // Store state
        videoStates.set(video, { lqSrc, hqSrc, startTime, currentTime: startTime });

        // Set up background playback
        video.muted = true;
        video.controls = false;
        video.playsInline = true;
        video.preload = 'metadata'; // Ensures first frame renders without playing

        // Handle looping: use native loop only if no start offset
        if (startTime > 0) {
            video.loop = false;
            // Manual loop back to start time
            video.addEventListener('ended', () => {
                video.currentTime = startTime;
                video.play().catch(() => {});
            });
        } else {
            video.loop = true;
        }

        // Set initial frame position (renders first frame without playing)
        const seekToStart = () => { video.currentTime = startTime; };
        if (video.readyState >= 1) {
            // Metadata already loaded
            seekToStart();
        } else {
            // Wait for metadata, then seek
            video.addEventListener('loadedmetadata', seekToStart, { once: true });
            video.load();
        }

        // Register with shared observer for visibility-based playback
        getSharedObserver().observe(container);

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

    // Guard against premature exit during async operations
    enteringFullscreen = true;

    // Mark as fullscreen (takes priority over all background videos)
    fullscreenVideo = video;

    // Pause the previous active background video if different
    if (activeVideo && activeVideo !== video) {
        activeVideo.pause();
    }

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
        await video.play();
    } catch {
        // Fallback: play inline with controls
        video.play().catch(() => {});
    } finally {
        enteringFullscreen = false;
    }
}

/**
 * Handle fullscreen state change.
 */
function onFullscreenChange(video) {
    // Don't react if we're in the middle of entering fullscreen
    if (enteringFullscreen) return;

    const isFullscreen = document.fullscreenElement === video || document.webkitFullscreenElement === video;
    if (!isFullscreen) exitFullscreen(video);
}

/**
 * Exit fullscreen, restore LQ background.
 */
function exitFullscreen(video) {
    const state = videoStates.get(video);
    if (!state) return;

    // Only exit if this video is actually the fullscreen video
    if (fullscreenVideo !== video) return;

    // Clear fullscreen state
    fullscreenVideo = null;

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
        // If near end, reset to start time; otherwise resume from current position
        video.currentTime = (state.currentTime > video.duration - 1) ? state.startTime : state.currentTime;
        // Don't auto-play here; let updateActiveVideo decide which video should play
        updateActiveVideo();
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
        const video = container.querySelector('video');
        if (video) {
            visibilityMap.delete(video);
            if (activeVideo === video) activeVideo = null;
            if (fullscreenVideo === video) fullscreenVideo = null;
        }
        sharedObserver?.unobserve(container);
    });
}