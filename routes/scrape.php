<?php

declare(strict_types=1);

use App\Http\Controllers\ScrapeController;
use Illuminate\Support\Facades\Route;

Route::get('{passkey}', [ScrapeController::class, 'index'])->name('scrape');