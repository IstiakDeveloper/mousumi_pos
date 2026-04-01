<?php

namespace App\Jobs;

use App\Models\ProductImage;
use App\Services\ProductImageProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OptimizeProductImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ProductImageProcessor $processor): void
    {
        ProductImage::query()
            ->orderBy('id')
            ->chunkById(200, function ($images) use ($processor) {
                foreach ($images as $image) {
                    $newPath = $processor->optimizeExisting((string) $image->image);
                    if ($newPath !== $image->image) {
                        $image->forceFill(['image' => $newPath])->save();
                    }
                }
            });
    }
}

