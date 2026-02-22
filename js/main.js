// Main JavaScript File

// Export to PDF function
function exportToPDF(elementId, filename) {
    const element = document.getElementById(elementId);
    const opt = {
        margin: 10,
        filename: filename + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    // Show loading
    showLoading();
    
    html2pdf().set(opt).from(element).save().then(function() {
        hideLoading();
    });
}

// Print function
function printReport(elementId) {
    const printContents = document.getElementById(elementId).innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    location.reload();
}

// Show loading spinner
function showLoading() {
    const overlay = document.createElement('div');
    overlay.className = 'spinner-overlay';
    overlay.id = 'loadingOverlay';
    overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
    document.body.appendChild(overlay);
}

// Hide loading spinner
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.remove();
    }
}

// Confirm delete
function confirmDelete(url, name) {
    if (confirm('ئایا دڵنیایت لە سڕینەوەی "' + name + '"؟')) {
        window.location.href = url;
    }
}

// Calculate total price
function calculateTotal() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
    const total = quantity * unitPrice;
    document.getElementById('total_price').value = total.toFixed(2);
}

// Age calculator
function calculateAgeFromDate(birthDate) {
    const birth = new Date(birthDate);
    const now = new Date();
    const diffTime = Math.abs(now - birth);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    const diffMonths = Math.floor(diffDays / 30);
    
    return {
        days: diffDays,
        months: diffMonths
    };
}

// Update age fields
function updateAge() {
    const birthDateInput = document.getElementById('birth_date');
    const ageInput = document.getElementById('age_months');
    
    if (birthDateInput && ageInput) {
        const age = calculateAgeFromDate(birthDateInput.value);
        ageInput.value = age.months;
    }
}

// Generate random code
function generateCode(prefix) {
    const date = new Date();
    const dateStr = date.getFullYear().toString() + 
                    (date.getMonth() + 1).toString().padStart(2, '0') + 
                    date.getDate().toString().padStart(2, '0');
    const random = Math.floor(Math.random() * 9000) + 1000;
    return prefix + '-' + dateStr + '-' + random;
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Number formatting
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Initialize Charts
function initChart(canvasId, type, labels, data, label, colors) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    
    new Chart(ctx, {
        type: type,
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: data,
                backgroundColor: colors || [
                    'rgba(52, 152, 219, 0.8)',
                    'rgba(46, 204, 113, 0.8)',
                    'rgba(241, 196, 15, 0.8)',
                    'rgba(231, 76, 60, 0.8)',
                    'rgba(155, 89, 182, 0.8)',
                    'rgba(52, 73, 94, 0.8)'
                ],
                borderColor: colors || [
                    'rgba(52, 152, 219, 1)',
                    'rgba(46, 204, 113, 1)',
                    'rgba(241, 196, 15, 1)',
                    'rgba(231, 76, 60, 1)',
                    'rgba(155, 89, 182, 1)',
                    'rgba(52, 73, 94, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            family: "'Noto Sans Arabic', sans-serif"
                        }
                    }
                }
            },
            scales: type === 'bar' || type === 'line' ? {
                y: {
                    beginAtZero: true
                }
            } : {}
        }
    });
}

// Document Ready
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate codes for new records
    const codeInputs = document.querySelectorAll('.auto-code');
    codeInputs.forEach(function(input) {
        if (!input.value) {
            const prefix = input.dataset.prefix || 'CODE';
            input.value = generateCode(prefix);
        }
    });
    
    // Birth date change handler
    const birthDateInput = document.getElementById('birth_date');
    if (birthDateInput) {
        birthDateInput.addEventListener('change', updateAge);
    }
    
    // Quantity and price change handlers
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unit_price');
    
    if (quantityInput) {
        quantityInput.addEventListener('input', calculateTotal);
    }
    if (unitPriceInput) {
        unitPriceInput.addEventListener('input', calculateTotal);
    }
    
    // Remove invalid class on input
    document.querySelectorAll('.form-control').forEach(function(input) {
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
    
    // Fade in animations
    document.querySelectorAll('.card, .stat-card').forEach(function(element, index) {
        element.style.animationDelay = (index * 0.1) + 's';
        element.classList.add('fade-in');
    });
});

// Date range filter
function filterByDateRange(startDate, endDate) {
    const url = new URL(window.location.href);
    url.searchParams.set('start_date', startDate);
    url.searchParams.set('end_date', endDate);
    window.location.href = url.toString();
}

// Reset filters
function resetFilters() {
    const url = new URL(window.location.href);
    url.search = '';
    window.location.href = url.toString();
}

// AJAX helper function
function ajaxRequest(url, method, data, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                callback(null, JSON.parse(xhr.responseText));
            } else {
                callback(new Error('Request failed'), null);
            }
        }
    };
    xhr.send(JSON.stringify(data));
}
