-- Migration: Add role column to users table
-- Run this on existing databases that were created before the role column was added.
-- New installations get this column from schema.sql automatically.

ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'admin';
