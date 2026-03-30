/**
 * @fileoverview FAQ Search - Client-side filtering for accordion FAQ view
 * @module nok-faq-search
 * @version 1.0.0
 *
 * @description
 * Filters FAQ accordion items by matching user input against pre-lowercased
 * data-search-title and data-search-excerpt attributes. Hides non-matching
 * items and their parent group headings when all items in a group are hidden.
 *
 * @example
 * <div data-requires="./nok-faq-search.mjs" id="faq-accordion-content">
 *   <div class="faq-group">
 *     <h2>Group</h2>
 *     <div>
 *       <details data-search-title="lowered title" data-search-excerpt="lowered excerpt">
 *         ...
 *       </details>
 *     </div>
 *   </div>
 * </div>
 */

import {logger} from './domule/core.log.min.mjs';

export const NAME = 'faq-search';

/**
 * Initializes FAQ search filtering on container elements.
 *
 * @param {NodeList|HTMLElement[]} elements - Container elements (the #faq-accordion-content div)
 */
export function init(elements) {
  elements.forEach(container => {
    const searchInput = document.getElementById('faq-search');
    if (!searchInput) return;

    const groups     = container.querySelectorAll('.faq-group');
    const allDetails = container.querySelectorAll('details[data-search-title]');
    const resultCount = document.getElementById('faq-result-count');
    const totalCount = allDetails.length;

    /**
     * Tests whether an item matches all query words.
     * Each word must appear in title or excerpt (AND logic).
     * Partial word matching via includes() — "vergoe" matches "vergoeding".
     *
     * @param {string} title - Pre-lowercased title
     * @param {string} excerpt - Pre-lowercased excerpt
     * @param {string[]} words - Query split into individual words
     * @returns {boolean}
     */
    const matchesAllWords = (title, excerpt, words) => {
      const text = title + ' ' + excerpt;
      return words.every(word => text.includes(word));
    };

    searchInput.addEventListener('input', () => {
      const fullQuery = searchInput.value.toLowerCase().trim();

      if (!fullQuery) {
        // Show all
        allDetails.forEach(d => d.style.display = '');
        groups.forEach(g => g.style.display = '');
        if (resultCount) resultCount.textContent = `${totalCount} vragen`;
        return;
      }

      const words = fullQuery.split(/\s+/).filter(w => w.length > 0);
      let visibleCount = 0;

      groups.forEach(group => {
        const details = group.querySelectorAll('details[data-search-title]');
        let groupVisible = false;

        details.forEach(detail => {
          const title   = detail.dataset.searchTitle || '';
          const excerpt = detail.dataset.searchExcerpt || '';
          const matches = matchesAllWords(title, excerpt, words);

          detail.style.display = matches ? '' : 'none';
          if (matches) {
            groupVisible = true;
            visibleCount++;
          }
        });

        group.style.display = groupVisible ? '' : 'none';
      });

      if (resultCount) {
        resultCount.textContent = visibleCount === 0
          ? 'Geen vragen gevonden'
          : `${visibleCount} van ${totalCount} vragen`;
      }
    });

    logger.info(NAME, `Initialized with ${totalCount} searchable items`);
  });
}
