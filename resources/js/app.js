import './bootstrap';

const clamp = (value, minimum, maximum) => Math.min(Math.max(value, minimum), maximum);

class ManualInfiniteCarousel {
    constructor(root) {
        this.root = root;
        this.track = root.querySelector('[data-carousel-track]');
        this.previousButton = root.parentElement?.querySelector('[data-carousel-previous]');
        this.nextButton = root.parentElement?.querySelector('[data-carousel-next]');
        this.originalItems = this.track ? Array.from(this.track.querySelectorAll('[data-carousel-item]')) : [];
        this.index = 0;
        this.cloneCount = 0;
        this.isAnimating = false;
        this.resizeTimer = null;

        if (!this.track || this.originalItems.length < 2 || !this.previousButton || !this.nextButton) {
            return;
        }

        this.previousButton.addEventListener('click', () => this.move(-1));
        this.nextButton.addEventListener('click', () => this.move(1));
        this.track.addEventListener('transitionend', () => this.onTransitionEnd());

        this.resizeObserver = new ResizeObserver(() => {
            window.clearTimeout(this.resizeTimer);
            this.resizeTimer = window.setTimeout(() => this.rebuild(), 120);
        });
        this.resizeObserver.observe(this.root);

        this.rebuild();
    }

    getItemsPerView() {
        if (window.matchMedia('(max-width: 639px)').matches) {
            return 1;
        }

        if (window.matchMedia('(max-width: 859px)').matches) {
            return 2;
        }

        if (window.matchMedia('(max-width: 1100px)').matches) {
            return 3;
        }

        return 4;
    }

    rebuild() {
        if (!this.track) {
            return;
        }

        this.track.querySelectorAll('[data-carousel-clone]').forEach((clone) => clone.remove());
        this.cloneCount = Math.min(this.getItemsPerView(), this.originalItems.length);

        const firstItems = this.originalItems.slice(0, this.cloneCount);
        const lastItems = this.originalItems.slice(-this.cloneCount);

        lastItems
            .map((item) => this.cloneItem(item))
            .reverse()
            .forEach((clone) => this.track.prepend(clone));

        firstItems
            .map((item) => this.cloneItem(item))
            .forEach((clone) => this.track.append(clone));

        this.index = this.cloneCount;
        this.setPosition(false);
    }

    cloneItem(item) {
        const clone = item.cloneNode(true);
        clone.dataset.carouselClone = 'true';
        clone.setAttribute('aria-hidden', 'true');
        clone.querySelectorAll('a, button, input, select, textarea, [tabindex]').forEach((element) => {
            element.setAttribute('tabindex', '-1');
        });

        if (clone.matches('a')) {
            clone.setAttribute('tabindex', '-1');
        }

        return clone;
    }

    getStep() {
        const item = this.track?.querySelector('[data-carousel-item], [data-carousel-clone]');

        if (!item || !this.track) {
            return 0;
        }

        const styles = window.getComputedStyle(this.track);
        const gap = Number.parseFloat(styles.columnGap || styles.gap || '0');

        return item.getBoundingClientRect().width + gap;
    }

    setPosition(animate) {
        if (!this.track) {
            return;
        }

        this.track.style.transition = animate ? 'transform 420ms cubic-bezier(0.22, 1, 0.36, 1)' : 'none';
        this.track.style.transform = `translate3d(${-this.index * this.getStep()}px, 0, 0)`;

        if (!animate) {
            requestAnimationFrame(() => {
                if (this.track) {
                    this.track.style.transition = '';
                }
            });
        }
    }

    move(direction) {
        if (this.isAnimating) {
            return;
        }

        this.isAnimating = true;
        this.index += direction;
        this.setPosition(true);
    }

    onTransitionEnd() {
        const itemCount = this.originalItems.length;

        if (this.index >= itemCount + this.cloneCount) {
            this.index = this.cloneCount;
            this.setPosition(false);
        } else if (this.index < this.cloneCount) {
            this.index = itemCount + this.cloneCount - 1;
            this.setPosition(false);
        }

        this.isAnimating = false;
    }
}

class ProductGallery {
    constructor(root) {
        this.root = root;
        this.mainImage = root.querySelector('[data-gallery-main-image]');
        this.thumbnails = Array.from(root.querySelectorAll('[data-gallery-thumbnail]'));
        this.zoomInButton = root.querySelector('[data-gallery-zoom-in]');
        this.zoomOutButton = root.querySelector('[data-gallery-zoom-out]');
        this.resetButton = root.querySelector('[data-gallery-reset]');
        this.zoomValue = root.querySelector('[data-gallery-zoom-value]');
        this.scale = 1;
        this.baseScale = 1;

        if (!this.mainImage) {
            return;
        }

        this.thumbnails.forEach((thumbnail) => {
            thumbnail.addEventListener('click', () => this.selectThumbnail(thumbnail));
        });
        this.zoomInButton?.addEventListener('click', () => this.setScale(this.scale + 0.2));
        this.zoomOutButton?.addEventListener('click', () => this.setScale(this.scale - 0.2));
        this.resetButton?.addEventListener('click', () => this.setScale(this.baseScale));

        this.updateZoomUi();
    }

    selectThumbnail(thumbnail) {
        const thumbnailImage = thumbnail.querySelector('img');

        if (!thumbnailImage || !this.mainImage) {
            return;
        }

        this.thumbnails.forEach((item) => {
            const active = item === thumbnail;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        this.baseScale = Number.parseFloat(thumbnail.dataset.galleryScale || '1');
        this.scale = this.baseScale;
        this.mainImage.classList.add('is-switching');

        window.setTimeout(() => {
            if (!this.mainImage) {
                return;
            }

            this.mainImage.src = thumbnailImage.currentSrc || thumbnailImage.src;
            this.mainImage.alt = thumbnailImage.alt;
            this.applyScale();
            this.mainImage.classList.remove('is-switching');
        }, 120);
    }

    setScale(scale) {
        this.scale = clamp(scale, 1, 2.6);
        this.applyScale();
    }

    applyScale() {
        if (!this.mainImage) {
            return;
        }

        this.mainImage.style.transform = `scale(${this.scale})`;
        this.updateZoomUi();
    }

    updateZoomUi() {
        if (this.zoomValue) {
            this.zoomValue.textContent = `${Math.round(this.scale * 100)}%`;
        }

        if (this.zoomOutButton) {
            this.zoomOutButton.disabled = this.scale <= 1;
        }

        if (this.zoomInButton) {
            this.zoomInButton.disabled = this.scale >= 2.6;
        }
    }
}

const initializePasswordFields = () => {
    document.querySelectorAll('[data-password-field]').forEach((field) => {
        if (field.dataset.passwordReady === 'true') {
            return;
        }

        const input = field.querySelector('input');
        const button = field.querySelector('[data-password-toggle]');

        if (!input || !button) {
            return;
        }

        field.dataset.passwordReady = 'true';
        button.addEventListener('click', () => {
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-pressed', showing ? 'false' : 'true');
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            field.classList.toggle('is-password-visible', !showing);
        });
    });
};

const initializeDetailsMenus = () => {
    if (document.documentElement.dataset.detailsMenusReady === 'true') {
        return;
    }

    document.documentElement.dataset.detailsMenusReady = 'true';
    document.addEventListener('click', (event) => {
        document.querySelectorAll('.store-account-menu[open], .store-mobile-menu[open]').forEach((details) => {
            if (!details.contains(event.target)) {
                details.removeAttribute('open');
            }
        });
    });
};

const initializeStorefrontUi = () => {
    document.querySelectorAll('[data-store-carousel], [data-brand-carousel]').forEach((carousel) => {
        if (carousel.dataset.carouselReady === 'true') {
            return;
        }

        carousel.dataset.carouselReady = 'true';
        new ManualInfiniteCarousel(carousel);
    });

    document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
        if (gallery.dataset.galleryReady === 'true') {
            return;
        }

        gallery.dataset.galleryReady = 'true';
        new ProductGallery(gallery);
    });

    initializePasswordFields();
    initializeDetailsMenus();
};

document.addEventListener('DOMContentLoaded', initializeStorefrontUi);
document.addEventListener('livewire:navigated', initializeStorefrontUi);
document.addEventListener('livewire:initialized', initializeStorefrontUi);
