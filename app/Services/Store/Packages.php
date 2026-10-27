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
        $this->inspect($product, $file, $data);
        $path = $file->store('products/packages', 'local');
        abort_unless($path, 503);
        try {
            return $product->releases()->create($data + ['package_path' => $path, 'sha256' => hash_file('sha256', $file->getRealPath())]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
    }

    public function inspect(DigitalProduct $product, UploadedFile $file, array $data): void
    {
        $zip = new ZipArchive;
        $this->ensure($zip->open($file->getRealPath()) === true, 'The file could not be opened as a ZIP archive.');
        try {
            $this->ensure($zip->numFiles > 0 && $zip->numFiles <= 5000, 'The ZIP must contain between 1 and 5,000 entries.');
            $main = $product->slug.'/'.$product->slug.'.php';
            // A correctly built ZIP for a different plugin (e.g. the Free plugin uploaded to the Pro product).
            $folder = strstr((string) $zip->getNameIndex(0), '/', true);
            $this->ensure($zip->locateName($main) !== false || ! $folder || $folder === $product->slug || $zip->locateName($folder.'/'.$folder.'.php') === false,
                "This ZIP contains the {$folder} plugin, but this product is {$product->slug}. Upload {$product->slug}-<version>.zip here, or create a product with the identifier {$folder} for this plugin.");
            $this->ensure($zip->locateName($main) !== false, "Expected {$main} inside the ZIP. The top-level plugin folder and main PHP filename must match this product identifier ({$product->slug}). Do not ZIP its contents without the folder.");
            $total = 0;
            $seen = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $info = $zip->statIndex($i);
                $name = $info['name'];
                $this->ensure(str_starts_with($name, $product->slug.'/'), 'Unexpected entry: '.mb_substr($name, 0, 150).'. Only the '.$product->slug.' folder may appear at the ZIP root.');
                $this->ensure(! preg_match('#(?:^|/)\.{1,2}(?:/|$)|[\\\\\x00-\x1f]#', $name), 'The ZIP contains an unsafe path.');
                $this->ensure(! isset($seen[strtolower($name)]), 'The ZIP contains duplicate or case-conflicting paths.');
                $seen[strtolower($name)] = true;
                $zip->getExternalAttributesIndex($i, $opsys, $attributes);
                $kind = ($attributes >> 16) & 0170000;
                $this->ensure(in_array($kind, [0, 0040000, 0100000], true), 'Symbolic links and special files are not allowed in plugin ZIPs.');
                $this->ensure(empty($info['encryption_method']), 'Password-protected ZIP entries are not supported.');
                $total += $info['size'];
                $this->ensure($total <= 100 * 1024 * 1024, 'The plugin exceeds the 100 MB expanded-size limit.');
            }
            $header = $zip->getFromName($main, 8192);
            $this->ensure(is_string($header) && preg_match('/^[ \t\/*#@]*Plugin Name:\s*\S.+$/mi', $header), 'The main PHP file must contain a WordPress Plugin Name header.');
            preg_match('/^[ \t\/*#@]*Version:\s*([^\r\n]+)/mi', $header, $version);
            $this->ensure(isset($version[1]) && trim($version[1]) === $data['version'], 'The release version must match the Version header in '.$main.' (found '.trim($version[1] ?? 'none').'). Renaming the ZIP does not change its version.');
            if ($product->slug === 'usp-form-builder') {
                preg_match('/^[ \t\/*#@]*USP Edition:\s*([^\r\n]+)/mi', $header, $edition);
                $this->ensure(isset($edition[1]) && trim($edition[1]) === $data['edition'], 'Upload the matching Free or Pro distribution built by scripts/build-plugin.py. This package is unlabelled or belongs to a different edition.');
                if ($data['edition'] === 'free') {
                    $this->ensure($zip->locateName($product->slug.'/includes/class-usp-address.php') === false, 'The Free package contains a Pro implementation. Use the generated free-only ZIP.');
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['package' => $message]);
        }
    }
}
