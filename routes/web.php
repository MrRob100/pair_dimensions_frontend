 <?php

use App\Http\Controllers\BacktestingController;
use App\Http\Controllers\PairUseController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BacktestingController::class, 'index'])->name('backtesting.index');
Route::get('/backtesting', [BacktestingController::class, 'index'])->name('backtesting.results');
Route::get('/test-lwc', [TestController::class, 'lwc'])->name('test.lwc');
Route::post('/pair-use/watch/{pairId}', [PairUseController::class, 'watch'])->name('pair-use.watch');
Route::post('/pair-use/set-status/{pairId}', [PairUseController::class, 'setStatus'])->name('pair-use.set-status');

