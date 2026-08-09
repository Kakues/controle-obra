<?php

use App\Http\Controllers\ComprovantePeriodoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\LancamentoController;
use App\Http\Controllers\MarcacaoRapidaController;
use App\Http\Controllers\ObraController;
use App\Http\Controllers\PeriodoPagamentoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RelatorioObraController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/presencas/marcacao', [MarcacaoRapidaController::class, 'create'])->name('presencas.marcacao');
    Route::post('/presencas/marcacao', [MarcacaoRapidaController::class, 'store'])->name('presencas.marcacao.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('admin')->group(function () {
        Route::resource('obras', ObraController::class)->except(['show']);
        Route::resource('lancamentos', LancamentoController::class)->except(['show']);

        Route::get('/funcionarios/create', [FuncionarioController::class, 'create'])->name('funcionarios.create');
        Route::post('/funcionarios', [FuncionarioController::class, 'store'])->name('funcionarios.store');

        Route::get('/periodos/create', [PeriodoPagamentoController::class, 'create'])->name('periodos.create');
        Route::post('/periodos', [PeriodoPagamentoController::class, 'store'])->name('periodos.store');

        Route::get('/relatorios/obras', RelatorioObraController::class)->name('relatorios.obras');
    });

    Route::get('/funcionarios', [FuncionarioController::class, 'index'])->name('funcionarios.index');
    Route::get('/funcionarios/{funcionario}', [FuncionarioController::class, 'show'])->name('funcionarios.show');

    Route::get('/periodos', [PeriodoPagamentoController::class, 'index'])->name('periodos.index');
    Route::get('/periodos/{periodo}', [PeriodoPagamentoController::class, 'show'])->name('periodos.show');
    Route::get('/periodos/{periodo}/comprovante', ComprovantePeriodoController::class)->name('periodos.comprovante');

    Route::middleware('admin')->group(function () {
        Route::get('/funcionarios/{funcionario}/edit', [FuncionarioController::class, 'edit'])->name('funcionarios.edit');
        Route::put('/funcionarios/{funcionario}', [FuncionarioController::class, 'update'])->name('funcionarios.update');
        Route::patch('/funcionarios/{funcionario}', [FuncionarioController::class, 'update']);
        Route::delete('/funcionarios/{funcionario}', [FuncionarioController::class, 'destroy'])->name('funcionarios.destroy');
        Route::post('/funcionarios/{funcionario}/reativar', [FuncionarioController::class, 'reativar'])
            ->name('funcionarios.reativar');

        Route::delete('/periodos/{periodo}', [PeriodoPagamentoController::class, 'destroy'])->name('periodos.destroy');
        Route::post('/periodos/{periodo}/fechar', [PeriodoPagamentoController::class, 'fechar'])->name('periodos.fechar');
        Route::get('/periodos/{periodo}/pagar', [PeriodoPagamentoController::class, 'formularioPagar'])->name('periodos.pagar.form');
        Route::post('/periodos/{periodo}/pagar', [PeriodoPagamentoController::class, 'pagar'])->name('periodos.pagar');
        Route::post('/periodos/{periodo}/reabrir', [PeriodoPagamentoController::class, 'reabrir'])->name('periodos.reabrir');
    });
});

require __DIR__.'/auth.php';
