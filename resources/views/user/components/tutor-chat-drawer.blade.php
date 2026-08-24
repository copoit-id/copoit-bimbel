<div
    x-data="{
        open: false,
        mode: 'contacts',
        activeContact: null,
        isOnline: false,
        init() {
            window.addEventListener('open-tutor-chat', (event) => {
                this.openDirectory(event.detail?.contact ?? null);
            });
            window.addEventListener('message', (event) => {
                if (event.origin !== window.location.origin || event.data?.type !== 'tutor-chat-presence') return;
                this.isOnline = Boolean(event.data.online);
            });
        },
        openDirectory(contact = null) {
            this.activeContact = null;
            this.mode = 'contacts';
            this.open = true;
            if (contact) this.openRoom(contact);
        },
        openRoom(contact) {
            this.activeContact = contact;
            this.isOnline = false;
            this.mode = 'room';
            this.open = true;
        },
        close() {
            this.open = false;
            this.mode = 'contacts';
            this.activeContact = null;
        }
    }"
    x-init="init()"
>
    <button
        type="button"
        @click="openDirectory()"
        class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl text-gray-500 transition hover:bg-primary/10 hover:text-primary"
        aria-label="Buka chat tutor"
        title="Chat tutor"
    >
        <i class="ri-chat-3-line text-xl"></i>
        @if($unreadCount > 0)
            <span class="absolute -right-1 -top-1 min-w-4 rounded-full bg-red-500 px-1 text-center text-[10px] font-bold leading-4 text-white ring-2 ring-white">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>

    <template x-teleport="body">
        <div x-cloak x-show="open" @keydown.escape.window="close()" class="fixed inset-0 z-[100000] pointer-events-none">
            <button type="button" @click="close()" class="absolute inset-0 cursor-default bg-slate-900/20 backdrop-blur-[1px] pointer-events-auto" aria-label="Tutup chat"></button>
            <section
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-4 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="translate-y-4 opacity-0"
                class="pointer-events-auto fixed bottom-4 right-4 flex h-[min(680px,calc(100vh-2rem))] w-[calc(100vw-2rem)] max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/20"
            >
            <header class="flex shrink-0 items-center gap-3 border-b border-slate-100 px-4 py-3">
                <template x-if="mode === 'contacts'">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary"><i class="ri-chat-3-line text-lg"></i></span>
                </template>
                <template x-if="mode === 'room'">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-bold text-primary" x-text="activeContact?.tutor_name?.slice(0, 1).toUpperCase()"></span>
                </template>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-slate-900" x-text="mode === 'contacts' ? 'Pesan' : activeContact?.tutor_name"></p>
                    <p class="truncate text-xs" :class="mode === 'room' ? (isOnline ? 'text-emerald-600' : 'text-slate-400') : 'text-slate-500'" x-text="mode === 'contacts' ? 'Tutor dari jadwal rutin Anda' : (isOnline ? 'Online' : 'Offline')"></p>
                </div>
                <button x-show="mode === 'room'" type="button" @click="openDirectory()" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-primary" title="Daftar tutor" aria-label="Daftar tutor"><i class="ri-arrow-left-line"></i></button>
                <a x-show="mode === 'room'" :href="activeContact?.url?.replace('?embed=1', '')" target="_blank" rel="noopener noreferrer" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-primary" title="Buka halaman penuh" aria-label="Buka halaman penuh"><i class="ri-external-link-line"></i></a>
                <button type="button" @click="close()" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup chat"><i class="ri-close-line text-lg"></i></button>
            </header>

            <div x-show="mode === 'contacts'" class="min-h-0 flex-1 overflow-y-auto bg-slate-50 p-3">
                @forelse($chatContacts as $contact)
                    <button type="button" @click="openRoom(@js($contact))" class="mb-2 flex w-full items-center gap-3 rounded-xl border border-slate-100 bg-white p-3 text-left transition hover:border-primary/30 hover:bg-primary/5">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 font-bold text-primary">{{ strtoupper(mb_substr($contact['tutor_name'], 0, 1)) }}</span>
                        <span class="min-w-0 flex-1"><span class="block truncate text-sm font-bold text-slate-900">{{ $contact['tutor_name'] }}</span><span class="mt-0.5 block truncate text-xs text-slate-500">{{ $contact['schedule_title'] }}</span></span>
                        <i class="ri-arrow-right-s-line text-lg text-slate-400"></i>
                    </button>
                @empty
                    <div class="flex h-full flex-col items-center justify-center px-5 text-center text-sm text-slate-500"><i class="ri-user-search-line mb-2 text-3xl text-slate-300"></i>Belum ada tutor dari jadwal rutin yang dapat dihubungi.</div>
                @endforelse
            </div>

            <template x-if="mode === 'room' && activeContact?.url">
                <iframe :src="activeContact.url" title="Chat Tutor" class="min-h-0 flex-1 w-full bg-slate-50"></iframe>
            </template>
            </section>
        </div>
    </template>
</div>
