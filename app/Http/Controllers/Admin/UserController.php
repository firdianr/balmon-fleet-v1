<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name', 'asc')->paginate(10);

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
            'role'       => 'required|in:admin,pegawai',
            'phone'      => 'nullable|string|max:20',
            'department' => 'nullable|string|max:100',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'Akun Pegawai berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'nip'        => 'nullable|string|max:20|unique:users,nip,' . $user->id,
            'email'      => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role'       => 'required|in:admin,pegawai',
            'phone'      => 'nullable|string|max:20',
            'department' => 'nullable|string|max:100',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8']);
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Anda tidak bisa menghapus akun sendiri!']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Akun pegawai ' . $user->name . ' berhasil dihapus.');
    }
}