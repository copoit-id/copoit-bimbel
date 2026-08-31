@if($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="bg-white rounded-lg border border-gray-200">
    <form action="{{ $action }}" method="POST">
        @csrf
        @if($method !== 'POST')
            @method($method)
        @endif

        <div class="p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nama Rombel <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $studyGroup?->name) }}" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    placeholder="Contoh: UTBK A - Senin Malam">
            </div>

            <div>
                <label for="tentor_id" class="block text-sm font-medium text-gray-700 mb-2">Tutor Default</label>
                <select id="tentor_id" name="tentor_id"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">Pilih Tutor</option>
                    @foreach($tentors as $tentor)
                        <option value="{{ $tentor->id }}" @selected(old('tentor_id', $studyGroup?->tentor_id) == $tentor->id)>
                            {{ $tentor->name }}{{ $tentor->expertise ? ' - ' . $tentor->expertise : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                <textarea id="description" name="description" rows="3"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                    placeholder="Catatan internal rombel">{{ old('description', $studyGroup?->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Peserta Rombel</label>
                @php
                    $selectedUserIds = collect(old('user_ids', $selectedUserIds ?? []))->map(fn ($id) => (int) $id)->all();
                @endphp
                <div class="mb-3 grid gap-3 md:grid-cols-2">
                    <div class="relative">
                        <i class="ri-search-line pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                        <input type="search" placeholder="Cari nama peserta" data-rombel-participant-search
                            class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div class="relative">
                        <i class="ri-school-line pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400"></i>
                        <input type="search" placeholder="Cari nama sekolah" data-rombel-school-search
                            class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    </div>
                </div>
                <div class="max-h-72 overflow-auto rounded-lg border border-gray-200" data-rombel-participant-list>
                    @forelse($users as $user)
                        <label class="flex items-start gap-3 border-b border-gray-100 px-4 py-3 last:border-b-0 hover:bg-gray-50" data-rombel-participant data-name="{{ strtolower($user->name) }}" data-school="{{ strtolower($user->origin_institution ?? '') }}">
                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                @checked(in_array((int) $user->id, $selectedUserIds, true))
                                class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                            <span>
                                <span class="block text-sm font-medium text-gray-900">{{ $user->name }}</span>
                                <span class="block text-xs text-gray-500">{{ $user->email }}</span>
                                @if($user->origin_institution)
                                    <span class="block text-xs text-gray-500">{{ $user->origin_institution }}</span>
                                @endif
                            </span>
                        </label>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-gray-500">Belum ada peserta.</div>
                    @endforelse
                </div>
                <p class="mt-2 hidden text-sm text-gray-500" data-rombel-empty-search>Tidak ada peserta yang sesuai.</p>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $studyGroup?->is_active ?? true)) class="rounded border-gray-300 text-primary focus:ring-primary">
                Rombel aktif
            </label>
        </div>

        <div class="flex items-center justify-end px-6 py-5 space-x-2 border-t border-gray-200">
            <a href="{{ route('admin.study-groups.index') }}"
                class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary/20 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">
                Batal
            </a>
            <button type="submit"
                class="text-white bg-primary hover:bg-primary/90 focus:ring-4 focus:outline-none focus:ring-primary/20 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                {{ $buttonLabel }}
            </button>
        </div>
    </form>
</div>

@once
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-rombel-participant-list]').forEach((list) => {
        const section = list.parentElement;
        const nameField = section.querySelector('[data-rombel-participant-search]');
        const schoolField = section.querySelector('[data-rombel-school-search]');
        const emptyMessage = section.querySelector('[data-rombel-empty-search]');
        const participants = [...list.querySelectorAll('[data-rombel-participant]')];
        const filter = () => {
            const name = nameField.value.trim().toLocaleLowerCase('id-ID');
            const school = schoolField.value.trim().toLocaleLowerCase('id-ID');
            let visible = 0;
            participants.forEach((participant) => {
                const matches = participant.dataset.name.includes(name) && participant.dataset.school.includes(school);
                participant.classList.toggle('hidden', !matches);
                visible += matches ? 1 : 0;
            });
            emptyMessage.classList.toggle('hidden', visible !== 0);
        };
        nameField.addEventListener('input', filter);
        schoolField.addEventListener('input', filter);
    });
});
</script>
@endonce
