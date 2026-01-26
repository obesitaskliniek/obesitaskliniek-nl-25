<?php
/**
 * PagePartSchema - Schema.org Structured Data for Page Parts
 *
 * Outputs JSON-LD structured data for page parts based on their template type.
 * Supports healthcare-specific schemas like FAQPage, MedicalOrganization,
 * and generic WebPageElement schemas.
 *
 * @package NOK2025\V1\SEO
 */

namespace NOK2025\V1\SEO;

use NOK2025\V1\PageParts\Registry;

class PagePartSchema {

	/** @var array Collected schemas to output */
	private array $schemas = [];

	/** @var Registry Page parts registry */
	private Registry $registry;

	public function __construct( Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Register WordPress hooks
	 */
	public function register_hooks(): void {
		// Collect schemas during page part rendering
		add_action( 'nok_page_part_rendered', [ $this, 'collect_schema' ], 10, 3 );

		// Output collected schemas in wp_footer
		add_action( 'wp_footer', [ $this, 'output_schemas' ], 5 );
	}

	/**
	 * Collect schema data when a page part is rendered
	 *
	 * @param int    $post_id   Page part post ID
	 * @param string $design    Design/template slug
	 * @param array  $context   Rendered context with field values
	 */
	public function collect_schema( int $post_id, string $design, array $context ): void {
		$schema = $this->build_schema( $post_id, $design, $context );
		if ( $schema ) {
			$this->schemas[] = $schema;
		}
	}

	/**
	 * Build schema based on page part template type
	 *
	 * @param int    $post_id Page part post ID
	 * @param string $design  Design/template slug
	 * @param array  $context Field values and metadata
	 * @return array|null Schema array or null if not applicable
	 */
	private function build_schema( int $post_id, string $design, array $context ): ?array {
		// Get template configuration
		$registry = $this->registry->get_registry();
		$template = $registry[ $design ] ?? null;

		if ( ! $template ) {
			return null;
		}

		// Build appropriate schema based on template type
		switch ( $design ) {
			case 'nok-faq':
			case 'nok-faq-section':
				return $this->build_faq_schema( $post_id, $context );

			case 'nok-team':
			case 'nok-team-section':
				return $this->build_medical_organization_schema();

			case 'nok-vestiging-card':
			case 'nok-vestiging-info':
				return $this->build_local_business_schema( $context );

			case 'nok-voorlichting-card':
				return $this->build_event_schema( $context );

			default:
				// No specific schema for this template type
				return null;
		}
	}

	/**
	 * Build FAQPage schema from FAQ page part content
	 *
	 * @param int   $post_id Page part post ID
	 * @param array $context Field values
	 * @return array|null FAQPage schema
	 */
	private function build_faq_schema( int $post_id, array $context ): ?array {
		// Extract FAQ items from repeater field or content
		$faq_items = $context['faq_items'] ?? $context['items'] ?? [];

		if ( empty( $faq_items ) ) {
			// Try to extract from post content
			$post = get_post( $post_id );
			if ( $post ) {
				$faq_items = $this->extract_faq_from_content( $post->post_content );
			}
		}

		if ( empty( $faq_items ) ) {
			return null;
		}

		$questions = [];
		foreach ( $faq_items as $item ) {
			$question = $item['question'] ?? $item['title'] ?? '';
			$answer   = $item['answer'] ?? $item['content'] ?? '';

			if ( ! empty( $question ) && ! empty( $answer ) ) {
				$questions[] = [
					'@type'          => 'Question',
					'name'           => wp_strip_all_tags( $question ),
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => wp_strip_all_tags( $answer ),
					],
				];
			}
		}

		if ( empty( $questions ) ) {
			return null;
		}

		return [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $questions,
		];
	}

	/**
	 * Extract FAQ items from HTML content
	 *
	 * Parses content looking for FAQ-like structures (details/summary, h3+p, etc.)
	 *
	 * @param string $content HTML content
	 * @return array Array of [question, answer] pairs
	 */
	private function extract_faq_from_content( string $content ): array {
		$faqs = [];

		// Try details/summary elements first
		if ( preg_match_all( '/<details[^>]*>.*?<summary[^>]*>(.*?)<\/summary>(.*?)<\/details>/is', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$faqs[] = [
					'question' => trim( strip_tags( $match[1] ) ),
					'answer'   => trim( strip_tags( $match[2] ) ),
				];
			}
		}

		return $faqs;
	}

	/**
	 * Build MedicalOrganization schema for team sections
	 *
	 * @return array MedicalOrganization schema
	 */
	private function build_medical_organization_schema(): array {
		return [
			'@context'         => 'https://schema.org',
			'@type'            => 'MedicalOrganization',
			'name'             => 'Nederlands Obesitas Kliniek',
			'url'              => home_url(),
			'medicalSpecialty' => [
				'@type' => 'MedicalSpecialty',
				'name'  => 'Bariatric Surgery',
			],
			'availableService' => [
				'@type'       => 'MedicalProcedure',
				'name'        => 'Bariatric Surgery',
				'procedureType' => 'Surgical',
			],
		];
	}

	/**
	 * Build LocalBusiness schema for vestiging (location) sections
	 *
	 * @param array $context Field values with address info
	 * @return array|null LocalBusiness schema
	 */
	private function build_local_business_schema( array $context ): ?array {
		$name = $context['title'] ?? '';
		if ( empty( $name ) ) {
			return null;
		}

		$schema = [
			'@context' => 'https://schema.org',
			'@type'    => 'MedicalClinic',
			'name'     => $name,
			'url'      => home_url(),
		];

		// Add address if available
		$street      = $context['street'] ?? '';
		$housenumber = $context['housenumber'] ?? '';
		$postal_code = $context['postal_code'] ?? '';
		$city        = $context['city'] ?? '';

		if ( $street || $city ) {
			$schema['address'] = [
				'@type'           => 'PostalAddress',
				'streetAddress'   => trim( "$street $housenumber" ),
				'postalCode'      => $postal_code,
				'addressLocality' => $city,
				'addressCountry'  => 'NL',
			];
		}

		// Add contact info if available
		if ( ! empty( $context['phone'] ) ) {
			$schema['telephone'] = $context['phone'];
		}

		if ( ! empty( $context['email'] ) ) {
			$schema['email'] = $context['email'];
		}

		return $schema;
	}

	/**
	 * Build Event schema for voorlichting (education session) sections
	 *
	 * @param array $context Field values with event info
	 * @return array|null Event schema
	 */
	private function build_event_schema( array $context ): ?array {
		$name = $context['title'] ?? 'Voorlichting';

		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'EducationEvent',
			'name'        => $name,
			'description' => 'Voorlichtingsbijeenkomst Nederlands Obesitas Kliniek',
			'organizer'   => [
				'@type' => 'MedicalOrganization',
				'name'  => 'Nederlands Obesitas Kliniek',
				'url'   => home_url(),
			],
		];

		// Add date/time if available
		if ( ! empty( $context['start_date'] ) ) {
			$schema['startDate'] = $context['start_date'];
		}

		if ( ! empty( $context['end_date'] ) ) {
			$schema['endDate'] = $context['end_date'];
		}

		// Add location
		if ( ! empty( $context['is_online'] ) ) {
			$schema['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
			$schema['location']            = [
				'@type' => 'VirtualLocation',
				'url'   => home_url(),
			];
		} elseif ( ! empty( $context['city'] ) ) {
			$schema['location'] = [
				'@type'   => 'Place',
				'name'    => 'NOK ' . $context['city'],
				'address' => [
					'@type'           => 'PostalAddress',
					'addressLocality' => $context['city'],
					'addressCountry'  => 'NL',
				],
			];
		}

		return $schema;
	}

	/**
	 * Output all collected schemas as JSON-LD
	 */
	public function output_schemas(): void {
		if ( empty( $this->schemas ) ) {
			return;
		}

		// Merge multiple schemas into a graph if more than one
		if ( count( $this->schemas ) === 1 ) {
			$output = $this->schemas[0];
		} else {
			$output = [
				'@context' => 'https://schema.org',
				'@graph'   => array_map( function ( $schema ) {
					unset( $schema['@context'] );
					return $schema;
				}, $this->schemas ),
			];
		}

		echo '<script type="application/ld+json">' .
			wp_json_encode( $output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) .
			'</script>' . "\n";
	}
}
