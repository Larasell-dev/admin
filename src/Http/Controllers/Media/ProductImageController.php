<?php

namespace Larasell\Admin\Http\Controllers\Media;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Actions\Media\DeleteProductImage;
use Larasell\Admin\Models\Media\Folder;
use Larasell\Larasell\Models\ProductImage;

class ProductImageController
{
    /**
     * Uploads one or more product images into the folder given as folder_id, or the top level
     * without one, and returns to the page they were uploaded on. One invalid file rejects
     * them all, so a retry never uploads the valid ones twice.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'folder_id' => ['nullable', 'integer', Rule::exists(Folder::class, 'id')],
            'files' => ['required', 'array', 'min:1'],
            // Raster images only: an SVG can carry scripts, which the images disk would serve.
            'files.*' => ['required', File::image()->max(10 * 1024)],
        ], [
            'files.*.image' => ':attribute isn\'t an image.',
            'files.*.mimes' => ':attribute isn\'t an image.',
            'files.*.max' => ':attribute is larger than 10 MB.',
        ], $this->fileNames($request));

        $folder = isset($validated['folder_id']) ? Folder::query()->findOrFail((int) $validated['folder_id']) : null;

        DB::transaction(function () use ($validated, $folder): void {
            foreach ($validated['files'] as $file) {
                $image = ProductImage::query()->create(['file' => $file]);
                $folder?->productImages()->attach($image);
            }
        });

        return back();
    }

    /**
     * Deletes the product image and its file, once its name was typed to confirm, and
     * returns to the page it was deleted on.
     *
     * @throws ValidationException
     */
    public function destroy(Request $request, ProductImage $productImage, DeleteProductImage $deleteProductImage): RedirectResponse
    {
        // The name the media table shows: the file's name without its directory.
        $name = basename($productImage->file);

        if ($request->input('confirmation') !== $name) {
            throw ValidationException::withMessages([
                'confirmation' => "Type \"{$name}\" to confirm.",
            ]);
        }

        $deleteProductImage->handle($productImage);

        return back();
    }

    /**
     * Names each file by its own name in errors, e.g. "notes.pdf" rather than "files.1".
     *
     * @return array<string, string>
     */
    private function fileNames(Request $request): array
    {
        $files = $request->file('files');

        if (! is_array($files)) {
            return [];
        }

        return collect($files)
            ->mapWithKeys(fn (UploadedFile $file, int|string $index): array => ["files.{$index}" => "\"{$file->getClientOriginalName()}\""])
            ->all();
    }
}
