// Indikator sibuk global: tandai <body> selama request Livewire berlangsung
// (progress bar wire:navigate sudah ditangani Livewire sendiri).
document.addEventListener('livewire:init', () => {
    Livewire.hook('commit', ({ respond }) => {
        document.body.classList.add('lw-busy');
        respond(() => document.body.classList.remove('lw-busy'));
    });
});
