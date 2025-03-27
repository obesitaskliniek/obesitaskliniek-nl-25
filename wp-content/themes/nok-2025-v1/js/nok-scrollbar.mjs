/*
Embedded scroll menu handler (c)2025 Klaas Leussink / hnldesign
 */

//todo: this file is queued for deletion

export const NAME = 'carousel';

export function init(elements){
  elements.forEach(element => {

    const carousel = element;
    const scrollbarThumb = element.querySelector('.fake-scrollbar-thumb');

    function updateScrollbar() {
      const scrollWidth = carousel.scrollWidth - carousel.clientWidth;
      const scrollLeft = carousel.scrollLeft;
      const thumbWidth = (carousel.clientWidth / carousel.scrollWidth) * 100;
      const thumbPosition = (scrollLeft / scrollWidth) * (100 - thumbWidth);

      scrollbarThumb.style.width = `${thumbWidth}%`;
      scrollbarThumb.style.left = `${thumbPosition}%`;
    }

    carousel.addEventListener('scroll', updateScrollbar);
    window.addEventListener('resize', updateScrollbar);
    updateScrollbar(); // Initial update


    // Drag Functionality
    let isDragging = false;
    let startX;
    let startScrollLeft;

    scrollbarThumb.addEventListener('pointerdown', (e) => {
      isDragging = true;
      startX = e.clientX;
      startScrollLeft = carousel.scrollLeft;
      document.body.style.userSelect = 'none'; // Prevents text selection
    });

    document.addEventListener('pointermove', (e) => {
      if (!isDragging) return;

      const deltaX = e.clientX - startX;
      const scrollWidth = carousel.scrollWidth - carousel.clientWidth;
      const thumbWidth = scrollbarThumb.clientWidth;
      const scrollbarWidth = scrollbarThumb.parentElement.clientWidth - thumbWidth;
      const scrollDelta = (deltaX / scrollbarWidth) * scrollWidth;

      carousel.dataset.scrollSnapping = "false";
      carousel.scrollLeft = startScrollLeft + scrollDelta;
    });

    document.addEventListener('pointerup', () => {
      isDragging = false;
      carousel.dataset.scrollSnapping = 'true';
      document.body.style.userSelect = ''; // Restore text selection
    });

  });
}