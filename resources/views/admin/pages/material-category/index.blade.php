@extends('admin.layout.admin')

@section('title', 'Kategori Subtest')

@section('content')
<div class="w-full max-w-full px-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Kategori Subtest</h1>
            <p class="text-gray-600">Kelola kategori yang dipakai untuk materi pembelajaran, struktur tryout, subtest, dan kebutuhan lainnya.</p>
        </div>
        <button onclick="openModal('createModal')" class="w-full sm:w-auto justify-center bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <i class="ri-add-line"></i>
            Tambah Kategori Utama
        </button>
    </div>

    <!-- Categories Table -->
    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="w-full overflow-x-auto">
        <table class="min-w-[980px] w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-16 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="w-72 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                    <th class="w-20 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Icon</th>
                    <th class="w-28 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="w-72 px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($categories as $index => $category)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $category->name }}</div>
                        <div class="text-xs text-gray-500">Order: {{ $category->order_number }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">{{ $category->description ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($category->icon)
                        <i class="{{ $category->icon }} text-lg text-primary"></i>
                        @else
                        <span class="text-gray-400">-</span>
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
                        <div class="flex items-center justify-end gap-3">
                        <button onclick="openSubcategoryModal({{ $category->category_id }}, @js($category->name))"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-primary/30 text-primary hover:bg-primary hover:text-white transition text-xs font-semibold"
                            title="Tambah Kategori Materi">
                            <i class="ri-add-line"></i>
                            Tambah Kategori Materi
                        </button>
                        <button onclick="openEditModal({{ $category->category_id }}, @js($category->name), @js($category->description), @js($category->icon), {{ $category->order_number }}, {{ $category->is_active ? 'true' : 'false' }}, {{ $category->parent_id ?? 'null' }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-900" title="Edit">
                            <i class="ri-edit-line text-lg"></i>
                        </button>
                        <form action="{{ route('admin.material.material-category.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori utama ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 hover:bg-red-50 hover:text-red-900">
                                <i class="ri-delete-bin-line text-lg"></i>
                            </button>
                        </form>
                        </div>
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
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900">{{ $child->name }}</div>
                                <div class="text-xs text-gray-500 whitespace-normal">Kategori Materi dari {{ $category->name }} · Order: {{ $child->order_number }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">{{ $child->description ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($child->icon)
                        <i class="{{ $child->icon }} text-lg text-primary"></i>
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($child->is_active)
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Aktif</span>
                        @else
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-3">
                        <button onclick="openEditModal({{ $child->category_id }}, @js($child->name), @js($child->description), @js($child->icon), {{ $child->order_number }}, {{ $child->is_active ? 'true' : 'false' }}, {{ $child->parent_id ?? 'null' }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-900">
                            <i class="ri-edit-line text-lg"></i>
                        </button>
                        <form action="{{ route('admin.material.material-category.destroy', $child) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori materi ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 hover:bg-red-50 hover:text-red-900">
                                <i class="ri-delete-bin-line text-lg"></i>
                            </button>
                        </form>
                        </div>
                    </td>
                </tr>
                @endforeach
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="ri-folder-5-line text-4xl mb-2"></i>
                        <p>Belum ada kategori utama</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all">
        <div class="flex justify-between items-center p-5 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Tambah Kategori Utama</h3>
            <button onclick="closeModal('createModal')" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <form action="{{ route('admin.material.material-category.store') }}" method="POST" class="p-4">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori Utama <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Icon (Remix Icon)</label>
                    <input type="text" name="icon" placeholder="ri-book-line" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-gray-500 mt-1">Contoh: ri-book-line, ri-video-line, ri-file-text-line</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Urut</label>
                    <input type="number" name="order_number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('createModal')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all">
        <div class="flex justify-between items-center p-5 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Edit Kategori</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <form id="editForm" method="POST" class="p-4">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="editName" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Utama</label>
                    <select name="parent_id" id="editParent" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Kategori utama</option>
                        @foreach($parentOptions as $parent)
                        <option value="{{ $parent->category_id }}">Kategori materi dari {{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="description" id="editDescription" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Icon (Remix Icon)</label>
                    <input type="text" name="icon" id="editIcon" placeholder="ri-book-line" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Urut</label>
                    <input type="number" name="order_number" id="editOrder" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="editIsActive" value="1" class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Aktif</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Subcategory Modal -->
<div id="subcategoryModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform transition-all">
        <div class="flex justify-between items-center p-5 border-b border-gray-100">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Tambah Kategori Materi</h3>
                <p class="text-sm text-gray-500">Kategori Utama: <span id="subcategoryParentName" class="font-medium text-gray-700"></span></p>
            </div>
            <button onclick="closeModal('subcategoryModal')" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1 rounded-lg transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <form action="{{ route('admin.material.material-category.store') }}" method="POST" class="p-4">
            @csrf
            <input type="hidden" name="parent_id" id="subcategoryParentId">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori Materi <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Icon (Remix Icon)</label>
                    <input type="text" name="icon" placeholder="ri-book-line" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-gray-500 mt-1">Opsional. Jika kosong, bisa mengikuti konteks kategori utama.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Urut</label>
                    <input type="number" name="order_number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('subcategoryModal')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">Simpan Kategori Materi</button>
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

    function openEditModal(id, name, description, icon, orderNumber, isActive, parentId) {
        const form = document.getElementById('editForm');
        form.action = `{{ route('admin.material.material-category.index') }}/${id}`;
        
        document.getElementById('editName').value = name;
        document.getElementById('editDescription').value = description || '';
        document.getElementById('editIcon').value = icon || '';
        document.getElementById('editOrder').value = orderNumber;
        document.getElementById('editIsActive').checked = isActive;
        document.getElementById('editParent').value = parentId || '';
        
        openModal('editModal');
    }

    // Close modal when clicking outside
    document.querySelectorAll('[id$="Modal"]').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
</script>
@endsection
