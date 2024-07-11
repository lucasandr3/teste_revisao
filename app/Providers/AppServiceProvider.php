<?php

namespace App\Providers;

use App\Services\LockTransactions;
use App\Services\Transaction;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
        $transactionsFile = base_path(Transaction::TRANSACTIONS_FILE);

        if (!File::exists($transactionsFile)) {
            try {
                File::put($transactionsFile, json_encode([], JSON_THROW_ON_ERROR));
            } catch (\JsonException $e) {
                Log::emergency($e->getMessage());
            }
        }

        $lockTransactionsFile = base_path(LockTransactions::LOCK_FILE);

        if (!File::exists($lockTransactionsFile)) {
            try {
                File::put($lockTransactionsFile, json_encode([], JSON_THROW_ON_ERROR));
            } catch (\JsonException $e) {
                Log::emergency($e->getMessage());
            }
        }
    }
}
