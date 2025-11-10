<?php
// inc/PageParts/RenderContext.php

namespace NOK2025\V1\PageParts;

/**
 * RenderContext - Detects and manages different rendering contexts
 *
 * Determines where page parts and post parts are being rendered:
 * - Frontend (normal page view)
 * - Page editor preview (Gutenberg with embedded page parts)
 * - Post editor preview (page part custom post type editor)
 * - REST embed (page part preview via REST API)
 *
 * Used to conditionally load assets and adjust output based on context.
 *
 * @example Check rendering context in template
 * $render_context = new RenderContext();
 * if ($render_context->is_frontend()) {
 *     // Load normal assets
 * }
 *
 * @example Conditionally inline CSS for previews
 * if ($render_context->needs_inline_css()) {
 *     echo '<style>' . $css . '</style>';
 * }
 *
 * @example Check if any preview mode is active
 * if ($render_context->is_any_preview()) {
 *     // Skip frontend-only features
 * }
 *
 * @package NOK2025\V1\PageParts
 */
class RenderContext {

	public const CONTEXT_FRONTEND = 'frontend';
	public const CONTEXT_PAGE_EDITOR_PREVIEW = 'page_editor_preview';
	public const CONTEXT_POST_EDITOR_PREVIEW = 'post_editor_preview';
	public const CONTEXT_REST_EMBED = 'rest_embed';

	private string $current_context;

	public function __construct() {
		$this->current_context = $this->detect_context();
	}

	/**
	 * Detect the current rendering context
	 *
	 * @return string One of the CONTEXT_* constants
	 */
	private function detect_context(): string {
		// AJAX requests aren't editor contexts
		if (wp_doing_ajax()) {
			return self::CONTEXT_FRONTEND;
		}

		// REST API request (page part preview in page editor)
		if (wp_is_serving_rest_request()) {
			return self::CONTEXT_REST_EMBED;
		}

		// WordPress preview mode (page part editor preview)
		if (is_preview()) {
			return self::CONTEXT_POST_EDITOR_PREVIEW;
		}

		// Admin context (could be block editor with post parts)
		if (is_admin()) {
			return self::CONTEXT_PAGE_EDITOR_PREVIEW;
		}

		// Default to frontend
		return self::CONTEXT_FRONTEND;
	}

	/**
	 * Get current context name
	 *
	 * @return string One of the CONTEXT_* constants
	 */
	public function get_context(): string {
		return $this->current_context;
	}

	/**
	 * Check if rendering on frontend
	 *
	 * @return bool
	 */
	public function is_frontend(): bool {
		return $this->current_context === self::CONTEXT_FRONTEND;
	}

	/**
	 * Check if rendering in page editor preview
	 *
	 * @return bool
	 */
	public function is_page_editor_preview(): bool {
		return $this->current_context === self::CONTEXT_PAGE_EDITOR_PREVIEW;
	}

	/**
	 * Check if rendering in post editor preview
	 *
	 * @return bool
	 */
	public function is_post_editor_preview(): bool {
		return $this->current_context === self::CONTEXT_POST_EDITOR_PREVIEW;
	}

	/**
	 * Check if rendering via REST embed
	 *
	 * @return bool
	 */
	public function is_rest_embed(): bool {
		return $this->current_context === self::CONTEXT_REST_EMBED;
	}

	/**
	 * Check if rendering in any preview context
	 *
	 * @return bool
	 */
	public function is_any_preview(): bool {
		return in_array($this->current_context, [
			self::CONTEXT_PAGE_EDITOR_PREVIEW,
			self::CONTEXT_POST_EDITOR_PREVIEW,
			self::CONTEXT_REST_EMBED
		]);
	}

	/**
	 * Check if context requires inline CSS
	 *
	 * Inline CSS needed when WordPress asset system isn't fully available
	 *
	 * @return bool
	 */
	public function needs_inline_css(): bool {
		return in_array($this->current_context, [
			self::CONTEXT_POST_EDITOR_PREVIEW,
			self::CONTEXT_REST_EMBED
		]);
	}
}