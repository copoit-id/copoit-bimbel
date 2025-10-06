@extends('admin.layout.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Forum Diskusi</h2>
            <p class="text-gray-500">Kelola diskusi dan moderasi konten</p>
        </div>
        <div class="flex items-center gap-2">
            <button class="text-gray-500 hover:text-gray-700">
                <i class="ri-download-line text-lg"></i>
            </button>
            <button class="text-gray-500 hover:text-gray-700">
                <i class="ri-upload-line text-lg"></i>
            </button>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Diskusi</p>
                    <h3 class="text-2xl font-bold">1,245</h3>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="ri-discuss-line text-blue-500 text-2xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-green-500 text-sm">+15%</span>
                <span class="text-gray-500 text-sm">dari bulan lalu</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Komentar</p>
                    <h3 class="text-2xl font-bold">8,532</h3>
                </div>
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="ri-chat-3-line text-green-500 text-2xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-green-500 text-sm">+22%</span>
                <span class="text-gray-500 text-sm">dari bulan lalu</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Diskusi Aktif</p>
                    <h3 class="text-2xl font-bold">456</h3>
                </div>
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="ri-fire-line text-purple-500 text-2xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-green-500 text-sm">+8%</span>
                <span class="text-gray-500 text-sm">dari bulan lalu</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Laporan Spam</p>
                    <h3 class="text-2xl font-bold">24</h3>
                </div>
                <div class="bg-red-100 p-3 rounded-lg">
                    <i class="ri-spam-2-line text-red-500 text-2xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-red-500 text-sm">+5%</span>
                <span class="text-gray-500 text-sm">dari bulan lalu</span>
            </div>
        </div>
    </div>

    <!-- Discussion List -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <input type="text" id="discussion-search" placeholder="Cari diskusi..."
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                    </div>
                    <select id="category-filter"
                        class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Semua Kategori</option>
                        <option value="general">Umum</option>
                        <option value="question">Pertanyaan</option>
                        <option value="discussion">Diskusi</option>
                    </select>
                    <select id="status-discussion-filter"
                        class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Semua Status</option>
                        <option value="open">Terbuka</option>
                        <option value="closed">Tertutup</option>
                        <option value="solved">Terpecahkan</option>
                    </select>
                    <button id="reset-discussion-filters"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="ri-refresh-line"></i> Reset
                    </button>
                </div>
                <div id="discussion-count" class="text-sm text-gray-500">
                    Total: <span class="font-medium text-gray-700">0 Diskusi</span>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-white p-6 rounded-lg border border-gray-200">
                    <div class="flex items-start justify-between">
                        <div class="flex gap-4">
                            <img src="https://ui-avatars.com/api/?name=John+Doe" class="w-10 h-10 rounded-full">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-medium">Bagaimana cara mempersiapkan tes TWK?</h3>
                                    <span
                                        class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">Pertanyaan</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Saya ingin bertanya mengenai tips dan trik dalam
                                    mempersiapkan tes TWK. Apakah ada saran dari teman-teman yang sudah pernah mengikuti
                                    tes
                                    ini?</p>
                                <div class="flex items-center gap-4 mt-4">
                                    <div class="flex items-center gap-1 text-gray-500">
                                        <i class="ri-chat-3-line"></i>
                                        <span class="text-sm">24 Komentar</span>
                                    </div>
                                    <div class="flex items-center gap-1 text-gray-500">
                                        <i class="ri-eye-line"></i>
                                        <span class="text-sm">156 Dilihat</span>
                                    </div>
                                    <div class="flex items-center gap-1 text-gray-500">
                                        <i class="ri-time-line"></i>
                                        <span class="text-sm">2 jam yang lalu</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button class="text-gray-500 hover:text-primary">
                                <i class="ri-pin-line text-lg"></i>
                            </button>
                            <button class="text-gray-500 hover:text-red-500">
                                <i class="ri-delete-bin-line text-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg border border-gray-200">
                    <div class="flex items-start justify-between">
                        <div class="flex gap-4">
                            <img src="https://ui-avatars.com/api/?name=Jane+Smith" class="w-10 h-10 rounded-full">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-medium">Diskusi soal TIU Matematika</h3>
                                    <span
                                        class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">Diskusi</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Mari kita diskusikan bersama mengenai soal-soal
                                    TIU
                                    matematika yang sering muncul dalam tes CPNS.</p>
                                <div class="flex items-center gap-4 mt-4">
                                    <div class="flex items-center gap-1 text-gray-500">
                                        <i class="ri-chat-3-line"></i>
                                        <span class="text-sm">45 Komentar</span>
                                    </div>
                                    <div class="flex items-center gap-1 text-gray-500">
                                        <i class="ri-eye-line"></i>
                                        <span class="text-sm">289 Dilihat</span>
                                    </div>
                                    <div class="flex items-center gap-1 text-gray-500">
                                        <i class="ri-time-line"></i>
                                        <span class="text-sm">5 jam yang lalu</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button class="text-gray-500 hover:text-primary">
                                <i class="ri-pin-line text-lg"></i>
                            </button>
                            <button class="text-gray-500 hover:text-red-500">
                                <i class="ri-delete-bin-line text-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg border border-gray-200 bg-red-50">
                    <div class="flex items-start justify-between">
                        <div class="flex gap-4">
                            <img src="https://ui-avatars.com/api/?name=Mike+Brown" class="w-10 h-10 rounded-full">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-medium">Jual buku CPNS murah!</h3>
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Spam</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Jual buku CPNS murah meriah! Hubungi WA
                                    0812xxxxxxxx...</p>
                                <div class="flex items-center gap-4 mt-4">
                                    <div class="flex items-center gap-1 text-gray-500">
                                        <i class="ri-flag-line"></i>
                                        <span class="text-sm">5 Laporan</span>
                                    </div>
                                    <div class="flex items-center gap-1 text-gray-500">
                                        <i class="ri-eye-line"></i>
                                        <span class="text-sm">45 Dilihat</span>
                                    </div>
                                    <div class="flex items-center gap-1 text-gray-500">
                                        <i class="ri-time-line"></i>
                                        <span class="text-sm">1 hari yang lalu</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button class="text-gray-500 hover:text-yellow-500">
                                <i class="ri-spam-2-line text-lg"></i>
                            </button>
                            <button class="text-gray-500 hover:text-red-500">
                                <i class="ri-delete-bin-line text-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center mt-4">
                <p class="text-gray-500 text-sm">Menampilkan 1-3 dari 50 diskusi</p>
                <div class="flex items-center gap-2">
                    <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="ri-arrow-left-s-line"></i>
                    </button>
                    <button class="px-3 py-1 bg-primary text-white rounded-lg hover:bg-primary/90">1</button>
                    <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50">2</button>
                    <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50">3</button>
                    <button class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="ri-arrow-right-s-line"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Mock discussion data
    const discussions = [
        { id: 1, title: 'Cara mengerjakan soal TIU', category: 'question', status: 'open', author: 'John Doe', replies: 5 },
        { id: 2, title: 'Tips belajar efektif', category: 'general', status: 'solved', author: 'Jane Smith', replies: 12 },
        { id: 3, title: 'Diskusi strategi ujian', category: 'discussion', status: 'closed', author: 'Bob Wilson', replies: 8 },
    ];

    const searchInput = document.getElementById('discussion-search');
    const categoryFilter = document.getElementById('category-filter');
    const statusFilter = document.getElementById('status-discussion-filter');
    const resetButton = document.getElementById('reset-discussion-filters');
    const discussionCount = document.getElementById('discussion-count');

    function filterDiscussions() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value;
        const selectedStatus = statusFilter.value;

        const filteredDiscussions = discussions.filter(discussion => {
            const matchesSearch = discussion.title.toLowerCase().includes(searchTerm) ||
                                discussion.author.toLowerCase().includes(searchTerm);
            const matchesCategory = !selectedCategory || discussion.category === selectedCategory;
            const matchesStatus = !selectedStatus || discussion.status === selectedStatus;

            return matchesSearch && matchesCategory && matchesStatus;
        });

        updateDiscussionCount(filteredDiscussions.length);
        console.log(`Filtered ${filteredDiscussions.length} discussions`);
    }

    function updateDiscussionCount(count) {
        discussionCount.innerHTML = `Total: <span class="font-medium text-gray-700">${count} Diskusi</span>`;
    }

    function resetFilters() {
        searchInput.value = '';
        categoryFilter.value = '';
        statusFilter.value = '';
        filterDiscussions();
    }

    // Event listeners
    searchInput.addEventListener('input', filterDiscussions);
    categoryFilter.addEventListener('change', filterDiscussions);
    statusFilter.addEventListener('change', filterDiscussions);
    resetButton.addEventListener('click', resetFilters);

    // Initial render
    filterDiscussions();
});
</script>
@endsection
