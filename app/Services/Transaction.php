<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use JsonException;

class Transaction
{
    public const TRANSACTIONS_FILE = 'transactions.json';
    private const PRECISION = 2;

    public function __construct(private readonly LockTransactions $lockTransactions)
    {
    }

    /**
     * @throws JsonException
     */
    public function getLastTransaction(): ?array
    {
        $transactions = $this->getTransactions();

        if (count($transactions) === 1) {
            return $transactions[0];
        }

        if (count($transactions) > 1) {
            return end($transactions);
        }

        return [];
    }

    /**
     * @throws JsonException
     * @throws \Exception
     */
    public function getTransactions(): array
    {
        $lock = $this->lockTransactions->lock();

        if (!$lock) {
            throw new Exception('Não foi possível iniciar o lock');
        }

        try {
            if (File::exists(base_path(self::TRANSACTIONS_FILE))) {
                return json_decode(
                    File::get(base_path(self::TRANSACTIONS_FILE)),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            }

            return [];
        } finally {
            $this->lockTransactions->unLock($lock);
        }
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function saveTransaction(array $transactions): void
    {
        $lock = $this->lockTransactions->lock();

        if (!$lock) {
            throw new \RuntimeException('Não foi possível iniciar o lock');
        }

        try {
            if (File::exists(base_path(self::TRANSACTIONS_FILE))) {
                $database = json_decode(
                    File::get(base_path(self::TRANSACTIONS_FILE)),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            }

            $database[] = $transactions;
            File::put(base_path(self::TRANSACTIONS_FILE), json_encode($database, JSON_THROW_ON_ERROR));
        } finally {
            $this->lockTransactions->unLock($lock);
        }
    }

    public function calculateStatistics(array $transactions): array
    {
        if (empty($transactions)) {
            return [
                'sum' => $this->formatFloat(0),
                'avg' => $this->formatFloat(0),
                'max' => $this->formatFloat(0),
                'min' => $this->formatFloat(0),
                'count' => 0
            ];
        }

        $sum = array_sum(array_column($transactions, 'amount'));
        $count = count($transactions);
        $avg = $sum / $count;
        $max = max(array_column($transactions, 'amount'));
        $min = min(array_column($transactions, 'amount'));

        return [
            'sum' => $this->formatFloat($sum),
            'avg' => $this->formatFloat($avg),
            'max' => $this->formatFloat($max),
            'min' => $this->formatFloat($min),
            'count' => $count
        ];
    }

    public function destroy(): void
    {
        if (File::exists(base_path(self::TRANSACTIONS_FILE))) {
            File::put(base_path(self::TRANSACTIONS_FILE), '[]');
        }
    }

    private function formatFloat($value): string
    {
        $roundedValue = round($value, self::PRECISION);
        return number_format($roundedValue, self::PRECISION, '.', '');
    }
}
