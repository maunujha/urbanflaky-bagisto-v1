<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Traits\Sanitizer;

class TinyMCEController extends Controller
{
    use Sanitizer;

    /**
     * Storage folder path.
     *
     * @var string
     */
    private $storagePath = 'tinymce';

    /**
     * Allowed image MIME types.
     *
     * @var array
     */
    private $allowedMimeTypes = [
        'image/gif',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/svg+xml',
        'image/webp',
    ];

    /**
     * Upload file from tinymce.
     *
     * @return JsonResponse
     */
    public function upload()
    {
        $result = $this->storeMedia();

        if (isset($result['error'])) {
            return response()->json([
                'error' => $result['error'],
            ], 400);
        }

        if (! empty($result)) {
            return response()->json([
                'location' => $result['file_url'],
            ]);
        }

        return response()->json([
            'error' => trans('admin::app.components.tinymce.errors.file-upload-failed'),
        ], 400);
    }

    /**
     * Store media.
     *
     * @return array
     */
    public function storeMedia()
    {
        if (! request()->hasFile('file')) {
            return ['error' => trans('admin::app.components.tinymce.errors.no-file-uploaded')];
        }

        $file = request()->file('file');

        $mimeType = $file->getMimeType();

        if (! in_array($mimeType, $this->allowedMimeTypes)) {
            return ['error' => trans('admin::app.components.tinymce.errors.invalid-file-type')];
        }

        $extension = strtolower($file->getClientOriginalExtension());

        $validExtensions = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/jpg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
            'image/svg+xml' => ['svg'],
        ];

        if (! isset($validExtensions[$mimeType]) || ! in_array($extension, $validExtensions[$mimeType])) {
            return ['error' => trans('admin::app.components.tinymce.errors.file-extension-mismatch')];
        }

        $path = $file->store($this->storagePath);

        $this->sanitizeSVG($path, $mimeType);

        /*
         * Auto-generate a WebP sibling for JPG/PNG uploads. RTE content keeps
         * embedding the original URL; the storefront wraps it in <picture>
         * with this .webp as the preferred <source> (see webp_picture_html()).
         */
        if (in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png'])) {
            try {
                $encoded = image_manager()->read($file)->encodeByExtension('webp', quality: webp_quality());

                Storage::put(preg_replace('/\.[^.\/]+$/', '.webp', $path), (string) $encoded);
            } catch (\Exception $e) {
                report($e);
            }
        }

        return [
            'file' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_url' => Storage::url($path),
        ];
    }
}
