<?php
require_once __DIR__ . '/../config/database.php';

class PortfolioData {
    private $pdo; // Changed from $conn to $pdo to match the methods
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect(); // Changed from $conn to $pdo
        
         if ($this->pdo === null) {
            error_log("Database connection failed");
            die("Database connection failed. Please contact support.");
        }
    }
    
    // Email Configuration
    public function getEmailConfig() {
        try {
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'email_config'");
            if ($tableCheck->rowCount() == 0) {
                throw new Exception("Table 'email_config' does not exist.");
            }
            
            $query = "SELECT * FROM email_config ORDER BY id DESC LIMIT 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new Exception("No email configuration found in database.");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Database Error in getEmailConfig(): " . $e->getMessage());
            die("Unable to load email configuration. Please contact support.");
        } catch (Exception $e) {
            error_log("Error in getEmailConfig(): " . $e->getMessage());
            die("Unable to load email configuration. Please contact support.");
        }
    }

    // Personal Info
    public function getPersonalInfo() {
        try {
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'personal_info'");
            if ($tableCheck->rowCount() == 0) {
                throw new Exception("Table 'personal_info' does not exist.");
            }
            
            $query = "SELECT * FROM personal_info ORDER BY id DESC LIMIT 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new Exception("No personal information found in database.");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Database Error in getPersonalInfo(): " . $e->getMessage());
            die("Unable to load personal information. Please contact support.");
        } catch (Exception $e) {
            error_log("Error in getPersonalInfo(): " . $e->getMessage());
            die("Unable to load personal information. Please contact support.");
        }
    }
    
    // Skills
    public function getSkillCategories() {
        try {
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'skill_categories'");
            if ($tableCheck->rowCount() == 0) {
                throw new Exception("Table 'skill_categories' does not exist. Please run the database setup script.");
            }
            
            $query = "SELECT * FROM skill_categories ORDER BY display_order, id";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($result)) {
                throw new Exception("No skill categories found in database. Please populate the skill_categories table.");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Database Error in getSkillCategories(): " . $e->getMessage());
            die("Unable to load skill categories. Please contact support.");
        } catch (Exception $e) {
            error_log("Error in getSkillCategories(): " . $e->getMessage());
            die("Unable to load skill categories. Please contact support.");
        }
    }
    
    public function getSkillsByCategory($categoryId) {
        try {
            // Check if table exists
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'skills'");
            if ($tableCheck->rowCount() == 0) {
                throw new Exception("Table 'skills' does not exist. Please run the database setup script.");
            }
            
            $query = "SELECT * FROM skills WHERE category_id = :category_id ORDER BY display_order, id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':category_id', $categoryId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error in getSkillsByCategory(): " . $e->getMessage());
            die("Unable to load skills by category. Please contact support.");
        } catch (Exception $e) {
            error_log("Error in getSkillsByCategory(): " . $e->getMessage());
            die("Unable to load skills by category. Please contact support.");
        }
    }
    
    public function getAllSkillsGrouped() {
        try {
            $categories = $this->getSkillCategories();
            foreach ($categories as &$category) {
                $category['skills'] = $this->getSkillsByCategory($category['id']);
            }
            return $categories;
        } catch (Exception $e) {
            error_log("Error in getAllSkillsGrouped(): " . $e->getMessage());
            die("Unable to load skills. Please contact support.");
        }
    }
    
    // Research Papers
    public function getResearchPapers() {
        try {
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'research_papers'");
            if ($tableCheck->rowCount() == 0) {
                throw new Exception("Table 'research_papers' does not exist. Please run the database setup script.");
            }
            
            $query = "SELECT * FROM research_papers WHERE is_published = 1 ORDER BY publication_date DESC, display_order";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error in getResearchPapers(): " . $e->getMessage());
            die("Unable to load research papers. Please contact support.");
        } catch (Exception $e) {
            error_log("Error in getResearchPapers(): " . $e->getMessage());
            die("Unable to load research papers. Please contact support.");
        }
    }
    
    // Experiences
    public function getExperiences($type = null) {
        try {
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'experiences'");
            if ($tableCheck->rowCount() == 0) {
                throw new Exception("Table 'experiences' does not exist. Please run the database setup script.");
            }
            
            $query = "SELECT * FROM experiences";
            if ($type) {
                $query .= " WHERE type = :type";
            }
            $query .= " ORDER BY end_date DESC, start_date DESC, display_order";
            
            $stmt = $this->pdo->prepare($query);
            if ($type) {
                $stmt->bindParam(':type', $type);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error in getExperiences(): " . $e->getMessage());
            die("Unable to load experiences. Please contact support.");
        } catch (Exception $e) {
            error_log("Error in getExperiences(): " . $e->getMessage());
            die("Unable to load experiences. Please contact support.");
        }
    }
    
    // Projects
    public function getProjects($featured_only = true) {
        try {
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'projects'");
            if ($tableCheck->rowCount() == 0) {
                throw new Exception("Table 'projects' does not exist. Please run the database setup script.");
            }
            
            $query = "SELECT * FROM projects";
            if ($featured_only) {
                $query .= " WHERE is_featured = 1";
            }
            $query .= " ORDER BY display_order, created_at DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error in getProjects(): " . $e->getMessage());
            die("Unable to load projects. Please contact support.");
        } catch (Exception $e) {
            error_log("Error in getProjects(): " . $e->getMessage());
            die("Unable to load projects. Please contact support.");
        }
    }
    
    // Social Links
    public function getSocialLinks() {
        try {
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'social_links'");
            if ($tableCheck->rowCount() == 0) {
                throw new Exception("Table 'social_links' does not exist. Please run the database setup script.");
            }
            
            $query = "SELECT * FROM social_links WHERE is_active = 1 ORDER BY display_order";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error in getSocialLinks(): " . $e->getMessage());
            die("Unable to load social links. Please contact support.");
        } catch (Exception $e) {
            error_log("Error in getSocialLinks(): " . $e->getMessage());
            die("Unable to load social links. Please contact support.");
        }
    }
    
    // Admin functions for CRUD operations
    public function addSkillCategory($data) {
        try {
            $query = "INSERT INTO skill_categories (name, icon, experience_years, display_order) 
                      VALUES (:name, :icon, :experience_years, :display_order)";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            throw new Exception("Database Error in addSkillCategory(): " . $e->getMessage());
        }
    }
    
    public function addSkill($data) {
        try {
            $query = "INSERT INTO skills (category_id, name, percentage, display_order) 
                      VALUES (:category_id, :name, :percentage, :display_order)";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            throw new Exception("Database Error in addSkill(): " . $e->getMessage());
        }
    }
    
    public function addResearchPaper($data) {
        try {
            $query = "INSERT INTO research_papers (title, journal, publication_date, authors, abstract, 
                      pdf_file, doi_link, code_link, display_order) 
                      VALUES (:title, :journal, :publication_date, :authors, :abstract, 
                      :pdf_file, :doi_link, :code_link, :display_order)";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            throw new Exception("Database Error in addResearchPaper(): " . $e->getMessage());
        }
    }
    
    public function addExperience($data) {
        try {
            $query = "INSERT INTO experiences (type, title, organization, location, start_date, 
                      end_date, is_current, description, display_order) 
                      VALUES (:type, :title, :organization, :location, :start_date, 
                      :end_date, :is_current, :description, :display_order)";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            throw new Exception("Database Error in addExperience(): " . $e->getMessage());
        }
    }
    
    public function addProject($data) {
        try {
            $query = "INSERT INTO projects (title, description, image, project_link, 
                      github_link, technologies, display_order, is_featured) 
                      VALUES (:title, :description, :image, :project_link, 
                      :github_link, :technologies, :display_order, :is_featured)";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            throw new Exception("Database Error in addProject(): " . $e->getMessage());
        }
    }

    public function addSocialLink($data) {
        try {
            $query = "INSERT INTO social_links (platform, url, icon_class, is_active, display_order) 
                      VALUES (:platform, :url, :icon_class, :is_active, :display_order)";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            throw new Exception("Database Error in addSocialLink(): " . $e->getMessage());
        }
    }
    
    // Email configuration management
    public function updateEmailConfig($data) {
        try {
            $query = "UPDATE email_config SET 
                      smtp_host = :smtp_host, 
                      smtp_port = :smtp_port, 
                      smtp_username = :smtp_username, 
                      smtp_password = :smtp_password, 
                      from_email = :from_email, 
                      from_name = :from_name, 
                      to_email = :to_email,
                      updated_at = CURRENT_TIMESTAMP 
                      WHERE id = :id";
            
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            throw new Exception("Database Error in updateEmailConfig(): " . $e->getMessage());
        }
    }

    // Method to check database status
    public function checkDatabaseStatus() {
        try {
            $requiredTables = [
                'personal_info', 
                'skill_categories', 
                'skills', 
                'research_papers', 
                'experiences', 
                'projects', 
                'social_links', 
                'email_config'
            ];
            
            $status = [];
            foreach ($requiredTables as $table) {
                $tableCheck = $this->pdo->query("SHOW TABLES LIKE '$table'");
                $status[$table] = $tableCheck->rowCount() > 0;
            }
            
            return $status;
        } catch (PDOException $e) {
            throw new Exception("Database Error in checkDatabaseStatus(): " . $e->getMessage());
        }
    }

    public function updatePersonalInfo($data) {
        try {
            $allowedFields = [
                'name',
                'title',
                'subtitle',
                'email',
                'phone',
                'location',
                'birth_date',
                'description',
                'about_me',
                'profile_image',
                'resume_file'
            ];
            
            $updates = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields, true)) {
                    $updates[] = "$field = ?";
                    $params[] = $value;
                } else {
                    error_log("SECURITY WARNING: Attempted to update non-whitelisted field in updatePersonalInfo: " . $field);
                }
            }
            
            if (empty($updates)) {
                error_log("No valid fields to update in updatePersonalInfo");
                return false;
            }
            
            $sql = "UPDATE personal_info SET " . implode(', ', $updates) . " WHERE id = 1";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Update personal info error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteSkill($skillId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM skills WHERE id = ?");
            return $stmt->execute([$skillId]);
        } catch (Exception $e) {
            error_log("Delete skill error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteSkillCategory($categoryId) {
        try {
            // First delete all skills in this category
            $stmt = $this->pdo->prepare("DELETE FROM skills WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            
            // Then delete the category
            $stmt = $this->pdo->prepare("DELETE FROM skill_categories WHERE id = ?");
            return $stmt->execute([$categoryId]);
        } catch (Exception $e) {
            error_log("Delete skill category error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteResearchPaper($paperId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM research_papers WHERE id = ?");
            return $stmt->execute([$paperId]);
        } catch (Exception $e) {
            error_log("Delete research paper error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteExperience($experienceId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM experiences WHERE id = ?");
            return $stmt->execute([$experienceId]);
        } catch (Exception $e) {
            error_log("Delete experience error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteProject($projectId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM projects WHERE id = ?");
            return $stmt->execute([$projectId]);
        } catch (Exception $e) {
            error_log("Delete project error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteSocialLink($socialId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM social_links WHERE id = ?");
            return $stmt->execute([$socialId]);
        } catch (Exception $e) {
            error_log("Delete social link error: " . $e->getMessage());
            return false;
        }
    }

    // Helper method to get item ID by name and type
    public function getItemIdByName($name, $type) {
        try {
            $table = '';
            $field = '';
            
            switch ($type) {
                case 'skill':
                    $table = 'skills';
                    $field = 'name';
                    break;
                case 'skill_category':
                    $table = 'skill_categories';
                    $field = 'name';
                    break;
                case 'research_paper':
                    $table = 'research_papers';
                    $field = 'title';
                    break;
                case 'experience':
                    $table = 'experiences';
                    $field = 'title';
                    break;
                case 'project':
                    $table = 'projects';
                    $field = 'title';
                    break;
                case 'social_link':
                    $table = 'social_links';
                    $field = 'platform';
                    break;
                default:
                    return null;
            }
            
            $stmt = $this->pdo->prepare("SELECT id FROM {$table} WHERE {$field} = ? LIMIT 1");
            $stmt->execute([$name]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['id'] : null;
        } catch (Exception $e) {
            error_log("Get item ID error: " . $e->getMessage());
            return null;
        }
    }

        // Get individual items by ID for editing
        public function getSkillById($id) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM skills WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Get skill error: " . $e->getMessage());
                return null;
            }
        }
        
        public function getSkillCategoryById($id) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM skill_categories WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Get skill category error: " . $e->getMessage());
                return null;
            }
        }
        
        public function getResearchPaperById($id) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM research_papers WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Get research paper error: " . $e->getMessage());
                return null;
            }
        }
        
        public function getExperienceById($id) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM experiences WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Get experience error: " . $e->getMessage());
                return null;
            }
        }
        
        public function getProjectById($id) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM projects WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Get project error: " . $e->getMessage());
                return null;
            }
        }
        
        public function getSocialLinkById($id) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM social_links WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Get social link error: " . $e->getMessage());
                return null;
            }
        }
        
        // Update methods
        public function updateSkill($id, $data) {
            try {
                $stmt = $this->pdo->prepare(
                    "UPDATE skills SET category_id = ?, name = ?, percentage = ?, display_order = ? WHERE id = ?"
                );
                return $stmt->execute([
                    $data['category_id'],
                    $data['name'],
                    $data['percentage'],
                    $data['display_order'],
                    $id
                ]);
            } catch (Exception $e) {
                error_log("Update skill error: " . $e->getMessage());
                return false;
            }
        }
        
        public function updateSkillCategory($id, $data) {
            try {
                $stmt = $this->pdo->prepare(
                    "UPDATE skill_categories SET name = ?, icon = ?, experience_years = ?, display_order = ? WHERE id = ?"
                );
                return $stmt->execute([
                    $data['name'],
                    $data['icon'],
                    $data['experience_years'],
                    $data['display_order'],
                    $id
                ]);
            } catch (Exception $e) {
                error_log("Update skill category error: " . $e->getMessage());
                return false;
            }
        }
        
        public function updateResearchPaper($id, $data) {
            try {
                $stmt = $this->pdo->prepare(
                    "UPDATE research_papers SET title = ?, journal = ?, publication_date = ?, authors = ?, 
                    abstract = ?, pdf_file = ?, doi_link = ?, code_link = ?, display_order = ? WHERE id = ?"
                );
                return $stmt->execute([
                    $data['title'],
                    $data['journal'],
                    $data['publication_date'],
                    $data['authors'],
                    $data['abstract'],
                    $data['pdf_file'],
                    $data['doi_link'],
                    $data['code_link'],
                    $data['display_order'],
                    $id
                ]);
            } catch (Exception $e) {
                error_log("Update research paper error: " . $e->getMessage());
                return false;
            }
        }
        
        public function updateExperience($id, $data) {
            try {
                $stmt = $this->pdo->prepare(
                    "UPDATE experiences SET type = ?, title = ?, organization = ?, location = ?, 
                    start_date = ?, end_date = ?, is_current = ?, description = ?, display_order = ? WHERE id = ?"
                );
                return $stmt->execute([
                    $data['type'],
                    $data['title'],
                    $data['organization'],
                    $data['location'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['is_current'],
                    $data['description'],
                    $data['display_order'],
                    $id
                ]);
            } catch (Exception $e) {
                error_log("Update experience error: " . $e->getMessage());
                return false;
            }
        }
        
        public function updateProject($id, $data) {
            try {
                $stmt = $this->pdo->prepare(
                    "UPDATE projects SET title = ?, description = ?, image = ?, project_link = ?, 
                    github_link = ?, technologies = ?, display_order = ?, is_featured = ? WHERE id = ?"
                );
                return $stmt->execute([
                    $data['title'],
                    $data['description'],
                    $data['image'],
                    $data['project_link'],
                    $data['github_link'],
                    $data['technologies'],
                    $data['display_order'],
                    $data['is_featured'],
                    $id
                ]);
            } catch (Exception $e) {
                error_log("Update project error: " . $e->getMessage());
                return false;
            }
        }
        
        public function updateSocialLink($id, $data) {
            try {
                $stmt = $this->pdo->prepare(
                    "UPDATE social_links SET platform = ?, url = ?, icon_class = ?, is_active = ?, display_order = ? WHERE id = ?"
                );
                return $stmt->execute([
                    $data['platform'],
                    $data['url'],
                    $data['icon_class'],
                    $data['is_active'],
                    $data['display_order'],
                    $id
                ]);
            } catch (Exception $e) {
                error_log("Update social link error: " . $e->getMessage());
                return false;
            }
        }

        // Email config methods
        public function getEmailConfigById($id) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM email_config WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Get email config error: " . $e->getMessage());
                return null;
            }
        }

        public function updateEmailConfigById($id, $data) {
            try {
                $stmt = $this->pdo->prepare(
                    "UPDATE email_config SET smtp_host = ?, smtp_port = ?, smtp_username = ?, 
                    smtp_password = ?, from_email = ?, from_name = ?, to_email = ? WHERE id = ?"
                );
                return $stmt->execute([
                    $data['smtp_host'],
                    $data['smtp_port'],
                    $data['smtp_username'],
                    $data['smtp_password'],
                    $data['from_email'],
                    $data['from_name'],
                    $data['to_email'],
                    $id
                ]);
            } catch (Exception $e) {
                error_log("Update email config error: " . $e->getMessage());
                return false;
            }
        }

        // Personal info methods
        public function getPersonalInfoById($id) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM personal_info WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Get personal info error: " . $e->getMessage());
                return null;
            }
        }

        // ⚠️ CRITICAL: This method handles HTML content - DO NOT ESCAPE
        public function updatePersonalInfoById($id, $data) {
            try {
                error_log("=== updatePersonalInfoById called ===");
                error_log("ID: " . $id);
                error_log("Data received: " . print_r($data, true));
                
                // SECURITY FIX: Define allowed fields as a static whitelist
                // This prevents SQL injection by using hardcoded field names
                $allowedFields = [
                    'name' => 'name',
                    'title' => 'title',
                    'subtitle' => 'subtitle',
                    'email' => 'email',
                    'phone' => 'phone',
                    'location' => 'location',
                    'birth_date' => 'birth_date',
                    'description' => 'description',
                    'about_me' => 'about_me'
                ];
                
                // Build SQL using hardcoded field names, not user input
                $updates = [];
                $params = [];
                
                foreach ($allowedFields as $safeFieldName => $columnName) {
                    // Check if this field exists in the data
                    if (array_key_exists($safeFieldName, $data)) {
                        // Use the hardcoded column name, NOT the user-provided key
                        $updates[] = $columnName . " = ?";
                        $params[] = $data[$safeFieldName];
                        
                        // Log HTML content fields
                        if ($safeFieldName === 'description' || $safeFieldName === 'about_me') {
                            error_log("Field " . $safeFieldName . ": " . substr($data[$safeFieldName], 0, 100));
                        }
                    }
                }
                
                if (empty($updates)) {
                    error_log("No valid fields to update!");
                    return false;
                }
                
                $params[] = $id;
                
                // SQL is built from hardcoded strings only
                $sql = "UPDATE personal_info SET " . implode(', ', $updates) . " WHERE id = ?";
                error_log("SQL: " . $sql);
                
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute($params);
                
                error_log("Execute result: " . ($result ? 'TRUE' : 'FALSE'));
                error_log("Rows affected: " . $stmt->rowCount());
                
                return $result;
            } catch (Exception $e) {
                error_log("Update personal info error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                return false;
            }
        }

        // Admin auth helpers
        public function getAdminById($id) {
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Get admin error: " . $e->getMessage());
                return null;
            }
        }

        public function updateAdminPassword($id, $passwordHash) {
            try {
                $stmt = $this->pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                return $stmt->execute([$passwordHash, $id]);
            } catch (Exception $e) {
                error_log("Update admin password error: " . $e->getMessage());
                return false;
            }
        }
    }
?>