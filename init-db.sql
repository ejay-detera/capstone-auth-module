CREATE USER IF NOT EXISTS 'auth_user'@'%' IDENTIFIED BY 'auth_password_123';
GRANT ALL PRIVILEGES ON auth_db.* TO 'auth_user'@'%';
FLUSH PRIVILEGES;
