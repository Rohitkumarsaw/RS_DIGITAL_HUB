// RS Digital Hub - Admin Panel JavaScript

// Theme Toggle (Admin)
(function() {
    const savedTheme = localStorage.getItem('theme') || 'dark';

    function updateIcon(theme) {
        const toggle = document.getElementById('themeToggle');
        if (!toggle) return;
        if (theme === 'light') {
            toggle.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
        } else {
            toggle.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateIcon(savedTheme);

        const toggle = document.getElementById('themeToggle');
        if (toggle) {
            toggle.addEventListener('click', function() {
                const current = document.documentElement.getAttribute('data-theme');
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                updateIcon(next);
            });
        }

        // Sidebar Toggle
        initSidebar();
    });
})();

// Sidebar Toggle (Mobile)
function initSidebar() {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const closeBtn = document.getElementById('sidebarClose');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('active');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (toggle) toggle.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Close sidebar on nav link click (mobile)
    if (sidebar) {
        sidebar.querySelectorAll('.admin-sidebar-nav a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 1024) closeSidebar();
            });
        });
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSidebar();
    });
}

// Confirm Delete
function confirmDelete(message) {
    showConfirm(message || 'Are you sure you want to delete this?', function(){ return true; });
}

// Password Toggle (Show/Hide) - Auto-attach to ALL .password-toggle buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.password-toggle').forEach(function(btn) {
        if (btn.dataset.listenerAttached) return;
        btn.dataset.listenerAttached = 'true';

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var input = null;

            var targetId = this.getAttribute('data-target');
            if (targetId) {
                input = document.getElementById(targetId);
            }

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
});

// Toast Notification Popup (Admin)
function initToast() {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const type = container.dataset.type || 'info';
    const message = container.dataset.message || '';

    container.remove();

    if (!message) return;

    showToast(message, type);
}

function showToast(message, type) {
    document.querySelectorAll('.toast-popup').forEach(function(t) { t.remove(); });

    var icons = {
        success: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        error: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        warning: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
    };

    var toast = document.createElement('div');
    toast.className = 'toast-popup toast-' + (type === 'error' ? 'danger' : type);

    var iconDiv = document.createElement('div');
    iconDiv.className = 'toast-popup-icon';
    iconDiv.innerHTML = icons[type] || icons.info;

    var msgDiv = document.createElement('div');
    msgDiv.className = 'toast-popup-msg';
    msgDiv.textContent = message;

    var closeBtn = document.createElement('button');
    closeBtn.className = 'toast-popup-close';
    closeBtn.innerHTML = '&times;';
    closeBtn.addEventListener('click', function() { this.parentElement.remove(); });

    toast.appendChild(iconDiv);
    toast.appendChild(msgDiv);
    toast.appendChild(closeBtn);
    document.body.appendChild(toast);

    setTimeout(function() {
        if (toast.parentNode) {
            toast.classList.add('toast-hiding');
            setTimeout(function() { if (toast.parentNode) toast.remove(); }, 300);
        }
    }, 4000);
}
