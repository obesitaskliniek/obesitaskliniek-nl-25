import StyleSheetCache from './nok-element-stylesheet-cache.mjs';

/**
 * The name for this module, used in logging and identifying dynamically loaded modules
 * @type {string}
 */
export const NAME = 'NOK Element Library';

const prefix = 'nok25';
const styleSheetLocation = '../css/nok-elements';

class AbstractClass extends HTMLElement {

  constructor() {
    super();
    this.attachShadow({ mode: "open" });

    // Define attribute properties
    if (this.constructor.observedAttributes?.length) {
      this.constructor.observedAttributes.forEach(attribute => {
        Object.defineProperty(this, attribute, {
          get: () => this.getAttribute(attribute),
          set: (value) => value ? this.setAttribute(attribute, value) : this.removeAttribute(attribute)
        });
      });
    }

    // Move children to shadow DOM
    Array.from(this.children).forEach(child => {
      // Clone each child and append it to shadowRoot
      const clonedChild = child.cloneNode(true);
      this.shadowRoot.appendChild(clonedChild);
    });
    this.innerHTML = ''; // Clear the light DOM

    // Fetch & apply styles
    this.loadStyles(`${styleSheetLocation}/${this.localName}.css`).then((success) => {
      //nothing
    }).catch((error) => {
      //console.error(error);
    });
  }

  connectedCallback() {
    console.log(`Connected component ${this.localName}`);
  }

  async loadStyles(url) {
    try {
      // Hide the component to prevent FOUC
      this.style.visibility = 'hidden';

      const sheet = await StyleSheetCache.get(url);
      this.shadowRoot.adoptedStyleSheets = [sheet];

      // Reveal component after styles are applied
      this.style.visibility = 'visible';
      console.log(`Rendering ${this.localName}`);
    } catch (error) {
      console.error(`Error loading stylesheet for ${this.localName}:`, error);
      // In case of failure, still reveal to avoid stuck invisible component
      this.style.visibility = 'visible';
    }
  }

  attributeChangedCallback(attrName, oldValue, newValue) {
    console.log(`Getting attribute: ${attrName} for ${this.localName}: ${newValue} (was ${oldValue})`);
    if (newValue !== oldValue) {
      this.setAttribute(attrName, newValue);
    }
  }

}

class NOKSquareBlockNew extends AbstractClass {
  constructor() {
    super();
  }
}

// Define the custom elements
customElements.define(`${prefix}-square-block`, NOKSquareBlockNew);