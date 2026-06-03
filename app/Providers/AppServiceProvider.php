<?php

namespace App\Providers;

use App\Models\CommentNotification;
use App\Models\Company;
use App\Support\CompanyContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gunakan markup pagination Bootstrap 5 agar sesuai tema (default Laravel = Tailwind).
        Paginator::useBootstrapFive();

        // Suplai data notifikasi komentar ke topbar pada setiap halaman.
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();

            if (! $user) {
                $view->with(['navNotifs' => collect(), 'navNotifUnread' => 0]);
                return;
            }

            // Data switcher perusahaan untuk topbar.
            $isGlobalAdmin = $user->isGlobalAdmin();
            $view->with([
                'isGlobalAdmin'  => $isGlobalAdmin,
                'activeCompany'  => CompanyContext::id() ? Company::find(CompanyContext::id()) : null,
                'switchCompanies' => $isGlobalAdmin
                    ? Company::where('is_active', true)->orderBy('id')->get()
                    : collect(),
            ]);

            $navNotifs = CommentNotification::where('user_id', $user->id)
                ->with(['comment.author', 'comment.report'])
                ->latest()
                ->limit(10)
                ->get();

            $navNotifUnread = CommentNotification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();

            $view->with(compact('navNotifs', 'navNotifUnread'));
        });
    }
}
