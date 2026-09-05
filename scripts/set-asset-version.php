<?php
// Senast uppdaterad: 2026-09-05 14:20 Asia/Bangkok | Version 1.11 | KlⒶssⓔ & Ⓐberg

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || (function_exists('posix_geteuid') && posix_geteuid() !== 0)) {
    fwrite(STDERR, "Run this command as root.\n");
    exit(77);
}
$version=$argv[1]??'';
if(!preg_match('/^\d+\.\d+\.\d+$/',$version)){
    fwrite(STDERR,"Usage: set-asset-version.php X.Y.Z\n");
    exit(64);
}
$path='/etc/chiang-mai-air-watch/config.php';
$config=require $path;
if(!is_array($config))throw new RuntimeException('Production configuration is invalid.');
$config['app']['asset_version']=$version;
$temporary=tempnam(dirname($path),'.config-asset-');
if($temporary===false)throw new RuntimeException('Unable to create temporary configuration.');
try{
    $contents="<?php\n// Chiang Mai Air Watch production configuration. Secrets must remain outside the repository.\nreturn ".var_export($config,true).";\n";
    if(file_put_contents($temporary,$contents,LOCK_EX)===false)throw new RuntimeException('Unable to write configuration.');
    chmod($temporary,0640);chown($temporary,'root');chgrp($temporary,'www-data');
    if(!rename($temporary,$path))throw new RuntimeException('Unable to replace configuration atomically.');
}finally{if(is_file($temporary))unlink($temporary);}
fwrite(STDOUT,"Asset version updated to $version; secrets were not displayed.\n");
