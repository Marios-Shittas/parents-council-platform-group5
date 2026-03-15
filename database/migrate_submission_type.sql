-- Migration: Add submission_type to Applications, text_content + nullable file_path to Submissions
-- Run this once against an existing parents_council database

USE parents_council;

ALTER TABLE Applications
    ADD COLUMN submission_type ENUM('file','text') NOT NULL DEFAULT 'file';

ALTER TABLE Submissions
    MODIFY file_path VARCHAR(255) NULL,
    ADD COLUMN text_content TEXT NULL;
