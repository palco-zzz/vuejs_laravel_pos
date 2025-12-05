<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'role' => $user?->role,
                'permissions' => $this->getUserPermissions($user),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Get user permissions based on role
     */
    protected function getUserPermissions($user): array
    {
        if (!$user) {
            return [];
        }

        // Admin has all permissions
        if ($user->role === 'admin') {
            return [
                'view_all_branches' => true,
                'manage_branches' => true,
                'manage_menu' => true,
                'manage_employees' => true,
                'view_reports' => true,
                'manage_pos' => true,
            ];
        }

        // Cashier has limited permissions
        if ($user->role === 'cashier') {
            return [
                'view_all_branches' => false,
                'manage_branches' => false,
                'manage_menu' => false,
                'manage_employees' => false,
                'view_reports' => false,
                'manage_pos' => true, // Can use POS
            ];
        }

        return [];
    }
}
