<?php

namespace App\Http\Controllers;

use App\Services\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    private const TIME_TRANSACTIONS = 60;

    public function __construct(private readonly Transaction $transactionService)
    {
    }

    /**
     * @throws JsonException
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = Validator::make($request->all(), [
            'amount' => 'required',
            'timestamp' => 'required|date|before_or_equal:now',
        ]);

        if ($validatedData->fails()) {
            return response()->json($validatedData->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $dataTransaction = $validatedData->validated();

        if (!isset($dataTransaction['amount'], $dataTransaction['timestamp']) || !is_numeric($dataTransaction['amount'])) {
            return response()->json([], Response::HTTP_BAD_REQUEST);
        }

        $timestamp = Carbon::parse($dataTransaction['timestamp']);

        if ($timestamp->isFuture()) {
            return response()->json([], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $lastTransaction = $this->transactionService->getLastTransaction();

        if (!empty($lastTransaction)) {
            $dateLastTransaction = Carbon::parse($lastTransaction['timestamp']);
            $differenceInSeconds = $dateLastTransaction->diffInSeconds($timestamp);

            if ($differenceInSeconds >= self::TIME_TRANSACTIONS) {
                return response()->json([], Response::HTTP_NO_CONTENT);
            }
        }

        $this->transactionService->saveTransaction($dataTransaction);
        return response()->json([], Response::HTTP_CREATED);
    }

    /**
     * @throws JsonException
     */
    public function statistics(): JsonResponse
    {
        $now = Carbon::now();
        $limitDate = $now->subSeconds(self::TIME_TRANSACTIONS)->toISOString();
        $transactions = $this->transactionService->getTransactions();

        $validTransactions = array_filter($transactions, static function($transaction) use ($limitDate) {
            return $transaction['timestamp'] >= $limitDate;
        });

        $statistics = $this->transactionService->calculateStatistics($validTransactions);
        return response()->json($statistics, Response::HTTP_OK);
    }

    public function destroy(): JsonResponse
    {
        $this->transactionService->destroy();
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
