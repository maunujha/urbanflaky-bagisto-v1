<?php

namespace Webkul\Theme\Repositories;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;
use Webkul\Core\Eloquent\Repository;
use Webkul\Theme\Contracts\ThemeCustomization;

class ThemeCustomizationRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return ThemeCustomization::class;
    }

    /**
     * Update the specified theme
     *
     * @param  array  $data
     * @param  int  $id
     */
    public function update($data, $id): ThemeCustomization
    {
        $locale = core()->getRequestedLocaleCode();

        if ($data['type'] == 'static_content') {
            $config = [
                'HTML.Allowed' => null,
                'HTML.ForbiddenElements' => 'script,iframe,form',
                'CSS.AllowedProperties' => null,
            ];

            $data[$locale]['options']['html'] = Purify::config($config)->clean($data[$locale]['options']['html']);

            $data[$locale]['options']['css'] = Purify::config($config)->clean($data[$locale]['options']['css']);
        }

        if ($data['type'] == 'video_banner') {
            return $this->updateVideoBanner($data, $id, $locale);
        }

        if (in_array($data['type'], ['image_carousel', 'services_content'])) {
            unset($data[$locale]['options']);
        }

        $theme = parent::update($data, $id);

        if (in_array($data['type'], ['image_carousel', 'services_content'])) {
            $this->uploadImage(request()->all(), $theme);
        }

        return $theme;
    }

    /**
     * Persist a video banner block.
     *
     * Scalar options (title, description, link, …) are submitted nested under
     * `<locale>[options]` and saved as-is. The four media files arrive as
     * top-level multipart inputs so they never end up as UploadedFile objects
     * inside the translatable JSON; we store each and fold its public path back
     * into the options array, keeping the previously stored path when no new
     * file was uploaded.
     *
     * @param  array  $data
     * @param  int  $id
     * @param  string  $locale
     */
    protected function updateVideoBanner($data, $id, $locale): ThemeCustomization
    {
        $theme = $this->find($id);

        $options = $data[$locale]['options'] ?? [];

        $media = [
            'video_file'        => ['key' => 'video',        'kind' => 'video'],
            'mobile_video_file' => ['key' => 'mobile_video', 'kind' => 'video'],
            'logo_file'         => ['key' => 'logo',         'kind' => 'image'],
            'poster_file'       => ['key' => 'poster',       'kind' => 'image'],
        ];

        foreach ($media as $input => $meta) {
            if (! request()->hasFile($input)) {
                continue;
            }

            /* Remove the file the hidden input was still pointing at. */
            if (! empty($options[$meta['key']])) {
                Storage::delete(str_replace('storage/', '', $options[$meta['key']]));
            }

            $options[$meta['key']] = $meta['kind'] === 'image'
                ? $this->storeBannerImage(request()->file($input), $theme)
                : $this->storeBannerVideo(request()->file($input), $theme);
        }

        /* Unchecked checkboxes are simply absent from the payload. */
        $options['show_price'] = ! empty($options['show_price']);

        $data[$locale]['options'] = $options;

        return parent::update($data, $id);
    }

    /**
     * Store a banner image (logo/poster) as WebP, mirroring the slider pipeline.
     */
    protected function storeBannerImage(UploadedFile $file, ThemeCustomization $theme): string
    {
        $path = 'theme/'.$theme->id.'/'.Str::random(40).'.webp';

        $encoded = image_manager()->read($file)->encodeByExtension('webp', quality: webp_quality());

        Storage::put($path, (string) $encoded);

        return 'storage/'.$path;
    }

    /**
     * Store a banner video (mp4/webm) untouched — videos must not be re-encoded.
     */
    protected function storeBannerVideo(UploadedFile $file, ThemeCustomization $theme): string
    {
        $name = Str::random(40).'.'.strtolower($file->getClientOriginalExtension() ?: 'mp4');

        Storage::putFileAs('theme/'.$theme->id, $file, $name);

        return 'storage/theme/'.$theme->id.'/'.$name;
    }

    /**
     * Mass update the status of themes in the repository.
     *
     * This method updates multiple records in the database based on the provided
     * theme IDs.
     *
     * @param  int  $themeIds
     * @return int The number of records updated.
     */
    public function massUpdateStatus(array $data, array $themeIds)
    {
        return $this->model->whereIn('id', $themeIds)->update($data);
    }

    /**
     * Upload images
     *
     * @return void|string
     */
    public function uploadImage(array $data, ThemeCustomization $theme)
    {
        $locale = core()->getRequestedLocaleCode();

        if (isset($data[$locale]['deleted_sliders'])) {
            foreach ($data[$locale]['deleted_sliders'] as $slider) {
                if (! empty($slider['image'])) {
                    Storage::delete(str_replace('storage/', '', $slider['image']));
                }
                if (! empty($slider['mobile_image'])) {
                    Storage::delete(str_replace('storage/', '', $slider['mobile_image']));
                }
            }
        }

        if (! isset($data[$locale]['options'])) {
            return;
        }

        $options = [];

        $storeImage = function ($file) use ($theme) {
            $path = 'theme/'.$theme->id.'/'.Str::random(40).'.webp';
            $encoded = image_manager()->read($file)->encodeByExtension('webp', quality: webp_quality());
            Storage::put($path, (string) $encoded);

            return 'storage/'.$path;
        };

        foreach ($data[$locale]['options'] as $image) {
            if (isset($image['service_icon'])) {
                $options['services'][] = [
                    'service_icon' => $image['service_icon'],
                    'description'  => $image['description'],
                    'title'        => $image['title'],
                ];

                continue;
            }

            $isUpload       = isset($image['image'])        && $image['image']        instanceof UploadedFile;
            $isMobileUpload = isset($image['mobile_image']) && $image['mobile_image'] instanceof UploadedFile;

            if ($isUpload || $isMobileUpload) {
                try {
                    $desktopPath = $isUpload       ? $storeImage($image['image'])        : ($image['image']        ?? null);
                    $mobilePath  = $isMobileUpload ? $storeImage($image['mobile_image']) : ($image['mobile_image'] ?? null);
                } catch (\Exception $e) {
                    session()->flash('error', $e->getMessage());

                    return redirect()->back();
                }

                if (($data['type'] ?? '') == 'static_content') {
                    return Storage::url(str_replace('storage/', '', $desktopPath));
                }

                $options['images'][] = [
                    'image'        => $desktopPath,
                    'mobile_image' => $mobilePath,
                    'link'         => $image['link'] ?? '',
                    'title'        => $image['title'] ?? '',
                ];
            } else {
                $options['images'][] = $image;
            }
        }

        $translatedModel = $theme->translate($locale);
        $translatedModel->options = $options ?? [];
        $translatedModel->theme_customization_id = $theme->id;
        $translatedModel->save();
    }
}
