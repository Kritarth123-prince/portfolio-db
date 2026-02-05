<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h2>Complete Skills Debugging</h2>";

try {
    $database = new Database();
    $conn = $database->connect();
    
    if (!$conn) {
        die("Database connection failed");
    }
    
    // 1. Check ALL skills in the database
    echo "<h3>1. ALL Skills in Database (Complete List)</h3>";
    $query = "SELECT * FROM skills ORDER BY id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $allSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Category ID</th><th>Skill Name</th><th>Percentage</th><th>Display Order</th><th>Created At</th>";
    echo "</tr>";
    
    foreach ($allSkills as $skill) {
        $highlight = (stripos($skill['name'], 'linux') !== false || stripos($skill['name'], 'docker') !== false) ? 'background: yellow;' : '';
        echo "<tr style='$highlight'>";
        echo "<td>{$skill['id']}</td>";
        echo "<td>{$skill['category_id']}</td>";
        echo "<td><strong>{$skill['name']}</strong></td>";
        echo "<td>{$skill['percentage']}%</td>";
        echo "<td>{$skill['display_order']}</td>";
        echo "<td>{$skill['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Total Skills Found:</strong> " . count($allSkills) . "</p>";
    
    // 2. Check Skills with Categories
    echo "<h3>2. Skills Grouped by Categories (As shown on website)</h3>";
    $query = "
        SELECT 
            sc.id as cat_id,
            sc.name as category_name,
            sc.experience_years,
            sc.display_order as cat_order,
            s.id as skill_id,
            s.name as skill_name,
            s.percentage,
            s.display_order as skill_order
        FROM skill_categories sc
        LEFT JOIN skills s ON sc.id = s.category_id
        ORDER BY sc.display_order, s.display_order
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $categorizedSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $currentCategory = '';
    foreach ($categorizedSkills as $row) {
        if ($currentCategory !== $row['category_name']) {
            if ($currentCategory !== '') echo "</ul></div>";
            echo "<div style='margin: 15px 0; padding: 10px; border: 1px solid #ddd;'>";
            echo "<h4 style='margin: 0 0 10px 0; color: #333;'>{$row['category_name']} (Category ID: {$row['cat_id']}) - {$row['experience_years']}+ Years</h4>";
            echo "<ul style='margin: 0;'>";
            $currentCategory = $row['category_name'];
        }
        
        if ($row['skill_name']) {
            $highlight = (stripos($row['skill_name'], 'linux') !== false || stripos($row['skill_name'], 'docker') !== false) ? 'background: yellow; font-weight: bold;' : '';
            echo "<li style='$highlight'>ID: {$row['skill_id']} - <strong>{$row['skill_name']}</strong> - {$row['percentage']}% (Order: {$row['skill_order']})</li>";
        }
    }
    if ($currentCategory !== '') echo "</ul></div>";
    
    // 3. Search specifically for Linux and Docker
    echo "<h3>3. Search for Linux and Docker Skills</h3>";
    $query = "
        SELECT 
            s.*,
            sc.name as category_name
        FROM skills s
        LEFT JOIN skill_categories sc ON s.category_id = sc.id
        WHERE s.name LIKE '%linux%' 
           OR s.name LIKE '%docker%' 
           OR s.name LIKE '%Linux%' 
           OR s.name LIKE '%Docker%'
           OR s.name LIKE '%LINUX%' 
           OR s.name LIKE '%DOCKER%'
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $foundSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($foundSkills) {
        echo "<div style='background: #ffe6e6; padding: 15px; border: 2px solid red; margin: 10px 0;'>";
        echo "<h4 style='color: red; margin: 0 0 10px 0;'>🔍 FOUND Linux/Docker Skills:</h4>";
        foreach ($foundSkills as $skill) {
            echo "<p><strong>ID:</strong> {$skill['id']} | <strong>Name:</strong> {$skill['name']} | <strong>Category:</strong> {$skill['category_name']} | <strong>%:</strong> {$skill['percentage']}%</p>";
            echo "<p><strong>Created:</strong> {$skill['created_at']}</p>";
            echo "<hr>";
        }
        echo "</div>";
    } else {
        echo "<div style='background: #e6ffe6; padding: 15px; border: 2px solid green; margin: 10px 0;'>";
        echo "<h4 style='color: green; margin: 0;'>✅ No Linux or Docker skills found in database!</h4>";
        echo "</div>";
    }
    
    // 4. Check what the website is actually loading
    echo "<h3>4. What Your Website Is Loading (Using PortfolioData class)</h3>";
    
    require_once 'classes/PortfolioData.php';
    $portfolioData = new PortfolioData();
    $websiteSkills = $portfolioData->getAllSkillsGrouped();
    
    echo "<div style='background: #f0f8ff; padding: 15px; border: 2px solid #007cba; margin: 10px 0;'>";
    echo "<h4 style='margin: 0 0 10px 0;'>Skills as loaded by your website:</h4>";
    
    foreach ($websiteSkills as $category) {
        echo "<h5>{$category['name']} ({$category['experience_years']}+ Years)</h5>";
        echo "<ul>";
        foreach ($category['skills'] as $skill) {
            $highlight = (stripos($skill['name'], 'linux') !== false || stripos($skill['name'], 'docker') !== false) ? 'style="background: yellow; font-weight: bold;"' : '';
            echo "<li $highlight>{$skill['name']} - {$skill['percentage']}%</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
    // 5. Count by category
    echo "<h3>5. Skills Count by Category</h3>";
    $query = "
        SELECT 
            sc.name as category_name,
            COUNT(s.id) as skill_count
        FROM skill_categories sc
        LEFT JOIN skills s ON sc.id = s.category_id
        GROUP BY sc.id, sc.name
        ORDER BY sc.display_order
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($counts as $count) {
        echo "<li><strong>{$count['category_name']}:</strong> {$count['skill_count']} skills</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<div style='background: #ffe6e6; padding: 15px; border: 2px solid red;'>";
    echo "<h4>Database Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    table { width: 100%; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; font-weight: bold; }
    h2, h3 { color: #333; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
</style>

<div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6;">
    <h4>Quick Actions:</h4>
    <a href="index_dynamic.php" style="background: #28a745; color: white; padding: 8px 15px; text-decoration: none; margin-right: 10px;">View Website</a>
    <a href="admin/" style="background: #007cba; color: white; padding: 8px 15px; text-decoration: none; margin-right: 10px;">Admin Panel</a>
    <a href="manage_skills.php" style="background: #fd7e14; color: white; padding: 8px 15px; text-decoration: none;">Manage Skills</a>
</div>