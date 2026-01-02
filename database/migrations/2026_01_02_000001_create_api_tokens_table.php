<?php

declare(strict_types=1);

use Core\Migration;

/**
 * Create API tokens table for stateless authentication.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = $this->getDriver();
        
        if ($driver === 'sqlite') {
            $this->execute("
                CREATE TABLE api_tokens (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    name TEXT DEFAULT 'default',
                    token TEXT NOT NULL UNIQUE,
                    last_used_at TEXT NULL,
                    expires_at TEXT NULL,
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $this->execute("CREATE INDEX idx_api_tokens_user_id ON api_tokens(user_id)");
            $this->execute("CREATE INDEX idx_api_tokens_token ON api_tokens(token)");
        } else {
            // MySQL / PostgreSQL
            $this->execute("
                CREATE TABLE api_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    name VARCHAR(255) DEFAULT 'default',
                    token VARCHAR(64) NOT NULL UNIQUE,
                    last_used_at TIMESTAMP NULL,
                    expires_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_token (token)
                )
            ");
        }
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS api_tokens");
    }
};
