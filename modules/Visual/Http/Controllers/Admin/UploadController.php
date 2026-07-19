<?php

namespace Modules\Visual\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /** Куда складываем картинки TinyMCE (публичный диск). */
    protected string $disk = 'public';
    protected string $base = 'visual/uploads';

    public function image(Request $request)
    {
        // TinyMCE 7 шлёт файл в поле "file"
        $request->validate([
            'file' => ['required','file','mimes:jpeg,jpg,png,gif,webp,svg','max:10240'], // до 10Мб
        ]);

        $file   = $request->file('file');
        $name   = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $ext    = strtolower($file->getClientOriginalExtension());
        $path   = $file->storeAs($this->base, $name.'-'.Str::random(6).'.'.$ext, $this->disk);

        return response()->json([
            'location' => Storage::disk($this->disk)->url($path),
        ]);
    }
}
