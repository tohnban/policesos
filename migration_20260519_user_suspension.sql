ALTER TABLE users
  ADD COLUMN suspended_until TIMESTAMP NULL AFTER status;
