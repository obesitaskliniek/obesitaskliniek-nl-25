/**
 * Page Part Usage Warning
 *
 * Shows warnings when users try to trash or unpublish page parts that are in use.
 * Works on both the admin list table and the Gutenberg editor.
 */
(function($, wp) {
	'use strict';

	const { ajaxUrl, nonce, i18n } = window.nokPagePartUsage || {};

	if (!ajaxUrl || !nonce) {
		console.warn('NOK Page Part Usage Warning: Missing configuration');
		return;
	}

	/**
	 * Check if a page part is in use via AJAX
	 */
	function checkUsage(postId) {
		return $.ajax({
			url: ajaxUrl,
			type: 'POST',
			data: {
				action: 'nok_check_page_part_usage',
				nonce: nonce,
				post_id: postId
			}
		});
	}

	/**
	 * Build the warning message HTML
	 */
	function buildWarningMessage(data, actionType) {
		const maxPagesToShow = 5;
		const pages = data.pages || [];
		const visiblePages = pages.slice(0, maxPagesToShow);
		const remainingCount = pages.length - maxPagesToShow;

		let pageList = '<ul style="margin: 8px 0; padding-left: 20px;">';
		visiblePages.forEach(function(title) {
			pageList += '<li>' + escapeHtml(title) + '</li>';
		});
		if (remainingCount > 0) {
			pageList += '<li><em>' + i18n.andMorePages.replace('%d', remainingCount) + '</em></li>';
		}
		pageList += '</ul>';

		const warningText = actionType === 'trash' ? i18n.trashWarning : i18n.unpublishWarning;

		return '<div style="text-align: left;">' +
			'<p><strong>' + i18n.warningMessage + '</strong></p>' +
			pageList +
			'<p style="margin-top: 12px; padding: 10px; background: #fff8e5; border-left: 4px solid #ffb900; border-radius: 2px;">' +
			warningText +
			'</p>' +
			'</div>';
	}

	/**
	 * Escape HTML to prevent XSS
	 */
	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	/**
	 * Show a modal dialog with the warning
	 */
	function showWarningModal(message, confirmText, onConfirm, onCancel) {
		// Create modal overlay
		const overlay = $('<div>', {
			css: {
				position: 'fixed',
				top: 0,
				left: 0,
				width: '100%',
				height: '100%',
				backgroundColor: 'rgba(0, 0, 0, 0.7)',
				zIndex: 100000,
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center'
			}
		});

		// Create modal content
		const modal = $('<div>', {
			css: {
				backgroundColor: '#fff',
				borderRadius: '8px',
				padding: '24px',
				maxWidth: '500px',
				width: '90%',
				maxHeight: '80vh',
				overflow: 'auto',
				boxShadow: '0 4px 20px rgba(0, 0, 0, 0.3)'
			}
		});

		// Modal header
		const header = $('<h2>', {
			text: i18n.warningTitle,
			css: {
				margin: '0 0 16px 0',
				fontSize: '18px',
				color: '#d63638',
				display: 'flex',
				alignItems: 'center',
				gap: '8px'
			}
		}).prepend('<span class="dashicons dashicons-warning" style="font-size: 24px;"></span>');

		// Modal body
		const body = $('<div>').html(message);

		// Modal footer with buttons
		const footer = $('<div>', {
			css: {
				marginTop: '20px',
				display: 'flex',
				justifyContent: 'flex-end',
				gap: '12px'
			}
		});

		const cancelBtn = $('<button>', {
			text: i18n.cancel,
			class: 'button button-secondary',
			click: function() {
				overlay.remove();
				if (onCancel) onCancel();
			}
		});

		const confirmBtn = $('<button>', {
			text: confirmText,
			class: 'button button-primary',
			css: {
				backgroundColor: '#d63638',
				borderColor: '#d63638'
			},
			click: function() {
				overlay.remove();
				if (onConfirm) onConfirm();
			}
		});

		footer.append(cancelBtn, confirmBtn);
		modal.append(header, body, footer);
		overlay.append(modal);
		$('body').append(overlay);

		// Close on overlay click
		overlay.on('click', function(e) {
			if (e.target === overlay[0]) {
				overlay.remove();
				if (onCancel) onCancel();
			}
		});

		// Close on Escape key
		$(document).on('keydown.nokUsageWarning', function(e) {
			if (e.key === 'Escape') {
				overlay.remove();
				$(document).off('keydown.nokUsageWarning');
				if (onCancel) onCancel();
			}
		});
	}

	/**
	 * Handle trash action on admin list table
	 */
	function initListTableWarnings() {
		// Intercept trash links
		$(document).on('click', '.post-type-page_part .row-actions .trash a, .post-type-page_part .row-actions .submitdelete', function(e) {
			const $link = $(this);
			const href = $link.attr('href');

			// Extract post ID from URL
			const match = href.match(/post=(\d+)/);
			if (!match) return;

			const postId = match[1];

			e.preventDefault();

			// Check usage before proceeding
			checkUsage(postId).done(function(response) {
				if (response.success && response.data.in_use) {
					const message = buildWarningMessage(response.data, 'trash');
					showWarningModal(
						message,
						i18n.confirmTrash,
						function() {
							window.location.href = href;
						}
					);
				} else {
					// Not in use, proceed normally
					window.location.href = href;
				}
			}).fail(function() {
				// On error, proceed anyway
				window.location.href = href;
			});
		});

		// Handle bulk actions
		$('#doaction, #doaction2').on('click', function(e) {
			const $select = $(this).prev('select');
			const action = $select.val();

			if (action !== 'trash') return;

			const checkedPosts = $('input[name="post[]"]:checked').map(function() {
				return $(this).val();
			}).get();

			if (checkedPosts.length === 0) return;

			e.preventDefault();

			// Check all selected posts
			const promises = checkedPosts.map(function(postId) {
				return checkUsage(postId);
			});

			$.when.apply($, promises).done(function() {
				const results = checkedPosts.length === 1 ? [arguments] : Array.from(arguments);
				const inUse = results.filter(function(result) {
					return result[0] && result[0].success && result[0].data.in_use;
				});

				if (inUse.length > 0) {
					// Combine all pages from all in-use posts
					const allPages = [];
					inUse.forEach(function(result) {
						if (result[0] && result[0].data && result[0].data.pages) {
							result[0].data.pages.forEach(function(page) {
								if (allPages.indexOf(page) === -1) {
									allPages.push(page);
								}
							});
						}
					});

					const message = buildWarningMessage({ pages: allPages, count: allPages.length }, 'trash');
					showWarningModal(
						message,
						i18n.confirmTrash,
						function() {
							$('#posts-filter').submit();
						}
					);
				} else {
					$('#posts-filter').submit();
				}
			});
		});
	}

	/**
	 * Initialize Gutenberg editor warnings
	 */
	function initEditorWarnings() {
		if (!wp || !wp.data || !wp.plugins) return;

		const { subscribe, select, dispatch } = wp.data;
		const { createElement, useState, useEffect } = wp.element;
		const { Modal, Button } = wp.components;
		const { registerPlugin } = wp.plugins;
		const { PluginPrePublishPanel } = wp.editor || wp.editPost || {};

		let previousStatus = null;
		let usageData = null;
		let isBlocking = false;

		// Get current post ID
		const getPostId = () => select('core/editor').getCurrentPostId();
		const getPostStatus = () => select('core/editor').getEditedPostAttribute('status');
		const getPostType = () => select('core/editor').getCurrentPostType();

		// Only run for page_part post type
		if (getPostType() !== 'page_part') return;

		// Fetch usage data on load
		const postId = getPostId();
		if (postId) {
			checkUsage(postId).done(function(response) {
				if (response.success && response.data.in_use) {
					usageData = response.data;
				}
			});
		}

		// Store initial status
		previousStatus = getPostStatus();

		// Subscribe to status changes
		subscribe(function() {
			const currentStatus = getPostStatus();
			const currentType = getPostType();

			if (currentType !== 'page_part') return;

			// Detect status change from publish to something else
			if (previousStatus === 'publish' && currentStatus !== 'publish' && currentStatus !== previousStatus) {
				if (usageData && usageData.in_use && !isBlocking) {
					isBlocking = true;

					// Revert the status change temporarily
					dispatch('core/editor').editPost({ status: 'publish' });

					// Show warning
					const message = buildWarningMessage(usageData, 'unpublish');
					showWarningModal(
						message,
						i18n.confirmUnpublish,
						function() {
							isBlocking = false;
							dispatch('core/editor').editPost({ status: currentStatus });
						},
						function() {
							isBlocking = false;
						}
					);
				}
			}

			previousStatus = currentStatus;
		});

		// Register pre-publish panel warning
		if (PluginPrePublishPanel) {
			registerPlugin('nok-page-part-usage-warning', {
				render: function() {
					const [data, setData] = useState(null);
					const postId = getPostId();

					useEffect(function() {
						if (postId) {
							checkUsage(postId).done(function(response) {
								if (response.success && response.data.in_use) {
									setData(response.data);
								}
							});
						}
					}, [postId]);

					if (!data || !data.in_use) return null;

					const pageList = data.pages.slice(0, 3).join(', ');
					const remaining = data.pages.length - 3;
					const pageText = remaining > 0
						? pageList + ' ' + i18n.andMorePages.replace('%d', remaining)
						: pageList;

					return createElement(
						PluginPrePublishPanel,
						{
							title: i18n.warningTitle,
							initialOpen: true
						},
						createElement('p', { style: { color: '#d63638' } },
							i18n.warningMessage + ' ' + pageText
						)
					);
				}
			});
		}
	}

	// Initialize on document ready
	$(document).ready(function() {
		// Initialize list table warnings
		if ($('body').hasClass('post-type-page_part') && $('body').hasClass('edit-php')) {
			initListTableWarnings();
		}

		// Initialize editor warnings
		if ($('body').hasClass('post-type-page_part') && ($('body').hasClass('post-php') || $('body').hasClass('post-new-php'))) {
			// Wait for Gutenberg to be ready
			if (wp && wp.domReady) {
				wp.domReady(initEditorWarnings);
			} else {
				$(window).on('load', initEditorWarnings);
			}
		}
	});

})(jQuery, window.wp);
