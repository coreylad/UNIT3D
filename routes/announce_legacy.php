<?php

declare(strict_types=1);

use App\Http\Controllers\AnnounceController;
use App\Http\Controllers\ScrapeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('announce.php', function (Request $request, AnnounceController $controller) {
    $passkey = $request->query->getString('passkey');

    $request->query->remove('passkey');

    return $controller->index($request, $passkey);
})->name('announce.legacy');

Route::get('scrape.php', function (Request $request, ScrapeController $controller) {
    $passkey = $request->query->getString('passkey');

    $request->query->remove('passkey');

    return $controller->index($request, $passkey);
})->name('scrape.legacy');