<?php

namespace App\Services\Store;

use App\Models\DigitalProduct;
use App\Models\ProductRelease;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class Packages
{
    public function publish(DigitalProduct $product, UploadedFile $file, array $data): ProductRelease
    {
        $zip = new ZipArchive;
        $valid = $zip->open($file->getRealPath()) === true;
        $total = 0;
        if ($valid) {
            $valid = $zip->numFiles > 0 && $zip->numFiles <= 5000 && $zip->locateName($product->slug.'/'.$product->slug.'.php') !== false;
            for ($i = 0; $i < $zip->numFiles && $valid; $i++) {
                $info = $zip->statIndex($i);
                $name = $info['name']; $total += $info['size'];
                $zip->getExternalAttributesIndex($i, $opsys, $attributes);
                $valid = str_starts_with($name, $product->slug.'/')
                    && ! preg_match('#(?:^|/)\.\.(?:/|$)|[\\\\\x00]#', $name)
                    && (($attributes >> 16) & 0170000) !== 0120000 && $total < 100 * 1024 * 1024;
            }
            $zip->close();
        }
        if (! $valid) { throw ValidationException::withMessages(['package' => 'Upload a ZIP containing only the plugin folder and its main PHP file (maximum 100 MB expanded).']); }
        $path = $file->store('products/packages', 'local');
        abort_unless($path, 503);
        try {
            return $product->releases()->create($data + ['package_path' => $path, 'sha256' => hash_file('sha256', $file->getRealPath())]);
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
    }
}
