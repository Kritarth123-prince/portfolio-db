<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'sql105.byethost14.com';
$username = 'b14_38641923';
$password = 'Relih@3732';
$database = 'b14_38641923_portfolio_db';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, platform, url, icon_class, display_order, is_active, created_at FROM social_links ORDER BY display_order ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h2>Social Links</h2><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>";
        echo "<strong>" . htmlspecialchars($row["platform"]) . ":</strong> ";
        echo "<a href='" . htmlspecialchars($row["url"]) . "' target='_blank'>" . htmlspecialchars($row["url"]) . "</a>";
        echo " <i class='" . htmlspecialchars($row["icon_class"]) . "'></i>";
        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "No social links found.";
}

$conn->close();
?>
