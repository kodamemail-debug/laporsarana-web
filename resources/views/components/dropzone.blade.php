@props(['name', 'label' => 'Unggah foto', 'maks' => 5])
<div>
    <div class="dropzone" data-dropzone data-max-mb="{{ $maks }}">
        <input type="file" name="{{ $name }}" id="{{ $name }}" accept="image/jpeg,image/png,image/webp">
        <div class="dz-prompt">
            <span class="dz-icon"><x-icon name="upload" :size="26" /></span>
            <strong>{{ $label }}</strong>
            <span class="dz-help">Seret foto ke sini atau klik untuk memilih. JPG, PNG, atau WEBP, maksimal {{ $maks }} MB.</span>
        </div>
        <div class="dz-preview">
            <img alt="Pratinjau foto">
            <div class="dz-name"></div>
            <button type="button" class="btn btn-outline btn-sm dz-clear">Ganti foto</button>
        </div>
    </div>
    <div class="field-error dz-error" style="display: none;"></div>
    @error($name)
        <div class="field-error">{{ $message }}</div>
    @enderror
</div>
