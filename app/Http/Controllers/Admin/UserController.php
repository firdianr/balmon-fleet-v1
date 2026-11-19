<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
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
}