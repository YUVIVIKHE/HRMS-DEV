// Toggle compose form modal
function toggleComposeForm() {
    const modal = document.getElementById('composeModal');
    modal.classList.toggle('active');
}

// Toggle department field based on target audience
function toggleDepartment() {
    const targetAudience = document.getElementById('targetAudience').value;
    const departmentSelect = document.getElementById('departmentSelect');
    
    if (targetAudience === 'department') {
        departmentSelect.style.display = 'block';
        departmentSelect.required = true;
    } else {
        departmentSelect.style.display = 'none';
        departmentSelect.required = false;
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('composeModal');
        if (modal && modal.classList.contains('active')) {
            toggleComposeForm();
        }
    }
});

// Auto-dismiss alerts after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 3000);
    });
});

// Add fadeOut animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }
`;
document.head.appendChild(style);
