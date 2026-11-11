<?php

namespace App\Providers;

use App\Services\PengaduanService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('pagination.custom');
        Paginator::defaultSimpleView('pagination.custom');

        // Angka lencana (notifikasi & pesan belum dibaca) pada sidebar.
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();

            if ($user) {
                $layanan = app(PengaduanService::class);
                $view->with('badgeNotif', $layanan->notifBelumDibaca($user));
                $view->with('badgePesan', $layanan->pesanBelumDibaca($user));
            }
        });
    }
}
