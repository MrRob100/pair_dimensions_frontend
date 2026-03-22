<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\LiveState;
use App\Models\LiveTrade;
use App\Models\Pair;
use App\Models\PairInput;
use App\Services\BinanceService;

class BinanceSwapTest extends TestCase
{
    private BinanceService $binance;

    protected function setUp(): void
    {
        parent::setUp();

        // Use MySQL test database, not SQLite
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'pair_dimensions_test',
            'binance.testnet' => true,
        ]);

        // Purge cached MySQL connection so it picks up the new config
        app('db')->purge('mysql');

        $this->binance = app(BinanceService::class);
    }

    public function test_fetch_balance_returns_balances(): void
    {
        $account = $this->binance->fetchBalance();

        $this->assertArrayNotHasKey('code', $account, 'API returned error: ' . ($account['msg'] ?? ''));
        $this->assertArrayHasKey('balances', $account);
        $this->assertIsArray($account['balances']);
    }

    public function test_fetch_balance_includes_known_assets(): void
    {
        $account = $this->binance->fetchBalance();

        $assets = array_column($account['balances'], 'asset');
        $this->assertContains('BTC', $assets);
        $this->assertContains('USDT', $assets);
    }

    public function test_market_sell_order(): void
    {
        // First check we have some BNB to sell
        $account = $this->binance->fetchBalance();
        $bnbBalance = null;
        foreach ($account['balances'] as $b) {
            if ($b['asset'] === 'BNB') {
                $bnbBalance = floatval($b['free']);
                break;
            }
        }

        $this->assertNotNull($bnbBalance, 'BNB not found in testnet balances');

        if ($bnbBalance < 0.01) {
            $this->markTestSkipped('Insufficient BNB testnet balance to test sell');
        }

        $result = $this->binance->createOrder('BNBUSDT', 'MARKET', 'SELL', 0.01);

        $this->assertArrayNotHasKey('code', $result, 'Sell order error: ' . ($result['msg'] ?? json_encode($result)));
        $this->assertEquals('BNBUSDT', $result['symbol']);
        $this->assertEquals('SELL', $result['side']);
        $this->assertEquals('FILLED', $result['status']);
        $this->assertNotEmpty($result['fills']);
    }

    public function test_market_buy_order(): void
    {
        // Check we have USDT
        $account = $this->binance->fetchBalance();
        $usdtBalance = null;
        foreach ($account['balances'] as $b) {
            if ($b['asset'] === 'USDT') {
                $usdtBalance = floatval($b['free']);
                break;
            }
        }

        $this->assertNotNull($usdtBalance, 'USDT not found in testnet balances');

        if ($usdtBalance < 10) {
            $this->markTestSkipped('Insufficient USDT testnet balance to test buy');
        }

        // Buy a small amount of BNB
        $result = $this->binance->createOrder('BNBUSDT', 'MARKET', 'BUY', 0.01);

        $this->assertArrayNotHasKey('code', $result, 'Buy order error: ' . ($result['msg'] ?? json_encode($result)));
        $this->assertEquals('BNBUSDT', $result['symbol']);
        $this->assertEquals('BUY', $result['side']);
        $this->assertEquals('FILLED', $result['status']);
    }

    public function test_exchange_info_returns_lot_size(): void
    {
        $info = $this->binance->exchangeInfo('BNBUSDT');

        $this->assertArrayHasKey('symbols', $info);
        $this->assertNotEmpty($info['symbols']);

        $symbol = $info['symbols'][0];
        $this->assertEquals('BNBUSDT', $symbol['symbol']);

        $lotSize = collect($symbol['filters'])->firstWhere('filterType', 'LOT_SIZE');
        $this->assertNotNull($lotSize, 'LOT_SIZE filter not found');
        $this->assertArrayHasKey('stepSize', $lotSize);
    }

    public function test_swap_endpoint_validates_params(): void
    {
        $response = $this->postJson('/api/binance/swap', []);
        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }

    public function test_input_endpoint(): void
    {
        $pair = Pair::firstOrCreate(
            ['symbol_1' => 'LTC', 'symbol_2' => 'DOGE'],
            ['type' => 'spot']
        );

        // Clean up any previous state
        LiveState::where('pair_id', $pair->id)->delete();

        $response = $this->postJson('/api/binance/input', [
            'pair_id' => $pair->id,
            'symbol' => 'LTC',
            'amount' => 1.5,
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals(1.5, $data['holdings']['coin1']);
        $this->assertEquals(0, $data['holdings']['coin2']);

        // Verify live_state
        $state = LiveState::where('pair_id', $pair->id)->first();
        $this->assertNotNull($state);
        $this->assertEquals(1.5, $state->holdings_coin1);
    }

    public function test_shave_endpoint(): void
    {
        $pair = Pair::firstOrCreate(
            ['symbol_1' => 'LTC', 'symbol_2' => 'DOGE'],
            ['type' => 'spot']
        );

        // Ensure there's something to shave
        LiveState::updateOrCreate(
            ['pair_id' => $pair->id],
            ['holdings_coin1' => 5.0, 'holdings_coin2' => 0, 'holdings_usdt' => 0, 'pair_position' => 1, 'is_active' => true]
        );

        $response = $this->postJson('/api/binance/shave', [
            'pair_id' => $pair->id,
            'symbol' => 'LTC',
            'amount' => 2.0,
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertEquals(3.0, $data['holdings']['coin1']);
    }

    public function test_shave_rejects_over_allocation(): void
    {
        $pair = Pair::firstOrCreate(
            ['symbol_1' => 'LTC', 'symbol_2' => 'DOGE'],
            ['type' => 'spot']
        );

        LiveState::updateOrCreate(
            ['pair_id' => $pair->id],
            ['holdings_coin1' => 1.0, 'holdings_coin2' => 0, 'holdings_usdt' => 0, 'pair_position' => 1, 'is_active' => true]
        );

        $response = $this->postJson('/api/binance/shave', [
            'pair_id' => $pair->id,
            'symbol' => 'LTC',
            'amount' => 5.0,
        ]);

        $response->assertStatus(400);
    }

    public function test_swap_requires_allocation(): void
    {
        $pair = Pair::firstOrCreate(
            ['symbol_1' => 'SOL', 'symbol_2' => 'AVAX'],
            ['type' => 'spot']
        );

        // No live_state = no allocation
        LiveState::where('pair_id', $pair->id)->delete();

        $response = $this->postJson('/api/binance/swap', [
            'from' => 'SOL',
            'to' => 'AVAX',
            'pair_id' => $pair->id,
        ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('input first', $response->json('error'));
    }

    public function test_swap_endpoint_full_flow(): void
    {
        // Use LTC — we have plenty on testnet
        $account = $this->binance->fetchBalance();
        $ltcBalance = null;
        foreach ($account['balances'] as $b) {
            if ($b['asset'] === 'LTC') {
                $ltcBalance = floatval($b['free']);
                break;
            }
        }

        if (!$ltcBalance || $ltcBalance < 1) {
            $this->markTestSkipped('Insufficient LTC testnet balance to test swap');
        }

        $pair = Pair::firstOrCreate(
            ['symbol_1' => 'LTC', 'symbol_2' => 'DOGE'],
            ['type' => 'spot']
        );

        // Clean state and fund pair with 1 LTC via input
        LiveState::where('pair_id', $pair->id)->delete();

        $this->postJson('/api/binance/input', [
            'pair_id' => $pair->id,
            'symbol' => 'LTC',
            'amount' => 1,
        ])->assertOk();

        $tradeCountBefore = LiveTrade::where('pair_id', $pair->id)->count();

        $response = $this->postJson('/api/binance/swap', [
            'from' => 'LTC',
            'to' => 'DOGE',
            'pair_id' => $pair->id,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'sell',
            'buy',
            'usdt_intermediate',
            'cross_price',
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertGreaterThan(0, $data['usdt_intermediate']);
        $this->assertGreaterThan(0, $data['cross_price']);

        // Verify trades were logged
        $trades = LiveTrade::where('pair_id', $pair->id)
            ->orderBy('id')
            ->skip($tradeCountBefore)
            ->take(2)
            ->get();

        $this->assertCount(2, $trades);
        $this->assertEquals('sell', $trades[0]->side);
        $this->assertEquals('LTC', $trades[0]->symbol);
        $this->assertEquals('buy', $trades[1]->side);
        $this->assertEquals('DOGE', $trades[1]->symbol);
        $this->assertEquals(2, $trades[1]->pair_position_after);

        // Verify live_state: all in coin2 (DOGE)
        $liveState = LiveState::where('pair_id', $pair->id)->first();
        $this->assertNotNull($liveState);
        $this->assertEquals(2, $liveState->pair_position);
        $this->assertEquals(0, $liveState->holdings_coin1);
        $this->assertGreaterThan(0, $liveState->holdings_coin2);

        // Verify input was recorded
        $inputs = PairInput::where('pair_id', $pair->id)->where('type', 'input')->get();
        $this->assertGreaterThanOrEqual(1, $inputs->count());
    }
}
