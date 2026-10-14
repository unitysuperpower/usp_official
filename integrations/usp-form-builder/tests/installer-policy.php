<?php
require __DIR__.'/store-license-client.php';
require __DIR__.'/../includes/class-usp-pro-installer.php';
$test_api_reply=$valid_reply;
$test_api_reply['update'] = array(
    'slug'=>'usp-form-builder', 'edition'=>'pro', 'version'=>'1.1.2',
    'sha256'=>str_repeat('b',64), 'package'=>'https://usp.com.pk/api/licenses/packages/1/2?expires=9999999999&signature=test',
);
USP_Security::process_license_action('TEST-KEY-123456','activate');
license_check(!is_wp_error(USP_Pro_Installer::package()),'An entitled same-version Free-to-Pro upgrade must be allowed.');
$valid_package=$test_api_reply['update'];
foreach (array(
    array('package'=>'https://evil.example/download.zip'),
    array('package'=>'http://usp.com.pk/api/licenses/packages/1/2'),
    array('package'=>'https://usp.com.pk@evil.example/api/licenses/packages/1/2'),
    array('package'=>'https://usp.com.pk:8443/api/licenses/packages/1/2'),
    array('slug'=>'another-product'), array('edition'=>'free'),
    array('sha256'=>'unverified'), array('version'=>'1.0.0'),
) as $invalid) {
    $test_api_reply['update']=array_merge($valid_package,$invalid);
    license_check(is_wp_error(USP_Pro_Installer::package()),'Untrusted/wrong/downgraded package must be rejected.');
}
$test_api_reply=array('success'=>false,'license'=>'invalid','error'=>'expired');
license_check(is_wp_error(USP_Pro_Installer::package()),'Expired licenses cannot download even with local cache.');
$test_api_reply=new WP_Error('offline','offline');
license_check(is_wp_error(USP_Pro_Installer::package()),'Offline grace must never grant a fresh Pro download.');
echo "Passed Pro installer package origin, checksum format, edition, downgrade and entitlement checks.\n";
