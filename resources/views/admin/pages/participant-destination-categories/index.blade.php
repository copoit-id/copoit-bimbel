@extends('admin.layout.admin')

@section('title', 'Tujuan / Instansi')

@section('content')
<div class="container mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Tujuan / Instansi</h1>
            <p class="text-gray-600">Kelola instansi tujuan dan sub tujuan peserta untuk profile dan filter leaderboard.</p>
        </div>
        <button onclick="openModal('createModal')" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <i class="ri-add-line"></i>
            Tambah Instansi
        </button>
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
                    <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Contoh: STAN">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Urut</label>
                    <input type="number" name="sort_order" min="0" value="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
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
                    <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Contoh: Manajemen Keuangan Negara">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Urut</label>
                    <input type="number" name="sort_order" min="0" value="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
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
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    function openSubcategoryModal(parentId, parentName) {
        document.getElementById('subcategoryParentId').value = parentId;
        document.getElementById('subcategoryParentName').textContent = parentName;
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

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            ['createModal', 'editModal', 'subcategoryModal'].forEach(closeModal);
        }
    });
</script>
@endsection
