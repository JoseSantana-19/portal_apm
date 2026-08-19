(() => {
    'use strict';

    const openButton = document.getElementById('profile-photo-open');
    const modal = document.getElementById('profile-photo-modal');
    const input = document.getElementById('profile-photo-input');
    const zoomInput = document.getElementById('profile-photo-zoom');
    const saveButton = document.getElementById('profile-photo-save');
    const avatar = document.getElementById('header-user-avatar');
    const stage = document.getElementById('profile-crop-stage');
    const placeholder = document.getElementById('profile-crop-placeholder');
    const cropCanvas = document.getElementById('profile-crop-canvas');
    const previewCanvas = document.getElementById('profile-preview-canvas');
    const previewFallback = document.getElementById('profile-preview-fallback');
    if (!openButton || !modal || !input || !zoomInput || !saveButton || !avatar || !stage || !cropCanvas || !previewCanvas) return;

    const cropContext = cropCanvas.getContext('2d', { alpha: false });
    const previewContext = previewCanvas.getContext('2d', { alpha: false });
    const state = { image: null, zoom: 1, offsetX: 0, offsetY: 0, dragging: false, lastX: 0, lastY: 0 };

    const notify = (message, type = 'info') => {
        document.querySelector('.toast.profile-photo-toast')?.remove();
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} profile-photo-toast show`;
        const icon = document.createElement('i');
        icon.className = `fa-solid ${type === 'success' ? 'fa-check-circle' : 'fa-circle-exclamation'}`;
        const text = document.createElement('span');
        text.textContent = message;
        toast.append(icon, text);
        document.body.appendChild(toast);
        window.setTimeout(() => {
            toast.classList.remove('show');
            window.setTimeout(() => toast.remove(), 400);
        }, 3500);
    };

    const loadImage = (file) => new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const image = new Image();
        image.onload = () => {
            URL.revokeObjectURL(url);
            resolve(image);
        };
        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('No se pudo leer la imagen seleccionada.'));
        };
        image.src = url;
    });

    const geometry = (size) => {
        const image = state.image;
        const baseScale = Math.max(size / image.naturalWidth, size / image.naturalHeight);
        const scale = baseScale * state.zoom;
        const width = image.naturalWidth * scale;
        const height = image.naturalHeight * scale;
        const factor = size / 320;
        return {
            width,
            height,
            x: (size - width) / 2 + state.offsetX * factor,
            y: (size - height) / 2 + state.offsetY * factor
        };
    };

    const clampOffsets = () => {
        if (!state.image) return;
        const baseScale = Math.max(320 / state.image.naturalWidth, 320 / state.image.naturalHeight) * state.zoom;
        const maxX = Math.max(0, (state.image.naturalWidth * baseScale - 320) / 2);
        const maxY = Math.max(0, (state.image.naturalHeight * baseScale - 320) / 2);
        state.offsetX = Math.max(-maxX, Math.min(maxX, state.offsetX));
        state.offsetY = Math.max(-maxY, Math.min(maxY, state.offsetY));
    };

    const drawInto = (context, size) => {
        context.clearRect(0, 0, size, size);
        if (!state.image) return;
        const box = geometry(size);
        context.drawImage(state.image, box.x, box.y, box.width, box.height);
    };

    const draw = () => {
        if (!state.image) return;
        clampOffsets();
        drawInto(cropContext, 320);
        drawInto(previewContext, 160);
        previewCanvas.style.display = 'block';
        if (previewFallback) previewFallback.style.display = 'none';
    };

    const showCurrentPreview = () => {
        const currentPhoto = avatar.querySelector('img');
        previewContext.clearRect(0, 0, 160, 160);
        if (currentPhoto?.complete && currentPhoto.naturalWidth > 0) {
            previewContext.drawImage(currentPhoto, 0, 0, 160, 160);
            previewCanvas.style.display = 'block';
            if (previewFallback) previewFallback.style.display = 'none';
        } else {
            previewCanvas.style.display = 'none';
            if (previewFallback) previewFallback.style.display = 'grid';
        }
    };

    const resetEditor = () => {
        state.image = null;
        state.zoom = 1;
        state.offsetX = 0;
        state.offsetY = 0;
        state.dragging = false;
        input.value = '';
        zoomInput.value = '1';
        zoomInput.disabled = true;
        saveButton.disabled = true;
        saveButton.classList.remove('is-loading');
        saveButton.innerHTML = '<i class="fa-solid fa-check"></i> Guardar foto';
        cropContext.clearRect(0, 0, 320, 320);
        stage.classList.remove('has-image', 'is-dragging');
        if (placeholder) placeholder.hidden = false;
        showCurrentPreview();
    };

    const showCurrentInEditor = () => {
        const currentPhoto = avatar.querySelector('img');
        if (!currentPhoto) return;

        const applyCurrentPhoto = () => {
            if (modal.hidden || currentPhoto.naturalWidth <= 0) return;
            state.image = currentPhoto;
            state.zoom = 1;
            state.offsetX = 0;
            state.offsetY = 0;
            zoomInput.value = '1';
            zoomInput.disabled = false;
            saveButton.disabled = false;
            stage.classList.add('has-image');
            if (placeholder) placeholder.hidden = true;
            draw();
        };

        if (currentPhoto.complete) applyCurrentPhoto();
        else currentPhoto.addEventListener('load', applyCurrentPhoto, { once: true });
    };

    const openModal = () => {
        resetEditor();
        modal.hidden = false;
        document.body.classList.add('profile-editor-open');
        showCurrentInEditor();
        window.setTimeout(() => modal.querySelector('.profile-select-button')?.focus(), 0);
    };

    const closeModal = () => {
        modal.hidden = true;
        document.body.classList.remove('profile-editor-open');
        resetEditor();
        openButton.focus();
    };

    openButton.addEventListener('click', openModal);
    modal.querySelectorAll('[data-profile-close]').forEach((button) => button.addEventListener('click', closeModal));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });

    input.addEventListener('change', async () => {
        const file = input.files?.[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            notify('Usa una imagen JPG, PNG o WEBP.', 'warning');
            input.value = '';
            return;
        }
        if (file.size > 8 * 1024 * 1024) {
            notify('La imagen original no puede superar 8 MB.', 'warning');
            input.value = '';
            return;
        }

        try {
            state.image = await loadImage(file);
            state.zoom = 1;
            state.offsetX = 0;
            state.offsetY = 0;
            zoomInput.value = '1';
            zoomInput.disabled = false;
            saveButton.disabled = false;
            stage.classList.add('has-image');
            if (placeholder) placeholder.hidden = true;
            draw();
        } catch (error) {
            notify(error.message, 'warning');
            resetEditor();
        }
    });

    zoomInput.addEventListener('input', () => {
        state.zoom = Number.parseFloat(zoomInput.value) || 1;
        draw();
    });

    stage.addEventListener('pointerdown', (event) => {
        if (!state.image) return;
        state.dragging = true;
        state.lastX = event.clientX;
        state.lastY = event.clientY;
        stage.classList.add('is-dragging');
        stage.setPointerCapture(event.pointerId);
    });

    stage.addEventListener('pointermove', (event) => {
        if (!state.dragging || !state.image) return;
        const rect = stage.getBoundingClientRect();
        state.offsetX += (event.clientX - state.lastX) * (320 / rect.width);
        state.offsetY += (event.clientY - state.lastY) * (320 / rect.height);
        state.lastX = event.clientX;
        state.lastY = event.clientY;
        draw();
    });

    const stopDragging = (event) => {
        if (!state.dragging) return;
        state.dragging = false;
        stage.classList.remove('is-dragging');
        if (stage.hasPointerCapture(event.pointerId)) stage.releasePointerCapture(event.pointerId);
    };
    stage.addEventListener('pointerup', stopDragging);
    stage.addEventListener('pointercancel', stopDragging);

    saveButton.addEventListener('click', async () => {
        if (!state.image || saveButton.disabled) return;
        saveButton.disabled = true;
        saveButton.classList.add('is-loading');
        saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

        try {
            const optimized = await new Promise((resolve) => previewCanvas.toBlob(resolve, 'image/webp', 0.8));
            if (!optimized) throw new Error('El navegador no pudo optimizar la imagen.');

            const data = new FormData();
            data.append('csrf', input.dataset.csrf || '');
            data.append('foto', optimized, 'perfil.webp');
            const response = await fetch('index.php?route=perfil_foto', {
                method: 'POST',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                body: data
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'No fue posible actualizar la foto.');

            const current = avatar.querySelector('img, .user-avatar-initials');
            const photo = document.createElement('img');
            photo.src = result.url;
            photo.alt = '';
            photo.width = 40;
            photo.height = 40;
            photo.decoding = 'async';
            if (current) current.replaceWith(photo);
            else avatar.prepend(photo);
            const hoverPhoto = document.querySelector('.profile-hover-photo');
            if (hoverPhoto) {
                const hoverImage = document.createElement('img');
                hoverImage.src = result.url;
                hoverImage.alt = '';
                hoverImage.width = 68;
                hoverImage.height = 68;
                hoverImage.decoding = 'async';
                hoverPhoto.replaceChildren(hoverImage);
            }
            closeModal();
            notify(result.message || 'Foto de perfil actualizada.', 'success');
        } catch (error) {
            notify(error.message || 'No fue posible actualizar la foto.', 'warning');
            saveButton.disabled = false;
            saveButton.classList.remove('is-loading');
            saveButton.innerHTML = '<i class="fa-solid fa-check"></i> Guardar foto';
        }
    });
})();
