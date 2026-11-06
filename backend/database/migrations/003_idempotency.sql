ALTER TABLE mutation_requests ADD COLUMN IF NOT EXISTS request_hash CHAR(64) NULL;
