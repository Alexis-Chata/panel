<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function ckeditor(Request $request)
    {
        // CKEditor SimpleUploadAdapter envía el archivo en el campo "upload"
        $request->validate([
            'upload' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'], // 5MB, sin SVG (puede llevar JS)
        ]);

        // Disco "public" explícito: en Laravel 11+ el disco por defecto ("local") apunta a
        // storage/app/private, y la URL devuelta daba 404.
        $path = $request->file('upload')->store('questions', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path), // /storage/questions/xxx.jpg
        ]);
    }
}
