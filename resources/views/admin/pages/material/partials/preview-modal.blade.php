@once
<div id="materialPreviewModal" class="fixed inset-0 z-50 hidden" aria-labelledby="materialPreviewTitle" role="dialog" aria-modal="true">
    <button type="button" class="absolute inset-0 bg-gray-900/70" data-material-preview-close aria-label="Tutup preview"></button>

    <div class="relative z-10 flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 p-4">
                <div class="min-w-0">
                    <p id="materialPreviewType" class="text-sm text-gray-500"></p>
                    <h2 id="materialPreviewTitle" class="truncate text-xl font-bold text-gray-800"></h2>
                </div>
                <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800" data-material-preview-close aria-label="Tutup preview">
                    <i class="ri-close-line text-2xl"></i>
                </button>
            </div>

            <div id="materialPreviewFrame" class="aspect-video bg-gray-950"></div>

            <div class="flex flex-col gap-3 border-t border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500">Preview memakai URL embed yang sama dengan tampilan user.</p>
                <a id="materialPreviewOpenLink" href="#" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                    <i class="ri-external-link-line"></i>
                    Buka URL Asli
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('materialPreviewModal');
    const frame = document.getElementById('materialPreviewFrame');
    const title = document.getElementById('materialPreviewTitle');
    const type = document.getElementById('materialPreviewType');
    const openLink = document.getElementById('materialPreviewOpenLink');

    if (!modal || !frame || !title || !type || !openLink) return;

    const clearFrame = () => {
        frame.innerHTML = '';
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        clearFrame();
    };

    const appendEmptyState = () => {
        const empty = document.createElement('div');
        empty.className = 'flex h-full items-center justify-center px-6 text-center text-white';
        empty.textContent = 'URL konten belum tersedia.';
        frame.appendChild(empty);
    };

    const appendLiveSession = (contentUrl) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex h-full items-center justify-center bg-gradient-to-br from-purple-800 to-indigo-900 px-6 text-center text-white';

        const content = document.createElement('div');
        const icon = document.createElement('i');
        icon.className = 'ri-live-line mb-4 block text-6xl';
        const heading = document.createElement('h3');
        heading.className = 'mb-2 text-2xl font-bold';
        heading.textContent = 'Live Session';
        const link = document.createElement('a');
        link.href = contentUrl;
        link.target = '_blank';
        link.rel = 'noopener';
        link.className = 'inline-flex items-center rounded-xl bg-white px-5 py-3 font-semibold text-purple-900 hover:bg-gray-100';
        link.innerHTML = '<i class="ri-video-chat-line mr-2"></i>Masuk Sesi';

        content.append(icon, heading, link);
        wrapper.appendChild(content);
        frame.appendChild(wrapper);
    };

    const appendIframe = (embedUrl, materialType) => {
        const iframe = document.createElement('iframe');
        iframe.src = embedUrl;
        iframe.className = 'h-full w-full';
        iframe.frameBorder = '0';

        if (materialType === 'video') {
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
            iframe.allowFullscreen = true;
        } else {
            iframe.classList.add('bg-white');
        }

        frame.appendChild(iframe);
    };

    const openModal = (trigger) => {
        const materialTitle = trigger.dataset.title || 'Preview Materi';
        const materialType = trigger.dataset.type || '';
        const materialTypeLabel = trigger.dataset.typeLabel || 'Materi';
        const embedUrl = trigger.dataset.embedUrl || '';
        const contentUrl = trigger.dataset.contentUrl || '#';

        clearFrame();
        title.textContent = materialTitle;
        type.textContent = materialTypeLabel;
        openLink.href = contentUrl;

        if (!contentUrl || contentUrl === '#') {
            appendEmptyState();
        } else if (materialType === 'live_session') {
            appendLiveSession(contentUrl);
        } else if (embedUrl) {
            appendIframe(embedUrl, materialType);
        } else {
            appendEmptyState();
        }

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    document.querySelectorAll('.material-preview-trigger').forEach((trigger) => {
        trigger.addEventListener('click', () => openModal(trigger));
    });

    document.querySelectorAll('[data-material-preview-close]').forEach((trigger) => {
        trigger.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
});
</script>
@endpush
@endonce
