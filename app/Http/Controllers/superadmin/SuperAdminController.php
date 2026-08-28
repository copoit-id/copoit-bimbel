<?php

namespace App\Http\Controllers\superadmin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use App\Models\Role;
use App\Models\User;
use App\Rules\SafeName;
use App\Support\Pagination;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuperAdminController extends Controller
{
    private const DEFAULT_ADMIN_PASSWORD = 'password123';

    private const DEMO_DEAL_STATUSES = [
        'baru' => 'Baru',
        'potensial' => 'Potensial',
        'menunggu_keputusan' => 'Menunggu Keputusan',
        'deal' => 'Deal',
        'tidak_jadi' => 'Tidak Jadi',
    ];

    public function index(Request $request): View
    {
        $tab = in_array($request->input('tab', 'admins'), ['admins', 'requests'], true)
            ? $request->input('tab', 'admins')
            : 'admins';
        $status = $request->input('status', 'all');
        $status = in_array($status, ['all', 'active', 'expired'], true) ? $status : 'all';

        $sort = $request->input('sort', 'latest');
        $sortOptions = [
            'latest' => 'Terbaru ditambahkan',
            'oldest' => 'Terlama ditambahkan',
            'name_asc' => 'Nama A-Z',
            'name_desc' => 'Nama Z-A',
            'expiry_asc' => 'Masa berlaku terdekat',
            'expiry_desc' => 'Masa berlaku terjauh',
        ];
        $sort = array_key_exists($sort, $sortOptions) ? $sort : 'latest';

        $now = now();
        $baseQuery = User::query()
            ->select(['id', 'name', 'email', 'phone', 'username', 'role', 'origin_institution', 'demo_note', 'demo_deal_status', 'admin_expires_at', 'created_at'])
            ->where('role', 'admin_demo')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });

        $counts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('admin_expires_at', '>', $now)->count(),
            'expired' => (clone $baseQuery)->where(function ($query) use ($now) {
                $query->whereNull('admin_expires_at')
                    ->orWhere('admin_expires_at', '<=', $now);
            })->count(),
        ];

        $admins = (clone $baseQuery)
            ->when($status === 'active', fn ($query) => $query->where('admin_expires_at', '>', $now))
            ->when($status === 'expired', function ($query) use ($now) {
                $query->where(function ($query) use ($now) {
                    $query->whereNull('admin_expires_at')
                        ->orWhere('admin_expires_at', '<=', $now);
                });
            })
            ->tap(function ($query) use ($sort) {
                match ($sort) {
                    'oldest' => $query->orderBy('created_at'),
                    'name_asc' => $query->orderBy('name'),
                    'name_desc' => $query->orderByDesc('name'),
                    'expiry_asc' => $query->orderBy('admin_expires_at'),
                    'expiry_desc' => $query->orderByDesc('admin_expires_at'),
                    default => $query->orderByDesc('created_at'),
                };
            })
            ->paginate(Pagination::perPage(20))
            ->withQueryString();

        $returnQuery = [
            'tab' => $tab,
            'page' => max(1, (int) $request->input('page', 1)),
            'status' => $status,
            'sort' => $sort,
            'search' => trim((string) $request->input('search', '')),
        ];
        $dealStatusOptions = self::DEMO_DEAL_STATUSES;
        $pendingRequestCount = DemoRequest::query()->pending()->count();
        $demoRequests = $tab === 'requests'
            ? DemoRequest::query()
                ->pending()
                ->select(['id', 'name', 'email', 'phone', 'origin_institution', 'request_note', 'created_at'])
                ->latest()
                ->paginate(Pagination::perPage(20))
                ->withQueryString()
            : null;

        return view('super-admin.admins.index', compact(
            'admins', 'counts', 'sortOptions', 'sort', 'status', 'returnQuery',
            'dealStatusOptions', 'tab', 'pendingRequestCount', 'demoRequests'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['phone' => $this->normalizeWhatsAppNumber($request->input('phone'))]);

        $request->validate([
            'name' => ['required', 'string', 'max:255', new SafeName],
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => ['required', 'string', 'regex:/^628[0-9]{7,13}$/'],
            'username' => 'nullable|string|max:255|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
            'origin_institution' => ['required', 'string', 'max:255'],
            'demo_note' => ['nullable', 'string', 'max:100000'],
            'demo_deal_status' => ['required', Rule::in(array_keys(self::DEMO_DEAL_STATUSES))],
            'expiry_type' => 'required|in:date,duration',
            'expires_at' => 'nullable|date',
            'duration_days' => 'nullable|integer|min:0|max:365',
            'duration_hours' => 'nullable|integer|min:0|max:720',
        ], [
            'phone.required' => 'Nomor WhatsApp peminta wajib diisi.',
            'phone.regex' => 'Masukkan nomor WhatsApp aktif, contoh 081234567890.',
        ]);

        $expiresAt = null;
        if ($request->expiry_type === 'date') {
            if (! $request->filled('expires_at')) {
                return back()->withErrors(['expires_at' => 'Tanggal berakhir wajib diisi.'])->withInput();
            }
            $expiresAt = Carbon::parse($request->expires_at, 'Asia/Jakarta');
        } else {
            $days = (int) $request->input('duration_days', 0);
            $hours = (int) $request->input('duration_hours', 0);
            if ($days <= 0 && $hours <= 0) {
                return back()->withErrors(['duration_days' => 'Isi durasi hari atau jam.'])->withInput();
            }
            $expiresAt = Carbon::now('Asia/Jakarta')->addDays($days)->addHours($hours);
        }

        $username = $request->input('username');
        if (! $username) {
            $username = strtolower(str_replace(' ', '', $request->name));
        }
        $baseUsername = $username;
        $suffix = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.$suffix;
            $suffix++;
        }

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $username,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'origin_institution' => $request->input('origin_institution'),
            'demo_note' => $this->sanitizeDemoNote($request->input('demo_note')),
            'demo_deal_status' => $request->input('demo_deal_status', 'baru'),
            'role' => 'admin_demo',
            'admin_expires_at' => $expiresAt,
            'status' => 'aktif',
            'email_verified_at' => now(),
        ]);

        $role = Role::where('slug', 'admin_demo')->first();
        if ($role) {
            $admin->roles()->syncWithoutDetaching([$role->id]);
        }

        return redirect()->route('super-admin.admins.index', $this->indexReturnQuery($request))
            ->with('success', 'Akun admin demo berhasil dibuat.');
    }

    /**
     * Update the account details of an existing demo admin.
     *
     * Access expiry is deliberately handled by extend() so this action cannot
     * accidentally create an account or change the access period.
     */
    public function update(Request $request, User $admin): RedirectResponse
    {
        if ($admin->role !== 'admin_demo') {
            abort(404);
        }

        $request->merge(['phone' => $this->normalizeWhatsAppNumber($request->input('phone'))]);

        $request->validate([
            'name' => ['required', 'string', 'max:255', new SafeName],
            'email' => 'required|email|max:255|unique:users,email,'.$admin->id,
            'phone' => ['required', 'string', 'regex:/^628[0-9]{7,13}$/'],
            'username' => 'nullable|string|max:255|unique:users,username,'.$admin->id,
            'password' => 'nullable|string|min:8|confirmed',
            'origin_institution' => ['required', 'string', 'max:255'],
            'demo_note' => ['nullable', 'string', 'max:100000'],
            'demo_deal_status' => ['required', Rule::in(array_keys(self::DEMO_DEAL_STATUSES))],
        ], [
            'phone.required' => 'Nomor WhatsApp peminta wajib diisi.',
            'phone.regex' => 'Masukkan nomor WhatsApp aktif, contoh 081234567890.',
        ]);

        $username = $request->input('username') ?: $admin->username;

        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->phone = $request->phone;
        $admin->username = $username;
        $admin->origin_institution = $request->input('origin_institution');
        $admin->demo_note = $this->sanitizeDemoNote($request->input('demo_note'));
        $admin->demo_deal_status = $request->input('demo_deal_status');

        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }

        $admin->save();

        return redirect()->route('super-admin.admins.index', $this->indexReturnQuery($request))
            ->with('success', 'Admin demo berhasil diperbarui.');
    }

    /**
     * Reset a demo admin password to the application default.
     */
    public function resetPassword(User $admin, ?Request $request = null): RedirectResponse
    {
        if ($admin->role !== 'admin_demo') {
            abort(404);
        }

        $request ??= request();

        $admin->forceFill([
            'password' => Hash::make(self::DEFAULT_ADMIN_PASSWORD),
            'remember_token' => null,
        ])->save();

        return redirect()->route('super-admin.admins.index', $this->indexReturnQuery($request))
            ->with('success', 'Password admin demo berhasil direset ke password default.');
    }

    /**
     * Extend only the access period of an existing demo admin.
     */
    public function extend(Request $request, User $admin): RedirectResponse
    {
        if ($admin->role !== 'admin_demo') {
            abort(404);
        }

        $request->validate([
            'expiry_type' => 'required|in:date,duration',
            'expires_at' => 'nullable|date',
            'duration_days' => 'nullable|integer|min:0|max:365',
            'duration_hours' => 'nullable|integer|min:0|max:720',
        ]);

        if ($request->expiry_type === 'date') {
            if (! $request->filled('expires_at')) {
                return back()->withErrors(['expires_at' => 'Tanggal berakhir wajib diisi.'])->withInput();
            }

            $expiresAt = Carbon::parse($request->expires_at, 'Asia/Jakarta');
            if ($expiresAt->lte(Carbon::now('Asia/Jakarta'))) {
                return back()->withErrors(['expires_at' => 'Tanggal berakhir harus di masa depan.'])->withInput();
            }
        } else {
            $days = (int) $request->input('duration_days', 0);
            $hours = (int) $request->input('duration_hours', 0);
            if ($days <= 0 && $hours <= 0) {
                return back()->withErrors(['duration_days' => 'Isi durasi hari atau jam.'])->withInput();
            }

            $now = Carbon::now('Asia/Jakarta');
            $baseExpiry = $admin->admin_expires_at?->copy()->setTimezone('Asia/Jakarta');
            $expiresAt = ($baseExpiry && $baseExpiry->gt($now) ? $baseExpiry : $now)
                ->addDays($days)
                ->addHours($hours);
        }

        $admin->update(['admin_expires_at' => $expiresAt]);

        return redirect()->route('super-admin.admins.index', $this->indexReturnQuery($request))
            ->with('success', 'Masa akses admin demo berhasil diperpanjang.');
    }

    public function approveRequest(Request $request, DemoRequest $demoRequest): RedirectResponse
    {
        if ($demoRequest->status !== 'pending') {
            return back()->with('error', 'Pengajuan demo ini sudah diproses.');
        }

        $request->validate([
            'expiry_type' => ['required', Rule::in(['date', 'duration'])],
            'expires_at' => ['nullable', 'date'],
            'duration_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'duration_hours' => ['nullable', 'integer', 'min:0', 'max:720'],
        ]);

        if ($request->input('expiry_type') === 'date') {
            if (! $request->filled('expires_at')) {
                return back()->withErrors(['expires_at' => 'Tanggal berakhir wajib diisi.'])->withInput();
            }

            $expiresAt = Carbon::parse($request->input('expires_at'), 'Asia/Jakarta');
            if ($expiresAt->lte(Carbon::now('Asia/Jakarta'))) {
                return back()->withErrors(['expires_at' => 'Tanggal berakhir harus di masa depan.'])->withInput();
            }
        } else {
            $days = (int) $request->input('duration_days', 0);
            $hours = (int) $request->input('duration_hours', 0);
            if ($days <= 0 && $hours <= 0) {
                return back()->withErrors(['duration_days' => 'Isi durasi hari atau jam.'])->withInput();
            }

            $expiresAt = Carbon::now('Asia/Jakarta')->addDays($days)->addHours($hours);
        }

        if (User::query()->where('email', $demoRequest->email)->exists()) {
            return back()->withErrors(['email' => 'Email pengaju sudah terdaftar sebagai pengguna.'])->withInput();
        }

        DB::transaction(function () use ($demoRequest, $expiresAt, $request): void {
            $lockedRequest = DemoRequest::query()->lockForUpdate()->findOrFail($demoRequest->id);
            if ($lockedRequest->status !== 'pending') {
                abort(409, 'Pengajuan demo ini sudah diproses.');
            }

            $admin = User::create([
                'name' => $lockedRequest->name,
                'email' => $lockedRequest->email,
                'username' => $this->availableUsername($lockedRequest->name),
                'phone' => $lockedRequest->phone,
                'password' => Hash::make(self::DEFAULT_ADMIN_PASSWORD),
                'origin_institution' => $lockedRequest->origin_institution,
                'demo_note' => $this->sanitizeDemoNote($lockedRequest->request_note),
                'demo_deal_status' => 'baru',
                'role' => 'admin_demo',
                'admin_expires_at' => $expiresAt,
                'status' => 'aktif',
                'email_verified_at' => now(),
            ]);

            $role = Role::query()->where('slug', 'admin_demo')->first();
            if ($role) {
                $admin->roles()->syncWithoutDetaching([$role->id]);
            }

            $lockedRequest->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'approved_by' => $request->user()->id,
                'approved_admin_id' => $admin->id,
            ]);
        });

        return redirect()->route('super-admin.admins.index', ['tab' => 'requests'])
            ->with('success', 'Pengajuan disetujui dan akun admin demo berhasil dibuat. Password awal: password123.');
    }

    public function exportExcel(): StreamedResponse
    {
        $filename = 'admin-demo-'.now('Asia/Jakarta')->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function (): void {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Admin Demo');

            $headers = [
                'Nama',
                'Email',
                'WhatsApp',
                'Username',
                'Asal Bimbel',
                'Catatan',
                'Status Deal',
                'Masa Berlaku',
                'Status Akses',
                'Ditambahkan',
            ];
            $sheet->fromArray($headers, null, 'A1');
            $sheet->getStyle('A1:J1')->getFont()->setBold(true);
            $sheet->getStyle('A1:J1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEFF6FF');

            $row = 2;
            User::query()
                ->where('role', 'admin_demo')
                ->orderBy('id')
                ->select([
                    'name',
                    'email',
                    'phone',
                    'username',
                    'origin_institution',
                    'demo_note',
                    'demo_deal_status',
                    'admin_expires_at',
                    'created_at',
                ])
                ->cursor()
                ->each(function (User $admin) use ($sheet, &$row): void {
                    $expired = $admin->admin_expires_at === null || now()->gte($admin->admin_expires_at);

                    $sheet->fromArray([
                        $admin->name,
                        $admin->email,
                        $admin->phone,
                        $admin->username,
                        $admin->origin_institution,
                        $this->plainDemoNote($admin->demo_note),
                        self::DEMO_DEAL_STATUSES[$admin->demo_deal_status] ?? self::DEMO_DEAL_STATUSES['baru'],
                        $admin->admin_expires_at?->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '-',
                        $expired ? 'Expired' : 'Aktif',
                        $admin->created_at?->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '-',
                    ], null, 'A'.$row);
                    $row++;
                });

            foreach (range('A', 'J') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            $sheet->getColumnDimension('F')->setWidth(60);
            $sheet->getStyle('F2:F'.max(2, $row - 1))->getAlignment()->setWrapText(true);

            try {
                (new Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /** @return array{tab: string, page: int, status: string, sort: string, search: string} */
    private function indexReturnQuery(Request $request): array
    {
        $status = (string) $request->input('return_status', 'all');
        $sort = (string) $request->input('return_sort', 'latest');
        $tab = (string) $request->input('return_tab', 'admins');

        return [
            'tab' => in_array($tab, ['admins', 'requests'], true) ? $tab : 'admins',
            'page' => max(1, (int) $request->input('return_page', 1)),
            'status' => in_array($status, ['all', 'active', 'expired'], true) ? $status : 'all',
            'sort' => in_array($sort, ['latest', 'oldest', 'name_asc', 'name_desc', 'expiry_asc', 'expiry_desc'], true) ? $sort : 'latest',
            'search' => mb_substr(trim((string) $request->input('return_search', '')), 0, 255),
        ];
    }

    private function sanitizeDemoNote(?string $note): ?string
    {
        $note = trim((string) $note);
        if ($note === '') {
            return null;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<!doctype html><html><body><div id="demo-note-root">'.$note.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $root = $document->getElementById('demo-note-root');
        if (! $root) {
            return null;
        }

        $html = '';
        foreach ($root->childNodes as $node) {
            $html .= $this->sanitizeDemoNoteNode($node);
        }

        return trim(strip_tags($html)) === '' ? null : trim($html);
    }

    private function sanitizeDemoNoteNode(\DOMNode $node): string
    {
        if ($node instanceof \DOMText) {
            return htmlspecialchars($node->wholeText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        if (! $node instanceof \DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
            return '';
        }

        $content = '';
        foreach ($node->childNodes as $child) {
            $content .= $this->sanitizeDemoNoteNode($child);
        }

        $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'a'];
        if (! in_array($tag, $allowedTags, true)) {
            return $content;
        }

        if ($tag === 'br') {
            return '<br>';
        }

        if ($tag === 'a') {
            $href = trim((string) $node->getAttribute('href'));
            $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));
            if ($href !== '' && in_array($scheme, ['http', 'https', 'mailto'], true)) {
                return '<a href="'.htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'" rel="noopener noreferrer">'.$content.'</a>';
            }

            return $content;
        }

        return '<'.$tag.'>'.$content.'</'.$tag.'>';
    }

    private function plainDemoNote(?string $note): string
    {
        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $note), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }

    private function availableUsername(string $name): string
    {
        $username = strtolower(str_replace(' ', '', $name));
        $baseUsername = $username !== '' ? $username : 'admin';
        $username = $baseUsername;
        $suffix = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = $baseUsername.$suffix;
            $suffix++;
        }

        return $username;
    }

    private function normalizeWhatsAppNumber(?string $phone): string
    {
        $normalized = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if (str_starts_with($normalized, '0')) {
            return '62'.substr($normalized, 1);
        }

        return str_starts_with($normalized, '8') ? '62'.$normalized : $normalized;
    }
}
