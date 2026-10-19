(() => {
    const header = document.querySelector('.site-header');
    if (header && 'ResizeObserver' in window) {
        const updateHeaderHeight = () => document.documentElement.style.setProperty('--public-header-height', `${header.getBoundingClientRect().height}px`);
        updateHeaderHeight();
        new ResizeObserver(updateHeaderHeight).observe(header);
    }
    const viewer = document.querySelector('.media-viewer');
    const mediaLinks = [...document.querySelectorAll('[data-media]')];
    if (viewer && typeof viewer.showModal === 'function' && mediaLinks.length) {
        const photo = viewer.querySelector('[data-media-image]');
        const video = viewer.querySelector('[data-media-video]');
        const caption = viewer.querySelector('[data-media-caption]');
        let current = 0;
        let trigger;
        const stopVideo = () => {
            video.pause();
            video.removeAttribute('src');
            video.load();
        };
        const show = (index) => {
            current = (index + mediaLinks.length) % mediaLinks.length;
            const link = mediaLinks[current];
            const isVideo = link.dataset.media === 'video';
            const description = link.dataset.caption || link.querySelector('img')?.alt || 'JCI Carmona chapter activity';
            stopVideo();
            photo.hidden = isVideo;
            video.hidden = !isVideo;
            if (isVideo) {
                video.src = link.href;
                video.setAttribute('aria-label', description);
            } else {
                photo.src = link.href;
                photo.alt = description;
            }
            caption.textContent = description;
            viewer.querySelector('[data-media-count]').textContent = `${current + 1} / ${mediaLinks.length}`;
        };
        mediaLinks.forEach((link, index) => link.addEventListener('click', (event) => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
            event.preventDefault();
            trigger = link;
            show(index);
            viewer.showModal();
            document.documentElement.classList.add('media-viewer-open');
        }));
        viewer.querySelector('[data-media-close]').addEventListener('click', () => viewer.close());
        viewer.querySelector('[data-media-prev]').addEventListener('click', () => show(current - 1));
        viewer.querySelector('[data-media-next]').addEventListener('click', () => show(current + 1));
        viewer.addEventListener('click', (event) => {
            if (event.target === viewer) viewer.close();
        });
        viewer.addEventListener('keydown', (event) => {
            if (event.target === video) return;
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                show(current + (event.key === 'ArrowRight' ? 1 : -1));
            }
        });
        viewer.addEventListener('close', () => {
            stopVideo();
            photo.removeAttribute('src');
            document.documentElement.classList.remove('media-viewer-open');
            trigger?.focus({ preventScroll: true });
        });
    }
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    if ('IntersectionObserver' in window && !reducedMotion.matches) {
        const items = document.querySelectorAll('.section-heading, .opportunity-card, .story-card, .gallery-heading, .gallery-grid figure, .join-grid article, .project-update');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(({ target, isIntersecting }) => {
                if (!isIntersecting) return;
                target.classList.remove('reveal-pending');
                observer.unobserve(target);
            });
        }, { threshold: 0.08, rootMargin: '0px 0px 24px 0px' });
        items.forEach((item) => {
            if (item.getBoundingClientRect().top < window.innerHeight) return;
            item.classList.add('reveal-item', 'reveal-pending');
            observer.observe(item);
        });
        reducedMotion.addEventListener('change', () => {
            if (!reducedMotion.matches) return;
            observer.disconnect();
            items.forEach(item => item.classList.remove('reveal-pending'));
        });
    }
    const menu = document.querySelector('.mobile-menu');
    if (!menu) return;
    menu.addEventListener('click', (event) => {
        if (event.target.closest('a')) menu.open = false;
    });
    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target)) menu.open = false;
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menu.open) {
            menu.open = false;
            menu.querySelector('summary').focus();
        }
    });
    window.matchMedia('(min-width: 981px)').addEventListener('change', (event) => {
        if (event.matches) menu.open = false;
    });
})();
