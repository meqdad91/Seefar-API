<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminGate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(private AdminGate $gate) {}

    public function showLogin(Request $request)
    {
        if ($request->session()->has('admin_user_id')) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('deleted', 0)
            ->where('suspended', 0)
            ->where('username', $credentials['username'])
            ->first();

        if (! $user || ! $user->password || ! password_verify($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['username' => 'Invalid credentials.']);
        }

        if (! $this->gate->isAdmin($user->id)) {
            throw ValidationException::withMessages(['username' => 'This account is not authorized for admin access.']);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_user_id', $user->id);
        $request->session()->put('admin_username', $user->username);
        $request->session()->put('admin_full_name', trim("{$user->firstname} {$user->lastname}"));

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
