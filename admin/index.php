<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Check if user is authenticated
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../classes/PortfolioData.php';

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Security functions
function sanitizeInput($input) {
    if ($input === null) {
        return '';
    }
    if (is_string($input)) {
        return strip_tags(trim($input));
    }
    return $input;
}

function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Initialize portfolio data
try {
    $portfolioData = new PortfolioData();
    $personalInfo = $portfolioData->getPersonalInfo();
    $emailConfig = $portfolioData->getEmailConfig();
    $skillCategories = $portfolioData->getSkillCategories();
    $allSkillsGrouped = $portfolioData->getAllSkillsGrouped();
    $researchPapers = $portfolioData->getResearchPapers();
    $workExperiences = $portfolioData->getExperiences('work');
    $education = $portfolioData->getExperiences('education');
    $projects = $portfolioData->getProjects(false);
    $socialLinks = $portfolioData->getSocialLinks();
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    die("System temporarily unavailable. Please try again later.");
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_item') {
    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: application/json');
    
    if (!validateCSRF($_GET['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
    
    $itemType = $_GET['type'] ?? '';
    $itemId = (int)($_GET['id'] ?? 0);
    
    if (!$itemType || !$itemId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing type or ID']);
        exit;
    }
    
    try {
        $ajaxPortfolioData = new PortfolioData();
        $data = null;
        
        switch ($itemType) {
            case 'skill':
                $data = $ajaxPortfolioData->getSkillById($itemId);
                break;
            case 'skill_category':
                $data = $ajaxPortfolioData->getSkillCategoryById($itemId);
                break;
            case 'research_paper':
                $data = $ajaxPortfolioData->getResearchPaperById($itemId);
                break;
            case 'experience':
                $data = $ajaxPortfolioData->getExperienceById($itemId);
                break;
            case 'project':
                $data = $ajaxPortfolioData->getProjectById($itemId);
                break;
            case 'social_link':
                $data = $ajaxPortfolioData->getSocialLinkById($itemId);
                break;
            case 'email_config':
                $data = $ajaxPortfolioData->getEmailConfigById($itemId);
                break;
            case 'personal_info':
                $data = $ajaxPortfolioData->getPersonalInfoById($itemId);
                break;
            default:
                throw new Exception('Invalid item type');
        }
        
        if (!$data) {
            http_response_code(404);
            echo json_encode(['error' => 'Item not found']);
            exit;
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $alertMessage = "Security token mismatch. Please try again.";
        $alertType = 'error';
    } else {
        $success = false;
        $message = "";
        
        try {
            switch ($_POST['action']) {
                case 'upload_photo':
                    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception("No photo uploaded or upload error occurred.");
                    }
                    
                    $uploadedFile = $_FILES['profile_photo'];
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    
                    if (!in_array(strtolower($uploadedFile['type']), $allowedTypes)) {
                        throw new Exception("Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.");
                    }
                    
                    $maxSize = 5 * 1024 * 1024; // 5MB
                    
                    if ($uploadedFile['size'] > $maxSize) {
                        throw new Exception("File too large. Please upload an image smaller than 5MB.");
                    }
                    
                    $uploadDir = '../assets/images/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileExtension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
                    
                    // SECURITY: Validate file extension against whitelist
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $fileExtension = strtolower($fileExtension);
                    
                    if (!in_array($fileExtension, $allowedExtensions)) {
                        throw new Exception("Invalid file extension. Only jpg, jpeg, png, gif, webp allowed.");
                    }
                    
                    $newFileName = 'profile_' . time() . '_' . uniqid() . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $newFileName;
                    $relativePath = 'assets/images/' . $newFileName;
                    
                    // SECURITY: Validate the relative path format before passing to database
                    if (!preg_match('/^assets\/images\/profile_\d+_[a-f0-9]+\.(jpg|jpeg|png|gif|webp)$/', $relativePath)) {
                        throw new Exception("Invalid file path generated.");
                    }
                    
                    if (!move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
                        throw new Exception("Failed to save uploaded file.");
                    }
                    
                    // SECURITY: Only pass validated, whitelisted field to database
                    $updateData = [];
                    $updateData['profile_image'] = $relativePath; // Now validated
                    
                    $success = $portfolioData->updatePersonalInfo($updateData);
                    
                    if ($success) {
                        if ($personalInfo['profile_image'] && 
                            $personalInfo['profile_image'] !== $relativePath && 
                            file_exists('../' . $personalInfo['profile_image'])) {
                            unlink('../' . $personalInfo['profile_image']);
                        }
                        $message = "Profile photo updated successfully!";
                    } else {
                        if (file_exists($uploadPath)) {
                            unlink($uploadPath);
                        }
                        throw new Exception("Failed to update profile photo in database.");
                    }
                    break;
                
                case 'upload_resume':
                    if (!isset($_FILES['resume_file']) || $_FILES['resume_file']['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception("No resume uploaded or upload error occurred.");
                    }
                    
                    $uploadedFile = $_FILES['resume_file'];
                    if (!in_array($uploadedFile['type'], ['application/pdf'])) {
                        throw new Exception("Invalid file type. Only PDF allowed.");
                    }
                    if ($uploadedFile['size'] > 5 * 1024 * 1024) {
                        throw new Exception("File too large. Upload <5MB.");
                    }
                
                    $resumeDir = '../assets/';
                    $resumeName = 'Kritarth_Ranjan_Resume.pdf';
                    $resumePath = $resumeDir . $resumeName;
                
                    // Version existing resume
                    if (file_exists($resumePath)) {
                        $i = 1;
                        while (file_exists($resumeDir . "Kritarth_Ranjan_Resume_v{$i}.pdf")) $i++;
                        rename($resumePath, $resumeDir . "Kritarth_Ranjan_Resume_v{$i}.pdf");
                    }
                
                    if (!move_uploaded_file($uploadedFile['tmp_name'], $resumePath)) {
                        throw new Exception("Failed to save uploaded resume.");
                    }
                
                    $portfolioData->updatePersonalInfo(['resume_file' => 'assets/' . $resumeName]);
                    $success = true;
                    $message = "Resume uploaded successfully!";
                    break;

                case 'delete_skill':
                    $itemId = (int)$_POST['item_id'];
                    if ($itemId <= 0) {
                        throw new Exception("Invalid skill ID.");
                    }
                    
                    $success = $portfolioData->deleteSkill($itemId);
                    $message = $success ? "Skill deleted successfully!" : "Failed to delete skill.";
                    break;

                case 'delete_skill_category':
                    $itemId = (int)$_POST['item_id'];
                    if ($itemId <= 0) {
                        throw new Exception("Invalid category ID.");
                    }
                    
                    $success = $portfolioData->deleteSkillCategory($itemId);
                    $message = $success ? "Skill category deleted successfully!" : "Failed to delete skill category.";
                    break;

                case 'delete_research_paper':
                    $itemId = (int)$_POST['item_id'];
                    if ($itemId <= 0) {
                        throw new Exception("Invalid research paper ID.");
                    }
                    
                    $success = $portfolioData->deleteResearchPaper($itemId);
                    $message = $success ? "Research paper deleted successfully!" : "Failed to delete research paper.";
                    break;

                case 'delete_experience':
                    $itemId = (int)$_POST['item_id'];
                    if ($itemId <= 0) {
                        throw new Exception("Invalid experience ID.");
                    }
                    
                    $success = $portfolioData->deleteExperience($itemId);
                    $message = $success ? "Experience deleted successfully!" : "Failed to delete experience.";
                    break;

                case 'delete_project':
                    $itemId = (int)$_POST['item_id'];
                    if ($itemId <= 0) {
                        throw new Exception("Invalid project ID.");
                    }
                    
                    $success = $portfolioData->deleteProject($itemId);
                    $message = $success ? "Project deleted successfully!" : "Failed to delete project.";
                    break;

                case 'delete_social_link':
                    $itemId = (int)$_POST['item_id'];
                    if ($itemId <= 0) {
                        throw new Exception("Invalid social link ID.");
                    }
                    
                    $success = $portfolioData->deleteSocialLink($itemId);
                    $message = $success ? "Social link deleted successfully!" : "Failed to delete social link.";
                    break;

                case 'update_skill':
                    $itemId = (int)$_POST['item_id'];
                    $data = [
                        'category_id' => (int)$_POST['category_id'],
                        'name' => sanitizeInput($_POST['name']),
                        'percentage' => (int)$_POST['percentage'],
                        'display_order' => (int)($_POST['display_order'] ?? 0)
                    ];
                    
                    if (empty($data['name']) || $data['category_id'] <= 0) {
                        throw new Exception("Skill name and valid category are required.");
                    }
                    
                    if ($data['percentage'] < 0 || $data['percentage'] > 100) {
                        throw new Exception("Percentage must be between 0 and 100.");
                    }
                    
                    $success = $portfolioData->updateSkill($itemId, $data);
                    $message = $success ? "Skill updated successfully!" : "Failed to update skill.";
                    break;
                    
                case 'update_skill_category':
                    $itemId = (int)$_POST['item_id'];
                    $data = [
                        'name' => sanitizeInput($_POST['name']),
                        'icon' => sanitizeInput($_POST['icon']),
                        'experience_years' => (int)$_POST['experience_years'],
                        'display_order' => (int)($_POST['display_order'] ?? 0)
                    ];
                    
                    if (empty($data['name'])) {
                        throw new Exception("Category name is required.");
                    }
                    
                    $success = $portfolioData->updateSkillCategory($itemId, $data);
                    $message = $success ? "Skill category updated successfully!" : "Failed to update skill category.";
                    break;
                    
                case 'update_research_paper':
                    $itemId = (int)$_POST['item_id'];
                    $data = [
                        'title' => sanitizeInput($_POST['title']),
                        'journal' => sanitizeInput($_POST['journal']),
                        'publication_date' => $_POST['publication_date'],
                        'authors' => sanitizeInput($_POST['authors']),
                        'abstract' => sanitizeInput($_POST['abstract']),
                        'pdf_file' => sanitizeInput($_POST['pdf_file']),
                        'doi_link' => filter_var($_POST['doi_link'], FILTER_VALIDATE_URL) ? $_POST['doi_link'] : '',
                        'code_link' => filter_var($_POST['code_link'], FILTER_VALIDATE_URL) ? $_POST['code_link'] : '',
                        'display_order' => (int)($_POST['display_order'] ?? 0)
                    ];
                    
                    if (empty($data['title'])) {
                        throw new Exception("Paper title is required.");
                    }
                    
                    $success = $portfolioData->updateResearchPaper($itemId, $data);
                    $message = $success ? "Research paper updated successfully!" : "Failed to update research paper.";
                    break;
                    
                case 'update_experience':
                    $itemId = (int)$_POST['item_id'];
                    $data = [
                        'type' => in_array($_POST['type'], ['work', 'education']) ? $_POST['type'] : 'work',
                        'title' => sanitizeInput($_POST['title']),
                        'organization' => sanitizeInput($_POST['organization']),
                        'location' => sanitizeInput($_POST['location'] ?? ''),
                        'start_date' => $_POST['start_date'],
                        'end_date' => $_POST['end_date'] ?? null,
                        'is_current' => isset($_POST['is_current']) ? 1 : 0,
                        'description' => sanitizeInput($_POST['description'] ?? ''),
                        'display_order' => (int)($_POST['display_order'] ?? 0)
                    ];
                    
                    if (empty($data['title']) || empty($data['organization'])) {
                        throw new Exception("Title and organization are required.");
                    }
                    
                    $success = $portfolioData->updateExperience($itemId, $data);
                    $message = $success ? "Experience updated successfully!" : "Failed to update experience.";
                    break;
                    
                case 'update_project':
                    $itemId = (int)$_POST['item_id'];
                    $data = [
                        'title' => sanitizeInput($_POST['title']),
                        'description' => sanitizeInput($_POST['description']),
                        'image' => filter_var($_POST['image'], FILTER_VALIDATE_URL) ? $_POST['image'] : '',
                        'project_link' => filter_var($_POST['project_link'], FILTER_VALIDATE_URL) ? $_POST['project_link'] : '',
                        'github_link' => filter_var($_POST['github_link'], FILTER_VALIDATE_URL) ? $_POST['github_link'] : '',
                        'technologies' => sanitizeInput($_POST['technologies']),
                        'display_order' => (int)($_POST['display_order'] ?? 0),
                        'is_featured' => isset($_POST['is_featured']) ? 1 : 0
                    ];
                    
                    if (empty($data['title']) || empty($data['description'])) {
                        throw new Exception("Project title and description are required.");
                    }
                    
                    $success = $portfolioData->updateProject($itemId, $data);
                    $message = $success ? "Project updated successfully!" : "Failed to update project.";
                    break;
                    
                case 'update_social_link':
                    $itemId = (int)$_POST['item_id'];
                    $data = [
                        'platform' => sanitizeInput($_POST['platform']),
                        'url' => filter_var($_POST['url'], FILTER_VALIDATE_URL) ? $_POST['url'] : '',
                        'icon_class' => sanitizeInput($_POST['icon_class']),
                        'is_active' => isset($_POST['is_active']) ? 1 : 0,
                        'display_order' => (int)($_POST['display_order'] ?? 0)
                    ];
                    
                    if (empty($data['platform']) || empty($data['url'])) {
                        throw new Exception("Platform and URL are required.");
                    }
                    
                    $success = $portfolioData->updateSocialLink($itemId, $data);
                    $message = $success ? "Social link updated successfully!" : "Failed to update social link.";
                    break;
                    
                case 'add_skill_category':
                    $data = [
                        'name' => sanitizeInput($_POST['name']),
                        'icon' => sanitizeInput($_POST['icon']),
                        'experience_years' => (int)$_POST['experience_years'],
                        'display_order' => (int)($_POST['display_order'] ?? 0)
                    ];
                    
                    if (empty($data['name'])) {
                        throw new Exception("Category name is required.");
                    }
                    
                    $success = $portfolioData->addSkillCategory($data);
                    $message = $success ? "Skill category added successfully!" : "Failed to add skill category.";
                    break;
                    
                case 'add_skill':
                    $data = [
                        'category_id' => (int)$_POST['category_id'],
                        'name' => sanitizeInput($_POST['name']),
                        'percentage' => (int)$_POST['percentage'],
                        'display_order' => (int)($_POST['display_order'] ?? 0)
                    ];
                    
                    if (empty($data['name']) || $data['category_id'] <= 0) {
                        throw new Exception("Skill name and valid category are required.");
                    }
                    
                    if ($data['percentage'] < 0 || $data['percentage'] > 100) {
                        throw new Exception("Percentage must be between 0 and 100.");
                    }
                    
                    $success = $portfolioData->addSkill($data);
                    $message = $success ? "Skill added successfully!" : "Failed to add skill.";
                    break;
                    
                case 'add_research_paper':
                    $data = [
                        'title' => sanitizeInput($_POST['title']),
                        'journal' => sanitizeInput($_POST['journal']),
                        'publication_date' => $_POST['publication_date'],
                        'authors' => sanitizeInput($_POST['authors']),
                        'abstract' => sanitizeInput($_POST['abstract']),
                        'pdf_file' => sanitizeInput($_POST['pdf_file']),
                        'doi_link' => filter_var($_POST['doi_link'], FILTER_VALIDATE_URL) ? $_POST['doi_link'] : '',
                        'code_link' => filter_var($_POST['code_link'], FILTER_VALIDATE_URL) ? $_POST['code_link'] : '',
                        'display_order' => (int)($_POST['display_order'] ?? 0)
                    ];
                    
                    if (empty($data['title'])) {
                        throw new Exception("Paper title is required.");
                    }
                    
                    $success = $portfolioData->addResearchPaper($data);
                    $message = $success ? "Research paper added successfully!" : "Failed to add research paper.";
                    break;
                    
                case 'add_experience':
                    $data = [
                        'type' => in_array($_POST['type'], ['work', 'education']) ? $_POST['type'] : 'work',
                        'title' => sanitizeInput($_POST['title']),
                        'organization' => sanitizeInput($_POST['organization']),
                        'location' => sanitizeInput($_POST['location'] ?? ''),
                        'start_date' => $_POST['start_date'],
                        'end_date' => $_POST['end_date'] ?? null,
                        'is_current' => isset($_POST['is_current']) ? 1 : 0,
                        'description' => sanitizeInput($_POST['description'] ?? ''),
                        'display_order' => (int)($_POST['display_order'] ?? 0)
                    ];
                    
                    if (empty($data['title']) || empty($data['organization'])) {
                        throw new Exception("Title and organization are required.");
                    }
                    
                    $success = $portfolioData->addExperience($data);
                    $message = $success ? "Experience added successfully!" : "Failed to add experience.";
                    break;
                    
                case 'add_project':
                    $data = [
                        'title' => sanitizeInput($_POST['title']),
                        'description' => sanitizeInput($_POST['description']),
                        'image' => filter_var($_POST['image'], FILTER_VALIDATE_URL) ? $_POST['image'] : '',
                        'project_link' => filter_var($_POST['project_link'], FILTER_VALIDATE_URL) ? $_POST['project_link'] : '',
                        'github_link' => filter_var($_POST['github_link'], FILTER_VALIDATE_URL) ? $_POST['github_link'] : '',
                        'technologies' => sanitizeInput($_POST['technologies']),
                        'display_order' => (int)($_POST['display_order'] ?? 0),
                        'is_featured' => isset($_POST['is_featured']) ? 1 : 0
                    ];
                    
                    if (empty($data['title']) || empty($data['description'])) {
                        throw new Exception("Project title and description are required.");
                    }
                    
                    $success = $portfolioData->addProject($data);
                    $message = $success ? "Project added successfully!" : "Failed to add project.";
                    break;

                case 'update_email_config':
                    $itemId = (int)$_POST['item_id'];
                    $data = [
                        'smtp_host' => sanitizeInput($_POST['smtp_host']),
                        'smtp_port' => (int)$_POST['smtp_port'],
                        'smtp_username' => sanitizeInput($_POST['smtp_username']),
                        'smtp_password' => sanitizeInput($_POST['smtp_password']),
                        'from_email' => filter_var($_POST['from_email'], FILTER_VALIDATE_EMAIL) ? $_POST['from_email'] : '',
                        'from_name' => sanitizeInput($_POST['from_name']),
                        'to_email' => filter_var($_POST['to_email'], FILTER_VALIDATE_EMAIL) ? $_POST['to_email'] : ''
                    ];
                    
                    if (empty($data['smtp_host']) || empty($data['smtp_username']) || empty($data['from_email'])) {
                        throw new Exception("SMTP host, username, and from email are required.");
                    }
                    
                    $success = $portfolioData->updateEmailConfigById($itemId, $data);
                    $message = $success ? "Email configuration updated successfully!" : "Failed to update email configuration.";
                    break;
                
                case 'update_personal_info':
                    $itemId = (int)$_POST['item_id'];
                    
                    // Validate item ID
                    if ($itemId <= 0) {
                        throw new Exception("Invalid item ID.");
                    }
                    
                    // SECURITY: Define allowed fields for personal info update
                    $allowedFields = [
                        'name', 'title', 'subtitle', 'email', 
                        'phone', 'location', 'birth_date', 
                        'description', 'about_me'
                    ];
                    
                    // Get HTML content WITHOUT escaping (for rich text editor)
                    $description = $_POST['description'] ?? '';
                    $aboutMe = $_POST['about_me'] ?? '';
                    
                    // SECURITY: Validate that description and about_me are strings
                    if (!is_string($description) || !is_string($aboutMe)) {
                        throw new Exception("Invalid data type for description or about_me.");
                    }
                    
                    error_log("Raw description received: " . substr($description, 0, 100));
                    error_log("Raw about_me received: " . substr($aboutMe, 0, 100));
                    
                    // SECURITY: Build data array with validated fields only
                    $data = [];
                    
                    // Add simple fields with sanitization
                    if (isset($_POST['name'])) {
                        $data['name'] = sanitizeInput($_POST['name']);
                    }
                    if (isset($_POST['title'])) {
                        $data['title'] = sanitizeInput($_POST['title']);
                    }
                    if (isset($_POST['subtitle'])) {
                        $data['subtitle'] = sanitizeInput($_POST['subtitle']);
                    }
                    if (isset($_POST['email'])) {
                        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
                        if ($email === false) {
                            throw new Exception("Invalid email format.");
                        }
                        $data['email'] = $email;
                    }
                    if (isset($_POST['phone'])) {
                        $data['phone'] = sanitizeInput($_POST['phone']);
                    }
                    if (isset($_POST['location'])) {
                        $data['location'] = sanitizeInput($_POST['location']);
                    }
                    if (isset($_POST['birth_date'])) {
                        // Validate date format
                        $birthDate = $_POST['birth_date'];
                        if (!empty($birthDate) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate)) {
                            throw new Exception("Invalid birth date format.");
                        }
                        $data['birth_date'] = $birthDate;
                    }
                    
                    // Add HTML fields (validated as strings, not sanitized)
                    if (!empty($description)) {
                        $data['description'] = $description;
                    }
                    if (!empty($aboutMe)) {
                        $data['about_me'] = $aboutMe;
                    }
                    
                    // SECURITY: Verify all fields in $data are in the whitelist
                    foreach (array_keys($data) as $field) {
                        if (!in_array($field, $allowedFields, true)) {
                            error_log("SECURITY WARNING: Attempted to update non-whitelisted field: " . $field);
                            unset($data[$field]);
                        }
                    }
                    
                    if (empty($data)) {
                        throw new Exception("No valid fields to update.");
                    }
                    
                    $success = $portfolioData->updatePersonalInfoById($itemId, $data);
                    break;
                    
                default:
                    throw new Exception("Invalid action specified.");
            }

            // Handle AJAX requests
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                if ($success) {
                    echo json_encode(['success' => true, 'message' => $message]);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => $message]);
                }
                exit;
            }

        } catch (Exception $e) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
            
            $success = false;
            $message = $e->getMessage();
            error_log("Form submission error: " . $e->getMessage());
        }
        
        $alertType = $success ? 'success' : 'error';
        $alertMessage = $message;
    }
    
    if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        $redirectUrl = $_SERVER['PHP_SELF'];
        if (isset($alertMessage)) {
            $redirectUrl .= "?alert=" . urlencode($alertMessage) . "&type=" . $alertType;
        }
        header("Location: " . $redirectUrl);
        exit;
    }
}

// Get current time
$currentUser = $_SESSION['admin_user'] ?? 'Kritarth123-prince';
$currentTime = new DateTime('2025-06-28 20:12:45', new DateTimeZone('UTC'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Admin Dashboard</title>
    <meta name="description" content="Portfolio Content Management System">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <!-- External CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <link rel="stylesheet" href="css/admin.css">
    <script src="js/admin.js"></script>
</head>
<body>
    <div class="mobile-overlay" id="mobileOverlay"></div>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1><i class="fas fa-chart-line"></i> Portfolio MS</h1>
            <p>Portfolio Management System</p>
        </div>
        <nav class="nav-menu">
            <div class="nav-item active" data-section="overview">
                <i class="fas fa-tachometer-alt"></i> <span>Overview</span>
            </div>
            <div class="nav-item" data-section="personal">
                <i class="fas fa-user-circle"></i> <span>Personal Info</span>
            </div>
            <div class="nav-item" data-section="skills">
                <i class="fas fa-code"></i> <span>Skills</span>
            </div>
            <div class="nav-item" data-section="research">
                <i class="fas fa-flask"></i> <span>Research Papers</span>
            </div>
            <div class="nav-item" data-section="experience">
                <i class="fas fa-briefcase"></i> <span>Experience</span>
            </div>
            <div class="nav-item" data-section="projects">
                <i class="fas fa-project-diagram"></i> <span>Projects</span>
            </div>
            <div class="nav-item" data-section="social">
                <i class="fas fa-share-alt"></i> <span>Social Links</span>
            </div>
            <div class="nav-item" data-section="email">
                <i class="fas fa-envelope-open"></i> <span>Email Config</span>
            </div>
            <div class="nav-item" data-section="add-forms">
                <i class="fas fa-plus-circle"></i> <span>Add New Content</span>
            </div>
            <div class="nav-item" data-href="change_password.php">
                <i class="fas fa-key"></i> <span>Change Password</span>
            </div>

        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle mobile menu">
                    <i class="fas fa-bars"></i>
                </button>
                <h2>Dashboard</h2>
            </div>
            <div class="top-bar-right">
                <div class="user-info">
                    <div class="user-avatar"><?= strtoupper(substr($currentUser, 0, 1)) ?></div>
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars(ucfirst($currentUser)) ?></div>
                        <div class="user-time"><?= $currentTime->format('Y-m-d H:i:s') ?> UTC</div>
                    </div>
                </div>
                <a href="?logout=1" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Alert Messages -->
            <?php if (isset($_GET['alert'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_GET['type'] ?? 'success') ?>" role="alert">
                    <i class="fas fa-<?= $_GET['type'] === 'error' ? 'exclamation-triangle' : 'check-circle' ?>"></i>
                    <span><?= htmlspecialchars($_GET['alert']) ?></span>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'" aria-label="Close alert">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Overview Section -->
            <div class="content-section active" id="overview">
                <div class="section-header">
                    <h3 class="section-title">Portfolio Overview</h3>
                    <!-- <div class="timestamp">
                        <i class="fas fa-clock"></i>
                         Last updated: <?= $currentTime->format('Y-m-d H:i:s') ?> UTC 
                    </div> -->
                </div>
                <div class="section-content">
                    <div class="stats-grid">
                        <div class="stat-card primary">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fas fa-code"></i></div>
                            </div>
                            <div class="stat-number"><?= count($allSkillsGrouped) ?></div>
                            <div class="stat-label">Skill Categories</div>
                        </div>
                        <div class="stat-card success">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fas fa-flask"></i></div>
                            </div>
                            <div class="stat-number"><?= count($researchPapers) ?></div>
                            <div class="stat-label">Research Papers</div>
                        </div>
                        <div class="stat-card warning">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                            </div>
                            <div class="stat-number"><?= count($workExperiences) + count($education) ?></div>
                            <div class="stat-label">Total Experiences</div>
                        </div>
                        <div class="stat-card info">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fas fa-project-diagram"></i></div>
                            </div>
                            <div class="stat-number"><?= count($projects) ?></div>
                            <div class="stat-label">Projects</div>
                        </div>
                    </div>

                    <div class="overview-cards">
                        <div class="overview-card">
                            <h4>Latest Work Experience</h4>
                            <?php if (!empty($workExperiences)): ?>
                                <?php $latest = $workExperiences[0]; ?>
                                <div class="overview-item">
                                    <h5><?= htmlspecialchars($latest['title']) ?></h5>
                                    <p class="overview-org"><?= htmlspecialchars($latest['organization']) ?></p>
                                    <p class="overview-date">
                                        <?= date('M Y', strtotime($latest['start_date'])) ?> - 
                                        <?= $latest['is_current'] ? 'Present' : date('M Y', strtotime($latest['end_date'])) ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <p class="empty-text">No work experience added yet.</p>
                            <?php endif; ?>
                        </div>

                        <div class="overview-card">
                            <h4>Latest Project</h4>
                            <?php if (!empty($projects)): ?>
                                <?php $latest = $projects[0]; ?>
                                <div class="overview-item">
                                    <h5><?= htmlspecialchars($latest['title']) ?></h5>
                                    <p class="overview-desc"><?= htmlspecialchars(substr($latest['description'], 0, 100)) ?>...</p>
                                    <p class="overview-tech"><?= htmlspecialchars($latest['technologies']) ?></p>
                                </div>
                            <?php else: ?>
                                <p class="empty-text">No projects added yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Info Section -->
            <div class="content-section" id="personal">
                <div class="section-header">
                    <h3 class="section-title">Personal Information</h3>
                    <button class="btn btn-primary btn-edit" data-type="personal_info" data-id="<?= $personalInfo['id'] ?>" data-name="Personal Information">
                        <i class="fas fa-edit"></i> Edit Info
                    </button>
                </div>
                <div class="section-content">
                    <div class="personal-info-grid">
                        <div class="profile-image-section">
                            <img src="../<?= htmlspecialchars($personalInfo['profile_image']) ?>" 
                                alt="Profile" 
                                class="profile-image">
                            
                            <!-- Change Photo Button -->
                            <button class="btn btn-secondary" id="changePhotoBtn">
                                <i class="fas fa-camera"></i> Change Photo
                            </button>

                            <!-- Photo Upload Form -->
                            <form method="post" enctype="multipart/form-data" id="photoUploadForm" style="display:none; margin-top:10px;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="update_photo">
                                
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" required style="margin-bottom: 10px;">
                                    <small style="display: block; color: #666; font-size: 12px;">Max size: 5MB. Formats: JPEG, PNG, GIF, WebP</small>
                                </div>
                                
                                <div style="display: flex; gap: 10px;">
                                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                                        <i class="fas fa-upload"></i> Upload
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="cancelPhotoUpload" style="flex: 1;">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>

                            <!-- Change Resume Button -->
                            <button class="btn btn-secondary" id="changeResumeBtn" style="margin-top:16px;">
                                <i class="fas fa-file-upload"></i> Change Resume
                            </button>

                            <!-- Resume Upload Form -->
                            <form method="post" enctype="multipart/form-data" id="resumeUploadForm" style="display:none; margin-top:10px;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="upload_resume">
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <input type="file" name="resume_file" id="resumeInput" accept="application/pdf" required style="margin-bottom: 10px;">
                                    <small style="display: block; color: #666; font-size: 12px;">PDF only. Max size: 5MB</small>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                                        <i class="fas fa-upload"></i> Upload
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="cancelResumeUpload" style="flex: 1;">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>

                        <script>
                        document.getElementById('changeResumeBtn').onclick = function() {
                            document.getElementById('resumeUploadForm').style.display = 'block';
                            this.style.display = 'none';
                        };
                        document.getElementById('cancelResumeUpload').onclick = function() {
                            document.getElementById('resumeUploadForm').style.display = 'none';
                            document.getElementById('changeResumeBtn').style.display = 'inline-block';
                        };
                        </script>

                        <div class="personal-details">
                            <table class="data-table">
                                <tr>
                                    <td><strong>Full Name</strong></td>
                                    <td><?= htmlspecialchars($personalInfo['name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Title</strong></td>
                                    <td><?= htmlspecialchars($personalInfo['title']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Subtitle</strong></td>
                                    <td><?= htmlspecialchars($personalInfo['subtitle']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email</strong></td>
                                    <td><?= htmlspecialchars($personalInfo['email']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Phone</strong></td>
                                    <td><?= htmlspecialchars($personalInfo['phone']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Location</strong></td>
                                    <td><?= htmlspecialchars($personalInfo['location']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Birth Date</strong></td>
                                    <td><?= date('F d, Y', strtotime($personalInfo['birth_date'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Description Section (Hero) -->
                    <div class="description-section" style="margin-bottom: 1rem;">
                        <h4><i class="fas fa-star"></i> Hero Section Description</h4>
                        <div class="description-text">
                            <?php 
                            $description = $personalInfo['description'] ?? '';
                            if (!empty($description)) {
                                echo $description;
                            } else {
                                echo '<p style="color: #a0aec0; font-style: italic;">No description added yet.</p>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- About Me Section (Detailed) -->
                    <div class="description-section">
                        <h4><i class="fas fa-user-circle"></i> About Me (Detailed)</h4>
                        <div class="description-text">
                            <?php 
                            $aboutMe = $personalInfo['about_me'] ?? '';
                            if (!empty($aboutMe)) {
                                // Render HTML directly - NO htmlspecialchars!
                                echo $aboutMe;
                            } else {
                                echo '<p style="color: #a0aec0; font-style: italic;">No detailed about me content added yet.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skills Section -->
            <div class="content-section" id="skills">
                <div class="section-header">
                    <h3 class="section-title">Skills Management</h3>
                    <span class="badge primary"><?= array_sum(array_map(function($cat) { return count($cat['skills']); }, $allSkillsGrouped)) ?> Total Skills</span>
                </div>
                <div class="section-content">
                    <?php foreach ($allSkillsGrouped as $category): ?>
                        <div class="skill-category">
                            <div class="skill-category-header">
                                <div class="category-info">
                                    <i class="<?= htmlspecialchars($category['icon']) ?> category-icon"></i>
                                    <div>
                                        <h4><?= htmlspecialchars($category['name']) ?></h4>
                                        <p><?= $category['experience_years'] ?>+ Years Experience • <?= count($category['skills']) ?> Skills</p>
                                    </div>
                                </div>
                                <div class="category-actions">
                                    <button class="btn btn-small btn-edit" data-name="<?= htmlspecialchars($category['name']) ?>" data-id="<?= $category['id'] ?>" data-type="skill_category">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-small btn-delete" data-name="<?= htmlspecialchars($category['name']) ?>" data-id="<?= $category['id'] ?>" data-type="skill_category">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="btn btn-small btn-primary" data-add-target="add-skill-card">
                                        <i class="fas fa-plus"></i> Add Skill
                                    </button>
                                </div>
                            </div>
                            <div class="skills-grid">
                                <?php foreach ($category['skills'] as $skill): ?>
                                    <div class="skill-item">
                                        <div class="skill-info">
                                            <span class="skill-name"><?= htmlspecialchars($skill['name']) ?></span>
                                            <div class="skill-progress">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?= $skill['percentage'] ?>%;"></div>
                                                </div>
                                                <span class="skill-percentage"><?= $skill['percentage'] ?>%</span>
                                            </div>
                                        </div>
                                        <div class="skill-actions">
                                            <button class="btn btn-small btn-edit" data-name="<?= htmlspecialchars($skill['name']) ?>" data-id="<?= $skill['id'] ?>" data-type="skill">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-small btn-delete" data-name="<?= htmlspecialchars($skill['name']) ?>" data-id="<?= $skill['id'] ?>" data-type="skill">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Research Papers Section -->
            <div class="content-section" id="research">
                <div class="section-header">
                    <h3 class="section-title">Research Publications</h3>
                    <div class="section-actions">
                        <span class="badge success"><?= count($researchPapers) ?> Papers</span>
                        <button class="btn btn-primary" data-add-target="add-research-card">
                            <i class="fas fa-plus"></i> Add Paper
                        </button>
                    </div>
                </div>
                <div class="section-content">
                    <?php if (!empty($researchPapers)): ?>
                        <div class="research-grid">
                            <?php foreach ($researchPapers as $paper): ?>
                                <div class="research-card">
                                    <div class="research-header">
                                        <h4><?= htmlspecialchars($paper['title']) ?></h4>
                                        <span class="badge primary"><?= date('Y', strtotime($paper['publication_date'])) ?></span>
                                    </div>
                                    <p class="research-journal"><?= htmlspecialchars($paper['journal']) ?></p>
                                    <p class="research-authors">Authors: <?= htmlspecialchars($paper['authors']) ?></p>
                                    <p class="research-abstract"><?= htmlspecialchars(substr($paper['abstract'], 0, 200)) ?>...</p>
                                    <div class="research-links">
                                        <?php if ($paper['pdf_file']): ?>
                                            <a href="../<?= htmlspecialchars($paper['pdf_file']) ?>" target="_blank" class="btn btn-small btn-primary">
                                                <i class="fas fa-file-pdf"></i> PDF
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($paper['doi_link']): ?>
                                            <a href="<?= htmlspecialchars($paper['doi_link']) ?>" target="_blank" class="btn btn-small btn-primary">
                                                <i class="fas fa-external-link-alt"></i> DOI
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($paper['code_link']): ?>
                                            <a href="<?= htmlspecialchars($paper['code_link']) ?>" target="_blank" class="btn btn-small btn-primary">
                                                <i class="fab fa-github"></i> Code
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="research-actions">
                                        <button class="btn btn-small btn-edit" data-name="<?= htmlspecialchars($paper['title']) ?>" data-id="<?= $paper['id'] ?>" data-type="research_paper">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-small btn-delete" data-name="<?= htmlspecialchars($paper['title']) ?>" data-id="<?= $paper['id'] ?>" data-type="research_paper">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-flask"></i>
                            <h4>No Research Papers</h4>
                            <p>Add your first research paper to get started.</p>
                            <button class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Research Paper
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Experience Section -->
            <div class="content-section" id="experience">
                <div class="section-header">
                    <h3 class="section-title">Experience & Education</h3>
                    <div class="section-actions">
                        <span class="badge warning"><?= count($workExperiences) ?> Work</span>
                        <span class="badge info"><?= count($education) ?> Education</span>
                        <button class="btn btn-primary" data-add-target="add-experience-card">
                            <i class="fas fa-plus"></i> Add Experience
                        </button>
                    </div>
                </div>
                <div class="section-content">
                    <div class="experience-grid">
                        <!-- Work Experience -->
                        <div class="experience-column">
                            <h4 class="column-title work-title">
                                <i class="fas fa-briefcase"></i> Work Experience
                            </h4>
                            <?php if (!empty($workExperiences)): ?>
                                <div class="timeline">
                                    <?php foreach ($workExperiences as $index => $work): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker work-marker"></div>
                                            <div class="timeline-content">
                                                <div class="experience-card">
                                                    <div class="experience-header">
                                                        <h5><?= htmlspecialchars($work['title']) ?></h5>
                                                        <div class="experience-actions">
                                                            <button class="btn btn-small btn-edit" data-name="<?= htmlspecialchars($work['title']) ?>" data-id="<?= $work['id'] ?>" data-type="experience">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-small btn-delete" data-name="<?= htmlspecialchars($work['title']) ?>" data-id="<?= $work['id'] ?>" data-type="experience">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <p class="experience-org"><?= htmlspecialchars($work['organization']) ?></p>
                                                    <p class="experience-date">
                                                        <i class="fas fa-calendar"></i>
                                                        <?= date('M Y', strtotime($work['start_date'])) ?> - 
                                                        <?= $work['is_current'] ? '<span class="badge success">Present</span>' : date('M Y', strtotime($work['end_date'])) ?>
                                                    </p>
                                                    <?php if ($work['location']): ?>
                                                        <p class="experience-location">
                                                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($work['location']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if ($work['description']): ?>
                                                        <p class="experience-desc"><?= htmlspecialchars($work['description']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-briefcase"></i>
                                    <p>No work experience added yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Education -->
                        <div class="experience-column">
                            <h4 class="column-title education-title">
                                <i class="fas fa-graduation-cap"></i> Education
                            </h4>
                            <?php if (!empty($education)): ?>
                                <div class="timeline">
                                    <?php foreach ($education as $index => $edu): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker education-marker"></div>
                                            <div class="timeline-content">
                                                <div class="experience-card">
                                                    <div class="experience-header">
                                                        <h5><?= htmlspecialchars($edu['title']) ?></h5>
                                                        <div class="experience-actions">
                                                            <button class="btn btn-small btn-edit" data-name="<?= htmlspecialchars($edu['title']) ?>" data-id="<?= $edu['id'] ?>" data-type="experience">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-small btn-delete" data-name="<?= htmlspecialchars($edu['title']) ?>" data-id="<?= $edu['id'] ?>" data-type="experience">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <p class="experience-org"><?= htmlspecialchars($edu['organization']) ?></p>
                                                    <p class="experience-date">
                                                        <i class="fas fa-calendar"></i>
                                                        <?= date('Y', strtotime($edu['start_date'])) ?> - 
                                                        <?= $edu['is_current'] ? '<span class="badge success">Present</span>' : date('Y', strtotime($edu['end_date'])) ?>
                                                    </p>
                                                    <?php if ($edu['location']): ?>
                                                        <p class="experience-location">
                                                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($edu['location']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if ($edu['description']): ?>
                                                        <p class="experience-desc"><?= htmlspecialchars($edu['description']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-graduation-cap"></i>
                                    <p>No education records added yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projects Section -->
            <div class="content-section" id="projects">
                <div class="section-header">
                    <h3 class="section-title">Projects Portfolio</h3>
                    <div class="section-actions">
                        <span class="badge success"><?= count($projects) ?> Projects</span>
                        <button class="btn btn-primary" data-add-target="add-project-card">
                            <i class="fas fa-plus"></i> Add Project
                        </button>
                    </div>
                </div>
                <div class="section-content">
                    <?php if (!empty($projects)): ?>
                        <div class="projects-grid">
                            <?php foreach ($projects as $project): ?>
                                <div class="project-card">
                                    <div class="project-header">
                                        <img src="<?= htmlspecialchars($project['image'] ?: 'https://img.icons8.com/dusk/64/group-of-projects.png') ?>" 
                                             alt="Project" 
                                             class="project-image">
                                        <div class="project-info">
                                            <h4><?= htmlspecialchars($project['title']) ?></h4>
                                            <?php if ($project['is_featured']): ?>
                                                <span class="badge primary">Featured</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="project-actions">
                                            <button class="btn btn-small btn-edit" data-name="<?= htmlspecialchars($project['title']) ?>" data-id="<?= $project['id'] ?>" data-type="project">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-small btn-delete" data-name="<?= htmlspecialchars($project['title']) ?>" data-id="<?= $project['id'] ?>" data-type="project">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="project-description"><?= htmlspecialchars($project['description']) ?></p>
                                    <?php if ($project['technologies']): ?>
                                        <div class="project-tech">
                                            <i class="fas fa-tools"></i> <?= htmlspecialchars($project['technologies']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="project-links">
                                        <?php if ($project['project_link']): ?>
                                            <a href="<?= htmlspecialchars($project['project_link']) ?>" target="_blank" class="btn btn-small btn-primary">
                                                <i class="fas fa-external-link-alt"></i> View
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($project['github_link']): ?>
                                            <a href="<?= htmlspecialchars($project['github_link']) ?>" target="_blank" class="btn btn-small btn-primary">
                                                <i class="fab fa-github"></i> GitHub
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-project-diagram"></i>
                            <h4>No Projects</h4>
                            <p>Add your first project to showcase your work.</p>
                            <button class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Project
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Social Links Section -->
            <div class="content-section" id="social">
                <div class="section-header">
                    <h3 class="section-title">Social Media Links</h3>
                    <div class="section-actions">
                        <span class="badge info"><?= count($socialLinks) ?> Links</span>
                        <button class="btn btn-primary" data-add-target="add-social-card">
                            <i class="fas fa-plus"></i> Add Link
                        </button>
                    </div>
                </div>
                <div class="section-content">
                    <?php if (!empty($socialLinks)): ?>
                        <div class="social-grid">
                            <?php foreach ($socialLinks as $social): ?>
                                <div class="social-card">
                                    <div class="social-icon">
                                        <i class="<?= htmlspecialchars($social['icon_class']) ?>"></i>
                                    </div>
                                    <div class="social-info">
                                        <h5><?= htmlspecialchars($social['platform']) ?></h5>
                                        <a href="<?= htmlspecialchars($social['url']) ?>" target="_blank" class="social-url">
                                            <?= htmlspecialchars($social['url']) ?>
                                        </a>
                                    </div>
                                    <div class="social-status">
                                        <span class="badge <?= $social['is_active'] ? 'success' : 'warning' ?>">
                                            <?= $social['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                        <div class="social-actions">
                                            <button class="btn btn-small btn-edit" data-name="<?= htmlspecialchars($social['platform']) ?>" data-id="<?= $social['id'] ?>" data-type="social_link">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-small btn-delete" data-name="<?= htmlspecialchars($social['platform']) ?>" data-id="<?= $social['id'] ?>" data-type="social_link">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-share-alt"></i>
                            <h4>No Social Links</h4>
                            <p>Add your social media profiles to increase visibility.</p>
                            <button class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Social Link
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email Configuration Section -->
            <div class="content-section" id="email">
                <div class="section-header">
                    <h3 class="section-title">Email Configuration</h3>
                    <div class="section-actions">
                        <span class="badge success">Active</span>
                        <button class="btn btn-primary btn-edit" data-type="email_config" data-id="<?= $emailConfig['id'] ?>" data-name="Email Configuration">
                            <i class="fas fa-edit"></i> Edit Config
                        </button>
                    </div>
                </div>
                <div class="section-content">
                    <div class="email-config-card">
                        <table class="data-table">
                            <tr>
                                <td><strong>SMTP Host</strong></td>
                                <td><?= htmlspecialchars($emailConfig['smtp_host']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>SMTP Port</strong></td>
                                <td><?= htmlspecialchars($emailConfig['smtp_port']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Username</strong></td>
                                <td><?= htmlspecialchars($emailConfig['smtp_username']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Password</strong></td>
                                <td>••••••••••••••••</td>
                            </tr>
                            <tr>
                                <td><strong>From Email</strong></td>
                                <td><?= htmlspecialchars($emailConfig['from_email']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>From Name</strong></td>
                                <td><?= htmlspecialchars($emailConfig['from_name']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>To Email</strong></td>
                                <td><?= htmlspecialchars($emailConfig['to_email']) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add New Content Section -->
            <div class="content-section" id="add-forms">
                <div class="section-header">
                    <h3 class="section-title">Add New Content</h3>
                    <p class="section-subtitle">Use the forms below to add new content to your portfolio</p>
                </div>
                <div class="section-content">
                    <div class="forms-grid">
                        
                        <!-- Add Skill Category Form -->
                        <!-- <div class="form-card"> -->
                        <div class="form-card" id="add-skill-category-card">
                            <div class="form-header">
                                <h4><i class="fas fa-layer-group"></i> Add Skill Category</h4>
                            </div>
                            <form method="POST" class="admin-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="add_skill_category">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Category Name</label>
                                        <input type="text" name="name" class="form-input" required placeholder="e.g., Programming Languages">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Icon Class</label>
                                        <input type="text" name="icon" class="form-input" placeholder="e.g., uil uil-brain">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Experience Years</label>
                                        <input type="number" name="experience_years" class="form-input" min="0" max="50" placeholder="3">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Display Order</label>
                                        <input type="number" name="display_order" class="form-input" value="0" placeholder="0">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-full">
                                    <i class="fas fa-plus"></i> Add Category
                                </button>
                            </form>
                        </div>

                                                <!-- Add Skill Form -->
                        <!-- <div class="form-card"> -->
                        <div class="form-card" id="add-skill-card">
                            <div class="form-header">
                                <h4><i class="fas fa-code"></i> Add Skill</h4>
                            </div>
                            <form method="POST" class="admin-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="add_skill">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Category</label>
                                        <select name="category_id" class="form-select" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($skillCategories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Skill Name</label>
                                        <input type="text" name="name" class="form-input" required placeholder="e.g., Python">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Proficiency (%)</label>
                                        <input type="number" name="percentage" class="form-input" min="0" max="100" required placeholder="85">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Display Order</label>
                                        <input type="number" name="display_order" class="form-input" value="0" placeholder="0">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success btn-full">
                                    <i class="fas fa-plus"></i> Add Skill
                                </button>
                            </form>
                        </div>

                        <!-- Add Research Paper Form -->
                        <!-- <div class="form-card full-width"> -->
						<div class="form-card full-width" id="add-research-card">
                            <div class="form-header">
                                <h4><i class="fas fa-flask"></i> Add Research Paper</h4>
                            </div>
                            <form method="POST" class="admin-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="add_research_paper">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="title" class="form-input" required placeholder="Research paper title">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Journal</label>
                                        <input type="text" name="journal" class="form-input" placeholder="Journal name">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Publication Date</label>
                                        <input type="date" name="publication_date" class="form-input">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Authors</label>
                                        <input type="text" name="authors" class="form-input" placeholder="Author 1, Author 2, Author 3">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Abstract</label>
                                    <textarea name="abstract" class="form-textarea" rows="4" placeholder="Research paper abstract..."></textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">PDF File Path</label>
                                        <input type="text" name="pdf_file" class="form-input" placeholder="assets/papers/paper.pdf">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">DOI Link</label>
                                        <input type="url" name="doi_link" class="form-input" placeholder="https://doi.org/...">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Code Link</label>
                                        <input type="url" name="code_link" class="form-input" placeholder="https://github.com/...">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Display Order</label>
                                        <input type="number" name="display_order" class="form-input" value="0" placeholder="0">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-warning btn-full">
                                    <i class="fas fa-plus"></i> Add Research Paper
                                </button>
                            </form>
                        </div>

                        <!-- Add Experience Form -->
                         <!-- <div class="form-card full-width"> -->
                        <div class="form-card full-width" id="add-experience-card">
                            <div class="form-header">
                                <h4><i class="fas fa-briefcase"></i> Add Experience</h4>
                            </div>
                            <form method="POST" class="admin-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="add_experience">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Type</label>
                                        <select name="type" class="form-select" required>
                                            <option value="">Select Type</option>
                                            <option value="work">Work Experience</option>
                                            <option value="education">Education</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="title" class="form-input" required placeholder="Job title or degree">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Organization</label>
                                        <input type="text" name="organization" class="form-input" required placeholder="Company or institution">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Location</label>
                                        <input type="text" name="location" class="form-input" placeholder="City, Country">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" name="start_date" class="form-input" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">End Date</label>
                                        <input type="date" name="end_date" class="form-input">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="is_current" name="is_current">
                                        <label for="is_current">Currently working/studying here</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-textarea" rows="3" placeholder="Describe your role and achievements..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-info btn-full">
                                    <i class="fas fa-plus"></i> Add Experience
                                </button>
                            </form>
                        </div>

                        <!-- Add Project Form -->
                        <!-- <div class="form-card full-width"> -->
                        <div class="form-card full-width" id="add-project-card">
                            <div class="form-header">
                                <h4><i class="fas fa-project-diagram"></i> Add Project</h4>
                            </div>
                            <form method="POST" class="admin-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="add_project">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Project Title</label>
                                        <input type="text" name="title" class="form-input" required placeholder="Project name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Image URL</label>
                                        <input type="url" name="image" class="form-input" placeholder="https://example.com/image.png">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-textarea" rows="3" required placeholder="Describe your project..."></textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Project Link</label>
                                        <input type="url" name="project_link" class="form-input" placeholder="https://project-demo.com">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">GitHub Link</label>
                                        <input type="url" name="github_link" class="form-input" placeholder="https://github.com/user/repo">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Technologies</label>
                                        <input type="text" name="technologies" class="form-input" placeholder="e.g., Python, React, TensorFlow">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Display Order</label>
                                        <input type="number" name="display_order" class="form-input" value="0" placeholder="0">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="is_featured" name="is_featured" checked>
                                        <label for="is_featured">Featured Project</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success btn-full">
                                    <i class="fas fa-plus"></i> Add Project
                                </button>
                            </form>
                        </div>

                        <!-- Add Social Link Form -->
                        <!-- <div class="form-card"> -->
                        <div class="form-card" id="add-social-card">
                            <div class="form-header">
                                <h4><i class="fas fa-share-alt"></i> Add Social Link</h4>
                            </div>
                            <form method="POST" class="admin-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="add_social_link">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Platform</label>
                                        <input type="text" name="platform" class="form-input" required placeholder="e.g., LinkedIn">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">URL</label>
                                        <input type="url" name="url" class="form-input" required placeholder="https://linkedin.com/in/username">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Icon Class</label>
                                        <input type="text" name="icon_class" class="form-input" placeholder="fab fa-linkedin">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Display Order</label>
                                        <input type="number" name="display_order" class="form-input" value="0" placeholder="0">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="is_active" name="is_active" checked>
                                        <label for="is_active">Active</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-info btn-full">
                                    <i class="fas fa-plus"></i> Add Social Link
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Load the admin JavaScript -->
    <!-- <script src="js/admin.js"></script> -->
    <script>
        // Global CSRF token for AJAX requests
        window.csrfToken = '<?= $_SESSION['csrf_token'] ?>';
        
        // Current user and time
        window.currentUser = '<?= htmlspecialchars($currentUser) ?>';
        window.currentTime = '<?= $currentTime->format('Y-m-d H:i:s') ?>';
        
        // Skill categories for edit forms
        window.skillCategories = <?= json_encode($skillCategories) ?>;
        // console.log('Quill loaded:', typeof Quill !== 'undefined');
    </script>
</body>
</html>