-- Insert admin user with plain password (for testing only)
INSERT INTO admin_users (admin_id, name, email, password_hash, role, created_at)
VALUES (
    'admin-001',
    'Admin',
    'admin@example.com',
    'abcd@1234',
    'admin',
    CURRENT_TIMESTAMP
);
