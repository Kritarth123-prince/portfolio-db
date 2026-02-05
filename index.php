<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';
require_once 'classes/PortfolioData.php';
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();


session_start();

// Initialize portfolio data
try {
    $portfolioData = new PortfolioData();
    $personalInfo = $portfolioData->getPersonalInfo();
    $emailConfig = $portfolioData->getEmailConfig();
    $skillsGrouped = $portfolioData->getAllSkillsGrouped();
    $researchPapers = $portfolioData->getResearchPapers();
    $workExperiences = $portfolioData->getExperiences('work');
    $education = $portfolioData->getExperiences('education');
    $projects = $portfolioData->getProjects();
    $socialLinks = $portfolioData->getSocialLinks();
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    die("
    <div style='font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1);'>
        <h2 style='color: #e74c3c; margin-bottom: 20px;'>Database Error</h2>
        <p style='font-size: 16px; line-height: 1.6; margin-bottom: 20px;'>Unable to connect to database. Please try again later.</p>
        <div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>
            <h3 style='margin-top: 0; color: #333;'>Quick Fix Options:</h3>
            <a href='database_diagnostic.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px; display: inline-block; margin-bottom: 10px;'>🔍 Diagnose Database</a>
            <a href='setup_database.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px; display: inline-block; margin-bottom: 10px;'>🛠️ Setup Database</a>
            <a href='create_tables.php' style='background: #fd7e14; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px; display: inline-block; margin-bottom: 10px;'>📋 Create Tables</a>
            <a href='populate_data.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 10px;'>📊 Populate Data</a>
        </div>
    </div>
    ");
}

// Calculate age dynamically from database
$dob = new DateTime($personalInfo['birth_date']);
$today = new DateTime();
$age = $today->diff($dob)->y;

function sanitizeForEmail($input, $type = 'text') {
    switch ($type) {
        case 'email':
            $sanitized = filter_var($input, FILTER_SANITIZE_EMAIL);
            return filter_var($sanitized, FILTER_VALIDATE_EMAIL) ? $sanitized : '';
            
        case 'phone':
            return preg_replace('/[^0-9+\-() ]/', '', $input);
            
        case 'message':
            // Remove ALL HTML tags
            $sanitized = strip_tags($input);
            // Escape HTML entities
            $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
            // Convert line breaks to <br> safely
            return nl2br($sanitized, false);
            
        case 'text':
        default:
            return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    }
}

// Contact form handling - SIMPLIFIED LOGIC
if (isset($_SESSION['form_success_time']) && (time() - $_SESSION['form_success_time']) > 5) {
    unset($_SESSION['form_success']);
    unset($_SESSION['form_success_time']);
}

$messageSent = isset($_SESSION['form_success']) ? $_SESSION['form_success'] : false;
$formDisabled = $messageSent && (time() - $_SESSION['form_success_time']) <= 5;

// Process contact form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contact_form'])) {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $country_code = htmlspecialchars(trim($_POST['country_code']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $message = htmlspecialchars(trim($_POST['message']));

    // Handle contact form submission
    if (isset($_POST['contact_form'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $country_code = trim($_POST['country_code'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (!empty($name) && !empty($email) && !empty($message) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = $emailConfig['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $emailConfig['smtp_username'];
                $mail->Password = $emailConfig['smtp_password'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $emailConfig['smtp_port'];

                // Recipients
                $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
                $mail->addAddress($emailConfig['to_email']);

                // SECURITY: Comprehensive sanitization
                $safeName = sanitizeForEmail($name, 'text');
                $safeEmail = sanitizeForEmail($email, 'email');
                $safePhone = sanitizeForEmail($phone, 'phone');
                $safeCountryCode = sanitizeForEmail($country_code, 'phone');
                $safeMessage = sanitizeForEmail($message, 'message');

                // Validate email
                if (empty($safeEmail)) {
                    throw new Exception("Invalid email address.");
                }

                $mail->addReplyTo($safeEmail, $safeName);

                // SECURITY: Use plain text email to eliminate injection risk
                $mail->isHTML(false);  // ✅ No HTML = No injection
                $mail->Subject = "New Contact: " . $safeName;

                // Build plain text body (no HTML parsing possible)
                $mail->Body = "═══════════════════════════════════════\n";
                $mail->Body .= "   NEW CONTACT FORM SUBMISSION\n";
                $mail->Body .= "═���═════════════════════════════════════\n\n";
                $mail->Body .= "Name:     " . $safeName . "\n";
                $mail->Body .= "Email:    " . $safeEmail . "\n";
                $mail->Body .= "Phone:    " . $safeCountryCode . " " . $safePhone . "\n\n";
                $mail->Body .= "MESSAGE:\n";
                $mail->Body .= "───────────────────────────────────────\n";
                $mail->Body .= strip_tags($message) . "\n";  // Remove ALL HTML tags
                $mail->Body .= "───────────────────────────────────────\n\n";
                $mail->Body .= "Sent from: " . $personalInfo['name'] . "'s Portfolio\n";
                $mail->Body .= "Date/Time: " . date('Y-m-d H:i:s') . "\n";
                $mail->Body .= "═══════════════════════════════════════\n";

                if ($mail->send()) {
                    $_SESSION['form_success'] = true;
                    $_SESSION['form_success_time'] = time();
                    header("Location: index.php#contact");
                    exit();
                } else {
                    $errorMessage = "Failed to send message. Please try again.";
                }
            } catch (Exception $e) {
                error_log("Email Error: " . $e->getMessage());
                $errorMessage = "Failed to send message. Please try again later.";
            }
        } else {
            $errorMessage = "Please fill in all required fields with valid information.";
        }
    }
}

function getCurrentUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    
    if (!preg_match('/^[a-zA-Z0-9.-]+$/', $host)) {
        $host = 'localhost';
    }
    
    $uri = substr($uri, 0, 2000);
    
    return htmlspecialchars($protocol . '://' . $host . $uri, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($personalInfo['name']) ?> | <?= htmlspecialchars($personalInfo['title']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($personalInfo['description']) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($personalInfo['name']) ?>, Machine Learning, Data Science, AI, Portfolio">
    <meta name="author" content="<?= htmlspecialchars($personalInfo['name']) ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= getCurrentUrl() ?>">
    <meta property="og:title" content="<?= htmlspecialchars($personalInfo['name']) ?> | <?= htmlspecialchars($personalInfo['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($personalInfo['description']) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($personalInfo['profile_image']) ?>">

    <link rel="icon" type="image/png" href="assets/title_icon_v1.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    
    <!-- <script>
        // Automatically hide success message and show form after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.querySelector('.form-success');
            const contactForm = document.querySelector('.contact-form form');

            if (successMessage && successMessage.style.display === 'block') {
                setTimeout(function() {
                    successMessage.style.display = 'none';
                    if (contactForm) contactForm.style.display = 'block';
                }, 5000); // Changed to 5 seconds
            }
        });
    </script> -->
</head>

<body>
    <!-- Header Section -->
    <header class="header" id="header">
        <nav class="nav container">
            <a href="#home" class="nav__logo">
                <i class="uil uil-circle"></i> 
                <?= htmlspecialchars(explode(' ', $personalInfo['name'])[0]) ?>
            </a>
            
            <div class="nav__menu" id="nav-menu">
                <ul class="nav__list">
                    <li class="nav__item"><a href="#home" class="nav__link active-link"><i class="uil uil-estate nav__icon"></i> Home</a></li>
                    <li class="nav__item"><a href="#about" class="nav__link"><i class="uil uil-user nav__icon"></i> About</a></li>
                    <li class="nav__item"><a href="#skills" class="nav__link"><i class="uil uil-chart-pie-alt nav__icon"></i> Skills</a></li>
                    <?php if (!empty($researchPapers)): ?>
                    <li class="nav__item"><a href="#research" class="nav__link"><i class="uil uil-flask nav__icon"></i> Research</a></li>
                    <?php endif; ?>
                    <li class="nav__item"><a href="#experience" class="nav__link"><i class="uil uil-briefcase-alt nav__icon"></i> Experience</a></li>
                    <?php if (!empty($projects)): ?>
                    <li class="nav__item"><a href="#projects" class="nav__link"><i class="uil uil-scenery nav__icon"></i> Projects</a></li>
                    <?php endif; ?>
                    <li class="nav__item"><a href="#contact" class="nav__link"><i class="uil uil-message nav__icon"></i> Contact</a></li>
                </ul>
                <i class="uil uil-times nav__close" id="nav-close"></i>
            </div>
            
            <div class="nav__btns">
                <i class="uil uil-moon change-theme" id="theme-button"></i>
                <div class="nav__toggle" id="nav-toggle"><i class="uil uil-apps"></i></div>
            </div>
        </nav>
    </header>

    <div class="container">
        <!-- Hero Section -->
        <header class="hero" id="home">
            <div class="hero-content">
                <h1 class="hero-title"><span id="typing-text"></span><span class="typed-cursor">|</span></h1>
                <h2 class="hero-subtitle"><?= htmlspecialchars($personalInfo['subtitle']) ?></h2>
                <p class="hero-description">
                    <?php
                    $heroDesc = $personalInfo['description'];
                    if (preg_match('/<[^>]+>/', $heroDesc)) {
                        $heroDesc = str_replace(['<p>', '</p>'], '', $heroDesc);
                        echo $heroDesc;
                    } else {
                        echo htmlspecialchars($heroDesc);
                    }
                    ?>
                </p>
                <div class="hero-buttons">
                    <a href="#contact" class="btn btn-primary">Contact Me</a>
                    <?php if ($personalInfo['resume_file']): ?>
                    <a href="<?= htmlspecialchars($personalInfo['resume_file']) ?>" download class="btn btn-secondary" target="_blank">Download Resume</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-image">
                <img src="<?= htmlspecialchars($personalInfo['profile_image']) ?>" alt="<?= htmlspecialchars($personalInfo['name']) ?>">
                <div class="image-caption">Born: <?= date('M d, Y', strtotime($personalInfo['birth_date'])) ?></div>
            </div>
        </header>

        <!-- About Section -->
        <section class="section about" id="about">
            <h2 class="section-title">About Me</h2>
            <div class="about-content">
                <div class="about-text">
                    <?php 
                    // Safe handling of about_me
                    $aboutText = $personalInfo['about_me'] ?? null;
                    
                    if ($aboutText !== null && $aboutText !== ''):
                        // Check if content has HTML tags
                        if (preg_match('/<[^>]+>/', $aboutText)) {
                            // Has HTML - render directly (no escaping)
                            echo $aboutText;
                        } else {
                            // Plain text - escape and convert line breaks
                            $paragraphs = explode("\n\n", $aboutText);
                            foreach ($paragraphs as $paragraph): 
                                $cleanParagraph = trim($paragraph);
                                if ($cleanParagraph !== ''):
                            ?>
                                <p><?= htmlspecialchars($cleanParagraph) ?></p>
                            <?php 
                                endif;
                            endforeach;
                        }
                    else:
                    ?>
                        <p style="color: #999; font-style: italic;">About me content coming soon...</p>
                    <?php
                    endif;
                    ?>
                </div>
            </div>
        </section>

        <!-- Dynamic Skills Section -->
        <section class="skills section" id="skills">
            <h2 class="section-title">Skills</h2>
            <span class="section-subtitle">My technical & other skills</span>

            <div class="skills-grid container">
                <div class="skills-row">
                    <?php 
                    $totalCategories = count($skillsGrouped);
                    $halfPoint = ceil($totalCategories / 2);
                    $columns = array_chunk($skillsGrouped, $halfPoint);
                    ?>
                    
                    <?php foreach ($columns as $columnIndex => $columnCategories): ?>
                    <div class="skills-col">
                        <?php foreach ($columnCategories as $category): ?>
                        <div class="skills-content skills-close">
                            <div class="skills-header">
                                <i class="<?= htmlspecialchars($category['icon']) ?> skills-icon"></i>
                                <div>
                                    <h1 class="skills-title"><?= htmlspecialchars($category['name']) ?></h1>
                                    <span class="skills-subtitle"><?= $category['experience_years'] ?>+ Years XP</span>
                                </div>
                                <i class="uil uil-angle-down skills-arrow"></i>
                            </div>
                            <div class="skills-list grid">
                                <?php foreach ($category['skills'] as $skill): ?>
                                <div class="skills-data">
                                    <div class="skills-titles">
                                        <h3 class="skills-name"><?= html_entity_decode(htmlspecialchars($skill['name'])) ?></h3>
                                        <span class="skills-number"><?= intval($skill['percentage']) ?>%</span>
                                    </div>
                                    <div class="skills-bar">
                                        <span class="skills-percentage" data-percent="<?= intval($skill['percentage']) ?>" style="width: 0%"></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Dynamic Research Section -->
        <?php if (!empty($researchPapers)): ?>
        <section class="section research" id="research">
            <h2 class="section-title">Research Publications</h2>
            <span class="section-subtitle">My academic contributions</span>

            <div class="research-wrapper">
                <button class="research-scroll-btn left" aria-label="Scroll left">
                    <i class="fas fa-angle-left"></i>
                </button>

                <div class="research-container">
                    <?php foreach ($researchPapers as $paper): ?>
                    <div class="research-card">
                        <div class="research-header">
                            <h3 class="research-title"><?= htmlspecialchars($paper['title']) ?></h3>
                            <?php if ($paper['journal']): ?>
                            <span class="research-journal"><?= htmlspecialchars($paper['journal']) ?></span>
                            <?php endif; ?>
                            <?php if ($paper['publication_date']): ?>
                            <span class="research-date"><?= date('Y', strtotime($paper['publication_date'])) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($paper['authors']): ?>
                        <p class="research-authors"><?= htmlspecialchars($paper['authors']) ?></p>
                        <?php endif; ?>
                        <?php if ($paper['abstract']): ?>
                        <p class="research-abstract"><?= htmlspecialchars(substr($paper['abstract'], 0, 200)) ?>...</p>
                        <?php endif; ?>
                        <div class="research-links">
                            <?php if ($paper['pdf_file']): ?>
                            <a href="<?= htmlspecialchars($paper['pdf_file']) ?>" class="research-link" target="_blank">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                            <?php endif; ?>
                            <?php if ($paper['doi_link']): ?>
                            <a href="<?= htmlspecialchars($paper['doi_link']) ?>" class="research-link" target="_blank">
                                <i class="fas fa-external-link-alt"></i> DOI
                            </a>
                            <?php endif; ?>
                            <?php if ($paper['code_link']): ?>
                            <a href="<?= htmlspecialchars($paper['code_link']) ?>" class="research-link" target="_blank">
                                <i class="fab fa-github"></i> Code
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button class="research-scroll-btn right" aria-label="Scroll right">
                    <i class="fas fa-angle-right"></i>
                </button>
            </div>
        </section>
        <?php endif; ?>

        <!-- Dynamic Experience Section -->
        <section class="qualification section" id="experience">
            <h2 class="section-title">Experience</h2>
            <span class="section-subtitle">My journey in the academic & professional front</span>

            <div class="qualification-container">
                <div class="qualification-tabs">
                    <?php if (!empty($education)): ?>
                    <div class="qualification-button button-flex" data-target="#education">
                        <i class="uil uil-graduation-cap qualification-icon"></i>
                        Academic
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($workExperiences)): ?>
                    <div class="qualification-button button-flex <?= empty($education) ? 'qualification-active' : '' ?>" data-target="#work">
                        <i class="uil uil-briefcase-alt qualification-icon"></i>
                        Professional
                    </div>
                    <?php endif; ?>
                </div>

                <div class="qualification-sections">
                    <!-- Education Tab -->
                    <?php if (!empty($education)): ?>
                    <div class="qualification-content" data-content id="education">
                        <?php foreach ($education as $index => $edu): ?>
                        <div class="qualification-data">
                            <?php if ($index % 2 == 0): ?>
                            <div>
                                <h3 class="qualification-title"><?= htmlspecialchars($edu['title']) ?></h3>
                                <span class="qualification-subtitle"><?= htmlspecialchars($edu['organization']) ?></span>
                                <?php if ($edu['location']): ?>
                                <p class="qualification-location"><?= htmlspecialchars($edu['location']) ?></p>
                                <?php endif; ?>
                                <?php if ($edu['description']): ?>
                                <p class="qualification-description"><?= htmlspecialchars($edu['description']) ?></p>
                                <?php endif; ?>
                                <div class="qualification-calendar">
                                    <i class="uil uil-calendar-alt"></i>
                                    <?= date('Y', strtotime($edu['start_date'])) ?> - 
                                    <?= $edu['is_current'] ? 'Present' : date('Y', strtotime($edu['end_date'])) ?>
                                </div>
                            </div>
                            <div>
                                <span class="qualification-rounder"></span>
                                <?php if ($index < count($education) - 1): ?>
                                <span class="qualification-line"></span>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div></div>
                            <div>
                                <span class="qualification-rounder"></span>
                                <?php if ($index < count($education) - 1): ?>
                                <span class="qualification-line"></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="qualification-title"><?= htmlspecialchars($edu['title']) ?></h3>
                                <span class="qualification-subtitle"><?= htmlspecialchars($edu['organization']) ?></span>
                                <?php if ($edu['location']): ?>
                                <p class="qualification-location"><?= htmlspecialchars($edu['location']) ?></p>
                                <?php endif; ?>
                                <?php if ($edu['description']): ?>
                                <p class="qualification-description"><?= htmlspecialchars($edu['description']) ?></p>
                                <?php endif; ?>
                                <div class="qualification-calendar">
                                    <i class="uil uil-calendar-alt"></i>
                                    <?= date('Y', strtotime($edu['start_date'])) ?> - 
                                    <?= $edu['is_current'] ? 'Present' : date('Y', strtotime($edu['end_date'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Work Tab -->
                    <?php if (!empty($workExperiences)): ?>
                    <div class="qualification-content <?= empty($education) ? 'qualification-active' : '' ?>" data-content id="work">
                        <?php foreach ($workExperiences as $index => $work): ?>
                        <div class="qualification-data">
                            <?php if ($index % 2 == 0): ?>
                            <div>
                                <h3 class="qualification-title"><?= htmlspecialchars($work['title']) ?></h3>
                                <span class="qualification-subtitle"><?= htmlspecialchars($work['organization']) ?></span>
                                <?php if ($work['location']): ?>
                                <p class="qualification-location"><?= htmlspecialchars($work['location']) ?></p>
                                <?php endif; ?>
                                <?php if ($work['description']): ?>
                                <p class="qualification-description"><?= htmlspecialchars($work['description']) ?></p>
                                <?php endif; ?>
                                <div class="qualification-calendar">
                                    <i class="uil uil-calendar-alt"></i>
                                    <?= date('M Y', strtotime($work['start_date'])) ?> - 
                                    <?= $work['is_current'] ? 'Present' : date('M Y', strtotime($work['end_date'])) ?>
                                </div>
                            </div>
                            <div>
                                <span class="qualification-rounder"></span>
                                <?php if ($index < count($workExperiences) - 1): ?>
                                <span class="qualification-line"></span>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div></div>
                            <div>
                                <span class="qualification-rounder"></span>
                                <?php if ($index < count($workExperiences) - 1): ?>
                                <span class="qualification-line"></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="qualification-title"><?= htmlspecialchars($work['title']) ?></h3>
                                <span class="qualification-subtitle"><?= htmlspecialchars($work['organization']) ?></span>
                                <?php if ($work['location']): ?>
                                <p class="qualification-location"><?= htmlspecialchars($work['location']) ?></p>
                                <?php endif; ?>
                                <?php if ($work['description']): ?>
                                <p class="qualification-description"><?= htmlspecialchars($work['description']) ?></p>
                                <?php endif; ?>
                                <div class="qualification-calendar">
                                    <i class="uil uil-calendar-alt"></i>
                                    <?= date('M Y', strtotime($work['start_date'])) ?> - 
                                    <?= $work['is_current'] ? 'Present' : date('M Y', strtotime($work['end_date'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Dynamic Projects Section -->
        <?php if (!empty($projects)): ?>
        <section class="section projects" id="projects">
            <h2 class="section-title">Featured Projects</h2>
            <div class="projects-wrapper">
                <button class="projects-scroll-btn left" aria-label="Scroll left">
                    <i class="fas fa-angle-left"></i>
                </button>
                <div class="projects-grid">
                    <?php foreach ($projects as $project): ?>
                    <div class="project-card">
                        <div class="project-image">
                            <img src="<?= htmlspecialchars($project['image'] ?: 'https://img.icons8.com/dusk/64/group-of-projects.png') ?>" 
                                 alt="<?= htmlspecialchars($project['title']) ?>" class="project-icon">
                        </div>
                        <div class="project-info">
                            <h3><?= htmlspecialchars($project['title']) ?></h3>
                            <p><?= htmlspecialchars(substr($project['description'], 0, 100)) ?>...</p>
                            <?php if ($project['technologies']): ?>
                            <div class="project-tech">
                                <small><?= htmlspecialchars($project['technologies']) ?></small>
                            </div>
                            <?php endif; ?>
                            <div class="project-links">
                                <?php if ($project['project_link']): ?>
                                <a href="<?= htmlspecialchars($project['project_link']) ?>" class="project-link" target="_blank">View Project →</a>
                                <?php endif; ?>
                                <?php if ($project['github_link']): ?>
                                <a href="<?= htmlspecialchars($project['github_link']) ?>" class="project-link" target="_blank">GitHub →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button class="projects-scroll-btn right" aria-label="Scroll right">
                    <i class="fas fa-angle-right"></i>
                </button>
            </div>
        </section>
        <?php endif; ?>

        <!-- Contact Section - SIMPLIFIED STRUCTURE -->
        <section class="section contact" id="contact">
            <h2 class="section-title">Get In Touch</h2>
            <div class="contact-container">
                <div class="contact-info">
                    <h3>Contact Information</h3>
                    <p><i class="fas fa-envelope"></i><?= htmlspecialchars($personalInfo['email']) ?></p>
                    <?php if ($personalInfo['phone']): ?>
                    <p><i class="fas fa-phone"></i><?= htmlspecialchars($personalInfo['phone']) ?></p>
                    <?php endif; ?>
                    <?php if ($personalInfo['location']): ?>
                    <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($personalInfo['location']) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($socialLinks)): ?>
                    <div class="social-links">
                        <?php foreach ($socialLinks as $social): ?>
                        <a href="<?= htmlspecialchars($social['url']) ?>" class="social-link" aria-label="<?= htmlspecialchars($social['platform']) ?>" target="_blank">
                            <i class="<?= htmlspecialchars($social['icon_class']) ?>"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="contact-form">
                    <?php if ($messageSent && !$formDisabled): ?>
                        <div class="form-success">
                            <p>Thank you for your message! I'll get back to you soon.</p>
                        </div>
                    <?php endif; ?>

                    <!-- <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>#contact" method="POST" style="<?php echo $formDisabled ? 'display:none;' : 'display:block;' ?>"> -->
                    <form action="#contact" method="POST">
                        <input type="hidden" name="contact_form" value="1">
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Your Email" required>
                        </div>
                        <div class="phone-input-group">
                            <input type="text" name="country_code" class="country-code" placeholder="+91" value="+91">
                            <input type="tel" name="phone" class="phone-number" placeholder="Phone Number">
                        </div>
                        <div class="form-group">
                            <textarea name="message" placeholder="Your Message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>

                    <?php if ($formDisabled): ?>
                        <div class="form-success">
                            <p>Thank you for your message! I'll get back to you soon.</p>
                        </div>
                        <script>
                            // Show form after 5 seconds
                            setTimeout(function() {
                                document.querySelector('.contact-form form').style.display = 'block';
                                document.querySelector('.contact-form .form-success').style.display = 'none';
                            }, 5000);
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> <?= htmlspecialchars($personalInfo['name']) ?>. All rights reserved.</p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>