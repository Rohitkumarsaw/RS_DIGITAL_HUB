// RS Digital Hub - Premium Dark/Light Theme JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Theme Toggle
    initTheme();

    // Mobile Menu
    initMobileMenu();

    // Search Toggle
    initSearchToggle();

    // FAQ Accordion
    initFAQ();

    // Form Validation
    initFormValidation();

    // Image Gallery
    initGallery();

    // Coupon Apply
    initCoupon();

    // Password Toggle
    initPasswordToggle();

    // Password Strength
    initPasswordStrength();
});

// Theme Toggle (Dark/Light)
function initTheme() {
    const toggle = document.getElementById('themeToggle');
    const savedTheme = localStorage.getItem('theme') || 'dark';

    updateThemeIcon(savedTheme);

    if (toggle) {
        toggle.addEventListener('click', function() {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';

            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon(next);
        });
    }
}

function updateThemeIcon(theme) {
    const toggle = document.getElementById('themeToggle');
    if (!toggle) return;

    if (theme === 'light') {
        // Sun icon for light mode
        toggle.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
    } else {
        // Moon icon for dark mode
        toggle.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
    }
}

// Mobile Menu
function initMobileMenu() {
    const toggle = document.getElementById('menuToggle');
    const menu = document.getElementById('mobileMenu');

    if (toggle && menu) {
        toggle.addEventListener('click', function() {
            menu.classList.toggle('active');
            const isOpen = menu.classList.contains('active');
            document.body.style.overflow = isOpen ? 'hidden' : '';
            toggle.innerHTML = isOpen
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>';
        });

        menu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                menu.classList.remove('active');
                document.body.style.overflow = '';
                toggle.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>';
            });
        });
    }
}

// Search Toggle (Mobile)
function initSearchToggle() {
    const toggle = document.getElementById('searchToggle');
    const search = document.getElementById('mobileSearch');

    if (toggle && search) {
        toggle.addEventListener('click', function() {
            search.classList.toggle('active');
            if (search.classList.contains('active')) {
                search.querySelector('input').focus();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && search.classList.contains('active')) {
                search.classList.remove('active');
            }
        });

        // Close search when clicking outside
        document.addEventListener('click', function(e) {
            if (search.classList.contains('active') && !search.contains(e.target) && e.target !== toggle && !toggle.contains(e.target)) {
                search.classList.remove('active');
            }
        });
    }
}

// FAQ Accordion
function initFAQ() {
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', function() {
            const item = this.parentElement;
            const isActive = item.classList.contains('active');

            document.querySelectorAll('.faq-item').forEach(faq => {
                faq.classList.remove('active');
            });

            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
}

// Flash Messages as Toast Popups
function initFlashMessages() {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const type = container.dataset.type || 'info';
    const message = container.dataset.message || '';

    // Remove the data container
    container.remove();

    if (!message) return;

    showToast(message, type);
}

// Toast Notification Popup
function showToast(message, type) {
    // Remove any existing toasts
    document.querySelectorAll('.toast-popup').forEach(t => t.remove());

    const icons = {
        success: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        error: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        warning: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
    };

    const toast = document.createElement('div');
    toast.className = 'toast-popup toast-' + (type === 'error' ? 'danger' : type);

    const iconDiv = document.createElement('div');
    iconDiv.className = 'toast-popup-icon';
    iconDiv.innerHTML = icons[type] || icons.info;

    const msgDiv = document.createElement('div');
    msgDiv.className = 'toast-popup-msg';
    msgDiv.textContent = message;

    const closeBtn = document.createElement('button');
    closeBtn.className = 'toast-popup-close';
    closeBtn.innerHTML = '&times;';
    closeBtn.addEventListener('click', function() { this.parentElement.remove(); });

    toast.appendChild(iconDiv);
    toast.appendChild(msgDiv);
    toast.appendChild(closeBtn);
    document.body.appendChild(toast);

    // Auto-dismiss
    setTimeout(() => {
        if (toast.parentNode) {
            toast.classList.add('toast-hiding');
            setTimeout(() => { if (toast.parentNode) toast.remove(); }, 300);
        }
    }, 4000);
}

// showNotification uses the new toast (backward compat)
var showNotification = showToast;

// Form Validation
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const required = form.querySelectorAll('[required]');

            required.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = 'var(--danger)';
                    input.style.boxShadow = '0 0 0 3px var(--danger-glow)';
                } else {
                    input.style.borderColor = '';
                    input.style.boxShadow = '';
                }
            });

            const emailInputs = form.querySelectorAll('input[type="email"]');
            emailInputs.forEach(input => {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (input.value && !emailRegex.test(input.value)) {
                    isValid = false;
                    input.style.borderColor = 'var(--danger)';
                }
            });

            const password = form.querySelector('#password');
            const confirmPassword = form.querySelector('#confirm_password');
            if (password && confirmPassword) {
                if (password.value !== confirmPassword.value) {
                    isValid = false;
                    confirmPassword.style.borderColor = 'var(--danger)';
                }
            }

            if (!isValid) e.preventDefault();
        });
    });
}

// Image Gallery
function initGallery() {
    const thumbs = document.querySelectorAll('.product-gallery-thumbs img');
    const mainImage = document.querySelector('.product-gallery-main img');

    if (thumbs.length && mainImage) {
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', function() {
                thumbs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                mainImage.src = this.src;
            });
        });
    }
}

// Coupon Apply (AJAX - no page reload)
function initCoupon() {
    // Handle remove coupon on page load (when coupon already applied)
    const removeCouponBtn = document.getElementById('removeCoupon');
    if (removeCouponBtn) {
        removeCouponBtn.addEventListener('click', function() {
            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            csrfToken = csrfToken ? csrfToken.getAttribute('content') : '';
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=remove_coupon&csrf_token=' + encodeURIComponent(csrfToken)
            })
            .then(function(res) { return res.json(); })
            .then(function() {
                location.reload();
            });
        });
        return;
    }

    const applyCouponBtn = document.getElementById('applyCoupon');
    if (!applyCouponBtn) return;

    // Prevent any form submission from the coupon form
    const couponForm = applyCouponBtn.closest('form');
    if (couponForm) {
        couponForm.addEventListener('submit', function(e) {
            if (document.activeElement === applyCouponBtn || e.submitter === applyCouponBtn) {
                e.preventDefault();
                applyCouponBtn.click();
            }
        });
    }

    applyCouponBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const codeInput = document.getElementById('couponCode');
        const code = codeInput ? codeInput.value.trim() : '';
        if (!code) return;

        applyCouponBtn.disabled = true;
        applyCouponBtn.textContent = 'Applying...';

        // Remove any existing error message
        const existingError = document.getElementById('couponError');
        if (existingError) existingError.remove();

        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        csrfToken = csrfToken ? csrfToken.getAttribute('content') : '';
        var productId = document.querySelector('input[name="product_id"]');
        productId = productId ? productId.value : '';
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=apply_coupon&code=' + encodeURIComponent(code) + '&csrf_token=' + encodeURIComponent(csrfToken) + '&product_id=' + encodeURIComponent(productId)
        })
        .then(function(res) {
            if (!res.ok) throw new Error('Network error');
            return res.json();
        })
        .then(function(data) {
            applyCouponBtn.disabled = false;
            applyCouponBtn.textContent = 'Apply';

            if (data.success) {
                // Hide coupon input row
                const inputRow = applyCouponBtn.parentElement;
                if (inputRow) inputRow.style.display = 'none';

                // Show applied coupon badge
                const badgeHtml = '<div class="mt-3" id="couponAppliedBadge"><span class="badge badge-success">' + escapeHtml(code) + ' applied</span><a href="javascript:void(0)" id="removeCouponLink" style="margin-left:0.5rem;font-size:0.85rem;color:var(--danger)">Remove</a></div>';
                inputRow.insertAdjacentHTML('afterend', badgeHtml);

                // Add discount row
                const couponSection = applyCouponBtn.closest('.cart-summary') || applyCouponBtn.parentElement;
                const summaryRows = couponSection.querySelectorAll('.cart-summary-row');
                const discountHtml = '<div class="cart-summary-row" style="color:var(--success)" id="discountRow"><span>Discount (' + escapeHtml(code) + ')</span><span>-' + data.discountFormatted + '</span></div>';
                const totalRow = summaryRows[summaryRows.length - 1];
                if (totalRow) totalRow.insertAdjacentHTML('beforebegin', discountHtml);

                // Update total
                if (data.newTotalFormatted) {
                    const totalSpan = totalRow ? totalRow.querySelectorAll('span')[1] : null;
                    if (totalSpan) totalSpan.textContent = data.newTotalFormatted;
                }

                // Attach remove coupon handler
                const removeLink = document.getElementById('removeCouponLink');
                if (removeLink) {
                    removeLink.addEventListener('click', function() {
                        var csrfToken = document.querySelector('meta[name="csrf-token"]');
                        csrfToken = csrfToken ? csrfToken.getAttribute('content') : '';
                        fetch(window.location.href, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'action=remove_coupon&csrf_token=' + encodeURIComponent(csrfToken)
                        })
                        .then(function(res) { return res.json(); })
                        .then(function() {
                            location.reload();
                        });
                    });
                }
            } else {
                const errorMsg = document.createElement('div');
                errorMsg.id = 'couponError';
                errorMsg.className = 'alert alert-danger mt-2';
                errorMsg.style.fontSize = '0.85rem';
                errorMsg.style.padding = '0.5rem';
                errorMsg.textContent = data.message || 'Invalid coupon code';
                applyCouponBtn.parentElement.after(errorMsg);
                setTimeout(function() { if (errorMsg.parentNode) errorMsg.remove(); }, 4000);
            }
        })
        .catch(function(err) {
            console.error('Coupon error:', err);
            applyCouponBtn.disabled = false;
            applyCouponBtn.textContent = 'Apply';
            // Fallback: submit form normally
            const form = applyCouponBtn.closest('form');
            if (form) {
                const hiddenAction = document.createElement('input');
                hiddenAction.type = 'hidden';
                hiddenAction.name = 'action';
                hiddenAction.value = 'apply_coupon';
                const hiddenCode = document.createElement('input');
                hiddenCode.type = 'hidden';
                hiddenCode.name = 'code';
                hiddenCode.value = code;
                form.appendChild(hiddenAction);
                form.appendChild(hiddenCode);
                form.submit();
            }
        });
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// Confirm Delete
function confirmDelete(message) {
    showConfirm(message || 'Are you sure you want to delete this?', function(){ return true; });
}

// Copy to Clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copied to clipboard!', 'success');
    });
}

// Password Toggle (Show/Hide) - Auto-attach to ALL .password-toggle buttons
function initPasswordToggle() {
    document.querySelectorAll('.password-toggle').forEach(function(btn) {
        // Skip if already has a listener attached via this init
        if (btn.dataset.listenerAttached) return;
        btn.dataset.listenerAttached = 'true';

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var input = null;

            // Try data-target first
            var targetId = this.getAttribute('data-target');
            if (targetId) {
                input = document.getElementById(targetId);
            }

            // Fallback: find input within the same .password-wrapper
            if (!input) {
                var wrapper = this.closest('.password-wrapper');
                if (wrapper) {
                    input = wrapper.querySelector('input[type="password"], input[type="text"]');
                }
            }

            if (!input) return;

            var eyeIcon = this.querySelector('.icon-eye');
            var eyeOffIcon = this.querySelector('.icon-eye-off');

            if (input.type === 'password') {
                input.type = 'text';
                if (eyeIcon) eyeIcon.style.display = 'none';
                if (eyeOffIcon) eyeOffIcon.style.display = '';
            } else {
                input.type = 'password';
                if (eyeIcon) eyeIcon.style.display = '';
                if (eyeOffIcon) eyeOffIcon.style.display = 'none';
            }
        });
    });
}

// Password Strength Validator
function initPasswordStrength() {
    const checkSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
    const circleSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';
    const xSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

    document.querySelectorAll('#password, #new_password').forEach(passwordInput => {
        const reqContainer = document.getElementById('passwordRequirements');
        if (!reqContainer) return;

        const confirmInput = document.getElementById('confirm_password');
        const matchMsg = document.getElementById('passwordMatch');

        passwordInput.addEventListener('focus', function() {
            reqContainer.style.display = 'block';
        });

        passwordInput.addEventListener('input', function() {
            const val = this.value;

            const checks = {
                length: val.length >= 8,
                uppercase: /[A-Z]/.test(val),
                lowercase: /[a-z]/.test(val),
                number: /[0-9]/.test(val),
                special: /[^A-Za-z0-9]/.test(val)
            };

            reqContainer.querySelectorAll('.req-item').forEach(item => {
                const req = item.dataset.req;
                const isValid = checks[req];

                item.classList.toggle('valid', isValid);
                item.classList.toggle('invalid', !isValid && val.length > 0);

                if (isValid) {
                    item.querySelector('.req-icon').outerHTML = checkSvg;
                } else if (val.length > 0) {
                    item.querySelector('.req-icon').outerHTML = xSvg;
                } else {
                    item.querySelector('.req-icon').outerHTML = circleSvg;
                }
            });

            if (confirmInput && confirmInput.value) {
                checkPasswordMatch(passwordInput, confirmInput, matchMsg, checkSvg, xSvg);
            }
        });

        if (confirmInput) {
            confirmInput.addEventListener('input', function() {
                checkPasswordMatch(passwordInput, confirmInput, matchMsg, checkSvg, xSvg);
            });
        }
    });
}

function checkPasswordMatch(passwordInput, confirmInput, matchMsg, checkSvg, xSvg) {
    if (!matchMsg) return;
    matchMsg.style.display = 'block';

    if (confirmInput.value === '') {
        matchMsg.style.display = 'none';
        return;
    }

    if (passwordInput.value === confirmInput.value) {
        matchMsg.className = 'password-match-msg match';
        matchMsg.innerHTML = checkSvg + ' Passwords match';
    } else {
        matchMsg.className = 'password-match-msg mismatch';
        matchMsg.innerHTML = xSvg + ' Passwords do not match';
    }
}
