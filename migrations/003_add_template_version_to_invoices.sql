ALTER TABLE orders ADD COLUMN invoice_template_version VARCHAR(32) DEFAULT 'current' AFTER status;
ALTER TABLE subscriptions ADD COLUMN invoice_template_version VARCHAR(32) DEFAULT 'current' AFTER status;
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('default_invoice_template', 'current');
