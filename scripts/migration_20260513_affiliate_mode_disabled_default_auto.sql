-- Add disabled affiliate mode and make auto the default for new properties
ALTER TABLE properties
    MODIFY COLUMN affiliate_approval_mode ENUM('manual', 'auto', 'disabled')
    NOT NULL
    DEFAULT 'auto';
