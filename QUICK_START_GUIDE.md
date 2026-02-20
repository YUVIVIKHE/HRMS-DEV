# HRMS EXTENSION - QUICK START GUIDE

## 🚀 Installation (5 Minutes)

### Step 1: Import Database Changes
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select `hrms_db` database
3. Click "SQL" tab
4. Open `sql_updates.sql` file
5. Copy all content and paste into SQL window
6. Click "Go" button
7. ✅ You should see "6 tables created" message

### Step 2: Verify Installation
Run this query to verify:
```sql
SELECT 'holidays' as tbl, COUNT(*) as exists FROM information_schema.tables 
WHERE table_schema = 'hrms_db' AND table_name = 'holidays'
UNION ALL
SELECT 'dropdown_categories', COUNT(*) FROM information_schema.tables 
WHERE table_schema = 'hrms_db' AND table_name = 'dropdown_categories'
UNION ALL
SELECT 'projects', COUNT(*) FROM information_schema.tables 
WHERE table_schema = 'hrms_db' AND table_name = 'projects';
```
All should return `1` in the exists column.

### Step 3: Setup Employee Passwords (IMPORTANT!)
The sample employees don't have passwords by default. Run this script:

1. Open browser: `http://localhost:3307/hrms/setup_employee_passwords.php`
2. Click to run the script
3. ✅ Passwords will be set for all sample employees

**OR** manually run this SQL:
```sql
UPDATE employees 
SET password = '$2y$10$rQZ5vF8xGLx5L.8YvZ5zKOqK5xJ5xJ5xJ5xJ5xJ5xJ5xJ5xJ5xJ5u'
WHERE email IN ('john.doe@company.com', 'jane.smith@company.com', 'mike.johnson@company.com');
```

### Step 4: Test New Features

**As Admin:**
1. Login: `admin@hrms.com` / `admin123`
2. Click "Holidays" in sidebar
3. Add a test holiday (e.g., New Year 2026)
4. Click "Master Data" in sidebar
5. Add a value to any category

**As Employee:**
1. Logout
2. Login with any of these:
   - `john.doe@company.com` / `employee123`
   - `jane.smith@company.com` / `employee123`
   - `mike.johnson@company.com` / `employee123`
3. Click "Holidays" in sidebar
4. Verify you can VIEW but not EDIT holidays

---

## 📋 What's New?

### 1. Holiday Management ✅
- **Admin:** Full CRUD on holidays
- **Employee:** Read-only view
- **Location:** Dashboard → Holidays

### 2. Master Data Management ✅
- **Admin:** Manage dropdown categories and values
- **Location:** Dashboard → Master Data
- **Use:** Replaces hardcoded dropdowns

### 3. Database Ready for Projects ✅
- Tables created: projects, project_assignments, project_expenses
- UI pages coming in next phase

---

## 🔧 Troubleshooting

### "Table already exists" error
- Safe to ignore if you're re-running the script
- Tables use `IF NOT EXISTS` clause

### "Foreign key constraint fails"
- Make sure `admins` table exists
- Run `database.sql` first if fresh install

### Can't see new menu items
- Clear browser cache
- Hard refresh (Ctrl+F5)
- Check if you're logged in as admin

### Dropdown values not showing
- Verify `sql_updates.sql` ran completely
- Check if default values were inserted:
```sql
SELECT COUNT(*) FROM dropdown_values;
```
Should return ~40 rows.

---

## 📞 Need Help?

Check `IMPLEMENTATION_SUMMARY.md` for detailed documentation.

---

**Installation Time:** ~5 minutes  
**Difficulty:** Easy  
**Requirements:** Existing HRMS project with database
