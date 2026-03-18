/**
 * Event Gallery – Lightbox
 * event-gallery.js
 *
 * Vanilla JS, no dependencies.
 * Enqueue via wp_enqueue_script() in functions.php
 */

(function () {
    'use strict';

    const FULL_IMAGES = [];

    function init() {
        const grid = document.getElementById('egGrid');
        const lb = document.getElementById('egLb');
        if (!grid || !lb) return;

        const img = document.getElementById('egImg');
        const loader = document.getElementById('egLoader');
        const counter = document.getElementById('egCounter');
        const strip = document.getElementById('egStrip');
        const close = document.getElementById('egClose');
        const prev = document.getElementById('egPrev');
        const next = document.getElementById('egNext');
        const backdrop = document.getElementById('egBackdrop');

        let current = 0;
        const items = [...grid.querySelectorAll('.eg-item')];
        const thumbs = [...strip.querySelectorAll('.eg-strip__thumb')];

        // Collect full-size src from PHP data-* or fallback to thumbnail src
        items.forEach((item, i) => {
            const itemImg = item.querySelector('img');
            FULL_IMAGES[i] = item.dataset.full || itemImg?.src || '';
        });

        // Open
        function open(index) {
            current = Math.max(0, Math.min(index, items.length - 1));
            lb.removeAttribute('hidden');
            document.body.style.overflow = 'hidden';
            loadImage(current);
            lb.style.animation = 'none';
            requestAnimationFrame(() => {
                lb.style.animation = '';
            });
        }

        // Close
        function closeLb() {
            lb.setAttribute('hidden', '');
            document.body.style.overflow = '';
        }

        // Load image into stage
        function loadImage(index) {
            const src = FULL_IMAGES[index];

            img.classList.remove('eg-lb__img--loaded');
            loader.classList.remove('eg-lb__loader--hidden');

            const newImg = new Image();
            newImg.onload = () => {
                img.src = src;
                img.alt = items[index].querySelector('img')?.alt || '';
                img.classList.add('eg-lb__img--loaded');
                loader.classList.add('eg-lb__loader--hidden');
            };
            newImg.onerror = () => {
                loader.classList.add('eg-lb__loader--hidden');
            };
            newImg.src = src;

            // Counter
            counter.textContent = `${index + 1} / ${items.length}`;

            // Filmstrip active state
            thumbs.forEach((t, i) => {
                t.classList.toggle('eg-strip__thumb--active', i === index);
            });

            // Scroll filmstrip to active thumb
            const activeThumb = thumbs[index];
            if (activeThumb) {
                activeThumb.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            }
        }

        function goTo(index) {
            current = (index + items.length) % items.length;
            loadImage(current);
        }

        // Event listeners
        items.forEach((item, i) => {
            item.addEventListener('click', () => open(i));
        });

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => goTo(Number(thumb.dataset.index)));
        });

        close.addEventListener('click', closeLb);
        backdrop.addEventListener('click', closeLb);
        prev.addEventListener('click', () => goTo(current - 1));
        next.addEventListener('click', () => goTo(current + 1));

        // Keyboard
        document.addEventListener('keydown', (e) => {
            if (lb.hasAttribute('hidden')) return;
            if (e.key === 'Escape') closeLb();
            if (e.key === 'ArrowLeft') goTo(current - 1);
            if (e.key === 'ArrowRight') goTo(current + 1);
        });

        // Touch swipe
        let touchStartX = 0;
        lb.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
        lb.addEventListener('touchend', e => {
            const dx = e.changedTouches[0].clientX - touchStartX;
            if (Math.abs(dx) > 50) goTo(dx < 0 ? current + 1 : current - 1);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);

        console.log('event-gallery.js loaded - checking egGrid click listener...');
    } else {
        init();
    }
})();
