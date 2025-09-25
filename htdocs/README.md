# AI-Solutions - Digital Employee Experience Platform

## Project Overview

AI-Solutions is a fictitious start-up company based in Sunderland that leverages AI to assist various industries with software solutions to rapidly and proactively address issues that can impact the digital employee experience, thus speeding up design, engineering, and innovation.

### Key Features
- **AI-Powered Virtual Assistant**: Unique selling point that responds to users' inquiries
- **Affordable Prototyping Solutions**: AI-based, cost-effective prototyping services
- **Customer Inquiry Management**: Contact form with comprehensive customer information collection
- **Admin Dashboard**: Password-protected area for managing customer inquiries and analytics
- **Portfolio Showcase**: Past solutions provided to industries
- **Event Management**: Promotional events and upcoming events
- **Blog System**: Articles promoting the company
- **Gallery**: Photo galleries of promotional events

## Scenario Compliance

This project has been updated to fully comply with the University of Sunderland CET333 Product Development assessment requirements for the AI-Solutions scenario.

### Requirements Met:

#### ✅ Contact Form Requirements
- **Name**: Full name collection
- **Email**: Email address validation
- **Phone Number**: Contact phone number
- **Company Name**: Customer's company
- **Country**: Customer's country
- **Job Title**: Customer's job title
- **Job Details**: Specific job requirements description

#### ✅ Admin Panel Requirements
- **Password Protection**: Secure admin login system
- **Customer Inquiry Management**: View and manage all customer inquiries
- **Status Tracking**: Track inquiry status (new, read, replied, archived)
- **Analytics**: Visitor analytics and inquiry statistics

#### ✅ Website Content Requirements
- **Software Solutions**: Detailed service offerings
- **Past Solutions**: Portfolio showcasing previous work
- **Customer Feedback**: Testimonials and ratings system
- **Company Articles**: Blog posts promoting the company
- **Event Galleries**: Photo galleries of promotional events
- **Upcoming Events**: Event management system

## Technical Implementation

### Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Styling**: Custom CSS with responsive design
- **Icons**: Font Awesome
- **Fonts**: Google Fonts (Roboto, Poppins)

### Database Schema

#### Inquiries Table
```sql
CREATE TABLE inquiries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    company VARCHAR(100),
    country VARCHAR(100),
    job_title VARCHAR(100),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### File Structure
```
htdocs/
├── index.html              # Homepage
├── about.html              # About Us page
├── services.html           # Services page
├── contact.html            # Contact form
├── process_form.php        # Form processing
├── portfolio.php           # Portfolio showcase
├── events.php              # Events listing
├── blogs.php               # Blog listing
├── gallery.php             # Photo gallery
├── css/
│   └── styles.css          # Main stylesheet
├── js/
│   ├── main.js             # Main JavaScript
│   └── analytics.js        # Analytics tracking
├── images/                 # Image assets
└── admin/                  # Admin panel
    ├── login.php           # Admin login
    ├── index.php           # Dashboard
    ├── inquiries.php       # Inquiry management
    ├── analytics.php       # Analytics
    ├── portfolio.php       # Portfolio management
    ├── events.php          # Event management
    ├── blogs.php           # Blog management
    ├── gallery.php         # Gallery management
    └── includes/
        ├── config.php      # Database configuration
        └── database_update.sql # Database update script
```

## Installation & Setup

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### Installation Steps

1. **Clone/Download the Project**
   ```bash
   # Place files in your web server directory
   ```

2. **Database Setup**
   ```bash
   # Create MySQL database
   CREATE DATABASE ai_solutions;
   
   # Import the database schema
   mysql -u username -p ai_solutions < admin/includes/database.sql
   
   # Run the update script for new fields
   mysql -u username -p ai_solutions < admin/includes/database_update.sql
   ```

3. **Configure Database Connection**
   - Edit `admin/includes/config.php`
   - Update database credentials:
     ```php
     define('DB_SERVER', 'your_host');
     define('DB_USERNAME', 'your_username');
     define('DB_PASSWORD', 'your_password');
     define('DB_NAME', 'ai_solutions');
     ```

4. **Set Up Admin Account**
   - Access `admin/login.php`
   - Default credentials (update in database):
     - Username: admin
     - Password: admin123

5. **Configure Email Settings**
   - Edit `process_form.php`
   - Update email addresses and SMTP settings

### File Permissions
```bash
# Ensure proper permissions for uploads
chmod 755 images/
chmod 755 admin/
```

## Usage Guide

### For Customers
1. **Browse Services**: Visit the homepage to explore AI solutions
2. **View Portfolio**: Check past solutions and case studies
3. **Read Blog**: Access company articles and insights
4. **Contact Us**: Fill out the contact form with job requirements
5. **Attend Events**: View upcoming events and galleries

### For Administrators
1. **Login**: Access admin panel at `/admin/login.php`
2. **Manage Inquiries**: View and respond to customer inquiries
3. **Update Content**: Manage portfolio, events, blogs, and gallery
4. **View Analytics**: Monitor website traffic and inquiry statistics
5. **System Settings**: Configure system parameters

## Key Features Implementation

### Contact Form Enhancement
- Added **Country** and **Job Title** fields as required by scenario
- Enhanced form validation and processing
- Improved email notifications with new fields
- Updated admin panel to display new information

### AI Virtual Assistant Integration
- Prepared infrastructure for AI assistant integration
- Contact form designed to capture specific job requirements
- Admin panel ready for assistant response management

### Affordable Prototyping Solutions
- Dedicated service pages for prototyping offerings
- Portfolio showcasing past prototyping projects
- Pricing and value proposition clearly communicated

## Security Features

- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Form token validation
- **Password Security**: Hashed passwords in admin system
- **Session Management**: Secure session handling

## Performance Optimizations

- **Image Optimization**: Compressed images for faster loading
- **CSS/JS Minification**: Optimized asset delivery
- **Database Indexing**: Proper indexing for queries
- **Caching**: Session and query caching where appropriate

## Testing

### Functional Testing
- [x] Contact form submission and validation
- [x] Admin login and authentication
- [x] Inquiry management workflow
- [x] Content management system
- [x] Responsive design across devices

### Security Testing
- [x] SQL injection prevention
- [x] XSS protection
- [x] Form validation
- [x] Admin access control

## Deployment

### Production Checklist
- [ ] Update database credentials
- [ ] Configure email settings
- [ ] Set up SSL certificate
- [ ] Configure backup system
- [ ] Set proper file permissions
- [ ] Test all functionality
- [ ] Monitor error logs

## Support & Maintenance

### Regular Maintenance
- Database backups
- Security updates
- Content updates
- Performance monitoring
- Analytics review

### Troubleshooting
- Check error logs in `/admin/logs/`
- Verify database connectivity
- Test email functionality
- Validate form submissions

## License

This project is developed for educational purposes as part of the University of Sunderland CET333 Product Development module.

## Contact

For technical support or questions about this implementation:
- **Student**: [Your Name]
- **Module**: CET333 Product Development
- **University**: University of Sunderland
- **Instructor**: Dr Barnali Das

---

**Note**: This is a prototype implementation for educational assessment purposes. The AI-Solutions company is fictitious and created for the CET333 module requirements. 