-- Migration: Create subscription_invoices table
-- This table stores generated invoices for subscription payments

CREATE TABLE IF NOT EXISTS subscription_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL,
    invoice_number VARCHAR(100) NOT NULL,
    invoice_path VARCHAR(255) DEFAULT NULL,
    invoice_template_version VARCHAR(32) DEFAULT 'current',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
