<?php

use Illuminate\Support\Facades\Route;

Route::get('/playground', function () {
    return view('playground.design-system');
})->name('playground');
