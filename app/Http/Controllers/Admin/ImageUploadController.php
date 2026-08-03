<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        if ($request->hasFile('upload')) {
            $file = $request->file('upload');
            $filename = time() . '_' . $file->getClientOriginalName();
            
            // Store the file in public disk, under 'job_images'
            $path = $file->storeAs('job_images', $filename, 'public');
            
            // Return JSON expected by CKEditor SimpleUploadAdapter
            return response()->json([
                'url' => asset('storage/' . $path)
            ]);
        }

        return response()->json(['error' => ['message' => 'No image uploaded.']], 400);
    }
}
