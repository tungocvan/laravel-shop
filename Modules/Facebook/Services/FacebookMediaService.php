<?php

namespace Modules\Facebook\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Facebook\Models\FacebookPost;
use Modules\Facebook\Models\FacebookPostMedia;

class FacebookMediaService
{
    public function attachPhoto(FacebookPost $post, UploadedFile $file): FacebookPostMedia
    {
        $extension = $file->guessExtension() ?: $file->extension() ?: 'jpg';
        $path = $file->storeAs(
            'facebook/posts/'.$post->id,
            Str::uuid()->toString().'.'.$extension,
            config('facebook.media_disk', 'local')
        );

        return $post->media()->create([
            'media_type' => 'photo',
            'disk' => config('facebook.media_disk', 'local'),
            'path' => $path,
            'original_name' => basename((string) $file->getClientOriginalName()),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sort_order' => 0,
            'status' => FacebookPostMedia::STATUS_PENDING,
        ]);
    }
}
