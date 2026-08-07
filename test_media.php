<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$media = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('collection_name', 'documents')->first();
if ($media) {
    echo "name: " . $media->name . "\n";
    echo "file_name: " . $media->file_name . "\n";
    echo "url: " . asset($media->getUrl()) . "\n";
} else {
    echo "No media found anywhere.\n";
}
