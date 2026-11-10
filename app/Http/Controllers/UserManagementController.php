<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
class UserManagementController extends Controller
{
    public function index() { $users = User::orderBy('role')->orderBy('name')->paginate(15); return view('users.index', compact('users')); }
    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required','string','max:120'], 'email' => ['required','email','max:255','unique:users,email'], 'role' => ['required',Rule::in(array_keys(User::ROLES))], 'password' => ['required','string','min:8','confirmed']]);
        $data['password'] = Hash::make($data['password']); User::create($data);
        return back()->with('success', 'Akun pengguna berhasil dibuat.');
    }
    public function update(Request $request, User $user)
    {
        $data = $request->validate(['name' => ['required','string','max:120'], 'email' => ['required','email','max:255',Rule::unique('users','email')->ignore($user->id)], 'role' => ['required',Rule::in(array_keys(User::ROLES))], 'password' => ['nullable','string','min:8','confirmed']]);
        if ($user->id === auth()->id() && $data['role'] !== $user->role) return back()->with('error', 'Role akun yang sedang digunakan tidak dapat diubah dari sesi ini.');
        if (!empty($data['password'])) $data['password'] = Hash::make($data['password']); else unset($data['password']);
        $user->update($data);
        return back()->with('success', 'Data akun berhasil diperbarui.');
    }
}
