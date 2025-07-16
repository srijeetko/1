-- Customer Support System Database Schema for Interakt WhatsApp Integration
-- Alpha Nutrition E-commerce Platform

-- =====================================================
-- SUPPORT AGENTS TABLE
-- =====================================================
CREATE TABLE support_agents (
    agent_id CHAR(36) PRIMARY KEY,
    admin_id CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    whatsapp_number VARCHAR(20),
    status ENUM('active', 'inactive', 'busy', 'away') DEFAULT 'active',
    max_concurrent_chats INT DEFAULT 5,
    specialization VARCHAR(100), -- e.g., 'product_queries', 'order_support', 'technical'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE CASCADE
);

-- =====================================================
-- SUPPORT TICKETS TABLE
-- =====================================================
CREATE TABLE support_tickets (
    ticket_id CHAR(36) PRIMARY KEY,
    ticket_number VARCHAR(20) UNIQUE NOT NULL, -- e.g., 'TKT-20250716-001'
    user_id CHAR(36),
    customer_phone VARCHAR(20) NOT NULL,
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    subject VARCHAR(200),
    category ENUM('product_inquiry', 'order_support', 'payment_issue', 'shipping_query', 'complaint', 'general', 'technical') DEFAULT 'general',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'pending_customer', 'resolved', 'closed') DEFAULT 'open',
    assigned_agent_id CHAR(36),
    source ENUM('whatsapp', 'website', 'email', 'phone') DEFAULT 'whatsapp',
    product_id CHAR(36), -- If ticket is related to specific product
    order_id CHAR(36), -- If ticket is related to specific order
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_agent_id) REFERENCES support_agents(agent_id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES checkout_orders(order_id) ON DELETE SET NULL,
    INDEX idx_ticket_status (status),
    INDEX idx_ticket_priority (priority),
    INDEX idx_customer_phone (customer_phone),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- WHATSAPP CONVERSATIONS TABLE
-- =====================================================
CREATE TABLE whatsapp_conversations (
    conversation_id CHAR(36) PRIMARY KEY,
    ticket_id CHAR(36) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    interakt_conversation_id VARCHAR(100), -- Interakt's internal conversation ID
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(ticket_id) ON DELETE CASCADE,
    INDEX idx_customer_phone (customer_phone),
    INDEX idx_last_message (last_message_at)
);

-- =====================================================
-- WHATSAPP MESSAGES TABLE
-- =====================================================
CREATE TABLE whatsapp_messages (
    message_id CHAR(36) PRIMARY KEY,
    conversation_id CHAR(36) NOT NULL,
    interakt_message_id VARCHAR(100), -- Interakt's message ID
    sender_type ENUM('customer', 'agent', 'system') NOT NULL,
    sender_id CHAR(36), -- agent_id if sent by agent, user_id if customer
    message_type ENUM('text', 'template', 'image', 'document', 'audio', 'video', 'location') DEFAULT 'text',
    content TEXT NOT NULL,
    template_name VARCHAR(100), -- If message_type is 'template'
    media_url TEXT, -- For media messages
    media_filename VARCHAR(255),
    delivery_status ENUM('sent', 'delivered', 'read', 'failed') DEFAULT 'sent',
    is_automated BOOLEAN DEFAULT FALSE,
    callback_data TEXT, -- Additional data from Interakt
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (conversation_id) REFERENCES whatsapp_conversations(conversation_id) ON DELETE CASCADE,
    INDEX idx_conversation_created (conversation_id, created_at),
    INDEX idx_delivery_status (delivery_status),
    INDEX idx_sender_type (sender_type)
);

-- =====================================================
-- SUPPORT TEMPLATES TABLE
-- =====================================================
CREATE TABLE support_templates (
    template_id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    interakt_template_name VARCHAR(100) NOT NULL, -- Template name in Interakt
    category VARCHAR(50) NOT NULL, -- e.g., 'greeting', 'order_status', 'product_info'
    language_code VARCHAR(10) DEFAULT 'en',
    subject VARCHAR(200),
    content TEXT NOT NULL,
    variables JSON, -- Template variables and their descriptions
    is_active BOOLEAN DEFAULT TRUE,
    usage_count INT DEFAULT 0,
    created_by CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES support_agents(agent_id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_active (is_active)
);

-- =====================================================
-- AGENT ASSIGNMENTS TABLE
-- =====================================================
CREATE TABLE agent_assignments (
    assignment_id CHAR(36) PRIMARY KEY,
    ticket_id CHAR(36) NOT NULL,
    agent_id CHAR(36) NOT NULL,
    assigned_by CHAR(36), -- admin who assigned
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unassigned_at TIMESTAMP NULL,
    status ENUM('active', 'completed', 'transferred') DEFAULT 'active',
    notes TEXT,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES support_agents(agent_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL,
    INDEX idx_ticket_agent (ticket_id, agent_id),
    INDEX idx_agent_status (agent_id, status)
);

-- =====================================================
-- SUPPORT ANALYTICS TABLE
-- =====================================================
CREATE TABLE support_analytics (
    analytics_id CHAR(36) PRIMARY KEY,
    date DATE NOT NULL,
    agent_id CHAR(36),
    tickets_created INT DEFAULT 0,
    tickets_resolved INT DEFAULT 0,
    tickets_closed INT DEFAULT 0,
    avg_response_time INT, -- in minutes
    avg_resolution_time INT, -- in minutes
    customer_satisfaction_score DECIMAL(3,2), -- 1.00 to 5.00
    total_messages_sent INT DEFAULT 0,
    total_messages_received INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES support_agents(agent_id) ON DELETE CASCADE,
    UNIQUE KEY unique_date_agent (date, agent_id),
    INDEX idx_date (date),
    INDEX idx_agent_date (agent_id, date)
);

-- =====================================================
-- CUSTOMER FEEDBACK TABLE
-- =====================================================
CREATE TABLE customer_feedback (
    feedback_id CHAR(36) PRIMARY KEY,
    ticket_id CHAR(36) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    feedback_text TEXT,
    agent_id CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES support_agents(agent_id) ON DELETE SET NULL,
    INDEX idx_ticket (ticket_id),
    INDEX idx_rating (rating),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- AUTOMATED RESPONSES TABLE
-- =====================================================
CREATE TABLE automated_responses (
    response_id CHAR(36) PRIMARY KEY,
    trigger_keyword VARCHAR(100) NOT NULL,
    response_type ENUM('template', 'text') DEFAULT 'text',
    template_id CHAR(36), -- If response_type is 'template'
    response_text TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 1, -- Higher number = higher priority
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES support_templates(template_id) ON DELETE SET NULL,
    INDEX idx_keyword (trigger_keyword),
    INDEX idx_active_priority (is_active, priority)
);

-- =====================================================
-- INTERAKT WEBHOOK LOGS TABLE
-- =====================================================
CREATE TABLE interakt_webhook_logs (
    log_id CHAR(36) PRIMARY KEY,
    webhook_type ENUM('message_status', 'incoming_message', 'conversation_status') NOT NULL,
    interakt_message_id VARCHAR(100),
    phone_number VARCHAR(20),
    payload JSON NOT NULL,
    processed BOOLEAN DEFAULT FALSE,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    INDEX idx_webhook_type (webhook_type),
    INDEX idx_processed (processed),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- SUPPORT SETTINGS TABLE
-- =====================================================
CREATE TABLE support_settings (
    setting_id CHAR(36) PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_by CHAR(36),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL
);

-- =====================================================
-- INSERT DEFAULT SETTINGS
-- =====================================================
-- =====================================================
-- BUSINESS NOTIFICATION TEMPLATES TABLE
-- =====================================================
CREATE TABLE business_notification_templates (
    template_id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    interakt_template_name VARCHAR(100) NOT NULL,
    category ENUM('order_updates', 'marketing', 'feedback', 'welcome', 'promotional', 'reminder') NOT NULL,
    trigger_event VARCHAR(100) NOT NULL, -- e.g., 'order_placed', 'order_shipped', 'cart_abandoned'
    language_code VARCHAR(10) DEFAULT 'en',
    subject VARCHAR(200),
    description TEXT,
    template_variables JSON, -- Variables and their descriptions
    is_active BOOLEAN DEFAULT TRUE,
    auto_send BOOLEAN DEFAULT FALSE, -- Auto-send when event occurs
    send_delay_minutes INT DEFAULT 0, -- Delay before sending (for reminders)
    usage_count INT DEFAULT 0,
    created_by CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_trigger_event (trigger_event),
    INDEX idx_active_auto (is_active, auto_send)
);

-- =====================================================
-- NOTIFICATION QUEUE TABLE
-- =====================================================
CREATE TABLE notification_queue (
    queue_id CHAR(36) PRIMARY KEY,
    template_id CHAR(36) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_name VARCHAR(100),
    template_variables JSON, -- Actual values for template variables
    trigger_event VARCHAR(100),
    related_id CHAR(36), -- order_id, product_id, etc.
    scheduled_at TIMESTAMP NOT NULL,
    sent_at TIMESTAMP NULL,
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES business_notification_templates(template_id) ON DELETE CASCADE,
    INDEX idx_scheduled_status (scheduled_at, status),
    INDEX idx_customer_phone (customer_phone),
    INDEX idx_trigger_event (trigger_event)
);

-- =====================================================
-- INSERT DEFAULT BUSINESS TEMPLATES
-- =====================================================
INSERT INTO business_notification_templates (template_id, name, interakt_template_name, category, trigger_event, description, template_variables, is_active, auto_send) VALUES
(UUID(), 'Order Confirmation', 'order_confirmation', 'order_updates', 'order_placed', 'Sent when customer places an order', '{"customer_name": "Customer name", "order_number": "Order ID", "total_amount": "Order total", "items": "Order items"}', TRUE, TRUE),
(UUID(), 'Order Shipped', 'order_shipped', 'order_updates', 'order_shipped', 'Sent when order is shipped', '{"customer_name": "Customer name", "order_number": "Order ID", "tracking_number": "Tracking ID", "courier": "Courier name"}', TRUE, TRUE),
(UUID(), 'Order Delivered', 'order_delivered', 'order_updates', 'order_delivered', 'Sent when order is delivered', '{"customer_name": "Customer name", "order_number": "Order ID"}', TRUE, TRUE),
(UUID(), 'Welcome New Customer', 'welcome_new_customer', 'welcome', 'user_registered', 'Welcome message for new customers', '{"customer_name": "Customer name", "discount_code": "Welcome discount"}', TRUE, TRUE),
(UUID(), 'Abandoned Cart Reminder', 'cart_abandoned', 'reminder', 'cart_abandoned', 'Reminder for abandoned cart', '{"customer_name": "Customer name", "cart_items": "Cart items", "cart_total": "Cart value"}', TRUE, TRUE),
(UUID(), 'Feedback Request', 'feedback_request', 'feedback', 'order_delivered_3days', 'Request feedback after delivery', '{"customer_name": "Customer name", "order_number": "Order ID", "product_name": "Product name"}', TRUE, FALSE),
(UUID(), 'Birthday Wishes', 'birthday_wishes', 'marketing', 'customer_birthday', 'Birthday wishes with special offer', '{"customer_name": "Customer name", "discount_code": "Birthday discount"}', TRUE, FALSE),
(UUID(), 'Low Stock Alert', 'low_stock_alert', 'promotional', 'product_low_stock', 'Alert customers about low stock on wishlisted items', '{"customer_name": "Customer name", "product_name": "Product name", "stock_left": "Stock quantity"}', TRUE, FALSE);

INSERT INTO support_settings (setting_id, setting_key, setting_value, description) VALUES
(UUID(), 'interakt_api_key', '', 'Interakt API Key for WhatsApp integration'),
(UUID(), 'interakt_base_url', 'https://api.interakt.ai/v1/public', 'Interakt API base URL'),
(UUID(), 'auto_assign_tickets', 'true', 'Automatically assign tickets to available agents'),
(UUID(), 'business_hours_start', '09:00', 'Support business hours start time'),
(UUID(), 'business_hours_end', '18:00', 'Support business hours end time'),
(UUID(), 'auto_response_enabled', 'true', 'Enable automated responses for common queries'),
(UUID(), 'max_response_time_minutes', '30', 'Maximum response time target in minutes'),
(UUID(), 'customer_satisfaction_enabled', 'true', 'Enable customer satisfaction surveys'),
(UUID(), 'business_notifications_enabled', 'true', 'Enable automated business notifications'),
(UUID(), 'cart_abandonment_delay_hours', '2', 'Hours to wait before sending cart abandonment reminder'),
(UUID(), 'feedback_request_delay_days', '3', 'Days to wait after delivery before requesting feedback');

-- =====================================================
-- CREATE INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX idx_tickets_agent_status ON support_tickets(assigned_agent_id, status);
CREATE INDEX idx_messages_conversation_time ON whatsapp_messages(conversation_id, created_at DESC);
CREATE INDEX idx_analytics_date_range ON support_analytics(date, agent_id);