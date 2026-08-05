@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2 text-sm text-gray-600">
            @foreach (request()->query() as $key => $value)
                @continue($key === 'per_page' || str_ends_with($key, 'page') || is_array($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <label for="per-page-{{ $paginator->getPageName() }}" class="whitespace-nowrap">Tampilkan</label>
            <select id="per-page-{{ $paginator->getPageName() }}" name="per_page" onchange="this.form.submit()"
                class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-700 focus:border-primary focus:ring-primary">
                @foreach (\App\Support\Pagination::options() as $pageSize)
                    <option value="{{ $pageSize }}" @selected($paginator->perPage() === $pageSize)>{{ $pageSize }}</option>
                @endforeach
            </select>
            <span class="whitespace-nowrap">per halaman</span>
        </form>
        <ul class="inline-flex items-center gap-1 rounded-2xl border border-gray-200 bg-white px-2 py-1 text-sm font-medium text-gray-600 shadow-sm">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li>
                    <span class="inline-flex items-center rounded-xl px-3 py-2 text-gray-300">
                        <i class="ri-arrow-left-line mr-1 text-base"></i>
                        Sebelumnya
                    </span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                        class="inline-flex items-center rounded-xl px-3 py-2 text-primary transition hover:bg-primary/5">
                        <i class="ri-arrow-left-line mr-1 text-base"></i>
                        Sebelumnya
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li>
                        <span class="inline-flex items-center rounded-xl px-3 py-2 text-gray-400">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li>
                                <span class="inline-flex items-center rounded-xl bg-primary px-3 py-2 text-white shadow-sm">
                                    {{ $page }}
                                </span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}" class="inline-flex items-center rounded-xl px-3 py-2 text-gray-600 transition hover:bg-primary/5 hover:text-primary">
                                    {{ $page }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                        class="inline-flex items-center rounded-xl px-3 py-2 text-primary transition hover:bg-primary/5">
                        Selanjutnya
                        <i class="ri-arrow-right-line ml-1 text-base"></i>
                    </a>
                </li>
            @else
                <li>
                    <span class="inline-flex items-center rounded-xl px-3 py-2 text-gray-300">
                        Selanjutnya
                        <i class="ri-arrow-right-line ml-1 text-base"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
