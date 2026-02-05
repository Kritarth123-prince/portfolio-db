<?php
$zipPath   = __DIR__ . '/bigfile.zip';    // Same folder as script
$extractTo = __DIR__ . '/';               // Extract to same folder

echo "🔍 Checking file...<br>";
echo "Full path: $zipPath<br>";
echo "File exists? " . (file_exists($zipPath) ? '✅ Yes' : '❌ No') . "<br>";
echo "Is readable? " . (is_readable($zipPath) ? '✅ Yes' : '❌ No') . "<br>";
echo "ZipArchive available? " . (class_exists('ZipArchive') ? '✅ Yes' : '❌ No') . "<br><br>";

if (!file_exists($zipPath)) {
    die("❌ ZIP file not found.");
}

$zip = new ZipArchive();
$openResult = $zip->open($zipPath);

if ($openResult === TRUE) {
    $zip->extractTo($extractTo);
    $zip->close();
    echo "✅ ZIP extracted successfully to: $extractTo<br>";
} else {
    echo "❌ Failed to open ZIP. Error code: $openResult<br>";
}
?>
