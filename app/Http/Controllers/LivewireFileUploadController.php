<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Custom Livewire file upload controller yang tidak memakai signed URL.
 *
 * Digunakan untuk environment dengan Cloudflare + reverse proxy, di mana
 * validasi signature signed URL sering gagal karena URL termodifikasi.
 *
 * Keamanan: Mengganti signature dengan auth session + CSRF (web middleware).
 */
class LivewireFileUploadController extends Controller
{
    /**
     * Handle file upload request.
     *
     * Compatible dengan Livewire JS - request/response format sama dengan
     * Livewire FileUploadController, hanya tanpa validasi signature.
     */
    public function __invoke(Request $request)
    {
        // Mengganti hasValidSignature dengan auth session (web middleware sudah include CSRF)
        if (! $request->user()) {
            abort(401, 'Unauthenticated');
        }

        $files = $this->normalizeFiles($request);

        if (empty($files)) {
            return response()->json(['message' => 'No files provided'], 422);
        }

        $disk = FileUploadConfiguration::disk();
        $filePaths = $this->validateAndStore($files, $disk);

        return ['paths' => $filePaths];
    }

    /**
     * Normalisasi input file: bisa single atau array.
     */
    private function normalizeFiles(Request $request): array
    {
        $files = $request->file('files') ?? $request->file('file');

        if (empty($files)) {
            return [];
        }

        return is_array($files) ? $files : [$files];
    }

    /**
     * Validasi dan simpan file - logika sama dengan Livewire FileUploadController.
     */
    private function validateAndStore(array $files, string $disk): array
    {
        Validator::make(['files' => $files], [
            'files.*' => FileUploadConfiguration::rules(),
        ])->validate();

        $fileHashPaths = collect($files)->map(function ($file) use ($disk) {
            $filename = TemporaryUploadedFile::generateHashNameWithOriginalNameEmbedded($file);

            return $file->storeAs('/' . FileUploadConfiguration::path(), $filename, [
                'disk' => $disk,
            ]);
        });

        return $fileHashPaths->map(function ($path) {
            return str_replace(FileUploadConfiguration::path('/'), '', $path);
        })->values()->all();
    }
}
