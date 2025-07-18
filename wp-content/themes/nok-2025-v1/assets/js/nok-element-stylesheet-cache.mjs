// stylesheet-cache.mjs
export const NAME = 'NOK Element Library Stylesheet Cache Handler';

const StyleSheetCache = (() => {
  const cache = new Map();

  return {
    async get(url) {
      // Check if stylesheet is already cached
      if (cache.has(url)) {
        const entry = cache.get(url);
        console.log(`[StyleSheetCache] Cache hit for: ${url}`);
        if (entry instanceof Promise) return await entry;
        return entry;
      }

      console.log(`[StyleSheetCache] Cache miss, fetching: ${url}`);
      const loadPromise = fetch(url)
      .then(response => {
        if (!response.ok) throw new Error(`Failed to load stylesheet: ${url}`);
        return response.text();
      })
      .then(cssText => {
        const sheet = new CSSStyleSheet();
        sheet.replaceSync(cssText);
        cache.set(url, sheet);
        return sheet;
      })
      .catch(error => {
        cache.delete(url);
        throw error;
      });

      cache.set(url, loadPromise);
      return await loadPromise;
    }

  };
})();

export default StyleSheetCache;
