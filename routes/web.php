<?php

use App\Http\Controllers\Auth\LoginController;
use App\Livewire\Account;
use App\Livewire\Categories;
use App\Livewire\Dashboard;
use App\Livewire\More;
use App\Livewire\Settings;
use App\Livewire\Items;
use App\Livewire\Lending;
use App\Livewire\Places;
use App\Livewire\Tags;
use App\Livewire\Upkeep;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');

    Route::get('/items', Items\Index::class)->name('items.index');
    Route::get('/items/create', Items\Form::class)->name('items.create');
    Route::get('/items/{item}', Items\Show::class)->name('items.show');
    Route::get('/items/{item}/edit', Items\Form::class)->name('items.edit');
    Route::get('/find', Items\Find::class)->name('find');

    Route::get('/places', Places\Index::class)->name('places.index');
    Route::get('/places/{place}', Places\Show::class)->name('places.show');

    Route::get('/categories', Categories\Index::class)->name('categories.index');
    Route::get('/tags', Tags\Index::class)->name('tags.index');

    Route::get('/lending', Lending\Index::class)->name('lending.index');
    Route::get('/upkeep', Upkeep\Index::class)->name('upkeep.index');

    Route::get('/more', More::class)->name('more');
    Route::get('/settings', Settings::class)->name('settings');
    Route::get('/account', Account::class)->name('account');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
