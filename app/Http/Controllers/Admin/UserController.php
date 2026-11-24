<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Daftar pesan validasi berbahasa Indonesia untuk setiap kolom import Excel/CSV.
     */
    private function importRowValidationMessages(): array
    {
        return [
            'name.required'       => 'Nama wajib diisi.',
            'name.string'         => 'Format nama tidak valid.',
            'name.max'            => 'Nama maksimal 255 karakter.',
            'nip.string'          => 'Format NIP tidak valid.',
            'nip.max'             => 'NIP maksimal 20 digit.',
            'nip.unique'          => 'NIP sudah terdaftar pada akun lain.',
            'email.required'      => 'Alamat email wajib diisi.',
            'email.email'         => 'Format alamat email tidak valid.',
            'email.max'           => 'Alamat email maksimal 255 karakter.',
            'email.unique'        => 'Alamat email sudah terdaftar pada akun lain.',
            'password.required'   => 'Password wajib diisi.',
            'password.string'     => 'Format password tidak valid.',
            'password.min'        => 'Password minimal 8 karakter.',
            'role.required'       => 'Role wajib diisi.',
            'role.in'             => 'Role tidak valid (gunakan: pegawai, driver, approver, admin_lap, atau admin_app).',
            'phone.string'        => 'Format nomor HP tidak valid.',
            'phone.max'           => 'Nomor HP maksimal 20 digit.',
            'pangkat.string'      => 'Format pangkat tidak valid.',
            'pangkat.max'         => 'Pangkat maksimal 100 karakter.',
            'jabatan.string'      => 'Format jabatan tidak valid.',
            'jabatan.max'         => 'Jabatan maksimal 100 karakter.',
            'department.string'   => 'Format unit kerja tidak valid.',
            'department.max'      => 'Unit kerja maksimal 200 karakter.',
        ];
    }

    public function index()
    {
        // Hanya ambil kolom spesifik yang dipakai oleh tampilan tabel/kartu
        $users = User::select(['id', 'name', 'nip', 'pangkat', 'jabatan', 'email', 'department', 'role', 'slug'])
            ->orderBy('name', 'asc')
            ->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'nip'        => 'nullable|string|max:20|unique:users,nip',
            'email'      => 'required|string|email|max:255|unique:users,email',
            'password'   => 'required|string|min:8',
            'role'       => 'required|in:admin_app,admin_lap,pegawai,approver,driver',
            'phone'      => 'nullable|string|max:20',
            'pangkat'    => 'nullable|string|max:100',
            'jabatan'    => 'nullable|string|max:100',
            'department' => 'nullable|string|max:200',
        ], [
            'nip.unique'     => 'NIP yang Anda masukkan sudah terdaftar pada akun lain.',
            'email.unique'   => 'Alamat Email Dinas yang Anda masukkan sudah terdaftar.',
            'email.required' => 'Alamat Email Dinas wajib diisi.',
            'role.required'  => 'Role / Hak Akses wajib dipilih.',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin_app.users.index')->with('success', 'Akun Pegawai berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $isSelf = $request->user()->id === $user->id;

        if ($isSelf && $request->filled('role') && $request->role !== $user->role) {
            return back()
                ->withErrors(['role' => 'Anda tidak dapat mengubah Role / Hak Akses akun Anda sendiri.'])
                ->withInput();
        }

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'nip'        => 'nullable|string|max:20|unique:users,nip,' . $user->id,
            'email'      => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role'       => ($isSelf ? 'nullable' : 'required') . '|in:admin_app,admin_lap,pegawai,approver,driver',
            'phone'      => 'nullable|string|max:20',
            'pangkat'    => 'nullable|string|max:100',
            'jabatan'    => 'nullable|string|max:100',
            'department' => 'nullable|string|max:200',
        ], [
            'role.in'        => 'Role / Hak Akses yang dipilih tidak valid.',
            'nip.unique'     => 'NIP yang Anda masukkan sudah terdaftar pada akun lain.',
            'email.unique'   => 'Alamat Email Dinas yang Anda masukkan sudah terdaftar.',
            'email.required' => 'Alamat Email Dinas wajib diisi.',
            'role.required'  => 'Role / Hak Akses wajib dipilih.',
        ]);

        if ($isSelf) {
            $validated['role'] = $user->role;
        }

        $plainPassword = null;
        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8']);
            $plainPassword = $request->password;
            $validated['password'] = Hash::make($plainPassword);
        }

        // Simpan nomor lama & isi atribut baru ke model untuk mengecek perubahan
        $oldPhone = $user->phone;
        $user->fill($validated);

        // Daftar kolom yang menjadi pemicu wajib notifikasi WA
        $triggerFields = ['password', 'email', 'role', 'phone', 'nip', 'jabatan', 'pangkat'];
        $hasImportantChange = $user->isDirty($triggerFields);

        // Susun rincian perubahan sebelum disimpan (saat isDirty masih aktif)
        $changes = [];
        if ($user->isDirty('name'))       $changes[] = "• *Nama:* {$user->name}";
        if ($user->isDirty('nip'))        $changes[] = "• *NIP:* " . ($user->nip ?: '-');
        if ($user->isDirty('email'))      $changes[] = "• *Email Login:* {$user->email}";
        if ($user->isDirty('role'))       $changes[] = "• *Hak Akses (Role):* " . strtoupper($user->role);
        if ($user->isDirty('pangkat'))    $changes[] = "• *Pangkat/Gol:* " . ($user->pangkat ?: '-');
        if ($user->isDirty('jabatan'))    $changes[] = "• *Jabatan:* " . ($user->jabatan ?: '-');
        if ($user->isDirty('department')) $changes[] = "• *Unit Kerja:* " . ($user->department ?: '-');
        if ($user->isDirty('phone'))      $changes[] = "• *No. WhatsApp:* " . ($user->phone ?: '-');
        if ($plainPassword !== null)      $changes[] = "• *Password Baru:* `{$plainPassword}`";

        $phoneChanged = $user->isDirty('phone');

        $user->save();

        // Kirim WA hanya jika ada perubahan penting & bukan sedang mengedit akun sendiri
        if ($hasImportantChange && !$isSelf) {
            $adminName = $request->user()->name;
            $detailText = implode("\n", $changes);

            if (!empty($user->phone)) {
                $message = "Halo *{$user->name}*,\n\n"
                    . "Data akun Anda pada sistem *Peminjaman Kendaraan Dinas (BALMON-FLEET)* telah diperbarui oleh Admin (*{$adminName}*).\n\n"
                    . "*Rincian Perubahan:*\n{$detailText}\n\n"
                    . ($plainPassword ? "_Demi keamanan, segera ganti password Anda setelah berhasil login._\n" : "")
                    . "Jika Anda tidak merasa meminta perubahan ini, segera hubungi Admin.";

                // Panggil service WA Gateway Anda di sini
                WhatsAppService::sendMessage($user->phone, $message);
            }

            // Opsional: Beri peringatan ke nomor WA lama jika nomor diganti
            if ($phoneChanged && !empty($oldPhone) && $oldPhone !== $user->phone) {
                $alertOldPhone = "Halo *{$user->name}*,\n\n"
                    . "Nomor WhatsApp yang terhubung dengan akun *Peminjaman Kendaraan Dinas (BALMON-FLEET)* Anda baru saja diubah oleh Admin (*{$adminName}*) ke nomor baru.\n"
                    . "Jika ini bukan permintaan Anda, segera hubungi Admin.";

                WhatsAppService::sendMessage($oldPhone, $alertOldPhone);
            }
        }

        return redirect()
            ->route('admin_app.users.index')
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Anda tidak bisa menghapus akun sendiri!']);
        }

        $user->delete();

        return redirect()->route('admin_app.users.index')->with('success', 'Akun pegawai ' . $user->name . ' berhasil dihapus.');
    }

    public function downloadImportTemplate()
    {
        $templatePath = public_path('file/template_user.xlsx');

        if (!file_exists($templatePath)) {
            abort(404, 'Berkas template Excel tidak ditemukan di public/file/template_user.xlsx.');
        }

        return response()->download($templatePath, 'template_user.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Tahap 1: Membaca & memvalidasi berkas Excel/CSV menjadi JSON pratinjau tanpa menyimpan ke DB.
     */
    public function previewImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'import_file' => 'required|file|mimes:xlsx,csv,txt|max:5120',
            'import_mode' => 'required|in:skip,update',
        ], [
            'import_file.required' => 'Silakan pilih berkas Excel (.xlsx) atau CSV terlebih dahulu.',
            'import_file.mimes'    => 'Format berkas harus berupa Excel (.xlsx) atau CSV (.csv).',
            'import_file.max'      => 'Ukuran berkas maksimal 5 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $file      = $request->file('import_file');
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $rows = $this->parseSpreadsheetRows($file->getRealPath(), $extension);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if (count($rows) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas tidak memiliki baris data (minimal 1 baris header dan 1 baris data pegawai).',
            ], 422);
        }

        // Ambil baris pertama sebagai header & bersihkan UTF-8 BOM
        $rawHeader = array_shift($rows);
        if (isset($rawHeader[0])) {
            $rawHeader[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $rawHeader[0]);
        }

        $headerMap = [];
        $aliases   = [
            'nama'       => 'name',
            'name'       => 'name',
            'nip'        => 'nip',
            'email'      => 'email',
            'password'   => 'password',
            'kata_sandi' => 'password',
            'role'       => 'role',
            'hak_akses'  => 'role',
            'no_hp'      => 'phone',
            'phone'      => 'phone',
            'hp'         => 'phone',
            'whatsapp'   => 'phone',
            'no_wa'      => 'phone',
            'pangkat'    => 'pangkat',
            'golongan'   => 'pangkat',
            'jabatan'    => 'jabatan',
            'unit_kerja' => 'department',
            'department' => 'department',
            'seksi'      => 'department',
        ];

        foreach ($rawHeader as $idx => $colName) {
            $normalized = strtolower(trim((string) $colName));
            $normalized = str_replace([' ', '-'], '_', $normalized);
            if (isset($aliases[$normalized])) {
                $headerMap[$aliases[$normalized]] = $idx;
            }
        }

        if (!isset($headerMap['name']) || !isset($headerMap['email'])) {
            return response()->json([
                'success' => false,
                'message' => 'Header kolom tidak sesuai template. Wajib memiliki kolom "nama" dan "email".',
            ], 422);
        }

        $importMode  = $request->input('import_mode', 'skip');
        $previewRows = [];
        $seenEmails  = [];
        $seenNips    = [];
        $rowNumber   = 1;

        foreach ($rows as $row) {
            $rowNumber++;

            if (count(array_filter($row, fn($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $getValue = fn(string $key) => isset($headerMap[$key]) && isset($row[$headerMap[$key]])
                ? trim((string) $row[$headerMap[$key]])
                : '';

            $name       = $getValue('name');
            $rawNip     = ltrim($getValue('nip'), "'\" ");
            $rawPhone   = ltrim($getValue('phone'), "'\" ");
            $email      = strtolower($getValue('email'));
            $rawPass    = $getValue('password');
            $role       = strtolower($getValue('role')) ?: 'pegawai';
            $pangkat    = $getValue('pangkat');
            $jabatan    = $getValue('jabatan');
            $department = $getValue('department');

            $nip   = preg_replace('/[^0-9]/', '', $rawNip);
            $phone = preg_replace('/[^0-9]/', '', $rawPhone);
            if ($phone !== '' && str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $baseItem = [
                'row_number'    => $rowNumber,
                'name'          => $name,
                'nip'           => $nip,
                'email'         => $email,
                'password'      => $rawPass,
                'password_info' => $rawPass !== '' ? 'Sesuai Berkas' : 'Default (password123)',
                'role'          => $role,
                'phone'         => $phone,
                'pangkat'       => $pangkat,
                'jabatan'       => $jabatan,
                'department'    => $department,
            ];

            // 1. Cek notasi ilmiah Excel (E+)
            if (preg_match('/e\+/i', $rawNip) || preg_match('/e\+/i', $rawPhone)) {
                $previewRows[] = $baseItem + [
                    'selected'      => false,
                    'status'        => 'invalid',
                    'status_reason' => 'Format NIP/No. HP rusak karena notasi ilmiah Excel (E+). Gunakan format Text.',
                ];
                continue;
            }

            // 2. Cek duplikat internal di dalam berkas
            if ($email !== '' && in_array($email, $seenEmails, true)) {
                $previewRows[] = $baseItem + [
                    'selected'      => false,
                    'status'        => 'invalid',
                    'status_reason' => 'Email duplikat di dalam berkas yang sama.',
                ];
                continue;
            }
            if ($nip !== '' && in_array($nip, $seenNips, true)) {
                $previewRows[] = $baseItem + [
                    'selected'      => false,
                    'status'        => 'invalid',
                    'status_reason' => 'NIP duplikat di dalam berkas yang sama.',
                ];
                continue;
            }

            // 3. Cek apakah akun sudah terdaftar di database
            $existingUser = User::where('email', $email)
                ->when($nip !== '', fn($q) => $q->orWhere('nip', $nip))
                ->first();

            if ($existingUser) {
                if ($importMode === 'skip') {
                    $previewRows[] = $baseItem + [
                        'selected'      => false,
                        'status'        => 'skip',
                        'status_reason' => 'Sudah terdaftar di sistem (Mode: Lewati).',
                    ];
                } else {
                    $rowValidator = Validator::make([
                        'name'       => $name,
                        'nip'        => $nip ?: null,
                        'email'      => $email,
                        'password'   => $rawPass ?: null,
                        'role'       => $role,
                        'phone'      => $phone ?: null,
                        'pangkat'    => $pangkat ?: null,
                        'jabatan'    => $jabatan ?: null,
                        'department' => $department ?: null,
                    ], [
                        'name'       => 'required|string|max:255',
                        'nip'        => 'nullable|string|max:20|unique:users,nip,' . $existingUser->id,
                        'email'      => 'required|email|max:255|unique:users,email,' . $existingUser->id,
                        'password'   => 'nullable|string|min:8',
                        'role'       => 'required|in:admin_app,admin_lap,pegawai,approver,driver',
                        'phone'      => 'nullable|string|max:20',
                        'pangkat'    => 'nullable|string|max:100',
                        'jabatan'    => 'nullable|string|max:100',
                        'department' => 'nullable|string|max:200',
                    ], $this->importRowValidationMessages());

                    if ($rowValidator->fails()) {
                        $previewRows[] = $baseItem + [
                            'selected'      => false,
                            'status'        => 'invalid',
                            'status_reason' => implode(', ', $rowValidator->errors()->all()),
                        ];
                    } else {
                        $previewRows[] = $baseItem + [
                            'selected'      => true,
                            'status'        => 'update',
                            'status_reason' => 'Akun terdaftar — siap diperbarui.',
                        ];
                    }
                }
            } else {
                $defaultPassword = $rawPass !== '' ? $rawPass : 'password123';

                $rowValidator = Validator::make([
                    'name'       => $name,
                    'nip'        => $nip ?: null,
                    'email'      => $email,
                    'password'   => $defaultPassword,
                    'role'       => $role,
                    'phone'      => $phone ?: null,
                    'pangkat'    => $pangkat ?: null,
                    'jabatan'    => $jabatan ?: null,
                    'department' => $department ?: null,
                ], [
                    'name'       => 'required|string|max:255',
                    'nip'        => 'nullable|string|max:20|unique:users,nip',
                    'email'      => 'required|email|max:255|unique:users,email',
                    'password'   => 'required|string|min:8',
                    'role'       => 'required|in:admin_app,admin_lap,pegawai,approver,driver',
                    'phone'      => 'nullable|string|max:20',
                    'pangkat'    => 'nullable|string|max:100',
                    'jabatan'    => 'nullable|string|max:100',
                    'department' => 'nullable|string|max:200',
                ], $this->importRowValidationMessages());

                if ($rowValidator->fails()) {
                    $previewRows[] = $baseItem + [
                        'selected'      => false,
                        'status'        => 'invalid',
                        'status_reason' => implode(', ', $rowValidator->errors()->all()),
                    ];
                } else {
                    $previewRows[] = $baseItem + [
                        'selected'      => true,
                        'status'        => 'new',
                        'status_reason' => 'Siap ditambahkan.',
                    ];
                }
            }

            if ($email !== '') $seenEmails[] = $email;
            if ($nip !== '')   $seenNips[]   = $nip;
        }

        if (empty($previewRows)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ditemukan baris data pegawai di dalam berkas.',
            ], 422);
        }

        return response()->json([
            'success'     => true,
            'import_mode' => $importMode,
            'file_name'   => $file->getClientOriginalName(),
            'rows'        => $previewRows,
        ]);
    }

    /**
     * Tahap 2: Menyimpan hanya daftar pengguna yang dicentang oleh Admin pada modal pratinjau.
     */
    public function import(Request $request)
    {
        $request->validate([
            'rows_json'   => 'required|string',
            'import_mode' => 'required|in:skip,update',
        ], [
            'rows_json.required' => 'Tidak ada data pratinjau yang dikirimkan.',
        ]);

        $decodedRows = json_decode($request->input('rows_json'), true);

        if (!is_array($decodedRows) || empty($decodedRows)) {
            return back()->withErrors(['import_file' => 'Tidak ada pengguna yang dipilih untuk disimpan.']);
        }

        $importMode = $request->input('import_mode', 'skip');
        $created    = 0;
        $updated    = 0;
        $skipped    = 0;
        $rowErrors  = [];

        DB::transaction(function () use ($decodedRows, $importMode, &$created, &$updated, &$skipped, &$rowErrors) {
            foreach ($decodedRows as $item) {
                if (empty($item['selected'])) {
                    continue;
                }

                $rowNumber  = (int) ($item['row_number'] ?? 0);
                $name       = trim((string) ($item['name'] ?? ''));
                $nip        = preg_replace('/[^0-9]/', '', (string) ($item['nip'] ?? ''));
                $email      = strtolower(trim((string) ($item['email'] ?? '')));
                $rawPass    = trim((string) ($item['password'] ?? ''));
                $role       = strtolower(trim((string) ($item['role'] ?? 'pegawai'))) ?: 'pegawai';
                $phone      = preg_replace('/[^0-9]/', '', (string) ($item['phone'] ?? ''));
                $pangkat    = trim((string) ($item['pangkat'] ?? ''));
                $jabatan    = trim((string) ($item['jabatan'] ?? ''));
                $department = trim((string) ($item['department'] ?? ''));

                if ($phone !== '' && str_starts_with($phone, '0')) {
                    $phone = '62' . substr($phone, 1);
                }

                $existingUser = User::where('email', $email)
                    ->when($nip !== '', fn($q) => $q->orWhere('nip', $nip))
                    ->first();

                if ($existingUser) {
                    if ($importMode === 'skip') {
                        $skipped++;
                        continue;
                    }

                    $validator = Validator::make([
                        'name'       => $name,
                        'nip'        => $nip ?: null,
                        'email'      => $email,
                        'password'   => $rawPass ?: null,
                        'role'       => $role,
                        'phone'      => $phone ?: null,
                        'pangkat'    => $pangkat ?: null,
                        'jabatan'    => $jabatan ?: null,
                        'department' => $department ?: null,
                    ], [
                        'name'       => 'required|string|max:255',
                        'nip'        => 'nullable|string|max:20|unique:users,nip,' . $existingUser->id,
                        'email'      => 'required|email|max:255|unique:users,email,' . $existingUser->id,
                        'password'   => 'nullable|string|min:8',
                        'role'       => 'required|in:admin_app,admin_lap,pegawai,approver,driver',
                        'phone'      => 'nullable|string|max:20',
                        'pangkat'    => 'nullable|string|max:100',
                        'jabatan'    => 'nullable|string|max:100',
                        'department' => 'nullable|string|max:200',
                    ], $this->importRowValidationMessages());

                    if ($validator->fails()) {
                        $rowErrors[] = "Baris {$rowNumber} ({$email}): " . implode(', ', $validator->errors()->all());
                        continue;
                    }

                    $updatePayload = [
                        'name'       => $name,
                        'nip'        => $nip ?: $existingUser->nip,
                        'email'      => $email,
                        'phone'      => $phone ?: $existingUser->phone,
                        'pangkat'    => $pangkat ?: $existingUser->pangkat,
                        'jabatan'    => $jabatan ?: $existingUser->jabatan,
                        'department' => $department ?: $existingUser->department,
                    ];

                    if ($existingUser->id !== Auth::id()) {
                        $updatePayload['role'] = $role;
                    }

                    if ($rawPass !== '') {
                        $updatePayload['password'] = Hash::make($rawPass);
                    }

                    $existingUser->update($updatePayload);
                    $updated++;
                } else {
                    $defaultPassword = $rawPass !== '' ? $rawPass : 'password123';

                    $validator = Validator::make([
                        'name'       => $name,
                        'nip'        => $nip ?: null,
                        'email'      => $email,
                        'password'   => $defaultPassword,
                        'role'       => $role,
                        'phone'      => $phone ?: null,
                        'pangkat'    => $pangkat ?: null,
                        'jabatan'    => $jabatan ?: null,
                        'department' => $department ?: null,
                    ], [
                        'name'       => 'required|string|max:255',
                        'nip'        => 'nullable|string|max:20|unique:users,nip',
                        'email'      => 'required|email|max:255|unique:users,email',
                        'password'   => 'required|string|min:8',
                        'role'       => 'required|in:admin_app,admin_lap,pegawai,approver,driver',
                        'phone'      => 'nullable|string|max:20',
                        'pangkat'    => 'nullable|string|max:100',
                        'jabatan'    => 'nullable|string|max:100',
                        'department' => 'nullable|string|max:200',
                    ], $this->importRowValidationMessages());

                    if ($validator->fails()) {
                        $rowErrors[] = "Baris {$rowNumber} ({$email}): " . implode(', ', $validator->errors()->all());
                        continue;
                    }

                    User::create([
                        'name'       => $name,
                        'nip'        => $nip ?: null,
                        'email'      => $email,
                        'password'   => Hash::make($defaultPassword),
                        'role'       => $role,
                        'phone'      => $phone ?: null,
                        'pangkat'    => $pangkat ?: null,
                        'jabatan'    => $jabatan ?: null,
                        'department' => $department ?: null,
                    ]);
                    $created++;
                }
            }
        });

        $summary = "Import selesai: {$created} akun ditambahkan"
            . ($updated > 0 ? ", {$updated} akun diperbarui" : '')
            . ($skipped > 0 ? ", {$skipped} baris dilewati" : '')
            . '.';

        $redirect = redirect()->route('admin_app.users.index')->with('success', $summary);

        if (!empty($rowErrors)) {
            $redirect->with('import_errors', $rowErrors);
        }

        return $redirect;
    }

    /**
     * Membaca baris data dari file .xlsx (sheet pertama) atau .csv secara native tanpa package eksternal.
     */
    private function parseSpreadsheetRows(string $filePath, string $extension): array
    {
        if ($extension === 'xlsx') {
            if (!class_exists(\ZipArchive::class)) {
                throw new \RuntimeException('Ekstensi PHP ZipArchive tidak aktif pada server.');
            }

            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                throw new \RuntimeException('Gagal membuka berkas Excel (.xlsx). Pastikan file tidak terkunci password.');
            }

            // 1. Baca tabel Shared Strings jika tersedia
            $sharedStrings = [];
            $sharedXmlRaw  = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedXmlRaw !== false) {
                $sharedXml = @simplexml_load_string($sharedXmlRaw);
                if ($sharedXml && isset($sharedXml->si)) {
                    foreach ($sharedXml->si as $si) {
                        if (isset($si->t)) {
                            $sharedStrings[] = (string) $si->t;
                        } elseif (isset($si->r)) {
                            $combined = '';
                            foreach ($si->r as $r) {
                                $combined .= (string) ($r->t ?? '');
                            }
                            $sharedStrings[] = $combined;
                        } else {
                            $sharedStrings[] = '';
                        }
                    }
                }
            }

            // 2. Baca worksheet pertama (xl/worksheets/sheet1.xml)
            $sheetXmlRaw = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();

            if ($sheetXmlRaw === false) {
                throw new \RuntimeException('Worksheet pertama tidak ditemukan di dalam berkas Excel.');
            }

            $sheetXml = @simplexml_load_string($sheetXmlRaw);
            if (!$sheetXml || !isset($sheetXml->sheetData->row)) {
                throw new \RuntimeException('Isi worksheet Excel tidak valid atau kosong.');
            }

            $colToIndex = static function (string $cellRef): int {
                $letters = preg_replace('/[^A-Z]/', '', strtoupper($cellRef));
                $index   = 0;
                $len     = strlen($letters);
                for ($i = 0; $i < $len; $i++) {
                    $index = $index * 26 + (ord($letters[$i]) - 64);
                }
                return max(0, $index - 1);
            };

            $rows = [];
            foreach ($sheetXml->sheetData->row as $row) {
                $cellsByIndex = [];
                $maxCol       = 0;

                foreach ($row->c as $cell) {
                    $cellRef = (string) ($cell['r'] ?? '');
                    $colIdx  = $cellRef !== '' ? $colToIndex($cellRef) : count($cellsByIndex);
                    $maxCol  = max($maxCol, $colIdx);
                    $type    = (string) ($cell['t'] ?? '');

                    if ($type === 's') {
                        $strIdx = (int) ($cell->v ?? -1);
                        $value  = $sharedStrings[$strIdx] ?? '';
                    } elseif ($type === 'inlineStr') {
                        if (isset($cell->is->t)) {
                            $value = (string) $cell->is->t;
                        } elseif (isset($cell->is->r)) {
                            $value = '';
                            foreach ($cell->is->r as $r) {
                                $value .= (string) ($r->t ?? '');
                            }
                        } else {
                            $value = '';
                        }
                    } else {
                        $value = isset($cell->v) ? (string) $cell->v : '';
                    }

                    $cellsByIndex[$colIdx] = $value;
                }

                $normalizedRow = array_fill(0, $maxCol + 1, '');
                foreach ($cellsByIndex as $idx => $val) {
                    $normalizedRow[$idx] = $val;
                }

                $rows[] = $normalizedRow;
            }

            return $rows;
        }

        // Fallback untuk berkas CSV (.csv / .txt)
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Gagal membaca berkas CSV.');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }
}