/**
 * KOMS - Karate Organization Management System
 * Global JavaScript Library: Micro-interactions, live search, and utilities
 */

document.addEventListener('DOMContentLoaded', () => {
    // Auto dismiss bootstrap alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Password confirmation validation in real-time
    const passInput = document.querySelector('input[name="password"]');
    const confirmInput = document.querySelector('input[name="confirm_password"]');
    if (passInput && confirmInput) {
        function validatePasswordMatch() {
            if (confirmInput.value && passInput.value !== confirmInput.value) {
                confirmInput.setCustomValidity("Passwords do not match");
                confirmInput.classList.add('is-invalid');
            } else {
                confirmInput.setCustomValidity("");
                confirmInput.classList.remove('is-invalid');
            }
        }
        passInput.addEventListener('input', validatePasswordMatch);
        confirmInput.addEventListener('input', validatePasswordMatch);
    }

    // Generic table search helper if table has class .table-searchable
    const tableSearch = document.getElementById('tableSearchInput');
    if (tableSearch) {
        tableSearch.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.table-searchable tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }

    // Smooth hover effect for cards
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transition = 'transform 0.25s ease, box-shadow 0.25s ease';
        });
    });
});
