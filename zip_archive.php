<?php
$zipFileName = 'archive.zip'; // Name of the output zip file
$zip = new ZipArchive();

// Get current script directory
$rootPath = realpath(dirname(__FILE__));

// Create or overwrite the zip file
if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    exit("Cannot open <$zipFileName>\n");
}

// Recursive function to add files and folders
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($files as $file) {
    $filePath = realpath($file);

    // Don't include the zip file itself
    if ($filePath === realpath($zipFileName)) {
        continue;
    }

    // Get relative path
    $relativePath = substr($filePath, strlen($rootPath) + 1);

    if (is_dir($filePath)) {
        $zip->addEmptyDir($relativePath);
    } elseif (is_file($filePath)) {
        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();
echo "ZIP archive created successfully: $zipFileName\n";
?>
