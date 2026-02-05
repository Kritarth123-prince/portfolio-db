<?php
$chunksDir   = __DIR__ . '/';              // Folder with .zip.001, .zip.002, etc.
$mergedZip   = $chunksDir . 'bigfile.zip';         // Final merged ZIP path
$extractTo   = $chunksDir . '/';           // Extraction folder

// Step 1: Merge chunk files
$chunkFiles = glob($chunksDir . 'bigfile.zip.*');
sort($chunkFiles, SORT_NATURAL); // Ensure proper order: 001, 002, ...

if (file_exists($mergedZip)) {
    unlink($mergedZip); // Remove existing merged file if any
}

$merged = fopen($mergedZip, 'ab');
if (!$merged) {
    die("❌ Cannot create merged ZIP file.");
}

foreach ($chunkFiles as $chunk) {
    $data = file_get_contents($chunk);
    fwrite($merged, $data);
    echo "✅ Merged: " . basename($chunk) . "<br>";
}
fclose($merged);

// Step 2: Extract the merged ZIP
$zip = new ZipArchive();
if ($zip->open($mergedZip) === TRUE) {
    if (!is_dir($extractTo)) {
        mkdir($extractTo, 0755, true);
    }

    $zip->extractTo($extractTo);
    $zip->close();
    echo "✅ Extracted to: " . $extractTo . "<br>";

    // Step 3: Cleanup - delete chunks and the merged zip
    foreach ($chunkFiles as $chunk) {
        unlink($chunk);
        echo "🗑️ Deleted chunk: " . basename($chunk) . "<br>";
    }

    unlink($mergedZip);
    echo "🗑️ Deleted merged ZIP file: " . basename($mergedZip) . "<br>";
    echo "<br>🎉 All done! Only unzipped files remain.";
} else {
    echo "❌ Failed to open merged ZIP.";
}
