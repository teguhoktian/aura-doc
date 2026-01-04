<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/media/{media}', function (\Spatie\MediaLibrary\MediaCollections\Models\Media $media) {
    abort_if(! auth()->check(), 403); // optional security

    return $media->toResponse(request());
})->name('secure.media');
