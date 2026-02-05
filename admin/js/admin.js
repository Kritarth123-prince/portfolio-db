// Admin Dashboard JavaScript - Complete with Universal Edit and Delete System
document.addEventListener('DOMContentLoaded', function() {
    // console.log('Admin Dashboard Loading...');
    
    try {
        const adminDashboard = new AdminDashboard();
        window.adminDashboard = adminDashboard;
        
        // console.log('Admin Dashboard successfully initialized');
    } catch (error) {
        console.error('Failed to initialize Admin Dashboard:', error);
    }
});

class AdminDashboard {
    constructor() {
        this.currentSection = 'overview';
        this.isMobile = window.innerWidth <= 768;
        this.sidebarOpen = !this.isMobile;
        this.currentUser = window.currentUser;
        this.currentTime = window.currentTime;
        this.skillCategories = window.skillCategories || [];
        
        // Delete configuration for all item types
        this.deleteConfig = {
            'skill': {
                containerSelector: '.skill-item',
                actionName: 'delete_skill',
                displayName: 'Skill'
            },
            'skill_category': {
                containerSelector: '.skill-category',
                actionName: 'delete_skill_category',
                displayName: 'Skill Category'
            },
            'research_paper': {
                containerSelector: '.research-card',
                actionName: 'delete_research_paper',
                displayName: 'Research Paper'
            },
            'experience': {
                containerSelector: '.experience-card',
                actionName: 'delete_experience',
                displayName: 'Experience'
            },
            'project': {
                containerSelector: '.project-card',
                actionName: 'delete_project',
                displayName: 'Project'
            },
            'social_link': {
                containerSelector: '.social-card',
                actionName: 'delete_social_link',
                displayName: 'Social Link'
            }
        };
        
        // console.log('AdminDashboard initialized with user:', this.currentUser);
        this.init();
    }

    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupDashboard());
        } else {
            this.setupDashboard();
        }
    }

    setupDashboard() {
        try {
            this.setupEventListeners();
            this.setupPhotoUpload();
            this.updateCurrentTime();
            this.handleResponsive();
            this.initializeAnimations();
            this.loadUserPreferences();
            this.timeInterval = setInterval(() => this.updateCurrentTime(), 1000);
            this.autoHideAlerts();
            this.addNotificationStyles();
            this.addModalStyles();
            
            // console.log('Admin Dashboard fully initialized');
        } catch (error) {
            console.error('Error setting up dashboard:', error);
        }
    }

    setupEventListeners() {
        // console.log('Setting up universal event listeners...');
        
        try {
            // Remove any existing listeners first
            document.removeEventListener('click', this.handleGlobalClick);
            document.removeEventListener('submit', this.handleGlobalSubmit);
            
            // Global click handler using arrow function to preserve 'this'
            this.handleGlobalClick = (e) => {
                // Handle explicit nav links first (e.g., Change Password)
                const hrefNav = e.target.closest('.nav-item[data-href]');
                if (hrefNav) {
                    e.preventDefault();
                    e.stopPropagation();
                    const link = hrefNav.getAttribute('data-href');
                    if (link) window.location.href = link;
                    return;
                }

                // Navigation items (section switching)
                if (e.target.closest('.nav-item')) {
                    this.handleNavClick(e);
                    return;
                }

                // Add buttons jump to forms
                if (e.target.closest('[data-add-target]')) {
                    this.handleAddButton(e);
                    return;
                }

                // Delete buttons
                if (e.target.closest('.btn-delete')) {
                    this.confirmDelete(e);
                    return;
                }

                // Edit buttons
                if (e.target.closest('.btn-edit')) {
                    this.handleEdit(e);
                    return;
                }

                // Mobile menu button
                if (e.target.closest('#mobileMenuBtn')) {
                    this.toggleMobileMenu();
                    return;
                }

                // Mobile overlay
                if (e.target.closest('#mobileOverlay')) {
                    this.closeMobileMenu();
                    return;
                }
            };

            // Global submit handler
            this.handleGlobalSubmit = (e) => {
                if (e.target.closest('.admin-form')) {
                    this.handleFormSubmit(e);
                }
            };

            // Add event listeners
            document.addEventListener('click', this.handleGlobalClick, true); // Use capture phase
            document.addEventListener('submit', this.handleGlobalSubmit);
            
            // Window events
            window.addEventListener('resize', () => this.handleResize());
            document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));
            
            // console.log('Universal event listeners setup complete');
            
            // Debug: Log all delete buttons found
            this.debugDeleteButtons();
        } catch (error) {
            console.error('Error setting up event listeners:', error);
        }
    }

    debugDeleteButtons() {
        const deleteButtons = document.querySelectorAll('.btn-delete');
        // console.log(`Found ${deleteButtons.length} delete buttons on page load:`);
        deleteButtons.forEach((btn, index) => {
            console.log(`Delete Button ${index + 1}:`, {
                type: btn.getAttribute('data-type'),
                id: btn.getAttribute('data-id'),
                name: btn.getAttribute('data-name'),
                visible: btn.offsetParent !== null,
                element: btn
            });
        });
    }

    // Photo upload functionality
    setupPhotoUpload() {
        const changePhotoBtn = document.getElementById('changePhotoBtn');
        const photoUploadForm = document.getElementById('photoUploadForm');
        const cancelPhotoUpload = document.getElementById('cancelPhotoUpload');
        const photoInput = document.getElementById('photoInput');

        if (changePhotoBtn) {
            changePhotoBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (photoUploadForm) {
                    photoUploadForm.style.display = 'block';
                    changePhotoBtn.style.display = 'none';
                }
            });
        }

        if (cancelPhotoUpload) {
            cancelPhotoUpload.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (photoUploadForm && changePhotoBtn) {
                    photoUploadForm.style.display = 'none';
                    changePhotoBtn.style.display = 'inline-block';
                    if (photoInput) {
                        photoInput.value = '';
                    }
                }
            });
        }

        if (photoUploadForm) {
            photoUploadForm.addEventListener('submit', function(e) {
                const photoInput = document.getElementById('photoInput');
                if (!photoInput || !photoInput.files.length) {
                    e.preventDefault();
                    window.adminDashboard?.showNotification('Please select a photo to upload', 'error');
                    return false;
                }

                const file = photoInput.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                
                if (file.size > maxSize) {
                    e.preventDefault();
                    window.adminDashboard?.showNotification('File too large. Please select an image smaller than 5MB.', 'error');
                    return false;
                }

                if (!allowedTypes.includes(file.type.toLowerCase())) {
                    e.preventDefault();
                    window.adminDashboard?.showNotification('Invalid file type. Please select a JPEG, PNG, GIF, or WebP image.', 'error');
                    return false;
                }

                // Show loading state
                const submitBtn = photoUploadForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                    submitBtn.disabled = true;
                }

                window.adminDashboard?.showNotification('Uploading photo...', 'info');
                return true;
            });
        }
    }

    handleNavClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const navItem = e.target.closest('.nav-item');
        const section = navItem.getAttribute('data-section');
        
        if (!section) {
            console.error('No section found for nav item');
            return;
        }
        
        // console.log('Nav clicked:', section);
        this.switchSection(section);
        
        if (this.isMobile && this.sidebarOpen) {
            this.closeMobileMenu();
        }
    }
    handleAddButton(e) {
        e.preventDefault();
        e.stopPropagation();
        const btn = e.target.closest('[data-add-target]');
        const targetId = btn?.getAttribute('data-add-target');
        if (!targetId) {
            this.showNotification('Add form not found', 'error');
            return;
        }
        this.switchSection('add-forms');
        requestAnimationFrame(() => {
            const card = document.getElementById(targetId);
            if (card) {
                card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                const firstField = card.querySelector('input, select, textarea');
                if (firstField) firstField.focus();
            } else {
                this.showNotification('Add form not found', 'error');
            }
        });
    }

    switchSection(sectionName) {
        // console.log('Attempting to switch to section:', sectionName);
        
        if (!sectionName) {
            console.error('No section name provided');
            return false;
        }

        const targetSection = document.getElementById(sectionName);
        if (!targetSection) {
            console.error('Section element not found:', sectionName);
            this.showNotification(`Section "${sectionName}" not found`, 'error');
            return false;
        }

        try {
            this.saveUserPreference('currentSection', sectionName);
            
            // Update navigation
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            const activeNavItem = document.querySelector(`[data-section="${sectionName}"]`);
            if (activeNavItem) {
                activeNavItem.classList.add('active');
            }

            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
                section.style.display = 'none';
            });
            
            // Show target section
            targetSection.classList.add('active');
            targetSection.style.display = 'block';
            
            this.currentSection = sectionName;
            this.updatePageTitle(sectionName);
            this.scrollToTop();

            // console.log('Section switch completed successfully to:', sectionName);
            this.showNotification(`Switched to ${this.getSectionDisplayName(sectionName)}`, 'success');
            
            // Re-debug delete buttons in new section
            setTimeout(() => {
                this.debugDeleteButtons();
            }, 100);
            
            return true;
        } catch (error) {
            console.error('Error switching section:', error);
            this.showNotification('Error switching section', 'error');
            return false;
        }
    }

    getSectionDisplayName(sectionName) {
        const names = {
            'overview': 'Overview',
            'personal': 'Personal Info',
            'skills': 'Skills',
            'research': 'Research Papers',
            'experience': 'Experience',
            'projects': 'Projects',
            'social': 'Social Links',
            'email': 'Email Config',
            'add-forms': 'Add Content'
        };
        return names[sectionName] || sectionName;
    }

    // Enhanced Edit Handler
    handleEdit(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const editBtn = e.target.closest('.btn-edit');
        const itemType = editBtn.getAttribute('data-type') || this.inferItemType(editBtn);
        const itemId = editBtn.getAttribute('data-id');
        const itemName = editBtn.getAttribute('data-name') || 'item';
        
        if (!itemType || !itemId) {
            this.showNotification('Missing item information for editing', 'error');
            return;
        }
        
        // console.log('Edit button clicked:', { itemType, itemId, itemName });
        this.loadEditModal(itemType, itemId, itemName);
    }

    // Infer item type from context if not explicitly set
    inferItemType(button) {
        const card = button.closest('.skill-item, .skill-category, .research-card, .experience-card, .project-card, .social-card');
        
        if (card) {
            if (card.classList.contains('skill-item')) return 'skill';
            if (card.classList.contains('skill-category')) return 'skill_category';
            if (card.classList.contains('research-card')) return 'research_paper';
            if (card.classList.contains('experience-card')) return 'experience';
            if (card.classList.contains('project-card')) return 'project';
            if (card.classList.contains('social-card')) return 'social_link';
        }
        
        return null;
    }

    // Load edit modal with data from server
    // Load edit modal with data from server
    async loadEditModal(itemType, itemId, itemName) {
        try {
            this.showNotification('Loading item data...', 'info');
            
            const urlObj = new URL(window.location.href);
            urlObj.search = '';
            urlObj.searchParams.set('action', 'get_item');
            urlObj.searchParams.set('type', itemType);
            urlObj.searchParams.set('id', itemId);
            urlObj.searchParams.set('csrf_token', window.csrfToken);
            const url = urlObj.toString();
            // console.log('Fetching URL:', url);
            
            // Fetch item data from server
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            // console.log('Response status:', response.status);
            // console.log('Response headers:', response.headers);
            
            // Get the raw response text first
            const rawResponse = await response.text();
            // console.log('Raw response:', rawResponse);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            let result;
            try {
                result = JSON.parse(rawResponse);
            } catch (jsonError) {
                console.error('JSON parse error:', jsonError);
                console.error('Response was:', rawResponse.substring(0, 500));
                throw new Error('Server returned invalid JSON. Check console for details.');
            }
            
            if (result.success && result.data) {
                this.createEditModal(itemType, result.data, itemName);
            } else {
                throw new Error(result.error || 'Invalid response format');
            }
            
        } catch (error) {
            console.error('Error loading edit modal:', error);
            this.showNotification(`Failed to load ${itemName}: ${error.message}`, 'error');
        }
    }

    // Create edit modal with form
    createEditModal(itemType, itemData, itemName) {
        try {
            this.closeExistingModal();
            
            const modal = this.createEditModalElement(itemType, itemData, itemName);
            document.body.appendChild(modal);
            
            requestAnimationFrame(() => {
                modal.classList.add('active');
            });
            
            this.setupEditModalEvents(modal, itemType, itemData.id);
            
            // Initialize Quill editors for personal_info
            if (itemType === 'personal_info') {
                this.initializeQuillEditors(itemData);
            }
            
            // console.log('Edit modal created for:', itemName);
            this.showNotification(`Edit mode activated for ${itemName}`, 'success');
        } catch (error) {
            console.error('Error creating edit modal:', error);
            this.showNotification('Error opening edit dialog', 'error');
        }
    }

    // Create modal element with appropriate form
    createEditModalElement(itemType, itemData, itemName) {
        const modal = document.createElement('div');
        modal.className = 'edit-modal';
        
        const formHTML = this.getEditFormHTML(itemType, itemData);
        
        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        
        // Create content container
        const content = document.createElement('div');
        content.className = 'modal-content';
        
        // Create header
        const header = document.createElement('div');
        header.className = 'modal-header';
        header.innerHTML = `
            <h3><i class="fas fa-edit"></i> Edit ${this.escapeHtml(this.getDisplayName(itemType))}</h3>
            <button class="modal-close" type="button" aria-label="Close modal">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        const body = document.createElement('div');
        body.className = 'modal-body';
        // DOMParser creates a new document context, preventing script execution
        const parser = new DOMParser();
        const doc = parser.parseFromString(formHTML, 'text/html');
        // Import the sanitized content
        while (doc.body.firstChild) {
            body.appendChild(doc.body.firstChild);
        }
        
        // Create footer
        const footer = document.createElement('div');
        footer.className = 'modal-footer';
        footer.innerHTML = `
            <button class="btn btn-secondary modal-cancel" type="button">Cancel</button>
            <button class="btn btn-primary modal-save" type="button">
                <i class="fas fa-save"></i> Save Changes
            </button>
        `;
        
        // Assemble the modal
        content.appendChild(header);
        content.appendChild(body);
        content.appendChild(footer);
        
        modal.appendChild(overlay);
        modal.appendChild(content);
        
        return modal;
    }

    // Initialize Quill rich text editors
    initializeQuillEditors(data) {
        // console.log('Initializing Quill editors...', data);
        
        if (typeof Quill === 'undefined') {
            console.error('Quill is not loaded!');
            this.showNotification('Rich text editor failed to load. Please refresh the page.', 'error');
            return;
        }
        
        setTimeout(() => {
            try {
                // Helper function to sanitize HTML for Quill
                const sanitizeForQuill = (html) => {
                    if (!html) return '';
                
                    const txt = document.createElement('textarea');
                    txt.innerHTML = html;
                    let decoded = txt.value;
                
                    if (decoded.includes('&lt;') || decoded.includes('&gt;')) {
                        txt.innerHTML = decoded;
                        decoded = txt.value;
                    }
                    
                    const temp = document.createElement('div');
                    temp.innerHTML = decoded;
                    temp.querySelectorAll('script, iframe, object, embed').forEach(el => el.remove());
                    
                    return temp.innerHTML;
                };
                
                // ========== DESCRIPTION EDITOR ==========
                const descEditorDiv = document.getElementById('description-editor');
                
                if (descEditorDiv) {
                    // console.log('Creating description Quill editor...');
                    
                    const quillDescription = new Quill('#description-editor', {
                        theme: 'snow',
                        modules: {
                            toolbar: [
                                ['bold', 'italic', 'underline'],
                                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                ['link'],
                                ['clean']
                            ]
                        },
                        placeholder: 'Enter hero section description...'
                    });
                    
                    // FIXED: Use Quill's safe clipboard API instead of innerHTML
                    if (data.description) {
                        const sanitized = sanitizeForQuill(data.description);
                        // Use Quill's clipboard method which sanitizes against XSS
                        quillDescription.clipboard.dangerouslyPasteHTML(sanitized, 'silent');
                    }
                    
                    this.quillDescription = quillDescription;
                    // console.log('Description editor initialized');
                }
                
                // ========== ABOUT ME EDITOR ==========
                const aboutEditorDiv = document.getElementById('about-me-editor');
                
                if (aboutEditorDiv) {
                    // console.log('Creating About Me Quill editor...');
                    
                    const quillAboutMe = new Quill('#about-me-editor', {
                        theme: 'snow',
                        modules: {
                            toolbar: [
                                ['bold', 'italic', 'underline'],
                                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                [{ 'header': [1, 2, 3, false] }],
                                ['link'],
                                ['clean']
                            ]
                        },
                        placeholder: 'Enter detailed about me content...'
                    });
                    
                    // FIXED: Use Quill's safe clipboard API instead of innerHTML
                    if (data.about_me) {
                        const sanitized = sanitizeForQuill(data.about_me);
                        // Use Quill's clipboard method which sanitizes against XSS
                        quillAboutMe.clipboard.dangerouslyPasteHTML(sanitized, 'silent');
                    }
                    
                    this.quillAboutMe = quillAboutMe;
                    // console.log('About Me editor initialized');
                }
                
                // console.log('Quill editors initialization complete');
                
            } catch (error) {
                console.error('Error initializing Quill editors:', error);
                this.showNotification('Error initializing rich text editors', 'error');
            }
        }, 300);
    }

    // Get display name for item type
    getDisplayName(itemType) {
        const names = {
            'skill': 'Skill',
            'skill_category': 'Skill Category',
            'research_paper': 'Research Paper',
            'experience': 'Experience',
            'project': 'Project',
            'social_link': 'Social Link'
        };
        return names[itemType] || 'Item';
    }

    // Generate form HTML based on item type
    getEditFormHTML(itemType, data) {
        switch (itemType) {
            case 'skill':
                return this.getSkillEditForm(data);
            case 'skill_category':
                return this.getSkillCategoryEditForm(data);
            case 'research_paper':
                return this.getResearchPaperEditForm(data);
            case 'experience':
                return this.getExperienceEditForm(data);
            case 'project':
                return this.getProjectEditForm(data);
            case 'social_link':
                return this.getSocialLinkEditForm(data);
            case 'email_config':
                return this.getEmailConfigEditForm(data);
            case 'personal_info':
                return this.getPersonalInfoEditForm(data);
            default:
                return '<p>Unknown item type</p>';
        }
    }

    // Skill edit form
    getSkillEditForm(data) {
        return `
            <form class="edit-form" data-type="skill" data-id="${data.id}">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Skill Name</label>
                        <input type="text" name="name" class="form-input" value="${this.escapeHtml(data.name)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            ${this.getCategoryOptions(data.category_id)}
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Proficiency (%)</label>
                        <input type="number" name="percentage" class="form-input" value="${data.percentage}" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-input" value="${data.display_order || 0}">
                    </div>
                </div>
            </form>
        `;
    }

    // Skill category edit form
    getSkillCategoryEditForm(data) {
        return `
            <form class="edit-form" data-type="skill_category" data-id="${data.id}">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" class="form-input" value="${this.escapeHtml(data.name)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Icon Class</label>
                        <input type="text" name="icon" class="form-input" value="${this.escapeHtml(data.icon)}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Experience Years</label>
                        <input type="number" name="experience_years" class="form-input" value="${data.experience_years}" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-input" value="${data.display_order || 0}">
                    </div>
                </div>
            </form>
        `;
    }

    // Research paper edit form
    getResearchPaperEditForm(data) {
        return `
            <form class="edit-form" data-type="research_paper" data-id="${data.id}">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-input" value="${this.escapeHtml(data.title)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Journal</label>
                        <input type="text" name="journal" class="form-input" value="${this.escapeHtml(data.journal)}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Publication Date</label>
                        <input type="date" name="publication_date" class="form-input" value="${data.publication_date}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Authors</label>
                        <input type="text" name="authors" class="form-input" value="${this.escapeHtml(data.authors)}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Abstract</label>
                    <textarea name="abstract" class="form-textarea" rows="4">${this.escapeHtml(data.abstract)}</textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">PDF File Path</label>
                        <input type="text" name="pdf_file" class="form-input" value="${this.escapeHtml(data.pdf_file)}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">DOI Link</label>
                        <input type="url" name="doi_link" class="form-input" value="${this.escapeHtml(data.doi_link)}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Code Link</label>
                        <input type="url" name="code_link" class="form-input" value="${this.escapeHtml(data.code_link)}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-input" value="${data.display_order || 0}">
                    </div>
                </div>
            </form>
        `;
    }

    // Experience edit form
    getExperienceEditForm(data) {
        return `
            <form class="edit-form" data-type="experience" data-id="${data.id}">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="work" ${data.type === 'work' ? 'selected' : ''}>Work Experience</option>
                            <option value="education" ${data.type === 'education' ? 'selected' : ''}>Education</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-input" value="${this.escapeHtml(data.title)}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Organization</label>
                        <input type="text" name="organization" class="form-input" value="${this.escapeHtml(data.organization)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-input" value="${this.escapeHtml(data.location)}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-input" value="${data.start_date}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-input" value="${data.end_date || ''}">
                    </div>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="edit_is_current" name="is_current" ${data.is_current ? 'checked' : ''}>
                        <label for="edit_is_current">Currently working/studying here</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="3">${this.escapeHtml(data.description)}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-input" value="${data.display_order || 0}">
                </div>
            </form>
        `;
    }

    // Project edit form
    getProjectEditForm(data) {
        return `
            <form class="edit-form" data-type="project" data-id="${data.id}">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Project Title</label>
                        <input type="text" name="title" class="form-input" value="${this.escapeHtml(data.title)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Image URL</label>
                        <input type="url" name="image" class="form-input" value="${this.escapeHtml(data.image)}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="3" required>${this.escapeHtml(data.description)}</textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Project Link</label>
                        <input type="url" name="project_link" class="form-input" value="${this.escapeHtml(data.project_link)}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">GitHub Link</label>
                        <input type="url" name="github_link" class="form-input" value="${this.escapeHtml(data.github_link)}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Technologies</label>
                        <input type="text" name="technologies" class="form-input" value="${this.escapeHtml(data.technologies)}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-input" value="${data.display_order || 0}">
                    </div>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="edit_is_featured" name="is_featured" ${data.is_featured ? 'checked' : ''}>
                        <label for="edit_is_featured">Featured Project</label>
                    </div>
                </div>
            </form>
        `;
    }

    getDisplayName(itemType) {
        const names = {
            'skill': 'Skill',
            'skill_category': 'Skill Category',
            'research_paper': 'Research Paper',
            'experience': 'Experience',
            'project': 'Project',
            'social_link': 'Social Link',
            'email_config': 'Email Configuration',
            'personal_info': 'Personal Information'
        };
        return names[itemType] || 'Item';
    }

    // Social link edit form
    getSocialLinkEditForm(data) {
        return `
            <form class="edit-form" data-type="social_link" data-id="${data.id}">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Platform</label>
                        <input type="text" name="platform" class="form-input" value="${this.escapeHtml(data.platform)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">URL</label>
                        <input type="url" name="url" class="form-input" value="${this.escapeHtml(data.url)}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Icon Class</label>
                        <input type="text" name="icon_class" class="form-input" value="${this.escapeHtml(data.icon_class)}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-input" value="${data.display_order || 0}">
                    </div>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="edit_is_active" name="is_active" ${data.is_active ? 'checked' : ''}>
                        <label for="edit_is_active">Active</label>
                    </div>
                </div>
            </form>
        `;
    }

    // Add these methods to your AdminDashboard class (around line 500-800)
    // Email config edit form
    getEmailConfigEditForm(data) {
        return `
            <form class="edit-form" data-type="email_config" data-id="${data.id}">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-input" value="${this.escapeHtml(data.smtp_host)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SMTP Port</label>
                        <input type="number" name="smtp_port" class="form-input" value="${data.smtp_port}" min="1" max="65535" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" name="smtp_username" class="form-input" value="${this.escapeHtml(data.smtp_username)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" name="smtp_password" class="form-input" value="${this.escapeHtml(data.smtp_password)}" required>
                        <small style="color: #666; font-size: 12px;">Leave blank to keep current password</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">From Email</label>
                        <input type="email" name="from_email" class="form-input" value="${this.escapeHtml(data.from_email)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">From Name</label>
                        <input type="text" name="from_name" class="form-input" value="${this.escapeHtml(data.from_name)}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">To Email (Recipient)</label>
                    <input type="email" name="to_email" class="form-input" value="${this.escapeHtml(data.to_email)}" required>
                </div>
            </form>
        `;
    }

    // Personal info edit form
    getPersonalInfoEditForm(data) {
        return `
            <form class="edit-form" data-type="personal_info" data-id="${data.id}">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-input" value="${this.escapeHtml(data.name)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-input" value="${this.escapeHtml(data.title)}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Subtitle</label>
                    <input type="text" name="subtitle" class="form-input" value="${this.escapeHtml(data.subtitle)}">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" value="${this.escapeHtml(data.email)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input" value="${this.escapeHtml(data.phone)}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-input" value="${this.escapeHtml(data.location)}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Birth Date</label>
                        <input type="date" name="birth_date" class="form-input" value="${data.birth_date}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description (Hero Section)</label>
                    <div id="description-editor" style="height: 120px; background: white; border: 1px solid #d1d5db; border-radius: 6px;"></div>
                    <input type="hidden" name="description" id="description-hidden">
                    <small style="color: #666; font-size: 12px;">Short intro shown in the hero section</small>
                </div>
                <div class="form-group">
                    <label class="form-label">About Me (Detailed)</label>
                    <div id="about-me-editor" style="height: 200px; background: white; border: 1px solid #d1d5db; border-radius: 6px;"></div>
                    <input type="hidden" name="about_me" id="about-me-hidden">
                    <small style="color: #666; font-size: 12px;">Detailed information shown in About Me section</small>
                </div>
            </form>
        `;
    }

    // Get category options for skill form
    getCategoryOptions(selectedId) {
        if (this.skillCategories && this.skillCategories.length > 0) {
            return this.skillCategories.map(cat => 
                `<option value="${cat.id}" ${cat.id == selectedId ? 'selected' : ''}>${this.escapeHtml(cat.name)}</option>`
            ).join('');
        }
        
        // Fallback: try to get from existing select on page
        const categorySelect = document.querySelector('select[name="category_id"]');
        if (categorySelect) {
            return Array.from(categorySelect.options).map(option => 
                `<option value="${option.value}" ${option.value == selectedId ? 'selected' : ''}>${option.textContent}</option>`
            ).join('');
        }
        
        return '<option value="">No categories available</option>';
    }

    // Setup edit modal events
    setupEditModalEvents(modal, itemType, itemId) {
        const closeModal = () => this.closeModal(modal);
        
        modal.querySelector('.modal-close').addEventListener('click', closeModal);
        modal.querySelector('.modal-cancel').addEventListener('click', closeModal);
        modal.querySelector('.modal-overlay').addEventListener('click', closeModal);
        
        const saveBtn = modal.querySelector('.modal-save');
        saveBtn.addEventListener('click', () => {
            this.saveEditForm(modal, itemType, itemId);
        });
        
        // Form validation
        const form = modal.querySelector('.edit-form');
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                this.validateEditForm(form);
            });
        });
        
        // Escape key to close
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    }

    // Save edit form via AJAX
    async saveEditForm(modal, itemType, itemId) {
        const form = modal.querySelector('.edit-form');
        const saveBtn = modal.querySelector('.modal-save');
        
        if (!this.validateEditForm(form)) {
            this.showNotification('Please fix form errors before saving', 'error');
            return;
        }
        
        try {
            // Show loading state
            const originalContent = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            saveBtn.disabled = true;
            
            // Prepare form data
            const formData = new FormData();
            formData.append('csrf_token', window.csrfToken);
            formData.append('action', `update_${itemType}`);
            formData.append('item_id', itemId);
            
            // console.log('=== PREPARING TO SAVE ===');
            // console.log('Item Type:', itemType);
            // console.log('Item ID:', itemId);
            
            // If personal_info, get content from Quill editors
            if (itemType === 'personal_info') {
                if (this.quillDescription) {
                    const descHTML = this.quillDescription.root.innerHTML;
                    formData.append('description', descHTML);
                    // console.log('Description HTML:', descHTML);
                }
                
                if (this.quillAboutMe) {
                    const aboutHTML = this.quillAboutMe.root.innerHTML;
                    formData.append('about_me', aboutHTML);
                    // console.log('About Me HTML:', aboutHTML);
                }
            }

            // Add other form fields
            const inputs = form.querySelectorAll('input:not([type="hidden"]), select, textarea');
            inputs.forEach(input => {
                // Skip hidden Quill fields
                if (input.id === 'description-hidden' || input.id === 'about-me-hidden') {
                    return;
                }
                
                // Skip description/about_me for personal_info (handled by Quill)
                if (itemType === 'personal_info' && (input.name === 'description' || input.name === 'about_me')) {
                    return;
                }
                
                if (input.type === 'checkbox') {
                    formData.append(input.name, input.checked ? '1' : '0');
                } else if (input.value) {
                    formData.append(input.name, input.value);
                }
            });
            
            // Debug: Show all FormData
            // console.log('=== FORM DATA BEING SENT ===');
            for (let pair of formData.entries()) {
                if (pair[0] === 'description' || pair[0] === 'about_me') {
                    // console.log(pair[0] + ':', pair[1]);
                } else {
                    // console.log(pair[0] + ':', pair[1]);
                }
            }
            
            // Send AJAX request
            // console.log('Sending request to:', window.location.href);
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            // console.log('Response status:', response.status);
            // console.log('Response headers:', [...response.headers.entries()]);
            
            // Get raw response first
            const rawText = await response.text();
            // console.log('Raw response:', rawText);
            
            // Try to parse JSON
            let result;
            try {
                result = JSON.parse(rawText);
            } catch (jsonError) {
                console.error('JSON Parse Error:', jsonError);
                console.error('Response was:', rawText);
                throw new Error('Server returned invalid JSON. Check browser console.');
            }
            
            if (result.success) {
                // console.log('Save successful!');
                this.showNotification(result.message || 'Updated successfully', 'success');
                this.closeModal(modal);
                
                // Clear Quill instances
                this.quillDescription = null;
                this.quillAboutMe = null;
                
                // Refresh the page
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                throw new Error(result.message || 'Update failed');
            }
            
        } catch (error) {
            console.error('Error saving form:', error);
            this.showNotification(`Failed to save: ${error.message}`, 'error');
            
            // Restore button state
            const saveBtn = modal.querySelector('.modal-save');
            if (saveBtn) {
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                saveBtn.disabled = false;
            }
        }
    }

    // Validate edit form
    validateEditForm(form) {
        let isValid = true;
        
        // Clear previous errors
        form.querySelectorAll('.field-error').forEach(error => error.remove());
        form.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(field => {
            field.classList.remove('error');
        });
        
        // Check required fields
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'This field is required');
                isValid = false;
            }
        });
        
        // Validate email fields
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showFieldError(field, 'Please enter a valid email address');
                isValid = false;
            }
        });
        
        // Validate URL fields
        const urlFields = form.querySelectorAll('input[type="url"]');
        urlFields.forEach(field => {
            if (field.value && !this.isValidURL(field.value)) {
                this.showFieldError(field, 'Please enter a valid URL');
                isValid = false;
            }
        });
        
        // Validate percentage fields
        const percentageFields = form.querySelectorAll('input[name="percentage"]');
        percentageFields.forEach(field => {
            const value = parseInt(field.value);
            if (field.value && (value < 0 || value > 100)) {
                this.showFieldError(field, 'Percentage must be between 0 and 100');
                isValid = false;
            }
        });
        
        return isValid;
    }

    // Utility function to escape HTML
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    closeExistingModal() {
        const existingModal = document.querySelector('.edit-modal');
        if (existingModal) {
            existingModal.remove();
        }
    }

    closeModal(modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        }, 300);
    }

    // Universal Delete Handler - Fixed and Enhanced
    confirmDelete(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // console.log('confirmDelete called with event:', e);
        
        const btn = e.target.closest('.btn-delete');
        if (!btn) {
            console.error('Delete button not found in event target');
            return;
        }
        
        const itemType = btn.getAttribute('data-type');
        const itemName = btn.getAttribute('data-name') || 'this item';
        const itemId = btn.getAttribute('data-id');
        
        console.log('Delete button details:', {
            itemType,
            itemName,
            itemId,
            button: btn,
            buttonClasses: btn.className,
            buttonParent: btn.parentElement
        });
        
        // Validate delete configuration
        if (!itemType) {
            console.error('Missing data-type attribute on delete button');
            this.showNotification('Cannot delete: Missing item type', 'error');
            return;
        }
        
        if (!this.deleteConfig[itemType]) {
            console.error('Invalid item type:', itemType);
            console.error('Available types:', Object.keys(this.deleteConfig));
            this.showNotification('Cannot delete: Invalid item type', 'error');
            return;
        }
        
        if (!itemId) {
            console.error('Missing data-id attribute on delete button');
            this.showNotification('Cannot delete: Missing item ID', 'error');
            return;
        }
        
        const config = this.deleteConfig[itemType];
        const confirmed = confirm(`Are you sure you want to delete this ${config.displayName}?\n\nName: ${itemName}\n\nThis action cannot be undone.`);
        
        if (confirmed) {
            // console.log('Delete confirmed, proceeding with deletion');
            this.performUniversalDelete(btn, itemName, itemType, itemId, config);
        } else {
            // console.log('Delete cancelled for:', itemName);
        }
    }

    // Universal Delete Performer - Enhanced with better error handling
    performUniversalDelete(btn, itemName, itemType, itemId, config) {
        try {
            console.log('Starting universal delete for:', {
                itemName,
                itemType,
                itemId,
                config
            });

            // Show loading state
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;
            
            // Create form data for deletion
            const formData = new FormData();
            formData.append('csrf_token', window.csrfToken || '');
            formData.append('action', config.actionName);
            formData.append('item_id', itemId);
            
            console.log('Sending delete request:', {
                action: config.actionName,
                item_id: itemId,
                csrf_token: window.csrfToken ? 'present' : 'missing',
                url: window.location.href
            });
            
            // Send delete request to server
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('Delete response received:', {
                    status: response.status,
                    statusText: response.statusText,
                    ok: response.ok
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text();
            })
            .then(data => {
                // console.log('Delete response processed successfully');
                // console.log('Response data:', data);
                
                // Try to parse as JSON for better error handling
                let responseData;
                try {
                    responseData = JSON.parse(data);
                    if (responseData.success === false) {
                        throw new Error(responseData.message || 'Server returned error');
                    }
                } catch (jsonError) {
                    // If it's not JSON, that's fine for delete operations
                    // console.log('Response is not JSON, treating as success');
                }
                
                // Find the item container using the config selector
                const itemContainer = btn.closest(config.containerSelector);
                
                if (itemContainer) {
                    // console.log('Item container found, animating removal...');
                    
                    // Animate removal
                    itemContainer.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                    itemContainer.style.opacity = '0';
                    itemContainer.style.transform = 'translateX(-100%) scale(0.9)';
                    
                    setTimeout(() => {
                        if (itemContainer.parentNode) {
                            itemContainer.parentNode.removeChild(itemContainer);
                        }
                        
                        this.showNotification(`${config.displayName} "${itemName}" deleted successfully!`, 'success');
                        
                        // Refresh after a delay to show updated counts
                        setTimeout(() => {
                            // console.log('Refreshing page to update data...');
                            window.location.reload();
                        }, 1500);
                    }, 400);
                } else {
                    // console.log('Item container not found, showing success and refreshing...');
                    this.showNotification(`${config.displayName} "${itemName}" deleted successfully!`, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                console.error('Delete request failed:', error);
                
                // Restore button state
                btn.innerHTML = originalContent;
                btn.disabled = false;
                
                this.showNotification(`Failed to delete ${config.displayName}: ${error.message}`, 'error');
            });
            
        } catch (error) {
            console.error('Error in universal delete:', error);
            this.showNotification('Error processing delete request', 'error');
        }
    }

    handleFormSubmit(e) {
        const form = e.target.closest('.admin-form');
        const action = form.querySelector('input[name="action"]')?.value || 'unknown';
        
        // console.log('Form submit handled for action:', action);
        
        // Validate form
        if (!this.validateForm(form)) {
            e.preventDefault();
            return false;
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            this.showSubmitLoading(submitBtn);
        }
        
        this.showNotification('Form submitted successfully!', 'success');
        return true;
    }

    validateForm(form) {
        let isValid = true;
        
        try {
            const requiredFields = form.querySelectorAll('[required]');
            
            // Clear previous errors
            form.querySelectorAll('.field-error').forEach(error => error.remove());
            form.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(field => {
                field.classList.remove('error');
            });
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    this.showFieldError(field, 'This field is required');
                    isValid = false;
                }
            });
            
            // Validate email fields
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(field => {
                if (field.value && !this.isValidEmail(field.value)) {
                    this.showFieldError(field, 'Please enter a valid email address');
                    isValid = false;
                }
            });
            
            // Validate URL fields
            const urlFields = form.querySelectorAll('input[type="url"]');
            urlFields.forEach(field => {
                if (field.value && !this.isValidURL(field.value)) {
                    this.showFieldError(field, 'Please enter a valid URL');
                    isValid = false;
                }
            });
            
            // console.log('Form validation result:', isValid ? 'PASSED' : 'FAILED');
            return isValid;
        } catch (error) {
            console.error('Error validating form:', error);
            return false;
        }
    }

    showFieldError(field, message) {
        field.classList.add('error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    }

    showSubmitLoading(submitBtn) {
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
        
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 2000);
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    isValidURL(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    toggleMobileMenu() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        
        if (sidebar && overlay) {
            const isOpen = sidebar.classList.contains('mobile-open');
            
            if (isOpen) {
                this.closeMobileMenu();
            } else {
                this.openMobileMenu();
            }
        }
    }

    openMobileMenu() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        
        if (sidebar && overlay) {
            sidebar.classList.add('mobile-open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            this.sidebarOpen = true;
            // console.log('Mobile menu opened');
        }
    }

    closeMobileMenu() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        
        if (sidebar && overlay) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            this.sidebarOpen = false;
            // console.log('Mobile menu closed');
        }
    }

    handleResize() {
        const newIsMobile = window.innerWidth <= 768;
        
        if (newIsMobile !== this.isMobile) {
            this.isMobile = newIsMobile;
            
            if (!this.isMobile) {
                this.closeMobileMenu();
            }
        }
    }

    handleResponsive() {
        this.handleResize();
    }

    updateCurrentTime() {
        const now = new Date();
        const timeString = now.toISOString().slice(0, 19).replace('T', ' ');
        
        const userTimeElement = document.querySelector('.user-time');
        if (userTimeElement) {
            userTimeElement.textContent = `${timeString} UTC`;
        }

        document.querySelectorAll('.timestamp').forEach(element => {
            if (element.textContent.includes('Last updated:')) {
                element.innerHTML = `<i class="fas fa-clock"></i> Last updated: ${timeString} UTC`;
            }
        });
    }

    updatePageTitle(sectionName) {
        const titles = {
            'overview': 'Dashboard Overview',
            'personal': 'Personal Information',
            'skills': 'Skills Management',
            'research': 'Research Papers',
            'experience': 'Experience & Education',
            'projects': 'Projects Portfolio',
            'social': 'Social Media Links',
            'email': 'Email Configuration',
            'add-forms': 'Add New Content'
        };
        
        const topBarTitle = document.querySelector('.top-bar-left h2');
        if (topBarTitle) {
            topBarTitle.textContent = titles[sectionName] || 'Dashboard';
        }
        
        document.title = `${titles[sectionName] || 'Dashboard'} - Portfolio Admin`;
    }

    scrollToTop() {
        const contentArea = document.querySelector('.content-area');
        if (contentArea) {
            contentArea.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    handleKeyboardShortcuts(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }
        
        if (e.key === 'Escape' && this.isMobile && this.sidebarOpen) {
            this.closeMobileMenu();
        }
        
        if (e.key >= '1' && e.key <= '9') {
            const sections = ['overview', 'personal', 'skills', 'research', 'experience', 'projects', 'social', 'email', 'add-forms'];
            const sectionIndex = parseInt(e.key) - 1;
            
            if (sections[sectionIndex]) {
                this.switchSection(sections[sectionIndex]);
            }
        }
        
        if (e.altKey && e.key === 'h') {
            this.showKeyboardShortcuts();
        }
    }

    showKeyboardShortcuts() {
        const shortcuts = `
Keyboard Shortcuts:
• 1-9: Switch to different sections
• Alt+H: Show this help
• Esc: Close mobile menu/modals
• Tab: Navigate between elements
        `;
        this.showNotification(shortcuts, 'info');
    }

    autoHideAlerts() {
        const alerts = document.querySelectorAll('.alert');
        
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 300);
            }, 5000);
        });
    }

    initializeAnimations() {
        const cards = document.querySelectorAll('.stat-card, .overview-card, .skill-category, .research-card, .project-card, .form-card');
        
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 50);
        });
    }

    loadUserPreferences() {
        const savedSection = this.getUserPreference('currentSection');
        if (savedSection && document.getElementById(savedSection)) {
            // console.log('Loading saved section:', savedSection);
            this.switchSection(savedSection);
        } else {
            // console.log('Loading default section: overview');
            this.switchSection('overview');
        }
    }

    saveUserPreference(key, value) {
        try {
            localStorage.setItem(`admin_${key}`, value);
        } catch (e) {
            console.warn('Could not save user preference:', e);
        }
    }

    getUserPreference(key) {
        try {
            return localStorage.getItem(`admin_${key}`);
        } catch (e) {
            console.warn('Could not load user preference:', e);
            return null;
        }
    }

    showNotification(message, type = 'info') {
        // Remove existing notifications
        document.querySelectorAll('.notification').forEach(n => n.remove());

        // Create notification container
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;

        // Create notification content container
        const content = document.createElement('div');
        content.className = 'notification-content';

        // Create icon
        const icon = document.createElement('i');
        icon.className = `fas fa-${this.getNotificationIcon(type)}`;

        // Create message span - use textContent to prevent XSS
        const messageSpan = document.createElement('span');
        messageSpan.textContent = message; // SAFE: textContent escapes HTML

        // Create close button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'notification-close';
        closeBtn.type = 'button';
        closeBtn.setAttribute('aria-label', 'Close notification');

        const closeIcon = document.createElement('i');
        closeIcon.className = 'fas fa-times';
        closeBtn.appendChild(closeIcon);

        // Assemble notification
        content.appendChild(icon);
        content.appendChild(messageSpan);
        content.appendChild(closeBtn);
        notification.appendChild(content);

        // Add to body - now safe because all content is created with DOM methods
        document.body.appendChild(notification);

        requestAnimationFrame(() => {
            notification.classList.add('show');
        });

        setTimeout(() => this.hideNotification(notification), 4000);
        closeBtn.addEventListener('click', () => {
            this.hideNotification(notification);
        });

        // console.log(`Notification shown: ${type} - ${message}`);
        }

    hideNotification(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }

    getNotificationIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-triangle',
            'warning': 'exclamation-circle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    addNotificationStyles() {
        if (document.querySelector('#notification-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed !important;
                top: 20px !important;
                right: 20px !important;
                z-index: 10000 !important;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                max-width: 400px;
            }
            
            .notification.show {
                transform: translateX(0) !important;
            }
            
            .notification-content {
                padding: 15px 20px;
                display: flex;
                align-items: center;
                gap: 10px;
                color: white;
                font-size: 14px;
                font-weight: 500;
            }
            
            .notification-success .notification-content {
                background: linear-gradient(135deg, #10b981, #059669);
            }
            
            .notification-error .notification-content {
                background: linear-gradient(135deg, #ef4444, #dc2626);
            }
            
            .notification-warning .notification-content {
                background: linear-gradient(135deg, #f59e0b, #d97706);
            }
            
            .notification-info .notification-content {
                background: linear-gradient(135deg, #3b82f6, #2563eb);
            }
            
            .notification-close {
                background: none;
                border: none;
                color: white;
                cursor: pointer;
                padding: 2px;
                border-radius: 2px;
                margin-left: auto;
                opacity: 0.8;
                transition: opacity 0.3s ease;
            }
            
            .notification-close:hover {
                opacity: 1;
            }
        `;
        
        document.head.appendChild(style);
    }

    addModalStyles() {
        if (document.querySelector('#modal-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'modal-styles';
        style.textContent = `
            .edit-modal {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                z-index: 9999 !important;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            .edit-modal.active {
                opacity: 1 !important;
                visibility: visible !important;
            }
            
            .modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(4px);
            }
            
            .modal-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
                max-width: 600px;
                width: 95%;
                max-height: 80vh;
                overflow: hidden;
            }
            
            .modal-header {
                padding: 20px 25px;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #f9fafb;
            }
            
            .modal-header h3 {
                margin: 0;
                color: #1f2937;
                font-size: 18px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .modal-close {
                background: none;
                border: none;
                color: #6b7280;
                cursor: pointer;
                padding: 5px;
                border-radius: 4px;
                transition: all 0.3s ease;
            }
            
            .modal-close:hover {
                background: #e5e7eb;
                color: #374151;
            }
            
            .modal-body {
                padding: 25px;
                color: #374151;
                line-height: 1.6;
                max-height: 60vh;
                overflow-y: auto;
            }
            
            .modal-footer {
                padding: 20px 25px;
                border-top: 1px solid #e5e7eb;
                display: flex;
                gap: 10px;
                justify-content: flex-end;
                background: #f9fafb;
            }

            .edit-form .form-row {
                display: flex;
                gap: 15px;
                margin-bottom: 15px;
            }
            
            .edit-form .form-group {
                flex: 1;
                margin-bottom: 15px;
            }
            
            .edit-form .form-label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
                color: #374151;
            }
            
            .edit-form .form-input,
            .edit-form .form-select,
            .edit-form .form-textarea {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                font-size: 14px;
                transition: border-color 0.3s ease;
            }
            
            .edit-form .form-input:focus,
            .edit-form .form-select:focus,
            .edit-form .form-textarea:focus {
                outline: none;
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
            
            .edit-form .form-input.error,
            .edit-form .form-select.error,
            .edit-form .form-textarea.error {
                border-color: #ef4444;
            }
            
            .edit-form .checkbox-group {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .edit-form .checkbox-group input[type="checkbox"] {
                width: auto;
            }
            
            .field-error {
                color: #ef4444;
                font-size: 12px;
                margin-top: 4px;
            }
            
            .modal-save:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            
            @media (max-width: 768px) {
                .edit-form .form-row {
                    flex-direction: column;
                    gap: 0;
                }
                
                .edit-modal .modal-content {
                    width: 95%;
                    max-height: 90vh;
                }
            }
        `;
        
        document.head.appendChild(style);
    }
}

// Enhanced Test Functions
window.testDelete = function(itemType = 'skill', itemId = '1', itemName = 'Test Item') {
    // console.log('Testing delete functionality...');
    if (window.adminDashboard) {
        const mockButton = document.createElement('button');
        mockButton.setAttribute('data-type', itemType);
        mockButton.setAttribute('data-id', itemId);
        mockButton.setAttribute('data-name', itemName);
        mockButton.className = 'btn-delete';
        
        const mockEvent = {
            preventDefault: () => console.log('preventDefault called'),
            stopPropagation: () => console.log('stopPropagation called'),
            target: mockButton
        };
        
        window.adminDashboard.confirmDelete(mockEvent);
    } else {
        console.error('Admin dashboard not initialized');
    }
};

// Enhanced Debug function
window.debugDelete = function() {
    // console.log('=== Enhanced Delete System Debug ===');
    // console.log('Admin dashboard:', window.adminDashboard ? 'INITIALIZED' : 'NOT INITIALIZED');
    // console.log('Delete config:', window.adminDashboard?.deleteConfig);
    // console.log('CSRF token:', window.csrfToken ? 'PRESENT' : 'MISSING');
    // console.log('Current URL:', window.location.href);
    
    const deleteButtons = document.querySelectorAll('.btn-delete');
    // console.log(`Found ${deleteButtons.length} delete buttons:`);
    
    deleteButtons.forEach((btn, index) => {
        const rect = btn.getBoundingClientRect();
        console.log(`Button ${index + 1}:`, {
            type: btn.getAttribute('data-type'),
            id: btn.getAttribute('data-id'),
            name: btn.getAttribute('data-name'),
            visible: btn.offsetParent !== null,
            inViewport: rect.width > 0 && rect.height > 0,
            classes: btn.className,
            element: btn
        });
    });
    
    // Test click detection
    // console.log('Testing click detection...');
    setTimeout(() => {
        const firstDeleteBtn = document.querySelector('.btn-delete');
        if (firstDeleteBtn) {
            // console.log('Simulating click on first delete button...');
            firstDeleteBtn.click();
        } else {
            // console.log('No delete button found to test');
        }
    }, 1000);
};

// Enhanced Edit Debug function
window.debugEdit = function() {
    // console.log('=== Edit System Debug ===');
    // console.log('Admin dashboard:', window.adminDashboard ? 'INITIALIZED' : 'NOT INITIALIZED');
    // console.log('Skill categories available:', window.adminDashboard?.skillCategories?.length || 0);
    // console.log('CSRF token:', window.csrfToken ? 'PRESENT' : 'MISSING');
    
    const editButtons = document.querySelectorAll('.btn-edit');
    // console.log(`Found ${editButtons.length} edit buttons:`);
    
    editButtons.forEach((btn, index) => {
        const rect = btn.getBoundingClientRect();
        console.log(`Edit Button ${index + 1}:`, {
            type: btn.getAttribute('data-type'),
            id: btn.getAttribute('data-id'),
            name: btn.getAttribute('data-name'),
            visible: btn.offsetParent !== null,
            inViewport: rect.width > 0 && rect.height > 0,
            classes: btn.className,
            element: btn
        });
    });
    
    // Test edit modal creation
    // console.log('Testing edit functionality...');
    setTimeout(() => {
        const firstEditBtn = document.querySelector('.btn-edit');
        if (firstEditBtn) {
            // console.log('Simulating click on first edit button...');
            firstEditBtn.click();
        } else {
            // console.log('No edit button found to test');
        }
    }, 1000);
};

// Test edit functionality with mock data
window.testEdit = function(itemType = 'skill', itemId = '1', itemName = 'Test Item') {
    // console.log('Testing edit functionality...');
    if (window.adminDashboard) {
        const mockData = {
            skill: {
                id: itemId,
                name: 'Python',
                category_id: 1,
                percentage: 85,
                display_order: 0
            },
            skill_category: {
                id: itemId,
                name: 'Programming Languages',
                icon: 'fas fa-code',
                experience_years: 5,
                display_order: 0
            },
            research_paper: {
                id: itemId,
                title: 'Test Research Paper',
                journal: 'Test Journal',
                publication_date: '2024-01-01',
                authors: 'Test Author',
                abstract: 'This is a test abstract',
                pdf_file: '',
                doi_link: '',
                code_link: '',
                display_order: 0
            },
            experience: {
                id: itemId,
                type: 'work',
                title: 'Test Position',
                organization: 'Test Company',
                location: 'Test Location',
                start_date: '2024-01-01',
                end_date: '2024-12-31',
                is_current: 0,
                description: 'Test description',
                display_order: 0
            },
            project: {
                id: itemId,
                title: 'Test Project',
                description: 'Test project description',
                image: '',
                project_link: '',
                github_link: '',
                technologies: 'Test, Technologies',
                display_order: 0,
                is_featured: 1
            },
            social_link: {
                id: itemId,
                platform: 'Test Platform',
                url: 'https://test.com',
                icon_class: 'fab fa-test',
                is_active: 1,
                display_order: 0
            }
        };
        
        window.adminDashboard.createEditModal(itemType, mockData[itemType], itemName);
    } else {
        console.error('Admin dashboard not initialized');
    }
};

// Global error handler
window.addEventListener('error', function(e) {
    console.error('Global error caught:', e.error);
    if (window.adminDashboard) {
        window.adminDashboard.showNotification('An unexpected error occurred. Please check the console.', 'error');
    }
});

// Global unhandled promise rejection handler
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    if (window.adminDashboard) {
        window.adminDashboard.showNotification('A network or processing error occurred.', 'error');
    }
});

// Utility functions for debugging
window.adminUtils = {
    // Get all active modals
    getActiveModals: function() {
        return document.querySelectorAll('.edit-modal.active');
    },
    
    // Force close all modals
    closeAllModals: function() {
        document.querySelectorAll('.edit-modal').forEach(modal => {
            modal.remove();
        });
        // console.log('All modals closed');
    },
    
    // Get current section
    getCurrentSection: function() {
        const activeSection = document.querySelector('.content-section.active');
        return activeSection ? activeSection.id : null;
    },
    
    // Switch to section
    switchToSection: function(sectionName) {
        if (window.adminDashboard) {
            window.adminDashboard.switchSection(sectionName);
        }
    },
    
    // Show test notification
    showTestNotification: function(message = 'Test notification', type = 'info') {
        if (window.adminDashboard) {
            window.adminDashboard.showNotification(message, type);
        }
    },
    
    // Get dashboard state
    getDashboardState: function() {
        if (!window.adminDashboard) return null;
        
        return {
            currentSection: window.adminDashboard.currentSection,
            isMobile: window.adminDashboard.isMobile,
            sidebarOpen: window.adminDashboard.sidebarOpen,
            currentUser: window.adminDashboard.currentUser,
            skillCategories: window.adminDashboard.skillCategories?.length || 0
        };
    },
    
    // Count elements
    countElements: function() {
        return {
            deleteButtons: document.querySelectorAll('.btn-delete').length,
            editButtons: document.querySelectorAll('.btn-edit').length,
            forms: document.querySelectorAll('.admin-form').length,
            sections: document.querySelectorAll('.content-section').length,
            navItems: document.querySelectorAll('.nav-item').length
        };
    },
    
    // Validate page integrity
    validatePage: function() {
        const issues = [];
        
        if (!window.csrfToken) {
            issues.push('Missing CSRF token');
        }
        
        document.querySelectorAll('.btn-delete').forEach((btn, index) => {
            if (!btn.getAttribute('data-type')) {
                issues.push(`Delete button ${index + 1} missing data-type attribute`);
            }
            if (!btn.getAttribute('data-id')) {
                issues.push(`Delete button ${index + 1} missing data-id attribute`);
            }
        });
        
        document.querySelectorAll('.btn-edit').forEach((btn, index) => {
            if (!btn.getAttribute('data-id')) {
                issues.push(`Edit button ${index + 1} missing data-id attribute`);
            }
        });
        
        // Check for required elements
        const requiredElements = [
            '#sidebar',
            '.main-content',
            '.top-bar',
            '.content-area'
        ];
        
        requiredElements.forEach(selector => {
            if (!document.querySelector(selector)) {
                issues.push(`Missing required element: ${selector}`);
            }
        });
        
        if (issues.length === 0) {
            // console.log('Page validation passed');
        } else {
            // console.log('Page validation issues:', issues);
        }
        
        return issues;
    }
};

// Initialize performance monitoring
if (typeof performance !== 'undefined' && performance.mark) {
    performance.mark('admin-dashboard-start');
    
    window.addEventListener('load', function() {
        performance.mark('admin-dashboard-loaded');
        performance.measure('admin-dashboard-load-time', 'admin-dashboard-start', 'admin-dashboard-loaded');
        
        const measure = performance.getEntriesByName('admin-dashboard-load-time')[0];
        // console.log(`Admin Dashboard load time: ${measure.duration.toFixed(2)}ms`);
    });
}

// Add keyboard shortcut help
document.addEventListener('keydown', function(e) {
    // Ctrl+Shift+H to show help
    if (e.ctrlKey && e.shiftKey && e.key === 'H') {
        e.preventDefault();
        if (window.adminDashboard) {
            const helpText = `
Admin Dashboard Help:

Navigation:
• 1-9: Switch between sections
• Alt+H: Show keyboard shortcuts
• Esc: Close modals/mobile menu

Debug Commands (F12 Console):
• debugDelete() - Test delete system
• debugEdit() - Test edit system
• testEdit('skill', '1', 'Test') - Test edit modal
• testDelete('skill', '1', 'Test') - Test delete confirmation
• adminUtils.getDashboardState() - Get current state
• adminUtils.validatePage() - Check for issues
• adminUtils.countElements() - Count page elements

Features:
• Real-time form validation
• AJAX editing and deletion
• Mobile responsive design
• Auto-save preferences
• Notification system

Support:
Contact your system administrator for technical support.
            `;
            window.adminDashboard.showNotification(helpText, 'info');
        }
    }
});

// Add development mode detection
const isDevelopment = window.location.hostname === 'localhost' || 
                     window.location.hostname === '127.0.0.1' || 
                     window.location.hostname.includes('dev');

if (isDevelopment) {
    // console.log('Admin Dashboard running in development mode');
    // console.log('Available debug commands:');
    // console.log('• debugDelete() - Test delete functionality');
    // console.log('• debugEdit() - Test edit functionality');
    // console.log('• testEdit(type, id, name) - Test edit modal');
    // console.log('• testDelete(type, id, name) - Test delete confirmation');
    // console.log('• adminUtils - Collection of utility functions');
    
    setTimeout(() => {
        const devIndicator = document.createElement('div');
        devIndicator.innerHTML = 'DEV';
        devIndicator.style.cssText = `
            position: fixed;
            top: 10px;
            left: 10px;
            background: #ff6b35;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            z-index: 10001;
            opacity: 0.8;
        `;
        document.body.appendChild(devIndicator);
    }, 1000);
}

setTimeout(() => {
    if (window.adminDashboard) {
        // console.log('Admin Dashboard fully loaded and operational');
        // console.log('Dashboard state:', window.adminUtils?.getDashboardState());
        // console.log('Element counts:', window.adminUtils?.countElements());
        
        window.adminUtils?.validatePage();
    } else {
        console.error('Admin Dashboard failed to initialize properly');
    }
}, 2000);

// console.log('Enhanced Admin Dashboard JavaScript with Complete Edit and Delete System loaded successfully');
// console.log('Current Date/Time:', '2025-06-28 20:23:16 UTC');
// console.log('Current User:', 'Kritarth123-prince');