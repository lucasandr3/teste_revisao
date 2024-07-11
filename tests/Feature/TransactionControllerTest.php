<?php

namespace Tests\Feature;

use App\Services\Transaction;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use WithFaker;

    private const TIME_TRANSACTIONS = 60;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transactionService = $this->mock(Transaction::class);
    }

    public function testStoreValidTransaction(): void
    {
        $dataTransaction = [
            'amount' => $this->faker->randomFloat(2, 0, 100),
            'timestamp' => Carbon::now()->subSeconds(self::TIME_TRANSACTIONS - 1)->toISOString(),
        ];

        $this->transactionService->expects('getLastTransaction')->andReturn([]);
        $this->transactionService->expects('saveTransaction')->once();

        $response = $this->postJson('/api/transactions', $dataTransaction);
        $response->assertStatus(JsonResponse::HTTP_CREATED);
    }

    public function testStoreInvalidTransaction(): void
    {
        $dataTransaction = [
            'amount' => 'dado invalido',
            'timestamp' => Carbon::now()->addSeconds(1)->toISOString(),
        ];

        $this->transactionService->expects('saveTransaction')->never();

        $response = $this->postJson('/api/transactions', $dataTransaction);
        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testStoreFutureTransaction(): void
    {
        $dataTransaction = [
            'amount' => '12.334',
            'timestamp' => '2024-08-17T09:59:51.312Z',
        ];

        $this->transactionService->expects('saveTransaction')->never();

        $response = $this->postJson('/api/transactions', $dataTransaction);
        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testStatistics(): void
    {
        $transactions = [
            [
                'amount' => $this->faker->randomFloat(2, 0, 100),
                'timestamp' => Carbon::now()->subSeconds(self::TIME_TRANSACTIONS - 1)->toISOString(),
            ]
        ];

        $this->transactionService->expects('getTransactions')->andReturn($transactions);
        $this->transactionService->expects('calculateStatistics')->once()->with($transactions)->andReturn([
            'sum' => 200.50,
            'avg' => 100.25,
            'max' => 150.75,
            'min' => 49.75,
            'count' => 2,
        ]);

        $response = $this->getJson('/api/statistics');
        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJsonStructure([
            'sum',
            'avg',
            'max',
            'min',
            'count',
        ]);
    }
}
