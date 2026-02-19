-- Add shift_type and required_hours columns to employees table
ALTER TABLE employees 
ADD COLUMN IF NOT EXISTS timezone VARCHAR(100) DEFAULT 'Asia/Kolkata' AFTER employee_type,
ADD COLUMN IF NOT EXISTS shift_type ENUM('fixed', 'flexible') DEFAULT 'fixed' AFTER timezone,
ADD COLUMN IF NOT EXISTS required_hours DECIMAL(4,2) DEFAULT 8.00 AFTER shift_type;

-- Update existing employees to have default values
UPDATE employees 
SET timezone = 'Asia/Kolkata', 
    shift_type = 'fixed', 
    required_hours = 8.00 
WHERE timezone IS NULL OR shift_type IS NULL OR required_hours IS NULL;
