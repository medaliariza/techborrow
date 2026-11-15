$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$sdkRoot = $env:ANDROID_SDK_ROOT
if (-not $sdkRoot) {
    $sdkRoot = $env:ANDROID_HOME
}
if (-not $sdkRoot) {
    $sdkRoot = Join-Path $env:LOCALAPPDATA 'Android\Sdk'
}

$studioJdk = 'C:\Program Files\Android\Android Studio\jbr'
if (-not $env:JAVA_HOME -and (Test-Path (Join-Path $studioJdk 'bin\javac.exe'))) {
    $env:JAVA_HOME = $studioJdk
}
if (-not $env:JAVA_HOME -or -not (Test-Path (Join-Path $env:JAVA_HOME 'bin\javac.exe'))) {
    throw 'Java 17 or newer is required. Install Android Studio or set JAVA_HOME.'
}

$buildTools = Join-Path $sdkRoot 'build-tools\36.1.0'
$androidJar = Join-Path $sdkRoot 'platforms\android-36.1\android.jar'
foreach ($requiredPath in @(
    (Join-Path $buildTools 'aapt.exe'),
    (Join-Path $buildTools 'd8.bat'),
    (Join-Path $buildTools 'zipalign.exe'),
    (Join-Path $buildTools 'apksigner.bat'),
    $androidJar
)) {
    if (-not (Test-Path $requiredPath)) {
        throw "Required Android SDK component not found: $requiredPath"
    }
}

$sourceRoot = Join-Path $PSScriptRoot 'app\src\main'
$resourceRoot = Join-Path $sourceRoot 'res'
$iconDirectory = Join-Path $resourceRoot 'mipmap-hdpi'
New-Item -ItemType Directory -Force -Path $iconDirectory | Out-Null
Copy-Item (Join-Path $projectRoot 'assets\images\techborrow-192.png') (Join-Path $iconDirectory 'ic_launcher.png') -Force

$buildRoot = Join-Path $PSScriptRoot 'build'
$generatedRoot = Join-Path $buildRoot 'generated'
$classesRoot = Join-Path $buildRoot 'classes'
$dexRoot = Join-Path $buildRoot 'dex'
New-Item -ItemType Directory -Force -Path $generatedRoot, $classesRoot, $dexRoot | Out-Null

$aapt = Join-Path $buildTools 'aapt.exe'
$java = Join-Path $env:JAVA_HOME 'bin\java.exe'
$javac = Join-Path $env:JAVA_HOME 'bin\javac.exe'
$jar = Join-Path $env:JAVA_HOME 'bin\jar.exe'
$aaptResources = Join-Path $buildRoot 'resources.ap_'
$unsignedApk = Join-Path $buildRoot 'TechBorrow-unsigned.apk'
$alignedApk = Join-Path $buildRoot 'TechBorrow-aligned.apk'
$keystore = Join-Path $buildRoot 'debug.keystore'
$outputApk = Join-Path $projectRoot 'TechBorrow.apk'

& $aapt package -f -m -J $generatedRoot -M (Join-Path $PSScriptRoot 'AndroidManifest.xml') -S $resourceRoot -I $androidJar -F $aaptResources
if ($LASTEXITCODE -ne 0) { throw 'Android resource packaging failed.' }

$sourceFile = Join-Path $sourceRoot 'java\com\techborrow\app\MainActivity.java'
$generatedR = Join-Path $generatedRoot 'com\techborrow\app\R.java'
& $javac -source 8 -target 8 -classpath $androidJar -d $classesRoot $sourceFile $generatedR
if ($LASTEXITCODE -ne 0) { throw 'Java compilation failed.' }

$classFiles = Get-ChildItem $classesRoot -Recurse -Filter '*.class' | ForEach-Object { $_.FullName }
if (-not $classFiles) { throw 'Java compilation did not produce any class files.' }
& (Join-Path $buildTools 'd8.bat') --lib $androidJar --min-api 23 --output $dexRoot $classFiles
if ($LASTEXITCODE -ne 0) { throw 'Android dex compilation failed.' }

Copy-Item $aaptResources $unsignedApk -Force
& $jar uf $unsignedApk -C $dexRoot classes.dex
if ($LASTEXITCODE -ne 0) { throw 'Adding app bytecode to the APK failed.' }

if (-not (Test-Path $keystore)) {
    & (Join-Path $env:JAVA_HOME 'bin\keytool.exe') -genkeypair -noprompt -keystore $keystore -storepass android -keypass android -alias androiddebugkey -dname 'CN=Android Debug,O=Android,C=US' -keyalg RSA -keysize 2048 -validity 10000
    if ($LASTEXITCODE -ne 0) { throw 'Debug signing key generation failed.' }
}

& (Join-Path $buildTools 'zipalign.exe') -f -p 4 $unsignedApk $alignedApk
if ($LASTEXITCODE -ne 0) { throw 'APK alignment failed.' }
& (Join-Path $buildTools 'apksigner.bat') sign --ks $keystore --ks-pass pass:android --key-pass pass:android --out $outputApk $alignedApk
if ($LASTEXITCODE -ne 0) { throw 'APK signing failed.' }
& (Join-Path $buildTools 'apksigner.bat') verify --verbose $outputApk
if ($LASTEXITCODE -ne 0) { throw 'APK signature verification failed.' }

Write-Output "APK created: $outputApk"
