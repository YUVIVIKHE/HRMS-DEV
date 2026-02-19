let currentStep = 1;
const totalSteps = 5;

// Initialize form
document.addEventListener('DOMContentLoaded', function() {
    showStep(currentStep);
    updateButtons();
});

// Change step function
function changeStep(direction) {
    // Validate current step before moving forward
    if (direction === 1 && !validateStep(currentStep)) {
        return;
    }
    
    // Hide current step
    const currentStepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);
    currentStepElement.classList.remove('active');
    
    // Update current step
    currentStep += direction;
    
    // Show new step
    showStep(currentStep);
    updateButtons();
    updateProgressBar();
    
    // Scroll to top of form
    document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Show specific step
function showStep(step) {
    const stepElement = document.querySelector(`.form-step[data-step="${step}"]`);
    if (stepElement) {
        stepElement.classList.add('active');
    }
}

// Update button visibility
function updateButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    // Show/hide previous button
    if (currentStep === 1) {
        prevBtn.style.display = 'none';
    } else {
        prevBtn.style.display = 'flex';
    }
    
    // Show/hide next and submit buttons
    if (currentStep === totalSteps) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'flex';
    } else {
        nextBtn.style.display = 'flex';
        submitBtn.style.display = 'none';
    }
}

// Update progress bar
function updateProgressBar() {
    // Update active step
    document.querySelectorAll('.progress-step').forEach((step, index) => {
        const stepNumber = index + 1;
        
        if (stepNumber < currentStep) {
            step.classList.add('completed');
            step.classList.remove('active');
        } else if (stepNumber === currentStep) {
            step.classList.add('active');
            step.classList.remove('completed');
        } else {
            step.classList.remove('active', 'completed');
        }
    });
}

// Validate current step
function validateStep(step) {
    const stepElement = document.querySelector(`.form-step[data-step="${step}"]`);
    const requiredInputs = stepElement.querySelectorAll('[required]');
    let isValid = true;
    
    requiredInputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.style.borderColor = '#ef4444';
            
            // Remove error styling after user starts typing
            input.addEventListener('input', function() {
                this.style.borderColor = '#e0e0e0';
            }, { once: true });
        }
    });
    
    if (!isValid) {
        // Show error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-error';
        errorDiv.innerHTML = `
            <svg class="alert-icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
            </svg>
            Please fill in all required fields
        `;
        
        // Remove existing error if any
        const existingError = stepElement.querySelector('.alert-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Insert error at the top of the step
        stepElement.insertBefore(errorDiv, stepElement.firstChild);
        
        // Remove error after 3 seconds
        setTimeout(() => {
            errorDiv.remove();
        }, 3000);
    }
    
    return isValid;
}

// Copy address function
function copyAddress() {
    const checkbox = document.getElementById('sameAsAbove');
    
    if (checkbox.checked) {
        document.querySelector('[name="perm_address_line1"]').value = document.querySelector('[name="address_line1"]').value;
        document.querySelector('[name="perm_address_line2"]').value = document.querySelector('[name="address_line2"]').value;
        document.querySelector('[name="perm_city"]').value = document.querySelector('[name="city"]').value;
        document.querySelector('[name="perm_state"]').value = document.querySelector('[name="state"]').value;
        document.querySelector('[name="perm_zip_code"]').value = document.querySelector('[name="zip_code"]').value;
    } else {
        document.querySelector('[name="perm_address_line1"]').value = '';
        document.querySelector('[name="perm_address_line2"]').value = '';
        document.querySelector('[name="perm_city"]').value = '';
        document.querySelector('[name="perm_state"]').value = '';
        document.querySelector('[name="perm_zip_code"]').value = '';
    }
}

// Add input animations
document.querySelectorAll('.form-input').forEach(input => {
    input.addEventListener('focus', function() {
        this.style.transform = 'scale(1.01)';
    });
    
    input.addEventListener('blur', function() {
        this.style.transform = 'scale(1)';
    });
});

// Form submission
document.getElementById('employeeForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <svg class="animate-spin" style="width: 20px; height: 20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Submitting...</span>
    `;
});

// Add CSS for spinner animation
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .animate-spin {
        animation: spin 1s linear infinite;
    }
`;
document.head.appendChild(style);

// Copy password function
function copyPassword(password) {
    navigator.clipboard.writeText(password).then(function() {
        alert('Password copied to clipboard!');
    }, function(err) {
        console.error('Could not copy password: ', err);
    });
}

// Allow clicking on progress steps to navigate (optional)
document.querySelectorAll('.progress-step').forEach((step, index) => {
    step.addEventListener('click', function() {
        const targetStep = index + 1;
        
        // Only allow going back or to completed steps
        if (targetStep < currentStep) {
            // Hide current step
            document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.remove('active');
            
            // Update current step
            currentStep = targetStep;
            
            // Show target step
            showStep(currentStep);
            updateButtons();
            updateProgressBar();
            
            // Scroll to top
            document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
    
    // Add cursor pointer for clickable steps
    if (index < currentStep - 1) {
        step.style.cursor = 'pointer';
    }
});
