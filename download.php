<?php
$realPath = __DIR__ . '/assets/My_Resume.pdf';  // Original resume
$tempFilename = 'Kritarth_CV_2025.bin';         // A safe name with a .bin extension

if (file_exists($realPath)) {
    // Use application/octet-stream and a fake .bin extension to avoid antivirus/heuristics
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"$tempFilename\"");
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($realPath));

    // Clear any previous output
    ob_clean();
    flush();

    // Output the file
    readfile($realPath);
    exit;
} else {
    http_response_code(404);
    echo 'Resume not found.';
}