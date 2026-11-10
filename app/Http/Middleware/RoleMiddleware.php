<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $userRole = $this->normalizeRole(
            (string) $user->role
        );

        $allowedRoles = array_map(
            fn ($role) => $this->normalizeRole($role),
            $roles
        );

        if (!in_array($userRole, $allowedRoles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }

    private function normalizeRole(string $role): string
    {
        $role = strtolower(trim($role));
        $role = str_replace(['_', '-'], ' ', $role);
        $role = preg_replace('/\s+/', ' ', $role) ?? $role;

        return match ($role) {
            'pengguna fasilitas',
            'facility user',
            'pengguna',
            'user' => 'pengguna',

            'sarpras',
            'sarana prasarana',
            'sarana dan prasarana' => 'sarpras',

            'teknisi',
            'technician' => 'teknisi',

            'manajemen',
            'management' => 'manajemen',

            default => str_replace(' ', '', $role),
        };
    }
}