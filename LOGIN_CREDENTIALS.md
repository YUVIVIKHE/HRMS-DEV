# HRMS - LOGIN CREDENTIALS

## 🔐 Default Login Credentials

### Admin Account
**Email:** `admin@hrms.com`  
**Password:** `admin123`  
**Access:** Full system access (CRUD on all modules)

---

### Sample Employee Accounts

⚠️ **IMPORTANT:** Sample employees need passwords to be set first!

Run this script to setup passwords:
```
http://localhost:3307/hrms/setup_employee_passwords.php
```

After running the script, use these credentials:

#### Employee 1 - John Doe (IT Department)
**Email:** `john.doe@company.com`  
**Password:** `employee123`  
**Employee ID:** EMP001  
**Department:** IT  
**Job Title:** Software Engineer

#### Employee 2 - Jane Smith (HR Department)
**Email:** `jane.smith@company.com`  
**Password:** `employee123`  
**Employee ID:** EMP002  
**Department:** HR  
**Job Title:** HR Manager

#### Employee 3 - Mike Johnson (Finance Department)
**Email:** `mike.johnson@company.com`  
**Password:** `employee123`  
**Employee ID:** EMP003  
**Department:** Finance  
**Job Title:** Accountant

---

## 🔧 Setup Instructions

### Option 1: Run PHP Script (Recommended)
1. Open browser
2. Navigate to: `http://localhost:3307/hrms/setup_employee_passwords.php`
3. Script will automatically set passwords for all sample employees
4. You'll see confirmation message

### Option 2: Manual SQL Update
Run this in phpMyAdmin:
```sql
-- Generate proper password hash in PHP first
-- Then update employees table
UPDATE employees 
SET password = '$2y$10$[HASH_HERE]'
WHERE email IN ('john.doe@company.com', 'jane.smith@company.com', 'mike.johnson@company.com');
```

### Option 3: Add New Employee via Admin Panel
1. Login as admin
2. Go to Employees → Add Employee
3. Fill in employee details
4. System will auto-generate password
5. Password will be displayed once (copy it!)

---

## 🎭 User Roles & Permissions

### Admin / HR
- ✅ View all employees
- ✅ Add/Edit/Delete employees
- ✅ Manage holidays (CRUD)
- ✅ Manage master data (dropdowns)
- ✅ Manage projects (when implemented)
- ✅ View all reports
- ✅ Manage managers
- ✅ Approve leave requests
- ✅ View all attendance

### Employee
- ✅ View own profile
- ✅ View holidays (read-only)
- ✅ Clock in/out (attendance)
- ✅ View own attendance history
- ✅ Request leave
- ✅ View assigned projects (when implemented)
- ❌ Cannot edit other employees
- ❌ Cannot manage holidays
- ❌ Cannot manage master data
- ❌ Cannot manage projects

### Manager (if managers table is setup)
- ✅ View team members
- ✅ Approve team leave requests
- ✅ View team attendance
- ✅ Manage team projects
- ❌ Cannot manage holidays
- ❌ Cannot manage master data

---

## 🔒 Password Security

### Current Implementation
- Passwords are hashed using PHP's `password_hash()` function
- Uses bcrypt algorithm (PASSWORD_DEFAULT)
- Passwords are never stored in plain text
- Verification uses `password_verify()` function

### Password Requirements
Currently, there are no enforced requirements, but recommended:
- Minimum 8 characters
- Mix of letters and numbers
- Change default passwords in production

### Changing Passwords

**For Admin to change employee password:**
1. Login as admin
2. Go to Employees
3. Edit employee
4. Enter new password
5. Save

**For Employee to change own password:**
Currently not implemented. Coming in next phase.

---

## 🚨 Troubleshooting Login Issues

### "Invalid email or password"
1. **Check if you're using the correct email format**
   - Must be exact match (case-sensitive)
   - Example: `admin@hrms.com` not `Admin@hrms.com`

2. **Verify password is set**
   - Run: `SELECT email, CASE WHEN password IS NOT NULL THEN 'Set' ELSE 'Not Set' END FROM employees;`
   - If "Not Set", run `setup_employee_passwords.php`

3. **Check user status**
   - User must have status = 'active'
   - Run: `SELECT email, status FROM employees WHERE email = 'your@email.com';`

4. **Clear browser cache**
   - Sometimes old session data causes issues
   - Try incognito/private mode

### "Connection failed"
- Check if MySQL is running in XAMPP
- Verify port is 3307 (check config.php)
- Verify database name is 'hrms_db'

### Employee can't see holidays
- Make sure you're logged in as employee (not admin)
- Check if holidays exist in database
- Verify `employee_holidays.php` file exists

---

## 📝 Adding New Users

### Add New Employee (via Admin Panel)
1. Login as admin
2. Navigate to: Employees → Add Employee
3. Fill in all required fields
4. System auto-generates 8-character password
5. **IMPORTANT:** Copy the password shown (it won't be shown again!)
6. Share credentials with employee securely

### Add New Admin (via SQL)
```sql
INSERT INTO admins (name, email, password, status) 
VALUES ('New Admin', 'newadmin@hrms.com', '$2y$10$[HASH]', 'active');
```
Generate hash using:
```php
<?php echo password_hash('your_password', PASSWORD_DEFAULT); ?>
```

### Add New Manager (via Admin Panel)
1. Login as admin
2. Navigate to: Managers → Add Manager
3. Fill in details
4. System auto-generates password
5. Copy and share credentials

---

## 🔄 Password Reset (Future Feature)

Currently, password reset is not implemented. Planned for future release:
- Forgot password link on login page
- Email-based password reset
- Security questions
- Admin can reset employee passwords

**Current Workaround:**
Admin can manually update password in database or via edit employee page.

---

## 📊 Quick Reference Table

| User Type | Email | Password | Access Level |
|-----------|-------|----------|--------------|
| Admin | admin@hrms.com | admin123 | Full Access |
| Employee 1 | john.doe@company.com | employee123 | Read-Only (most) |
| Employee 2 | jane.smith@company.com | employee123 | Read-Only (most) |
| Employee 3 | mike.johnson@company.com | employee123 | Read-Only (most) |

---

## ⚠️ Security Recommendations

### For Development
- ✅ Current setup is fine for development/testing
- ✅ Passwords are properly hashed
- ✅ SQL injection protection via prepared statements

### For Production
- 🔒 Change all default passwords
- 🔒 Enforce strong password policy
- 🔒 Implement password expiry
- 🔒 Add 2FA (Two-Factor Authentication)
- 🔒 Implement account lockout after failed attempts
- 🔒 Add password reset functionality
- 🔒 Use HTTPS only
- 🔒 Regular security audits

---

**Last Updated:** 2026-02-20  
**Version:** 1.0  
**Status:** Development/Testing
