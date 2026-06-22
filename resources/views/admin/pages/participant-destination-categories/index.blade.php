@extends('admin.layout.admin')

@section('title', 'Tujuan / Instansi')

@section('content')
<div class="container mx-auto px-4">
    <div class="flex flex-col gap-4 md:flex-row md:justify-between md:items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Tujuan / Instansi</h1>
            <p class="text-gray-600">Kelola instansi tujuan dan sub tujuan peserta untuk profile dan filter leaderboard.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            <button onclick="openModal('importSnpmbModal')" class="border border-primary text-primary hover:bg-primary hover:text-white px-4 py-2 rounded-lg flex items-center justify-center gap-2">
                <i class="ri-download-cloud-2-line"></i>
                Tarik Data Perguruan Tinggi Resmi
            </button>
            <button onclick="openModal('createModal')" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg flex items-center justify-center gap-2">
                <i class="ri-add-line"></i>
                Tambah Instansi
            </button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peserta</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($categories as $index => $category)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $category->name }}</div>
                        <div class="text-xs text-gray-500">Order: {{ $category->sort_order }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $category->slug }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $category->users_count ?? 0 }} peserta
                        @if($category->children->isNotEmpty())
                        <span class="text-xs text-gray-400">, {{ $category->children->count() }} sub tujuan</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($category->is_active)
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Aktif</span>
                        @else
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="openSubcategoryModal({{ $category->id }}, @js($category->name))"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-primary/30 text-primary hover:bg-primary hover:text-white transition mr-3 text-xs font-semibold"
                            title="Tambah Sub Tujuan">
                            <i class="ri-add-line"></i>
                            Tambah Sub Tujuan
                        </button>
                        <button onclick="openEditModal({{ $category->id }}, @js($category->name), {{ $category->sort_order }}, {{ $category->is_active ? 'true' : 'false' }}, {{ $category->parent_id ?? 'null' }})" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                            <i class="ri-edit-line text-lg"></i>
                        </button>
                        <form action="{{ route('admin.participant-destination-categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus instansi ini? Peserta yang memakai instansi ini akan dikosongkan.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">
                                <i class="ri-delete-bin-line text-lg"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @foreach($category->children as $child)
                <tr class="hover:bg-gray-50 bg-gray-50/60">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}.{{ $loop->iteration }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2 pl-7">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <i class="ri-corner-down-right-line text-sm"></i>
                            </span>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $child->name }}</div>
                                <div class="text-xs text-gray-500">Sub Tujuan dari {{ $category->name }} · Order: {{ $child->sort_order }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $child->slug }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $child->users_count ?? 0 }} peserta</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($child->is_active)
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Aktif</span>
                        @else
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="openEditModal({{ $child->id }}, @js($child->name), {{ $child->sort_order }}, {{ $child->is_active ? 'true' : 'false' }}, {{ $child->parent_id ?? 'null' }})" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                            <i class="ri-edit-line text-lg"></i>
                        </button>
                        <form action="{{ route('admin.participant-destination-categories.destroy', $child) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus sub tujuan ini? Peserta yang memakai sub tujuan ini akan dikosongkan.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">
                                <i class="ri-delete-bin-line text-lg"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="ri-building-2-line text-4xl mb-2"></i>
                        <p>Belum ada instansi tujuan</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="importSnpmbModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 transform transition-all">
        <div class="flex justify-between items-start p-5 border-b border-gray-100">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Tarik Data Perguruan Tinggi Resmi</h3>
                <p class="text-sm text-gray-500 mt-1">Tampilkan referensi universitas dan program studi tanpa menyimpan otomatis.</p>
            </div>
            <button onclick="closeModal('importSnpmbModal')" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-5 space-y-4">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <p class="font-semibold mb-2">Info sebelum menarik data</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Sumber data memakai endpoint resmi yang juga dipakai halaman statistik perguruan tinggi SNPMB.</li>
                    <li>Fetch ini hanya menampilkan data sebagai referensi, tidak langsung menyimpan ke database.</li>
                    <li>Klik Pakai untuk mengisi form manual, lalu simpan jika data memang ingin dimasukkan.</li>
                    <li>Prodi hanya diambil untuk perguruan tinggi yang dipilih, jadi tidak membebani server dengan tarik data massal.</li>
                </ul>
            </div>

            <div>
                <label for="snpmb_source" class="block text-sm font-medium text-gray-700 mb-1">Jalur data</label>
                <select id="snpmb_source" name="source" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="all">Lengkap (SNBT + SNBP)</option>
                    <option value="snbt">SNBT</option>
                    <option value="snbp">SNBP</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Pilih Lengkap untuk mengambil gabungan SNBT dan SNBP.</p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="button" onclick="fetchOfficialInstitutions()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    Tampilkan Perguruan Tinggi
                </button>
                <button type="button" onclick="closeModal('importSnpmbModal')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Tutup</button>
            </div>

            <div id="officialDataStatus" class="hidden rounded-lg border px-4 py-3 text-sm"></div>

            <div id="officialInstitutionPanel" class="hidden space-y-3">
                <input type="text" id="officialInstitutionSearch" placeholder="Cari perguruan tinggi..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                <div id="officialInstitutionList" class="max-h-64 overflow-y-auto rounded-lg border border-gray-200 divide-y divide-gray-100"></div>
            </div>

            <div id="officialProgramPanel" class="hidden space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Program studi</p>
                        <p id="officialSelectedInstitution" class="text-xs text-gray-500"></p>
                    </div>
                    <button type="button" onclick="clearOfficialPrograms()" class="text-xs text-gray-500 hover:text-gray-800">Tutup prodi</button>
                </div>
                <input type="text" id="officialProgramSearch" placeholder="Cari prodi..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                <div id="officialProgramList" class="max-h-64 overflow-y-auto rounded-lg border border-gray-200 divide-y divide-gray-100"></div>
            </div>
        </div>
    </div>
</div>

<div id="createModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all">
        <div class="flex justify-between items-center p-5 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Tambah Instansi</h3>
            <button onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <form action="{{ route('admin.participant-destination-categories.store') }}" method="POST" class="p-4">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Instansi <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="createName" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Contoh: STAN">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Urut</label>
                    <input type="number" name="sort_order" id="createOrder" min="0" value="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" value="1" class="rounded text-primary focus:ring-primary" checked>
                    Aktif
                </label>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('createModal')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">Simpan Instansi</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all">
        <div class="flex justify-between items-center p-5 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Edit Tujuan / Instansi</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <form id="editForm" method="POST" class="p-4">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="editName" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Instansi</label>
                    <select name="parent_id" id="editParent" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Instansi utama</option>
                        @foreach($parentOptions as $parent)
                        <option value="{{ $parent->id }}">Sub tujuan dari {{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Urut</label>
                    <input type="number" name="sort_order" id="editOrder" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" id="editIsActive" value="1" class="rounded text-primary focus:ring-primary">
                    Aktif
                </label>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<div id="subcategoryModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all">
        <div class="flex justify-between items-center p-5 border-b border-gray-100">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Tambah Sub Tujuan</h3>
                <p class="text-sm text-gray-500">Instansi: <span id="subcategoryParentName" class="font-medium text-gray-700"></span></p>
            </div>
            <button onclick="closeModal('subcategoryModal')" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <form action="{{ route('admin.participant-destination-categories.store') }}" method="POST" class="p-4">
            @csrf
            <input type="hidden" name="parent_id" id="subcategoryParentId">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Sub Tujuan <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="subcategoryName" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Contoh: Manajemen Keuangan Negara">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Urut</label>
                    <input type="number" name="sort_order" id="subcategoryOrder" min="0" value="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" value="1" class="rounded text-primary focus:ring-primary" checked>
                    Aktif
                </label>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('subcategoryModal')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">Simpan Sub Tujuan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const officialInstitutionsUrl = @js(route('admin.participant-destination-categories.official.institutions'));
    const officialProgramsUrl = @js(route('admin.participant-destination-categories.official.programs'));
    const existingParentOptions = @json($parentOptions->map(fn($parent) => ['id' => $parent->id, 'name' => $parent->name])->values());
    let officialInstitutions = [];
    let officialPrograms = [];
    let selectedOfficialInstitution = null;

    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    function openSubcategoryModal(parentId, parentName) {
        document.getElementById('subcategoryParentId').value = parentId;
        document.getElementById('subcategoryParentName').textContent = parentName;
        document.getElementById('subcategoryName').value = '';
        openModal('subcategoryModal');
    }

    function openEditModal(id, name, sortOrder, isActive, parentId) {
        document.getElementById('editForm').action = `{{ route('admin.participant-destination-categories.index') }}/${id}`;
        document.getElementById('editName').value = name || '';
        document.getElementById('editOrder').value = sortOrder ?? 0;
        document.getElementById('editIsActive').checked = Boolean(isActive);
        document.getElementById('editParent').value = parentId || '';
        openModal('editModal');
    }

    function setOfficialStatus(message, type = 'info') {
        const status = document.getElementById('officialDataStatus');
        status.textContent = message;
        status.className = 'rounded-lg border px-4 py-3 text-sm';

        if (type === 'error') {
            status.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
        } else if (type === 'success') {
            status.classList.add('border-green-200', 'bg-green-50', 'text-green-700');
        } else {
            status.classList.add('border-blue-200', 'bg-blue-50', 'text-blue-700');
        }

        status.classList.remove('hidden');
    }

    function selectedOfficialSource() {
        return document.getElementById('snpmb_source')?.value || 'all';
    }

    async function fetchOfficialInstitutions() {
        clearOfficialPrograms();
        officialInstitutions = [];
        document.getElementById('officialInstitutionPanel').classList.add('hidden');
        setOfficialStatus('Mengambil daftar perguruan tinggi resmi...', 'info');

        try {
            const url = `${officialInstitutionsUrl}?source=${encodeURIComponent(selectedOfficialSource())}`;
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) throw new Error('Gagal mengambil data perguruan tinggi.');
            const payload = await response.json();
            officialInstitutions = Array.isArray(payload.data) ? payload.data : [];
            renderOfficialInstitutions();
            document.getElementById('officialInstitutionPanel').classList.remove('hidden');
            setOfficialStatus(`${officialInstitutions.length} perguruan tinggi berhasil ditampilkan. Data belum disimpan ke database.`, 'success');
        } catch (error) {
            setOfficialStatus(error.message || 'Gagal mengambil data perguruan tinggi.', 'error');
        }
    }

    function renderOfficialInstitutions() {
        const query = (document.getElementById('officialInstitutionSearch')?.value || '').toLowerCase();
        const list = document.getElementById('officialInstitutionList');
        const filtered = officialInstitutions
            .filter(item => String(item.nama || '').toLowerCase().includes(query))
            .slice(0, 80);

        list.innerHTML = filtered.length
            ? filtered.map((item, index) => `
                <div class="flex items-center justify-between gap-3 p-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-800">${escapeHtml(item.nama || '-')}</p>
                        <p class="text-xs text-gray-500">Kode: ${escapeHtml(String(item.kode_ptn || item.id_ptn || '-'))}</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button type="button" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50" onclick="fetchOfficialPrograms(${index})">Lihat Prodi</button>
                        <button type="button" class="rounded-lg bg-primary px-3 py-1.5 text-xs text-white hover:bg-primary-dark" onclick="useOfficialInstitution(${index})">Pakai</button>
                    </div>
                </div>
            `).join('')
            : '<div class="p-4 text-center text-sm text-gray-500">Data tidak ditemukan.</div>';
    }

    async function fetchOfficialPrograms(index) {
        const filtered = filteredOfficialInstitutions();
        selectedOfficialInstitution = filtered[index] || null;
        if (!selectedOfficialInstitution) return;

        officialPrograms = [];
        document.getElementById('officialProgramPanel').classList.remove('hidden');
        document.getElementById('officialSelectedInstitution').textContent = selectedOfficialInstitution.nama;
        document.getElementById('officialProgramList').innerHTML = '<div class="p-4 text-sm text-gray-500">Mengambil daftar prodi...</div>';

        try {
            const params = new URLSearchParams({
                source: selectedOfficialSource(),
                ptn: selectedOfficialInstitution.id_ptn || '',
            });

            if (selectedOfficialInstitution.source_ids?.snbt) {
                params.set('ptn_snbt', selectedOfficialInstitution.source_ids.snbt);
            }

            if (selectedOfficialInstitution.source_ids?.snbp) {
                params.set('ptn_snbp', selectedOfficialInstitution.source_ids.snbp);
            }

            const url = `${officialProgramsUrl}?${params.toString()}`;
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) throw new Error('Gagal mengambil data prodi.');
            const payload = await response.json();
            officialPrograms = Array.isArray(payload.data) ? payload.data : [];
            renderOfficialPrograms();
        } catch (error) {
            document.getElementById('officialProgramList').innerHTML = `<div class="p-4 text-sm text-red-600">${escapeHtml(error.message || 'Gagal mengambil data prodi.')}</div>`;
        }
    }

    function filteredOfficialInstitutions() {
        const query = (document.getElementById('officialInstitutionSearch')?.value || '').toLowerCase();
        return officialInstitutions
            .filter(item => String(item.nama || '').toLowerCase().includes(query))
            .slice(0, 80);
    }

    function renderOfficialPrograms() {
        const query = (document.getElementById('officialProgramSearch')?.value || '').toLowerCase();
        const list = document.getElementById('officialProgramList');
        const filtered = officialPrograms
            .filter(item => String(item.nama || '').toLowerCase().includes(query))
            .slice(0, 120);

        list.innerHTML = filtered.length
            ? filtered.map((item, index) => `
                <div class="flex items-center justify-between gap-3 p-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-800">${escapeHtml(item.nama || '-')}</p>
                        <p class="text-xs text-gray-500">Kode: ${escapeHtml(String(item.kode_prodi || item.id_prodi || '-'))}</p>
                    </div>
                    <button type="button" class="shrink-0 rounded-lg bg-primary px-3 py-1.5 text-xs text-white hover:bg-primary-dark" onclick="useOfficialProgram(${index})">Pakai</button>
                </div>
            `).join('')
            : '<div class="p-4 text-center text-sm text-gray-500">Data prodi tidak ditemukan.</div>';
    }

    function useOfficialInstitution(index) {
        const item = filteredOfficialInstitutions()[index];
        if (!item) return;

        document.getElementById('createName').value = item.nama || '';
        document.getElementById('createOrder').value = 0;
        closeModal('importSnpmbModal');
        openModal('createModal');
    }

    function useOfficialProgram(index) {
        const query = (document.getElementById('officialProgramSearch')?.value || '').toLowerCase();
        const item = officialPrograms
            .filter(program => String(program.nama || '').toLowerCase().includes(query))
            .slice(0, 120)[index];

        if (!item || !selectedOfficialInstitution) return;

        const parent = existingParentOptions.find(parent => normalizeName(parent.name) === normalizeName(selectedOfficialInstitution.nama));
        if (!parent) {
            document.getElementById('createName').value = selectedOfficialInstitution.nama || '';
            document.getElementById('createOrder').value = 0;
            closeModal('importSnpmbModal');
            openModal('createModal');
            alert('Instansi parent belum ada di database. Simpan instansi ini dulu, lalu buka lagi data resmi untuk memilih prodinya.');
            return;
        }

        document.getElementById('subcategoryParentName').textContent = selectedOfficialInstitution.nama || '';
        document.getElementById('subcategoryParentId').value = parent.id;
        document.getElementById('subcategoryName').value = item.nama || '';
        document.getElementById('subcategoryOrder').value = 0;
        closeModal('importSnpmbModal');
        openModal('subcategoryModal');
    }

    function clearOfficialPrograms() {
        selectedOfficialInstitution = null;
        officialPrograms = [];
        document.getElementById('officialProgramPanel')?.classList.add('hidden');
        const search = document.getElementById('officialProgramSearch');
        if (search) search.value = '';
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function normalizeName(value) {
        return String(value || '').toLowerCase().trim().replace(/\s+/g, ' ');
    }

    document.getElementById('officialInstitutionSearch')?.addEventListener('input', renderOfficialInstitutions);
    document.getElementById('officialProgramSearch')?.addEventListener('input', renderOfficialPrograms);

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            ['createModal', 'editModal', 'subcategoryModal', 'importSnpmbModal'].forEach(closeModal);
        }
    });
</script>
@endsection
