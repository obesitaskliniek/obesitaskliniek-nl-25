<?php

namespace NOK2025\V1;

use WP_Block_Editor_Context;
use WP_Block_Type_Registry;

/**
 * AllowedEditorBlocks - Controls which blocks are available in the editor per post type
 *
 * Maintains a strict allowlist of Gutenberg blocks available to editors.
 * This prevents content authors from using unsupported or off-brand blocks
 * (e.g. core/video when a custom NOK video block exists).
 *
 * NOK custom blocks (nok2025/*) are auto-discovered from the block registry.
 * New blocks added to blocks/ are automatically available — no need to update
 * this file unless you want to restrict them from specific post types.
 *
 * To modify available blocks:
 * - Core blocks: edit get_common_core_blocks()
 * - NOK blocks per post type: edit get_post_type_config()
 *
 * @package NOK2025\V1
 */
class AllowedEditorBlocks {

	/** @var string Block namespace prefix for auto-discovery */
	private const NOK_NAMESPACE = 'nok2025/';

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'allowed_block_types_all', [ $this, 'filter_allowed_blocks' ], 10, 2 );
	}

	/**
	 * Filter allowed block types based on post type
	 *
	 * @param bool|string[] $allowed_block_types Default allowed block types (true = all)
	 * @param WP_Block_Editor_Context $editor_context Current editor context
	 *
	 * @return bool|string[] Filtered list of allowed block type names, or true for all
	 */
	public function filter_allowed_blocks( $allowed_block_types, $editor_context ) {
		$post = $editor_context->post ?? null;
		if ( ! $post ) {
			return $allowed_block_types;
		}

		$post_type = $post->post_type ?? null;
		if ( ! $post_type ) {
			return $allowed_block_types;
		}

		$config = $this->get_post_type_config( $post_type );

		// null = no opinion for this post type, allow all
		if ( $config === null ) {
			return $allowed_block_types;
		}

		return $this->resolve_blocks( $config );
	}

	/**
	 * Get block configuration for a specific post type
	 *
	 * Returns a config array describing what this post type allows:
	 * - 'core'     => bool    — include common core blocks
	 * - 'nok'      => 'all'   — include all NOK blocks (auto-discovered)
	 *              => string[] — include only these NOK blocks (short names without prefix)
	 * - 'extra'    => string[] — additional specific blocks beyond core/nok sets
	 *
	 * Returns null to allow all blocks (no restriction).
	 *
	 * @param string $post_type The post type slug
	 *
	 * @return array{core: bool, nok: 'all'|string[], extra?: string[]}|null
	 */
	private function get_post_type_config( string $post_type ): ?array {
		$configs = [
			// Pages: admins get full access; editors only get page-part embeds
			'page'            => current_user_can( 'manage_options' )
				? [ 'core' => true, 'nok' => 'all' ]
				: [ 'core' => false, 'nok' => [ 'embed-nok-page-part' ] ],

			// Page parts: core content blocks + all NOK blocks
			'page_part'       => [ 'core' => true, 'nok' => 'all' ],

			// Template layouts: all NOK blocks for template composition
			'template_layout' => [ 'core' => false, 'nok' => 'all' ],

			// Kennisbank: rich content + all NOK blocks
			'kennisbank'      => [ 'core' => true, 'nok' => 'all' ],

			// Regular posts (ervaringen, blogs): rich content + all NOK blocks
			'post'            => [ 'core' => true, 'nok' => 'all' ],

			// Vestiging / Regio: structural (template layout handles rendering)
			'vestiging'       => [ 'core' => false, 'nok' => [ 'embed-nok-page-part' ] ],
			'regio'           => [ 'core' => false, 'nok' => [ 'embed-nok-page-part' ] ],

			// Voorlichting: synced from HubSpot, minimal editing
			'voorlichting'    => [ 'core' => true, 'nok' => [] ],
		];

		return $configs[ $post_type ] ?? null;
	}

	/**
	 * Resolve a post type config into a flat array of allowed block names
	 *
	 * @param array{core: bool, nok: 'all'|string[], extra?: string[]} $config
	 *
	 * @return string[] Allowed block names
	 */
	private function resolve_blocks( array $config ): array {
		$blocks = [];

		// Core blocks
		if ( $config['core'] ) {
			$blocks = $this->get_common_core_blocks();
		}

		// NOK blocks
		$nok = $config['nok'];
		if ( $nok === 'all' ) {
			$blocks = array_merge( $blocks, $this->get_all_nok_blocks() );
		} elseif ( is_array( $nok ) ) {
			foreach ( $nok as $short_name ) {
				$blocks[] = self::NOK_NAMESPACE . $short_name;
			}
		}

		// Extra one-off blocks
		if ( ! empty( $config['extra'] ) ) {
			$blocks = array_merge( $blocks, $config['extra'] );
		}

		// Empty allowlist would lock out the editor entirely
		if ( empty( $blocks ) ) {
			return true;
		}

		return array_values( array_unique( $blocks ) );
	}

	/**
	 * Auto-discover all registered NOK custom blocks
	 *
	 * Queries the WordPress block type registry for all blocks in the
	 * nok2025/ namespace. Adding a new block to blocks/ automatically
	 * includes it here — no manual registration needed.
	 *
	 * @return string[] All registered nok2025/* block names
	 */
	private function get_all_nok_blocks(): array {
		$registry   = WP_Block_Type_Registry::get_instance();
		$nok_blocks = [];

		foreach ( array_keys( $registry->get_all_registered() ) as $name ) {
			if ( str_starts_with( $name, self::NOK_NAMESPACE ) ) {
				$nok_blocks[] = $name;
			}
		}

		return $nok_blocks;
	}

	/**
	 * Get the common set of core WordPress blocks for rich content editing
	 *
	 * Blocks NOT in this list (e.g. core/video, core/html, core/code) are
	 * intentionally excluded. Edit this method to change the core allowlist.
	 *
	 * @return string[] Array of core block names
	 */
	private function get_common_core_blocks(): array {
		return [
			// Text
			'core/paragraph',
			'core/heading',
			'core/list',
			'core/list-item',
			'core/quote',
			'core/pullquote',
			'core/table',

			// Media
			'core/image',
			'core/gallery',

			// Layout
			'core/separator',
			'core/spacer',
			'core/group',
			'core/columns',
			'core/column',
			'core/buttons',
			'core/button',
		];
	}
}
