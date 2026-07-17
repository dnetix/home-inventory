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

// Cmd/Ctrl+K opens the Find screen from anywhere.
document.addEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        window.Livewire ? window.Livewire.navigate('/find') : window.location.assign('/find');
    }
});
