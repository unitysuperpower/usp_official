<?php

use App\Models\DigitalProduct;
use App\Services\Store\Packages;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function storeTestZip(array $entries, ?string $link = null): UploadedFile
{
    $file = tempnam(sys_get_temp_dir(),'usp-package-');
    $zip = new ZipArchive;
    $zip->open($file,ZipArchive::OVERWRITE);
    foreach ($entries as $name=>$content) { $zip->addFromString($name,$content); }
    if ($link) { $zip->setExternalAttributesName($link, ZipArchive::OPSYS_UNIX, 0120777 << 16); }
    $zip->close();
    return new UploadedFile($file,'plugin.zip','application/zip',null,true);
}

beforeEach(function () {
    Storage::fake('local');
    $this->product=DigitalProduct::create(['slug'=>'usp-form-builder','name'=>'USP Form Builder','summary'=>'WordPress forms']);
    $this->header="<?php\n/**\n * Plugin Name: USP Form Builder\n * Version: 1.0.8\n * USP Edition: free\n */\n";
});

test('valid free packages are kept private and record the archive checksum', function () {
    $file=storeTestZip(['usp-form-builder/usp-form-builder.php'=>$this->header,'usp-form-builder/includes/free.php'=>'<?php // Free']);
    try {
        $release=app(Packages::class)->publish($this->product,$file,['version'=>'1.0.8','edition'=>'free','published'=>true]);
        Storage::disk('local')->assertExists($release->package_path);
        expect($release->sha256)->toBe(hash_file('sha256',$file->getRealPath()));
        expect($release->toArray())->not->toHaveKey('package_path');
    } finally { unlink($file->getRealPath()); }
});

test('ZIP rejection explains the exact layout, version or edition problem', function ($variant,$message) {
    $entries=['usp-form-builder/usp-form-builder.php'=>$this->header];
    $data=['version'=>'1.0.8','edition'=>'free'];
    $link=null;
    switch ($variant) {
        case 'root': $entries=['usp-form-builder.php'=>$this->header]; break;
        case 'nested': $entries=['download/usp-form-builder/usp-form-builder.php'=>$this->header]; break;
        case 'version': $data['version']='1.0.0'; break;
        case 'edition': $data['edition']='pro'; break;
        case 'unlabelled': $entries['usp-form-builder/usp-form-builder.php']=str_replace(' * USP Edition: free', '', $this->header); break;
        case 'pro-code': $entries['usp-form-builder/includes/class-usp-address.php']='<?php'; break;
        case 'traversal': $entries['usp-form-builder/../outside.php']='<?php'; break;
        case 'metadata': $entries['__MACOSX/file']='metadata'; break;
        case 'symlink': $link='usp-form-builder/link'; $entries[$link]='/etc/passwd'; break;
    }
    $file=storeTestZip($entries,$link);
    try {
        try { app(Packages::class)->publish($this->product,$file,$data); test()->fail('Unsafe/mismatched ZIP accepted'); }
        catch (ValidationException $error) { expect($error->errors()['package'][0])->toContain($message); }
        expect(Storage::disk('local')->allFiles())->toBe([]);
    } finally { unlink($file->getRealPath()); }
})->with([
    ['root','Expected usp-form-builder/usp-form-builder.php'],
    ['nested','Expected usp-form-builder/usp-form-builder.php'],
    ['version','Version header'],['edition','different edition'],['unlabelled','unlabelled'],
    ['pro-code','contains a Pro implementation'],['traversal','unsafe path'],['metadata','Unexpected entry'],['symlink','Symbolic links'],
]);

test('a Pro add-on product offers its separate base plugin as the Free release, never the Pro code', function ($folder, $edition, $message) {
    $product = DigitalProduct::create(['slug' => 'techsolution-form-studio-pro', 'name' => 'Form Studio Pro', 'summary' => 'Add-on']);
    $file = storeTestZip(["{$folder}/{$folder}.php" => "<?php\n/**\n * Plugin Name: Plugin\n * Version: 2.3.0\n */\n"]);
    try {
        if ($message === null) {
            expect(app(Packages::class)->publish($product, $file, ['version' => '2.3.0', 'edition' => $edition])->edition)->toBe($edition);
            return;
        }
        try { app(Packages::class)->publish($product, $file, ['version' => '2.3.0', 'edition' => $edition]); test()->fail('Mismatched ZIP accepted'); }
        catch (ValidationException $error) { expect($error->errors()['package'][0])->toContain($message); }
    } finally { unlink($file->getRealPath()); }
})->with([
    ['techsolution-form-studio', 'free', null],
    ['techsolution-form-studio-pro', 'pro', null],
    ['techsolution-form-studio-pro', 'free', 'cannot be the free release'],
    ['techsolution-form-studio', 'pro', 'Choose the Free edition'],
    ['other-plugin', 'free', 'cannot be the free release'],
]);
