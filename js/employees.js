// Filter employees function (Search + Dropdowns)
function filterEmployees() {
    const searchInput = document.getElementById('searchInput');
    const deptFilter = document.getElementById('deptFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    const searchText = searchInput.value.toLowerCase();
    const deptValue = deptFilter.value.toLowerCase();
    const statusValue = statusFilter.value.toLowerCase();
    
    const table = document.getElementById('employeesTable');
    const rows = table.getElementsByTagName('tr');
    
    // Loop through all table rows, and hide those who don't match the search query
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        
        // Columns indices (based on the updated table structure):
        // 0: ID, 1: Name, 2: Email, 3: Phone, 4: Department, 5: Job Title, 6: Designation, 7: Status, 8: Actions
        const nameDate = cells[1] ? (cells[1].textContent || cells[1].innerText).toLowerCase() : '';
        const emailData = cells[2] ? (cells[2].textContent || cells[2].innerText).toLowerCase() : '';
        const deptData = cells[4] ? (cells[4].textContent || cells[4].innerText).toLowerCase() : '';
        const statusData = cells[7] ? (cells[7].textContent || cells[7].innerText).toLowerCase() : '';
        
        let matchesSearch = true;
        let matchesDept = true;
        let matchesStatus = true;
        
        // Check Search Text (Name or Email)
        if (searchText && nameDate.indexOf(searchText) === -1 && emailData.indexOf(searchText) === -1) {
            matchesSearch = false;
        }
        
        // Check Department
        if (deptValue && deptData !== deptValue) {
            matchesDept = false;
        }
        
        // Check Status
        if (statusValue && statusData !== statusValue) {
            // Simple string comparison might fail if status has extra whitespace or casing
            // Using includes since status contains badge text
             if (statusData.indexOf(statusValue) === -1) {
                 matchesStatus = false;
             }
        }
        
        if (matchesSearch && matchesDept && matchesStatus) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    }
}

// Toggle bulk upload section
function toggleBulkUpload() {
    const section = document.getElementById('bulkUploadSection');
    section.classList.toggle('active');
    
    // Scroll to section if opening
    if (section.classList.contains('active')) {
        setTimeout(() => {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }
}

// Handle file selection
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        document.getElementById('fileSelected').style.display = 'flex';
        document.querySelector('.upload-placeholder').style.display = 'none';
        document.getElementById('fileName').textContent = file.name;
    }
}

// Remove selected file
function removeFile() {
    document.getElementById('csvFile').value = '';
    document.getElementById('fileSelected').style.display = 'none';
    document.querySelector('.upload-placeholder').style.display = 'block';
}

// Drag and drop functionality
const fileUploadArea = document.getElementById('fileUploadArea');

if (fileUploadArea) {
    fileUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#0078D4';
        this.style.background = '#f9fafb';
    });

    fileUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '#d1d5db';
        this.style.background = 'transparent';
    });

    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#d1d5db';
        this.style.background = 'transparent';
        
        const file = e.dataTransfer.files[0];
        if (file && file.name.endsWith('.csv')) {
            const input = document.getElementById('csvFile');
            input.files = e.dataTransfer.files;
            handleFileSelect({ target: input });
        }
    });
}

// Add animation on page load
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.employees-table tbody tr');
    rows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease-out';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, index * 50);
    });
});
