</div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/main.js"></script>
    <script>
        // Admin Panel JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.querySelector('.sidebar');

            if (mobileMenuToggle && sidebar) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }

            // Close sidebar on mobile when clicking outside
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 992 && sidebar && !sidebar.contains(e.target) && !mobileMenuToggle?.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            });

            // Auto-hide flash messages
            setTimeout(() => {
                const flashMessages = document.querySelectorAll('.flash-message');
                flashMessages.forEach(message => {
                    message.style.opacity = '0';
                    message.style.transform = 'translateY(-2rem)';
                    setTimeout(() => {
                        message.remove();
                    }, 300);
                });
            }, 5000);

            // Form validation
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;

                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.style.borderColor = 'var(--danger-color)';
                            isValid = false;
                        } else {
                            field.style.borderColor = '';
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                    }
                });
            });

            // Confirm delete actions
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-delete') || e.target.closest('.btn-delete')) {
                    if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>