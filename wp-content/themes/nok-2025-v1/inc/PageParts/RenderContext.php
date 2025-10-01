<?php
// inc/PageParts/RenderContext.php

namespace NOK2025\V1\PageParts;

/**
 * Determines and manages different rendering contexts for page parts and post parts
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

	public function get_context(): string {
		return $this->current_context;
	}

	public function is_frontend(): bool {
		return $this->current_context === self::CONTEXT_FRONTEND;
	}

	public function is_page_editor_preview(): bool {
		return $this->current_context === self::CONTEXT_PAGE_EDITOR_PREVIEW;
	}

	public function is_post_editor_preview(): bool {
		return $this->current_context === self::CONTEXT_POST_EDITOR_PREVIEW;
	}

	public function is_rest_embed(): bool {
		return $this->current_context === self::CONTEXT_REST_EMBED;
	}

	public function is_any_preview(): bool {
		return in_array($this->current_context, [
			self::CONTEXT_PAGE_EDITOR_PREVIEW,
			self::CONTEXT_POST_EDITOR_PREVIEW,
			self::CONTEXT_REST_EMBED
		]);
	}

	public function needs_inline_css(): bool {
		// Inline CSS needed when WordPress asset system isn't fully available
		return in_array($this->current_context, [
			self::CONTEXT_POST_EDITOR_PREVIEW,
			self::CONTEXT_REST_EMBED
		]);
	}
}