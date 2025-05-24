        // DOM Elements
        const loginForm = document.getElementById('loginForm');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const loginButton = document.getElementById('loginButton');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const errorMessage = document.getElementById('errorMessage');
        const rememberMe = document.getElementById('rememberMe');

        // Toggle password visibility
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });

        // Handle form submission - FIXED: Only ONE event listener
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Hide any previous error messages
            errorMessage.style.display = 'none';
            
            // Get form data
            const username = usernameInput.value.trim();
            const password = passwordInput.value.trim();
            
            // Basic validation
            if (!username || !password) {
                showError('Please fill in all fields.');
                return;
            }
            
            // Show loading state
            showLoading(true);
            
            console.log('Attempting to login with:', { username }); // Don't log password
            
            // API call to PHP backend
            fetch('auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                if (data.success) {
                    // alert(data.message);
                    // Uncomment the line below to redirect to dashboard
                    window.location.href = data.redirect;
                } else {
                    // Show error message
                    showError(data.error || 'Login failed');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showError('Connection error: ' + error.message);
            })
            .finally(() => {
                // Hide loading state
                showLoading(false);
            });
        });

        // Show/hide loading state
        function showLoading(isLoading) {
            if (isLoading) {
                loginButton.disabled = true;
                loadingSpinner.style.display = 'inline-block';
                loginButton.style.opacity = '0.8';
            } else {
                loginButton.disabled = false;
                loadingSpinner.style.display = 'none';
                loginButton.style.opacity = '1';
            }
        }

        // Show error message
        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            
            // Add shake animation
            errorMessage.style.animation = 'shake 0.5s ease-in-out';
            setTimeout(() => {
                errorMessage.style.animation = '';
            }, 500);
        }

        // Load remembered username if exists
        window.addEventListener('load', function() {
            const rememberedUsername = localStorage.getItem('rememberedUsername');
            if (rememberedUsername) {
                usernameInput.value = rememberedUsername;
                rememberMe.checked = true;
            }
        });

        // Save username if remember me is checked
        rememberMe.addEventListener('change', function() {
            if (this.checked && usernameInput.value.trim()) {
                localStorage.setItem('rememberedUsername', usernameInput.value.trim());
            } else {
                localStorage.removeItem('rememberedUsername');
            }
        });

        // Save username when typing if remember me is checked
        usernameInput.addEventListener('input', function() {
            if (rememberMe.checked) {
                localStorage.setItem('rememberedUsername', this.value.trim());
            }
        });

        // Add input validation styling
        function addInputValidation() {
            const inputs = [usernameInput, passwordInput];
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.style.borderColor = '#fc8181';
                    } else {
                        this.style.borderColor = '#48bb78';
                    }
                });
                
                input.addEventListener('focus', function() {
                    this.style.borderColor = '#667eea';
                });
            });
        }

        // Initialize input validation
        addInputValidation();