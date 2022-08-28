$ErrorActionPreference = "Stop"

if (-not (Test-Path 'c:\build-cache')) {
    [void](New-Item 'c:\build-cache' -ItemType 'directory')
}

$bname = "php-sdk-$env:BIN_SDK_VER.zip"
Write-Host $bname
if (-not (Test-Path c:\build-cache\$bname)) {
    Invoke-WebRequest "https://github.com/microsoft/php-sdk-binary-tools/archive/$bname" -OutFile "c:\build-cache\$bname"
}
$dname0 = "php-sdk-binary-tools-php-sdk-$env:BIN_SDK_VER"
$dname1 = "php-sdk-$env:BIN_SDK_VER"
if (-not (Test-Path 'c:\build-cache\$dname1')) {
    Expand-Archive "c:\build-cache\$bname" "c:\build-cache"
    Move-Item "c:\build-cache\$dname0" "c:\build-cache\$dname1"
}

$releases = @{
    '7.0' = '7.0.33';
    '7.1' = '7.1.33';
    '7.2' = '7.2.34';
    '7.3' = '7.3.33';
}
if ($releases.ContainsKey($env:PHP_VER)) {
    $phpversion = $releases.$env:PHP_VER;
    $base_url = 'http://windows.php.net/downloads/releases/archives';
} else {
    $releases = Invoke-WebRequest https://windows.php.net/downloads/releases/releases.json | ConvertFrom-Json
    $phpversion = $releases.$env:PHP_VER.version
    $base_url = 'http://windows.php.net/downloads/releases';
}

$ts_part = ''
if ($env:TS -eq '0') {
    $ts_part += '-nts'
}

$bname = "php-devel-pack-$phpversion$ts_part-Win32-$env:VC-$env:ARCH.zip"
if (-not (Test-Path "c:\build-cache\$bname")) {
    Invoke-WebRequest "$base_url/$bname" -OutFile "c:\build-cache\$bname"
}
$dname0 = "php-$phpversion-devel-$env:VC-$env:ARCH"
$dname1 = "php-$phpversion$ts_part-devel-$env:VC-$env:ARCH"
if (-not (Test-Path "c:\build-cache\$dname1")) {
    Expand-Archive "c:\build-cache\$bname" "c:\build-cache"
    if ($dname0 -ne $dname1) {
        Move-Item "c:\build-cache\$dname0" "c:\build-cache\$dname1"
    }
}
$env:PATH = "c:\build-cache\$dname1;$env:PATH"

$bname = "php-$phpversion$ts_part-Win32-$env:VC-$env:ARCH.zip"
if (-not (Test-Path "c:\build-cache\$bname")) {
    Invoke-WebRequest "$base_url/$bname" -OutFile "c:\build-cache\$bname"
}
$dname = "php-$phpversion$ts_part-$env:VC-$env:ARCH"
if (-not (Test-Path "c:\build-cache\$dname")) {
    Expand-Archive "c:\build-cache\$bname" "c:\build-cache\$dname"
}
$env:PATH = "c:\build-cache\$dname;$env:PATH"
