// Search employees function
function searchEmployees() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('employeesTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length - 1; j++) {
            const cell = cells[j];
            if (cell) {
                const textValue = cell.textContent || cell.innerText;
                if (textValue.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        if (found) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
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
