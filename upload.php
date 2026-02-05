<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file'])) {
        echo json_encode(["error" => "❌ No files uploaded."]);
        exit;
    }

    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $results = [];

    // Detect whether we're handling a single file or multiple files
    if (is_array($_FILES['file']['name'])) {
        // Multiple files
        $files = $_FILES['file'];
        for ($i = 0; $i < count($files['name']); $i++) {
            $fileName = basename($files['name'][$i]);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($files['tmp_name'][$i], $targetFile)) {
                $results[] = "✅ Uploaded: " . htmlspecialchars($fileName);
            } else {
                $results[] = "❌ Failed: " . htmlspecialchars($fileName);
            }
        }
    } else {
        // Single file
        $fileName = basename($_FILES['file']['name']);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            $results[] = "✅ Uploaded: " . htmlspecialchars($fileName);
        } else {
            $results[] = "❌ Failed: " . htmlspecialchars($fileName);
        }
    }

    header('Content-Type: application/json');
    echo json_encode($results);
} else {
    echo json_encode(["error" => "Invalid request."]);
}
