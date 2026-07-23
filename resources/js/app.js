// Re-apply the persisted theme after Livewire SPA navigations — wire:navigate
// swaps <html> attributes with the incoming document's, which has no data-theme.
document.addEventListener('livewire:navigated', () => window.applyTheme?.());

// Settings dispatches theme-changed when the user picks light/dark/system;
// persist it for the pre-paint script and apply immediately.
window.addEventListener('theme-changed', (event) => {
    localStorage.setItem('hi-theme', event.detail.theme ?? 'system');
    window.applyTheme?.();
});

// Downscale picked photos in the browser before uploading: phone photos are
// 3–8MB, but a 1600px JPEG is plenty for inventory shots. Falls back to the
// original file whenever decoding fails (the server re-checks size anyway).
window.shrinkPhoto = async (file) => {
    if (!file.type.startsWith('image/')) return file;

    try {
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const scale = Math.min(1, 1600 / Math.max(bitmap.width, bitmap.height));

        if (scale === 1 && file.size <= 1024 * 1024) {
            bitmap.close();
            return file;
        }

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));

        return blob ? new File([blob], 'photo.jpg', { type: 'image/jpeg' }) : file;
    } catch {
        return file;
    }
};

// Batch selection state, kept client-side so checking items is instant.
// `sel` is entangled (deferred) with the component's $selectedIds — the server
// receives it with the next real request (opening the Move/Status sheet).
document.addEventListener('alpine:init', () => {
    // Masks a money input with es-CO style thousand separators while typing
    // (50000 → 50.000; decimals via comma). The Livewire model receives the
    // plain machine number ("50000" / "50000.75") via a deferred set, so
    // validation and Money::fromDollars keep working untouched.
    window.Alpine.data('moneyInput', (prop) => ({
        last: '',
        init() {
            this.render(String(this.$wire.get(prop) ?? '').replace('.', ','));
        },
        onInput(event) {
            let value = this.$el.value;
            let caret = this.$el.selectionStart;

            // A typed "." is decimal intent (group dots are only ever added
            // by the mask itself) — treat it as the display comma.
            if (event.inputType === 'insertText' && event.data === '.') {
                value = `${value.slice(0, caret - 1)},${value.slice(caret)}`;
            }

            // Deleting only a group separator would be undone by the reformat,
            // trapping the caret — extend the deletion to the adjacent digit.
            if (event.inputType?.startsWith('deleteContent') && this.countSignificant(value) === this.countSignificant(this.last)) {
                if (event.inputType === 'deleteContentBackward' && caret > 0) {
                    value = value.slice(0, caret - 1) + value.slice(caret);
                    caret--;
                } else if (event.inputType === 'deleteContentForward') {
                    value = value.slice(0, caret) + value.slice(caret + 1);
                }
            }

            const significantBeforeCaret = this.countSignificant(value.slice(0, caret));

            this.render(value);
            this.moveCaretAfter(significantBeforeCaret);
        },
        render(value) {
            const [integers, ...decimals] = value.replace(/[^\d,]/g, '').split(',');
            const decimal = decimals.length ? `,${decimals.join('')}` : '';

            this.$el.value = integers.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + decimal;
            this.last = this.$el.value;
            this.$wire.set(prop, integers + decimal.replace(',', '.'), false);
        },
        countSignificant(value) {
            return (value.match(/[\d,]/g) ?? []).length;
        },
        moveCaretAfter(count) {
            let pos = 0;

            for (let seen = 0; pos < this.$el.value.length && seen < count; pos++) {
                if (/[\d,]/.test(this.$el.value[pos])) seen++;
            }

            this.$el.setSelectionRange(pos, pos);
        },
    }));

    window.Alpine.data('itemSelection', (sel) => ({
        sel,
        has(id) {
            return this.sel.includes(id);
        },
        toggle(id) {
            this.sel = this.has(id) ? this.sel.filter((other) => other !== id) : [...this.sel, id];
        },
        all(ids) {
            this.sel = [...new Set([...this.sel, ...ids])];
        },
    }));
});

// Search lives on the items list. Cmd/Ctrl+K (and the search buttons, via
// the __focusItemsSearch flag) focus the visible search input, navigating
// there first when needed.
const focusItemsSearch = () => {
    const input = [...document.querySelectorAll('[data-items-search]')].find((el) => el.offsetParent !== null);
    input?.focus();

    return Boolean(input);
};

document.addEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();

        if (!focusItemsSearch()) {
            window.__focusItemsSearch = true;
            window.Livewire ? window.Livewire.navigate('/items') : window.location.assign('/items');
        }
    }
});

document.addEventListener('livewire:navigated', () => {
    if (window.__focusItemsSearch) {
        window.__focusItemsSearch = false;
        focusItemsSearch();
    }
});
