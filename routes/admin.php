<?php

use Illuminate\Support\Facades\Route;

#configuracion
Route::view("configuracion/index", "admin.configuracion.configuracion_index")->middleware('can:admin.configuracion.titulo')->name("configuracion.index");
