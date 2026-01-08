@extends('admin.layout.admin')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
                <span>Landing Page</span>
                <i class="ri-arrow-right-s-line"></i>
                <span>Call To Action</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Call To Action Section</h1>
        </div>
        @if(!$cta)
        <a href="{{ route('admin.landing-page.cta.create') }}" 
           class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors flex items-center">
            <i class="ri-add-line mr-2"></i>
            Tambah CTA
        </a>
        @endif
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    @if($cta)
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Data CTA Section</h2>
                <div class="flex space-x-2">
                    <a href="{{ route('admin.landing-page.cta.edit') }}" 
                       class="bg-yellow-500 text-white px-3 py-2 rounded-lg hover:bg-yellow-600 transition-colors text-sm flex items-center">
                        <i class="ri-edit-line mr-1"></i>
                        Edit
                    </a>
                    <form action="{{ route('admin.landing-page.cta.destroy', $cta) }}" method="POST" class="inline-block">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                onclick="return confirm('Yakin ingin menghapus CTA section ini?')"
                                class="bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600 transition-colors text-sm flex items-center">
                            <i class="ri-delete-bin-line mr-1"></i>
                            Hapus
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Judul</h3>
                    <p class="text-gray-900 mb-4">{{ $cta->title }}</p>

                    <h3 class="text-sm font-medium text-gray-700 mb-2">Deskripsi</h3>
                    <p class="text-gray-900 mb-4">{{ $cta->description }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Button Text (Primary)</h3>
                    <p class="text-gray-900 mb-4">{{ $cta->primary_button_text }}</p>

                    <h3 class="text-sm font-medium text-gray-700 mb-2">Button Text (Secondary)</h3>
                    <p class="text-gray-900 mb-4">{{ $cta->secondary_button_text }}</p>

                    <h3 class="text-sm font-medium text-gray-700 mb-2">Status</h3>
                    <span class="px-2 py-1 text-xs rounded-full {{ $cta->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $cta->is_active ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                </div>
            </div>
        </div>
    @else
        <div class="p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-cursor-line text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada CTA Section</h3>
            <p class="text-gray-600 mb-6">Tambahkan CTA section untuk menampilkan call-to-action di landing page.</p>
            <a href="{{ route('admin.landing-page.cta.create') }}" 
               class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary/90 transition-colors inline-flex items-center">
                <i class="ri-add-line mr-2"></i>
                Tambah CTA Section
            </a>
        </div>
    @endif
</div>
@endsection
