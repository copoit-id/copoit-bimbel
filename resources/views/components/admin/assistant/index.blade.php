<style>
    [x-cloak] { display: none !important; }
</style>

<div
    x-data="adminAssistant()"
    x-cloak
    class="fixed bottom-5 right-5 z-50 md:bottom-6 md:right-6"
>
    <div
        x-show="open"
        x-transition.origin.bottom.right
        class="mb-4 w-[calc(100vw-2.5rem)] max-w-sm overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
    >
        <div class="bg-slate-950 px-4 py-3 text-white">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold">Asisten Admin</p>
                    <p class="text-xs text-slate-300">Tanya data bimbel secara cepat</p>
                </div>
                <button
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-300 hover:bg-white/10 hover:text-white"
                    @click="open = false"
                    aria-label="Tutup asisten"
                >
                    <i class="ri-close-line text-lg"></i>
                </button>
            </div>
        </div>

        <div class="max-h-80 space-y-3 overflow-y-auto bg-slate-50 px-4 py-4" x-ref="messages">
            <template x-for="(message, index) in messages" :key="index">
                <div class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                    <div
                        class="max-w-[86%] whitespace-pre-line rounded-2xl px-3 py-2 text-sm leading-relaxed"
                        :class="message.role === 'user'
                            ? 'bg-blue-600 text-white rounded-br-md'
                            : 'border border-slate-200 bg-white text-slate-800 rounded-bl-md shadow-sm'"
                        x-text="message.text"
                    ></div>
                </div>
            </template>

            <div x-show="loading" class="flex justify-start">
                <div class="rounded-2xl rounded-bl-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 shadow-sm">
                    <i class="ri-loader-4-line mr-1 inline-block animate-spin"></i>
                    Mengambil data...
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200 bg-white px-4 py-3">
            <div class="mb-3 flex gap-2 overflow-x-auto pb-1">
                <template x-for="suggestion in suggestions" :key="suggestion">
                    <button
                        type="button"
                        class="shrink-0 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                        @click="ask(suggestion)"
                        x-text="suggestion"
                    ></button>
                </template>
            </div>

            <form class="flex items-end gap-2" @submit.prevent="send()">
                <textarea
                    x-model="input"
                    rows="1"
                    maxlength="500"
                    class="max-h-24 min-h-10 flex-1 resize-none rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    placeholder="Tanya: pendapatan hari ini?"
                    @keydown.enter.prevent="send()"
                ></textarea>
                <button
                    type="submit"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                    :disabled="loading || input.trim() === ''"
                    aria-label="Kirim pertanyaan"
                >
                    <i class="ri-send-plane-2-line"></i>
                </button>
            </form>
        </div>
    </div>

    <button
        type="button"
        class="group flex h-14 w-14 items-center justify-center rounded-full bg-slate-950 text-white shadow-xl transition hover:-translate-y-0.5 hover:bg-blue-600"
        @click="open = !open"
        aria-label="Buka asisten admin"
    >
        <i class="ri-sparkling-2-line text-2xl group-hover:hidden"></i>
        <i class="ri-chat-3-line hidden text-2xl group-hover:block"></i>
    </button>
</div>

<script>
    function adminAssistant() {
        return {
            open: false,
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
