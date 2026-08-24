<style>
    [x-cloak] { display: none !important; }
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<div
    x-data="adminAssistant()"
    x-cloak
    class="fixed bottom-5 right-5 z-50 md:bottom-6 md:right-6"
>
    <!-- Chat Assistant Dialog Panel -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 translate-y-8 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-250 transform"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 scale-95"
        class="mb-4 flex h-[500px] max-h-[calc(100vh-8rem)] w-[calc(100vw-2.5rem)] max-w-sm flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xl"
    >
        <!-- Header with Dark/Indigo Gradient -->
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 px-4 py-3.5 text-white border-b border-white/5 flex-shrink-0">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 text-indigo-400">
                        <i class="ri-sparkling-2-line text-lg animate-pulse"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold tracking-wide">Asisten Admin</p>
                        <p class="text-[10px] text-slate-300">Tanya data bimbel secara instan</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-white/10 hover:text-white"
                    @click="open = false"
                    aria-label="Tutup asisten"
                >
                    <i class="ri-close-line text-lg"></i>
                </button>
            </div>
        </div>

        <!-- Message History (Scrollable Area) -->
        <div class="flex-1 space-y-3 overflow-y-auto bg-slate-50/60 px-4 py-4" x-ref="messages">
            <template x-for="(message, index) in messages" :key="index">
                <div class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                    <div
                        class="max-w-[86%] whitespace-pre-line rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed shadow-sm"
                        :class="message.role === 'user'
                            ? 'bg-gradient-to-tr from-blue-600 to-indigo-600 text-white rounded-tr-none'
                            : 'border border-slate-100 bg-white text-slate-800 rounded-tl-none'"
                        x-text="message.text"
                    ></div>
                </div>
            </template>

            <!-- Loading Indicator -->
            <div x-show="loading" class="flex justify-start">
                <div class="rounded-2xl rounded-tl-none border border-slate-100 bg-white px-3.5 py-2.5 text-sm text-slate-500 shadow-sm flex items-center gap-2">
                    <i class="ri-loader-4-line animate-spin text-indigo-500 text-base"></i>
                    <span>Mengambil data...</span>
                </div>
            </div>
        </div>

        <!-- Input Area and Suggestions (Sticky Bottom) -->
        <div class="border-t border-slate-100 bg-white px-4 py-3 flex-shrink-0">
            <!-- Suggestion Pills -->
            <div class="mb-3 flex gap-2 overflow-x-auto pb-1.5 no-scrollbar">
                <template x-for="suggestion in suggestions" :key="suggestion">
                    <button
                        type="button"
                        class="shrink-0 rounded-full border border-slate-200/80 bg-slate-50/80 px-3.5 py-1.5 text-xs font-medium text-slate-600 transition-all hover:border-indigo-200 hover:bg-indigo-50/50 hover:text-indigo-600 active:scale-95"
                        @click="ask(suggestion)"
                        x-text="suggestion"
                    ></button>
                </template>
            </div>

            <!-- Form -->
            <form class="flex items-end gap-2" @submit.prevent="send()">
                <textarea
                    x-model="input"
                    rows="1"
                    maxlength="500"
                    class="max-h-24 min-h-10 flex-1 resize-none rounded-xl border border-slate-200/80 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 transition-all"
                    placeholder="Tanya: pendapatan hari ini?"
                    @keydown.enter.prevent="send()"
                ></textarea>
                <button
                    type="submit"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white shadow-md shadow-indigo-500/10 hover:from-blue-700 hover:to-indigo-700 transition-all active:scale-95 disabled:cursor-not-allowed disabled:from-slate-200 disabled:to-slate-300 disabled:text-slate-400 disabled:shadow-none"
                    :disabled="loading || input.trim() === ''"
                    aria-label="Kirim pertanyaan"
                >
                    <i class="ri-send-plane-2-line text-base"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Floating Trigger Button -->
    <div class="relative flex justify-end group">
        <!-- Outer Glow Ring -->
        <div
            class="absolute -inset-1 rounded-full bg-gradient-to-tr from-blue-500 via-indigo-500 to-violet-500 opacity-40 blur-md transition-all duration-300 pointer-events-none"
            :class="open ? 'opacity-0 scale-75' : 'opacity-40 scale-100 group-hover:opacity-75 group-hover:scale-110'"
        ></div>

        <!-- Outer Pulse Ring -->
        <span
            class="absolute -inset-2 animate-pulse rounded-full border border-indigo-500/20 bg-indigo-500/5 pointer-events-none"
            x-show="!open"
        ></span>

        <button
            type="button"
            class="relative flex h-14 w-14 items-center justify-center overflow-hidden rounded-full bg-slate-950 text-white shadow-2xl transition-all duration-300 hover:scale-110 hover:-translate-y-1 active:scale-95"
            @click="open = !open"
            @mouseenter="hovered = true"
            @mouseleave="hovered = false"
            aria-label="Buka asisten admin"
        >
            <!-- Background Gradient Layer -->
            <div class="absolute inset-0 bg-gradient-to-tr from-slate-900 via-slate-950 to-indigo-950 transition-opacity duration-300 group-hover:opacity-0"></div>
            <!-- Hover Gradient Layer -->
            <div class="absolute inset-0 bg-gradient-to-tr from-blue-600 via-indigo-600 to-violet-600 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

            <!-- Content Container (Icons) -->
            <div class="relative z-10 flex h-6 w-6 items-center justify-center">
                <!-- Close icon (open state) -->
                <i
                    class="ri-close-line text-2xl transition-all duration-300 absolute"
                    :class="open ? 'opacity-100 rotate-0 scale-100' : 'opacity-0 -rotate-90 scale-75 pointer-events-none'"
                ></i>
                <!-- Sparkles (closed state, not hovered) -->
                <i
                    class="ri-sparkling-2-line text-2xl transition-all duration-300 absolute"
                    :class="(!open && !hovered) ? 'opacity-100 rotate-0 scale-100' : 'opacity-0 rotate-45 scale-75 pointer-events-none'"
                ></i>
                <!-- Chat (closed state, hovered) -->
                <i
                    class="ri-chat-3-line text-2xl transition-all duration-300 absolute"
                    :class="(!open && hovered) ? 'opacity-100 rotate-0 scale-100' : 'opacity-0 -rotate-45 scale-75 pointer-events-none'"
                ></i>
            </div>
        </button>
    </div>
</div>

<script>
    function adminAssistant() {
        return {
            open: false,
            hovered: false,
            input: '',
            loading: false,
            suggestions: [
                'Pendapatan hari ini dibanding kemarin',
                'Ada berapa pembayaran pending?',
                'Berapa peserta yang daftar hari ini?',
                'Jumlah tryout dikerjakan hari ini',
                'Pengajuan paket pending ada berapa?',
                'Ringkasan paket aktif',
                'Materi aktif ada berapa?',
                'Top paket bulan ini',
            ],
            messages: [
                {
                    role: 'assistant',
                    text: 'Halo, aku bisa bantu cek data bimbel. Pilih rekomendasi atau tulis pertanyaan sendiri.',
                },
            ],
            ask(question) {
                this.input = question;
                this.send();
            },
            async send() {
                const message = this.input.trim();
                if (!message || this.loading) return;

                this.messages.push({ role: 'user', text: message });
                this.input = '';
                this.loading = true;
                this.scrollToBottom();

                try {
                    const response = await fetch('{{ route('admin.assistant.chat') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ message }),
                    });

                    if (!response.ok) {
                        throw new Error('Request failed');
                    }

                    const data = await response.json();
                    this.messages.push({
                        role: 'assistant',
                        text: data.answer || 'Data belum tersedia.',
                    });

                    if (Array.isArray(data.suggestions) && data.suggestions.length > 0) {
                        this.suggestions = data.suggestions.slice(0, 8);
                    }
                } catch (error) {
                    this.messages.push({
                        role: 'assistant',
                        text: 'Maaf, asisten belum bisa mengambil data. Coba refresh halaman atau ulangi pertanyaannya.',
                    });
                } finally {
                    this.loading = false;
                    this.scrollToBottom();
                }
            },
            scrollToBottom() {
                this.$nextTick(() => {
                    if (this.$refs.messages) {
                        this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
                    }
                });
            },
        };
    }
</script>

