// Firebase Authentication Handler for Cretzo

// Safely define base_url and CSRF parameters if not defined (useful for standalone pages)
if (typeof base_url === 'undefined') {
  var base_url = window.location.origin + '/cretzo/';
}
if (typeof csrfName === 'undefined') {
  var csrfName = 'ci_csrf_token';
}
if (typeof csrfHash === 'undefined') {
  var csrfHash = '';
}

// Handle Redirect Sign-in Result
firebase.auth().getRedirectResult()
  .then((result) => {
    if (result && result.user) {
      const user = result.user;
      console.log('Redirect Sign-in Success:', user);
      
      // Save user data to localStorage
      localStorage.setItem('user_uid', user.uid);
      localStorage.setItem('user_email', user.email);
      localStorage.setItem('user_name', user.displayName || user.email);
      localStorage.setItem('user_photo', user.photoURL);

      // Notify server...
      try {
        var postData = {};
        postData['provider'] = (result.credential && result.credential.providerId.indexOf('facebook') !== -1) ? 'facebook' : 'google';
        postData['uid'] = user.uid;
        postData['name'] = user.displayName || '';
        postData['email'] = user.email || '';
        postData['photo'] = user.photoURL || '';
        postData[csrfName] = csrfHash;

        fetch(base_url + 'auth/social_login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams(postData)
        }).then(function(res){ return res.json(); }).then(function(sres){
          if (sres && sres.error === false) {
            window.location.href = 'dashboard.php';
          }
        }).catch(function(err){ console.error(err); });
      } catch(e) { console.error(e); }

      showAlert('success', 'Login successful! Redirecting...');
      setTimeout(() => {
        window.location.href = 'dashboard.php';
      }, 1000);
    }
  })
  .catch((error) => {
    console.error('Redirect Sign-in Error:', error);
  });

// Initialize Firebase Auth State Listener
firebase.auth().onAuthStateChanged((user) => {
  if (user) {
    console.log('User is signed in:', user);
    localStorage.setItem('user_uid', user.uid);
    localStorage.setItem('user_email', user.email);
    localStorage.setItem('user_name', user.displayName || user.email);
    
    // Redirect to dashboard after successful login
    if (window.location.pathname.includes('login.php') || window.location.pathname.includes('signup.php')) {
      window.location.href = 'dashboard.php';
    }
  } else {
    console.log('User is signed out');
    localStorage.removeItem('user_uid');
    localStorage.removeItem('user_email');
    localStorage.removeItem('user_name');
  }
});

// Facebook Login Function
function facebookLogin() {
  const provider = new firebase.auth.FacebookAuthProvider();
  
  // Set Facebook App ID from PHP constant (if available via data attribute)
  // Or use the provider as-is, Facebook SDK will handle it
  
  firebase.auth()
    .signInWithPopup(provider)
    .then((result) => {
      const user = result.user;
      console.log('Facebook Login Success:', user);
      
      // Save user data to localStorage
      localStorage.setItem('user_uid', user.uid);
      localStorage.setItem('user_email', user.email);
      localStorage.setItem('user_name', user.displayName);
      localStorage.setItem('user_photo', user.photoURL);

      // Notify server to create or login account (prevents duplicate accounts)
      try {
        var postData = {};
        postData['provider'] = 'facebook';
        postData['uid'] = user.uid;
        postData['name'] = user.displayName || '';
        postData['email'] = user.email || '';
        postData['photo'] = user.photoURL || '';
        postData[csrfName] = csrfHash;

        fetch(base_url + 'auth/social_login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams(postData)
        }).then(function(res){ return res.json(); }).then(function(sres){
          if (sres && sres.error === false) {
            // redirect to dashboard
            setTimeout(function () { window.location.href = 'dashboard.php'; }, 800);
          } else {
            console.warn('Server social login failed', sres);
          }
        }).catch(function(err){ console.error('social_login request failed', err); });
      } catch (e) { console.error(e); }
      
      // Show success message
      showAlert('success', 'Login successful! Redirecting...');
      
      // Redirect to dashboard
      setTimeout(() => {
        window.location.href = 'dashboard.php';
      }, 1500);
    })
    .catch((error) => {
      console.error('Facebook Login Error:', error);
      // Fallback to redirect if popup is blocked or closed
      if (error && (error.code === 'auth/popup-blocked' || error.code === 'auth/popup-closed-by-user')) {
        firebase.auth().signInWithRedirect(provider);
        return;
      }
      showAlert('error', 'Facebook login failed: ' + error.message);
    });
}

// Google Login Function
function googleLogin() {
  const provider = new firebase.auth.GoogleAuthProvider();
  
  firebase.auth()
    .signInWithPopup(provider)
    .then((result) => {
      const user = result.user;
      console.log('Google Login Success:', user);
      
      // Save user data to localStorage
      localStorage.setItem('user_uid', user.uid);
      localStorage.setItem('user_email', user.email);
      localStorage.setItem('user_name', user.displayName);
      localStorage.setItem('user_photo', user.photoURL);
      
      // Show success message
      showAlert('success', 'Login successful! Redirecting...');
      
      // Redirect to dashboard
      setTimeout(() => {
        window.location.href = 'dashboard.php';
      }, 1500);
    })
    .catch((error) => {
      console.error('Google Login Error:', error);
      // Fallback to redirect if popup is blocked or closed
      if (error && (error.code === 'auth/popup-blocked' || error.code === 'auth/popup-closed-by-user')) {
        firebase.auth().signInWithRedirect(provider);
        return;
      }
      showAlert('error', 'Google login failed: ' + error.message);
    });
}

// Email/Password Sign Up
function signUp(name, email, phone, password, passwordConfirm) {
  // Validate inputs
  if (!name || !email || !phone || !password || !passwordConfirm) {
    showAlert('error', 'All fields are required');
    return;
  }
  
  if (password !== passwordConfirm) {
    showAlert('error', 'Passwords do not match');
    return;
  }
  
  if (password.length < 6) {
    showAlert('error', 'Password must be at least 6 characters');
    return;
  }
  
  // Create user account
  firebase.auth()
    .createUserWithEmailAndPassword(email, password)
    .then((userCredential) => {
      const user = userCredential.user;
      
      // Update user profile
      user.updateProfile({
        displayName: name,
        photoURL: null
      }).then(() => {
        // Save additional user data
        localStorage.setItem('user_uid', user.uid);
        localStorage.setItem('user_email', user.email);
        localStorage.setItem('user_name', name);
        localStorage.setItem('user_phone', phone);
        
        showAlert('success', 'Account created successfully! Redirecting...');
        
        setTimeout(() => {
          window.location.href = 'dashboard.php';
        }, 1500);
      });
    })
    .catch((error) => {
      console.error('Sign Up Error:', error);
      showAlert('error', 'Sign up failed: ' + error.message);
    });
}

// Email/Password Login
function emailLogin(email, password) {
  // Validate inputs
  if (!email || !password) {
    showAlert('error', 'Email and password are required');
    return;
  }
  
  firebase.auth()
    .signInWithEmailAndPassword(email, password)
    .then((userCredential) => {
      const user = userCredential.user;
      console.log('Email Login Success:', user);
      
      showAlert('success', 'Login successful! Redirecting...');
      
      setTimeout(() => {
        window.location.href = 'dashboard.php';
      }, 1500);
    })
    .catch((error) => {
      console.error('Login Error:', error);
      showAlert('error', 'Login failed: ' + error.message);
    });
}

// Logout Function
function logoutUser() {
  firebase.auth()
    .signOut()
    .then(() => {
      localStorage.removeItem('user_uid');
      localStorage.removeItem('user_email');
      localStorage.removeItem('user_name');
      localStorage.removeItem('user_photo');
      localStorage.removeItem('user_phone');
      
      console.log('User logged out');
      window.location.href = 'login.php';
    })
    .catch((error) => {
      console.error('Logout Error:', error);
      showAlert('error', 'Logout failed: ' + error.message);
    });
}

// Check if user is authenticated
function isUserLoggedIn() {
  return !!localStorage.getItem('user_uid');
}

// Get current user data
function getCurrentUser() {
  return {
    uid: localStorage.getItem('user_uid'),
    email: localStorage.getItem('user_email'),
    name: localStorage.getItem('user_name'),
    photo: localStorage.getItem('user_photo'),
    phone: localStorage.getItem('user_phone')
  };
}

// Show Alert Messages
function showAlert(type, message) {
  // Create alert element
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
  alertDiv.setAttribute('role', 'alert');
  alertDiv.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;
  
  // Insert at top of body or a designated alert container
  const container = document.querySelector('#alert-container') || document.body;
  container.insertBefore(alertDiv, container.firstChild);
  
  // Auto-dismiss after 5 seconds
  setTimeout(() => {
    alertDiv.remove();
  }, 5000);
}

// DOM Ready - Attach Event Listeners
document.addEventListener('DOMContentLoaded', function() {
  // Facebook Login Button
  const facebookBtn = document.getElementById('facebook-login-btn');
  if (facebookBtn) {
    facebookBtn.addEventListener('click', function(e) {
      e.preventDefault();
      facebookLogin();
    });
  }
  
  // Google Login Button  
  const googleBtn = document.getElementById('google-login-btn');
  if (googleBtn) {
    googleBtn.addEventListener('click', function(e) {
      e.preventDefault();
      googleLogin();
    });
  }
  
  // Email Login Form
  const loginForm = document.getElementById('email-login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const email = document.getElementById('login-email').value;
      const password = document.getElementById('login-password').value;
      emailLogin(email, password);
    });
  }
  
  // Sign Up Form
  const signupForm = document.getElementById('signup-form');
  if (signupForm) {
    signupForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const name = document.getElementById('signup-name').value;
      const email = document.getElementById('signup-email').value;
      const phone = document.getElementById('signup-phone').value;
      const password = document.getElementById('signup-password').value;
      const passwordConfirm = document.getElementById('signup-password-confirm').value;
      signUp(name, email, phone, password, passwordConfirm);
    });
  }
  
  // Logout Button
  const logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function(e) {
      e.preventDefault();
      logoutUser();
    });
  }
});
