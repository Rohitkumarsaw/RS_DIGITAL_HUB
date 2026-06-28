-- =====================================================
-- RS Digital Hub - Complete Database Schema & Data
-- =====================================================
-- Hosting pe phpMyAdmin me directly import karo
-- NOTE: CREATE DATABASE line hata di hai kyunki hosting pe DB already bana hota hai
-- =====================================================

-- If running locally, uncomment the next line:
-- CREATE DATABASE IF NOT EXISTS ybt_digital CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use your hosting database name (it will be auto-selected by phpMyAdmin)

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin', 'super_admin', 'developer') DEFAULT 'user',
    status ENUM('active', 'blocked') DEFAULT 'active',
    avatar VARCHAR(255) DEFAULT NULL,
    store_name VARCHAR(200) DEFAULT NULL,
    store_slug VARCHAR(200) DEFAULT NULL,
    store_description TEXT DEFAULT NULL,
    payout_method VARCHAR(50) DEFAULT NULL,
    account_holder VARCHAR(200) DEFAULT NULL,
    bank_name VARCHAR(200) DEFAULT NULL,
    account_number VARCHAR(100) DEFAULT NULL,
    ifsc_code VARCHAR(50) DEFAULT NULL,
    upi_id VARCHAR(200) DEFAULT NULL,
    payout_paypal_email VARCHAR(200) DEFAULT NULL,
    payout_razorpay_id VARCHAR(200) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- CATEGORIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- PRODUCTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    developer_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10, 2) NOT NULL,
    sale_price DECIMAL(10, 2) DEFAULT NULL,
    is_free TINYINT(1) DEFAULT 0,
    category_id INT,
    product_type VARCHAR(50) DEFAULT 'general',
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255),
    file_size VARCHAR(50),
    file_url VARCHAR(500) DEFAULT NULL,
    screenshots JSON,
    image_url VARCHAR(500) DEFAULT NULL,
    demo_url VARCHAR(255),
    status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
    download_limit INT DEFAULT 5,
    download_expiry_hours INT DEFAULT 48,
    views INT DEFAULT 0,
    sales_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (developer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product_type (product_type),
    INDEX idx_developer_id (developer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- CART TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- ORDERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    discount_amount DECIMAL(10, 2) DEFAULT 0,
    tax_amount DECIMAL(10, 2) DEFAULT 0,
    final_amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    coupon_code VARCHAR(50),
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- ORDER ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DOWNLOADS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id INT NOT NULL,
    download_token VARCHAR(100) UNIQUE NOT NULL,
    expires_at TIMESTAMP NULL,
    download_count INT DEFAULT 0,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- COUPONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('percentage', 'flat') NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    min_order_amount DECIMAL(10, 2) DEFAULT 0,
    max_discount DECIMAL(10, 2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    status ENUM('active', 'expired', 'disabled') DEFAULT 'active',
    expires_at TIMESTAMP NULL,
    developer_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_coupon_developer (developer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TESTIMONIALS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    message TEXT NOT NULL,
    rating INT DEFAULT 5,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'pending', 'hidden') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- FAQ TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    status ENUM('active', 'hidden') DEFAULT 'active',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SUPPORT TICKETS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TICKET REPLIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS ticket_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SETTINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- PASSWORD RESETS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(100) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- POLICIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_type VARCHAR(50) DEFAULT 'general',
    slug VARCHAR(100) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('active', 'draft') DEFAULT 'active',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_type (product_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SOCIAL LINKS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS social_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_type VARCHAR(50) DEFAULT 'general',
    platform VARCHAR(50) NOT NULL,
    icon_svg TEXT DEFAULT NULL,
    url VARCHAR(500) NOT NULL DEFAULT '',
    label VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_platform_section (platform, product_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- ABOUT US TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS about_us (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_type VARCHAR(50) DEFAULT 'general',
    title VARCHAR(200) NOT NULL DEFAULT 'About Us',
    subtitle VARCHAR(300) DEFAULT NULL,
    content TEXT NOT NULL,
    mission TEXT DEFAULT NULL,
    vision TEXT DEFAULT NULL,
    stats_json JSON DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_type (product_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- NOTICES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_type VARCHAR(50) DEFAULT 'general',
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    priority ENUM('info', 'warning', 'urgent', 'success') DEFAULT 'info',
    status ENUM('active', 'draft', 'expired') DEFAULT 'active',
    display_from DATETIME DEFAULT NULL,
    display_to DATETIME DEFAULT NULL,
    pinned TINYINT(1) DEFAULT 0,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_product_type (product_type),
    INDEX idx_display_dates (display_from, display_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- CONTACT MESSAGES TABLE (form submissions, not user tickets)
-- =====================================================
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DEFAULT DATA
-- =====================================================

-- Admin User (change this password after first login!)
INSERT INTO users (name, email, password, role) VALUES 
('Super Admin', 'dellofficial795@gmail.com', '$2y$10$3WnfXR0oDNYjvCHd/cy7jepc6w5rkY0qJ9tRaSvXMhYTHmeA.B/g6', 'super_admin');

-- All Categories
INSERT INTO categories (name, slug, description) VALUES
('Full-Stack Web Applications', 'full-stack-web-applications', 'Complete web application source code'),
('Frontend Templates', 'frontend-templates', 'React, Vue, Next.js, HTML/CSS templates'),
('Backend Scripts & REST APIs', 'backend-scripts-rest-apis', 'Node.js, Python, PHP backend code'),
('Mobile App Source Code', 'mobile-app-source-code', 'Flutter, React Native, Java/Swift apps'),
('Database Schemas & Firebase', 'database-schemas-firebase', 'Database designs and Firebase configs'),
('Automation & Web Scraping', 'automation-web-scraping', 'Python, Node.js automation scripts'),
('Browser Extensions Code', 'browser-extensions', 'Chrome, Firefox extension source code'),
('WordPress Themes & Plugins', 'wordpress-themes-plugins', 'WP themes and plugin source code'),
('UI Component Libraries', 'ui-component-libraries', 'Tailwind CSS, Bootstrap component blocks'),
('Game Development Scripts', 'game-development-scripts', 'Unity, Unreal, Godot game assets'),
('Micro-SaaS Tools & Web Apps', 'micro-saas-tools', 'Ready-to-deploy SaaS applications'),
('Desktop Applications', 'desktop-applications', 'Windows, macOS desktop software'),
('IT Utilities & Recovery Tools', 'it-utilities-recovery', 'System recovery and IT tools'),
('Command Line Interface Tools', 'cli-tools', 'Terminal and CLI utilities'),
('Cybersecurity & Pentesting', 'cybersecurity-pentesting', 'Security testing and pentest scripts'),
('UI/UX Kits & Figma Files', 'ui-ux-kits-figma', 'Design system kits and Figma files'),
('Vector Graphics & Logos', 'vector-graphics-logos', 'SVG logos and vector designs'),
('Social Media Kits & Canva', 'social-media-kits', 'Instagram, Facebook, Canva templates'),
('Custom Fonts & Typography', 'custom-fonts-typography', 'Font families and typography sets'),
('3D Models, Textures & Shaders', '3d-models-textures', '3D assets for games and renders'),
('Presentation Slides', 'presentation-slides', 'PowerPoint, Google Slides templates'),
('Branding & Mockups', 'branding-mockups', 'Brand identity and mockup templates'),
('E-books & Tech Guides', 'ebooks-tech-guides', 'Digital books and technical documentation'),
('Online Video Courses', 'online-video-courses', 'Video courses and bootcamp content'),
('Interview Prep & DSA', 'interview-prep-dsa', 'Coding interview preparation materials'),
('Cheatsheets & Reference', 'cheatsheets-reference', 'Quick reference guides and cheatsheets'),
('Research Papers & Studies', 'research-papers', 'Technical research and case studies'),
('Notion Dashboards', 'notion-dashboards', 'Notion workspace templates'),
('Excel & Sheets Trackers', 'excel-sheets-trackers', 'Spreadsheet templates and trackers'),
('Project Management', 'project-management', 'PM templates and frameworks'),
('Resume & Cover Letters', 'resume-cover-letters', 'Professional resume templates'),
('Digital Planners & Journals', 'digital-planners', 'Digital planning and journal layouts'),
('Mobile & Desktop Wallpapers', 'wallpapers', 'Dark, Neon, Cyberpunk wallpapers'),
('Custom App Icon Packs', 'app-icon-packs', 'iOS and Android icon sets'),
('Print-on-Demand Arts', 'print-on-demand-arts', 'Poster and merchandise designs'),
('Stream Overlays & Twitch', 'stream-overlays', 'Twitch and streaming overlays'),
('Concept Art & Paintings', 'concept-art', 'Digital artwork and paintings'),
('Royalty-Free Background Music', 'background-music', 'BGM for videos and projects'),
('Sound Effects (SFX)', 'sound-effects', 'SFX for videos, games, and apps'),
('Instrumental Beats & Tracks', 'instrumental-beats', 'Beats and backing tracks'),
('Podcast Intros & Jingles', 'podcast-intros', 'Podcast audio assets'),
('Vocal Samples & Synth Presets', 'vocal-samples-synth', 'Serum, Massive presets and samples'),
('High-Resolution Stock Photos', 'stock-photos', 'Professional stock photography'),
('4K/HD Video B-Roll', 'video-broll', 'Stock video footage'),
('Video Transitions & LUTS', 'video-transitions-luts', 'Premiere, After Effects transitions'),
('Motion Graphics Templates', 'motion-graphics', 'After Effects motion templates'),
('Animated Backgrounds', 'animated-backgrounds', 'Looping animated backgrounds'),
('Private Communities & Discord', 'private-communities', 'Discord server access'),
('Subscription Asset Libraries', 'subscription-libraries', 'Recurring asset access'),
('Mastermind Groups & Forums', 'mastermind-groups', 'Premium forum access'),
('Exclusive Newsletters', 'exclusive-newsletters', 'Premium newsletter subscriptions');

-- Default Settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('site_name', 'RS Digital Hub'),
('site_logo', ''),
('site_footer_text', '© 2026 RS Digital Hub. All rights reserved.'),
('currency', 'INR'),
('currency_symbol', '₹'),
('tax_percentage', '0'),
('tax_type', 'GST'),
('payment_gateway', 'stripe'),
('stripe_publishable_key', ''),
('stripe_secret_key', ''),
('razorpay_key_id', ''),
('razorpay_key_secret', ''),
('paypal_client_id', ''),
('paypal_client_secret', ''),
('email_from', 'noreply@rsdigitalhub.com'),
('email_from_name', 'RS Digital Hub'),
('download_expiry_hours', '48'),
('download_limit', '5'),
('contact_email', 'support@rsdigitalhub.com'),
('contact_phone', '+91 9876543210'),
('contact_address', 'New Delhi, India'),
('contact_hours', 'Monday - Saturday: 10AM - 7PM IST'),
('contact_map_embed', '');

-- Social Media Links
INSERT INTO social_links (platform, url, label, sort_order) VALUES
('facebook', 'https://facebook.com/', 'Facebook', 1),
('instagram', 'https://instagram.com/', 'Instagram', 2),
('twitter', 'https://twitter.com/', 'Twitter / X', 3),
('youtube', 'https://youtube.com/', 'YouTube', 4),
('linkedin', 'https://linkedin.com/', 'LinkedIn', 5),
('telegram', 'https://t.me/', 'Telegram', 6),
('whatsapp', 'https://wa.me/91', 'WhatsApp', 7),
('github', 'https://github.com/', 'GitHub', 8),
('pinterest', 'https://pinterest.com/', 'Pinterest', 9),
('threads', 'https://threads.net/', 'Threads', 10);

-- About Us
INSERT INTO about_us (title, subtitle, content, mission, vision, stats_json) VALUES
('About RS Digital Hub', 'Your Trusted Digital Marketplace Since 2026',
'<p>RS Digital Hub is a premium digital products marketplace dedicated to providing high-quality templates, scripts, eBooks, design assets, and more to creators, developers, and businesses worldwide.</p>
<p>Founded with a vision to make premium digital resources accessible to everyone, we carefully curate and verify every product on our platform to ensure the highest standards of quality and usability.</p>
<p>Our team of experts works closely with creators to bring you products that are not only visually stunning but also technically robust and easy to use.</p>',
'Our mission is to empower creators, entrepreneurs, and businesses with premium digital tools and resources that help them build, grow, and succeed in the digital world.',
'To become the most trusted and comprehensive digital marketplace in India, known for quality, affordability, and exceptional customer experience.',
'{"products":"500+","customers":"10,000+","downloads":"50,000+","rating":"4.9/5"}');

-- Sample Notices
INSERT INTO notices (title, content, priority, status, pinned) VALUES
('Welcome to RS Digital Hub!', 'We are excited to launch our new digital marketplace. Explore our collection of premium products at affordable prices. Use code <strong>LAUNCH20</strong> for 20% off on your first purchase!', 'success', 'active', 1),
('New Products Added Weekly', 'We add new products every week. Check back regularly or subscribe to our newsletter to stay updated on the latest additions.', 'info', 'active', 0),
('Scheduled Maintenance on May 25', 'Our platform will undergo scheduled maintenance on May 25, 2026 from 2:00 AM to 4:00 AM IST. Services may be temporarily unavailable during this period.', 'warning', 'active', 1),
('Report Any Issues Immediately', 'If you face any issues with downloads or payments, please raise a support ticket immediately. Our team responds within 24 hours.', 'urgent', 'active', 0);

-- Default Policies
INSERT INTO policies (slug, title, content, status) VALUES
('privacy-policy', 'Privacy Policy', '<h2>Privacy Policy</h2><p>Last updated: May 21, 2026</p><h3>1. Information We Collect</h3><p>We collect information you provide directly to us, including name, email address, and payment information when you create an account or make a purchase.</p><h3>2. How We Use Your Information</h3><p>We use the information we collect to process transactions, send order confirmations, provide customer support, and improve our services.</p><h3>3. Information Sharing</h3><p>We do not sell, trade, or rent your personal information to third parties. We may share information with trusted service providers who assist us in operating our website.</p><h3>4. Data Security</h3><p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, or disclosure.</p><h3>5. Cookies</h3><p>We use cookies to enhance your browsing experience and analyze site traffic. You can control cookie settings through your browser preferences.</p><h3>6. Contact Us</h3><p>If you have questions about this Privacy Policy, please contact us at privacy@rsdigitalhub.com</p>', 'active'),
('terms-and-conditions', 'Terms and Conditions', '<h2>Terms and Conditions</h2><p>Last updated: May 21, 2026</p><h3>1. Acceptance of Terms</h3><p>By accessing and using RS Digital Hub, you accept and agree to be bound by these Terms and Conditions.</p><h3>2. Digital Products</h3><p>All products sold on our platform are digital downloads. Upon successful payment, you will receive instant access to download your purchased products.</p><h3>3. License Agreement</h3><p>Purchased products are licensed for personal use only. You may not redistribute, resell, or share purchased products with others without explicit permission.</p><h3>4. User Accounts</h3><p>You are responsible for maintaining the confidentiality of your account credentials. You agree to accept responsibility for all activities that occur under your account.</p><h3>5. Prohibited Activities</h3><p>You may not use our platform for any illegal purposes, upload malicious content, or attempt to gain unauthorized access to our systems.</p><h3>6. Limitation of Liability</h3><p>RS Digital Hub shall not be liable for any indirect, incidental, or consequential damages arising from the use of our products or services.</p><h3>7. Changes to Terms</h3><p>We reserve the right to modify these terms at any time. Continued use of our platform constitutes acceptance of updated terms.</p>', 'active'),
('refund-cancellation-policy', 'Refund and Cancellation Policy', '<h2>Refund and Cancellation Policy</h2><p>Last updated: May 21, 2026</p><h3>1. Refund Eligibility</h3><p>Due to the digital nature of our products, refunds are generally not provided once a download has been initiated. However, we evaluate refund requests on a case-by-case basis.</p><h3>2. Refund Requests</h3><p>To request a refund, please submit a support ticket within 7 days of purchase with a detailed explanation of your issue.</p><h3>3. Valid Refund Reasons</h3><ul><li>Product does not match its description</li><li>Technical issues preventing download or use</li><li>Duplicate purchase</li><li>Product is defective or corrupted</li></ul><h3>4. Non-Refundable Items</h3><p>Products that have been downloaded and used are generally non-refundable. Custom orders and personalized products are also non-refundable.</p><h3>5. Refund Processing</h3><p>Approved refunds will be processed within 5-7 business days to your original payment method.</p><h3>6. Cancellation</h3><p>You may cancel your order before the download link is accessed. Contact support immediately for cancellations.</p>', 'active'),
('shipping-delivery-policy', 'Shipping and Delivery Policy', '<h2>Shipping and Delivery Policy</h2><p>Last updated: May 21, 2026</p><h3>1. Digital Delivery</h3><p>All products on RS Digital Hub are digital downloads. There is no physical shipping involved.</p><h3>2. Instant Access</h3><p>Upon successful payment, you will receive immediate access to download your purchased products from your account dashboard.</p><h3>3. Download Links</h3><p>Download links are valid for 48 hours from the time of purchase and can be used up to 5 times. After expiry, you can request a new link from your account.</p><h3>4. Email Delivery</h3><p>You will also receive download links via email. Please check your spam folder if you do not receive the email within 5 minutes.</p><h3>5. File Formats</h3><p>Products are delivered in standard digital formats (ZIP, PDF, etc.). Ensure you have appropriate software to open the files.</p><h3>6. Delivery Issues</h3><p>If you experience any issues with downloading your product, please contact our support team immediately for assistance.</p>', 'active'),
('copyright-dmca-policy', 'Copyright and DMCA Policy', '<h2>Copyright and DMCA Policy</h2><p>Last updated: May 21, 2026</p><h3>1. Copyright Notice</h3><p>All content on RS Digital Hub, including text, graphics, logos, and digital products, is protected by copyright laws and is the property of RS Digital Hub or its content suppliers.</p><h3>2. User Content</h3><p>Users who upload content to our platform warrant that they own or have permission to distribute such content. Users retain ownership of their original content.</p><h3>3. DMCA Takedown</h3><p>If you believe that your copyrighted work has been copied in a way that constitutes copyright infringement, please submit a DMCA notice to our designated agent.</p><h3>4. DMCA Notice Requirements</h3><p>Your notice must include: identification of the copyrighted work, identification of the infringing material, your contact information, a statement of good faith belief, and a statement under penalty of perjury.</p><h3>5. Counter-Notice</h3><p>If you believe your content was wrongly removed, you may submit a counter-notice. We will restore the content within 10-14 business days unless the copyright owner files a court action.</p><h3>6. Repeat Infringers</h3><p>We reserve the right to terminate accounts of users who are found to be repeat infringers of copyright.</p><h3>7. Contact</h3><p>DMCA inquiries: dmca@rsdigitalhub.com</p>', 'active'),
('license', 'License', '<h2>License</h2><p>Last updated: May 23, 2026</p><h3>1. General Terms</h3><p>By purchasing and downloading any product from RS Digital Hub, you agree to the terms of this license. Each product is protected by copyright and intellectual property laws.</p><h3>2. Regular License</h3><p>A Regular License grants you the right to use the purchased product for a single personal or commercial project. You may not resell, sublicense, or redistribute the product in its original form.</p><ul><li>Use in one end product for yourself or a client</li><li>End product cannot be sold or given away for free</li><li>You may not claim the product as your own work</li></ul><h3>3. Extended License</h3><p>An Extended License allows for multiple use cases, including use in products that are sold to end users. Contact us for extended licensing options.</p><h3>4. What You Cannot Do</h3><ul><li>Resell or redistribute the product as-is</li><li>Share your download link with others</li><li>Use the product in a way that violates applicable laws</li><li>Remove copyright or attribution notices</li></ul><h3>5. Developer License</h3><p>Developers who create and sell products on our platform retain full ownership of their work. By uploading products, you grant RS Digital Hub a license to distribute your products to customers.</p><h3>6. Termination</h3><p>This license terminates automatically if you violate any of its terms. Upon termination, you must destroy all copies of the product in your possession.</p><h3>7. Contact</h3><p>For licensing inquiries: license@rsdigitalhub.com</p>', 'active');

-- Sample FAQs
INSERT INTO faqs (question, answer, sort_order) VALUES 
('How do I download my purchased product?', 'After successful payment, you can download your product from the Orders page. You will also receive a download link via email.', 1),
('What payment methods do you accept?', 'We accept payments via Stripe, PayPal, and Razorpay. The available options will be shown at checkout.', 2),
('Can I get a refund?', 'Refund requests can be submitted through our support ticket system. Each request is reviewed on a case-by-case basis.', 3),
('How many times can I download a product?', 'You can download each purchased product up to 5 times within 48 hours of purchase.', 4),
('Do you offer technical support?', 'Yes, we provide technical support through our ticket system. Submit a ticket and our team will assist you.', 5);

-- Sample Testimonials
INSERT INTO testimonials (name, email, message, rating, status) VALUES 
('John Doe', 'john@example.com', 'Amazing products! The templates saved me hours of work.', 5, 'active'),
('Jane Smith', 'jane@example.com', 'Great quality digital products. Highly recommended!', 5, 'active'),
('Mike Johnson', 'mike@example.com', 'Excellent customer support and fast downloads.', 4, 'active');

-- =====================================================
-- UPGRADE: Existing database se naye columns add karo
-- =====================================================
-- Ye queries tabhi chalana agar aapke paas pehle se database hai
-- Aur isme new columns nahi hain. Sirf EK BAAR chalana hai.
-- =====================================================

-- Users table: developer role and store columns
ALTER TABLE users ADD COLUMN IF NOT EXISTS store_name VARCHAR(200) DEFAULT NULL AFTER avatar;
ALTER TABLE users ADD COLUMN IF NOT EXISTS store_slug VARCHAR(200) DEFAULT NULL AFTER store_name;
ALTER TABLE users ADD COLUMN IF NOT EXISTS store_description TEXT DEFAULT NULL AFTER store_slug;
ALTER TABLE users MODIFY COLUMN role ENUM('user','admin','super_admin','developer') NOT NULL DEFAULT 'user';

-- Products table: developer, type, free columns
ALTER TABLE products ADD COLUMN IF NOT EXISTS developer_id INT DEFAULT NULL AFTER id;
ALTER TABLE products ADD COLUMN IF NOT EXISTS is_free TINYINT(1) DEFAULT 0 AFTER sale_price;
ALTER TABLE products ADD COLUMN IF NOT EXISTS product_type VARCHAR(50) DEFAULT 'general' AFTER category_id;
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_product_type (product_type);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_developer_id (developer_id);

-- Product files/versions table
CREATE TABLE IF NOT EXISTS product_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    version VARCHAR(50) DEFAULT '',
    file_path VARCHAR(500) DEFAULT '',
    file_url VARCHAR(500) DEFAULT '',
    file_size VARCHAR(50) DEFAULT '',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Content tables: product_type column
ALTER TABLE policies ADD COLUMN IF NOT EXISTS product_type VARCHAR(50) DEFAULT 'general' AFTER id;
ALTER TABLE about_us ADD COLUMN IF NOT EXISTS product_type VARCHAR(50) DEFAULT 'general' AFTER id;
ALTER TABLE notices ADD COLUMN IF NOT EXISTS product_type VARCHAR(50) DEFAULT 'general' AFTER id;
ALTER TABLE social_links ADD COLUMN IF NOT EXISTS product_type VARCHAR(50) DEFAULT 'general' AFTER id;
