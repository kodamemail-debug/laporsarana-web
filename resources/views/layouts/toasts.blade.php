<div class="toasts" aria-live="polite">
    @if (session('success'))
        <div class="toast toast-success" role="status">
            <x-icon name="check" :size="18" />
            <span>{{ session('success') }}</span>
            <button type="button" class="toast-x" aria-label="Tutup">&times;</button>
        </div>
    @endif
    @if (session('error'))
        <div class="toast toast-error" role="alert">
            <x-icon name="alert" :size="18" />
            <span>{{ session('error') }}</span>
            <button type="button" class="toast-x" aria-label="Tutup">&times;</button>
        </div>
    @endif
    @if ($errors->any())
        <div class="toast toast-error" role="alert">
            <x-icon name="alert" :size="18" />
            <span>Ada isian yang perlu diperbaiki. Periksa bagian yang ditandai merah.</span>
            <button type="button" class="toast-x" aria-label="Tutup">&times;</button>
        </div>
    @endif
</div>
