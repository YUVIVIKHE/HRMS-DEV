@echo off
echo Running database update...
"C:\xampp\mysql\bin\mysql.exe" -u root -p hrms_db < update_database.sql
echo.
echo Database updated successfully!
pause
