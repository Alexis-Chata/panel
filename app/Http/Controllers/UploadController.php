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
            'upload' => ['required','image','max:5120'], // 5MB
        ]);

        $path = $request->file('upload')->store('public/questions'); // storage/app/public/questions
        return response()->json([
            'url' => Storage::url($path), // /storage/questions/xxx.jpg
        ]);
    }
}
