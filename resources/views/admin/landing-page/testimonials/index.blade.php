@extends('admin.layout.admin')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Testimoni</h1>
        <p class="text-gray-600">Kelola testimoni yang ditampilkan di landing page</p>
    </div>
    <a href="{{ route('admin.landing-page.testimonials.create') }}" 
       class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
        <i class="ri-add-line mr-2"></i>Tambah Testimoni
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="p-6">
        @if($testimonials->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Foto</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Nama</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Posisi</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Rating</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Content</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Order</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Status</th>
                            <th class="text-left py-3 px-4 font-medium text-gray-700">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($testimonials as $testimonial)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4">
                                @if($testimonial->photo)
                                    <img src="{{ asset('storage/' . $testimonial->photo) }}" alt="{{ $testimonial->name }}" 
                                         class="w-12 h-12 rounded-full object-cover">
                                @else
                                    <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                                        <i class="ri-user-line text-gray-400"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-medium text-gray-900">{{ $testimonial->name }}</div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-gray-600">{{ $testimonial->position ?? '-' }}</div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center">
                                    <div class="text-yellow-400 mr-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $testimonial->rating)
                                                <i class="ri-star-fill"></i>
                                            @else
                                                <i class="ri-star-line"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <span class="text-sm text-gray-600">({{ $testimonial->rating }})</span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-gray-600 max-w-xs truncate">{{ $testimonial->content }}</div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                    {{ $testimonial->order }}
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                @if($testimonial->is_active)
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        Aktif
                                    </span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                        Tidak Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.landing-page.testimonials.edit', $testimonial) }}" 
                                       class="text-blue-600 hover:text-blue-800 text-sm">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    <form action="{{ route('admin.landing-page.testimonials.destroy', $testimonial) }}" method="POST" 
                                          class="inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <i class="ri-chat-quote-line text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada testimoni</h3>
                <p class="text-gray-600 mb-4">Mulai dengan menambahkan testimoni pertama untuk landing page.</p>
                <a href="{{ route('admin.landing-page.testimonials.create') }}" 
                   class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                    Tambah Testimoni
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
