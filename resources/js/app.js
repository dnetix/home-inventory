// Re-apply the persisted theme after Livewire SPA navigations — wire:navigate
// swaps <html> attributes with the incoming document's, which has no data-theme.
document.addEventListener('livewire:navigated', () => window.applyTheme?.());

// Settings dispatches theme-changed when the user picks light/dark/system;
// persist it for the pre-paint script and apply immediately.
window.addEventListener('theme-changed', (event) => {
    localStorage.setItem('hi-theme', event.detail.theme ?? 'system');
    window.applyTheme?.();
});

// Cmd/Ctrl+K opens the Find screen from anywhere.
document.addEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        window.Livewire ? window.Livewire.navigate('/find') : window.location.assign('/find');
    }
});
