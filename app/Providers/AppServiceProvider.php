<?php

namespace App\Providers;

use App\Models\CommentNotification;
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
        // Suplai data notifikasi komentar ke topbar pada setiap halaman.
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();

            if (! $user) {
                $view->with(['navNotifs' => collect(), 'navNotifUnread' => 0]);
                return;
            }

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
