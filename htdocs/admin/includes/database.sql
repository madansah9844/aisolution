-- AI-Solutions Database Schema
-- Complete database structure for the AI-Solutions platform

-- Use existing live server database
USE depc_38153831_ai;

-- Users table for admin authentication
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'manager', 'editor') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inquiries table for contact form submissions
DROP TABLE IF EXISTS inquiries;
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

-- Services table for company services
DROP TABLE IF EXISTS services;
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    image VARCHAR(255),
    icon VARCHAR(100),
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Portfolio table for case studies and past solutions
DROP TABLE IF EXISTS portfolio;
CREATE TABLE portfolio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    client VARCHAR(100),
    category VARCHAR(100),
    image VARCHAR(255),
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events table for company events
DROP TABLE IF EXISTS events;
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    date DATE,
    time TIME,
    location VARCHAR(200),
    image VARCHAR(255),
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Blogs table for company articles
DROP TABLE IF EXISTS blogs;
CREATE TABLE blogs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    excerpt VARCHAR(500),
    author VARCHAR(100),
    category VARCHAR(100),
    image VARCHAR(255),
    published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Gallery table for promotional event photos
DROP TABLE IF EXISTS gallery;
CREATE TABLE gallery (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200),
    description VARCHAR(500),
    image VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Visitors table for analytics
DROP TABLE IF EXISTS visitors;
CREATE TABLE visitors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    page_visited VARCHAR(255),
    referrer VARCHAR(255),
    visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Subscribers table for newsletter
DROP TABLE IF EXISTS subscribers;
CREATE TABLE subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@ai-solutions.com', 'admin');

-- Insert sample services
INSERT INTO services (title, description, short_description, icon, featured) VALUES 
('AI Virtual Assistant', 'Our unique AI-powered virtual assistant responds to users inquiries and provides intelligent support, setting us apart from competitors.', 'Smart AI assistant for customer support and employee assistance', 'fas fa-robot', TRUE),
('Affordable Prototyping', 'Our AI-based, affordable prototyping solutions help you quickly develop and test ideas, reducing time-to-market for your innovations.', 'Cost-effective AI prototyping for rapid development', 'fas fa-code', TRUE),
('Custom AI Solutions', 'We build bespoke AI solutions tailored to your specific business needs and integrated with your existing systems.', 'Tailored AI solutions for your business requirements', 'fas fa-cogs', FALSE);

-- Insert sample portfolio items
INSERT INTO portfolio (title, description, client, category, featured) VALUES 
('AI-Powered Customer Service Platform', 'Developed an intelligent customer service platform for a retail chain, reducing response times by 60%.', 'Retail Chain Ltd', 'Customer Service', TRUE),
('Smart Manufacturing Assistant', 'Created an AI assistant for manufacturing processes, improving efficiency by 40%.', 'Manufacturing Corp', 'Manufacturing', TRUE),
('Healthcare AI Prototype', 'Rapid prototyping of AI solutions for healthcare diagnostics, reducing development time by 70%.', 'Healthcare Solutions', 'Healthcare', FALSE);

-- Insert sample events
INSERT INTO events (title, description, date, location, featured) VALUES 
('AI Innovation Summit 2024', 'Join us for the biggest AI innovation event in Sunderland, featuring industry experts and cutting-edge demonstrations.', '2024-03-15', 'Sunderland Conference Centre', TRUE),
('Digital Employee Experience Workshop', 'Learn how AI can transform your workplace and improve employee productivity.', '2024-02-28', 'AI-Solutions Office, Sunderland', FALSE),
('AI Prototyping Masterclass', 'Hands-on workshop on affordable AI prototyping techniques and best practices.', '2024-04-10', 'Tech Hub Sunderland', FALSE);

-- Insert sample blogs
INSERT INTO blogs (title, content, excerpt, author, category, published) VALUES 
('The Future of AI in Digital Employee Experience', 'Artificial Intelligence is revolutionizing how employees interact with digital systems...', 'Exploring how AI is transforming workplace productivity and employee satisfaction.', 'AI-Solutions Team', 'AI Trends', TRUE),
('Affordable AI Prototyping: A Game Changer for SMEs', 'Small and medium enterprises can now access powerful AI prototyping tools...', 'How affordable AI prototyping is democratizing innovation for smaller businesses.', 'Dr. Sarah Johnson', 'Innovation', TRUE),
('Virtual Assistants: Beyond Customer Service', 'AI virtual assistants are evolving beyond simple customer service applications...', 'Discover the expanding role of AI assistants in modern business operations.', 'Mike Chen', 'Technology', TRUE);

-- Create indexes for better performance
DROP INDEX IF EXISTS idx_inquiries_status ON inquiries;
CREATE INDEX idx_inquiries_status ON inquiries(status);

DROP INDEX IF EXISTS idx_inquiries_created_at ON inquiries;
CREATE INDEX idx_inquiries_created_at ON inquiries(created_at);

DROP INDEX IF EXISTS idx_events_date ON events;
CREATE INDEX idx_events_date ON events(date);

DROP INDEX IF EXISTS idx_blogs_published ON blogs;
CREATE INDEX idx_blogs_published ON blogs(published);

DROP INDEX IF EXISTS idx_visitors_visit_time ON visitors;
CREATE INDEX idx_visitors_visit_time ON visitors(visit_time);
