-- Add must_change_password column to user table
ALTER TABLE public."user" ADD COLUMN IF NOT EXISTS must_change_password BOOLEAN DEFAULT FALSE NOT NULL;
