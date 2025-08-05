INSERT INTO pulsar.user_profile(firstname, lastname, email, avatar)
VALUES
    ('Agent', 'Smith', 'info@ophelios.com', 'system.jpg'), -- System user
    ('Thomas', 'Anderson', 'admin@ophelios.com', 'admin.jpg'); -- Root backdoor administrator

-- The default hashed password and validator fields should be updated in a production environment
-- For dev values, the admin password should be "OmegaDelta123*"
INSERT INTO pulsar.user_authentication(id, username, password_hash, validator, superuser)
VALUES
    (1, 'system', '$2y$10$7nC7O/6MAaS8SnWTigS.C.ZbMLxp0K1a1OjIzUVJmJpX44Hlce8Ee', 'YPscA0NuuARWvlF6LWygzxHyezX1dyWjsfEAXf2jULyLciLCvRhiuwT7Lcf6dhA7', true),
    (2, 'admin', '$2y$13$xcllaNIZRoEMpzd/vyZrluK8.e8VIgE3K8tFPWM6Htf5dEfn94c86', 'yOEmeDs0GF6PrxNSYFlbaIXZyfmD9xSbvHX2jOKW7oYrU0IjgXJUCFnE7YKfTFs1', true);
