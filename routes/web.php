<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tickets'); // stuur de homepage meteen naar het tickets overzicht

Route::livewire('/tickets', 'pages::tickets.index') // overzichtspagina metlisting, filters en pagination
    ->name('tickets.index');

Route::livewire('/tickets/create', 'pages::tickets.create') // pagina om een nieuw ticket aan te maken
    ->name('tickets.create');

Route::livewire('/tickets/{ticket}', 'pages::tickets.show')
    ->name('tickets.show');
