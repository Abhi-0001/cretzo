# Cretzo Facebook Login - Setup Guide

## ✅ What Has Been Fixed

### 1. **Created Authentication System** (`cretzo/assets/auth.js`)
   - Firebase authentication handler with Facebook & Google login
   - Email/password signup and login functionality
   - User session management via localStorage
   - Alert system for user feedback
   - Auto-redirect after successful login

### 2. **Updated Login Page** (`cretzo/login.php`)
   - Added proper HTML form with email/password inputs
   - Connected Facebook login button with `id="facebook-login-btn"`
   - Connected Google login button with `id="google-login-btn"`
   - Added Firebase scripts initialization
   - Improved UI/UX with proper buttons and styling

### 3. **Updated Signup Page** (`cretzo/signup.php`)
   - Added complete signup form with name, email, phone, password fields
   - Connected social authentication options
   - Added form validation
   - Same Firebase initialization as login page

### 4. **Updated Firebase Configuration** (`firebase-config.js`)
   - Enabled Firebase Auth with proper SDK initialization
   - Added authentication provider settings

### 5. **Updated Header** (`cretzo/header.php`)
   - Added Facebook SDK initialization with App ID: `1541338137599309`
   - Facebook SDK version: v18.0

## 📋 Important Pre-Requirements

### Firebase Console Setup
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project: **cretzo-bcb9e**
3. Go to **Authentication** → **Sign-in method**
4. **Enable Facebook** provider:
   - Click "Facebook"
   - Set app ID as: `1541338137599309`
   - Set app secret (get it from Facebook Developers console)
   - Add authorized redirect URI: Your domain's login page
   - Save

### Facebook Developers Setup
1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Navigate to your app settings
3. Under **Products**, enable **Facebook Login**
4. Go to **Settings** → **Basic** to get:
   - App ID: `1541338137599309` ✓ (Already configured)
   - App Secret: (Add this to Firebase)
5. Add valid OAuth redirect URIs:
   - `https://cretzo-bcb9e.firebaseapp.com/__/auth/handler`
   - Your production domain

### Google Setup (Optional)
1. In Firebase Console → Authentication → Google
2. Enable Google provider
3. Configure OAuth consent screen in Google Cloud Console

## 🧪 Testing

### Test Facebook Login
1. Navigate to `http://localhost/cretzo/login.php` (or `signup.php`)
2. Click "Login with Facebook" button
3. You should see a Facebook popup
4. After successful authentication:
   - User should be redirected to `dashboard.php`
   - User data stored in localStorage

### Test Email/Password Login
1. Go to signup page
2. Create account with:
   - Full Name
   - Email
   - Phone Number
   - Password (min 6 characters)
3. Should redirect to dashboard
4. Go to login page and sign in with credentials

### Debug Console
1. Open browser DevTools (F12)
2. Go to **Console** tab
3. Should see logs like:
   ```
   User is signed in: {...}
   Facebook Login Success: {...}
   ```

## 🐛 Common Issues & Solutions

### Issue: "Facebook popup blocked"
**Solution:** Check browser popup settings for your domain

### Issue: "App Not Set Up" popup from Facebook
**Solution:** 
1. Check Facebook App ID matches Firebase settings
2. Verify app is in Development mode (for testing)
3. Add test users in Facebook App Roles

### Issue: "Invalid OAuth redirect URI"
**Solution:** 
1. In Facebook App, add your domain to Valid OAuth Redirect URIs
2. If testing locally, use: `http://localhost:3000` and `https://cretzo-bcb9e.firebaseapp.com/__/auth/handler`

### Issue: User data not saving to localStorage
**Solution:** 
1. Check if Firebase auth is properly initialized
2. Verify firebase-config.js is loaded before auth.js
3. Check browser console for errors

### Issue: Redirect to dashboard not working
**Solution:**
1. Verify `dashboard.php` exists at `cretzo/dashboard.php`
2. Check browser console for JavaScript errors
3. Verify localStorage is not disabled in browser

## 📱 Environment Variables (For Production)

Create a `.env` file in project root with:
```
FACEBOOK_APP_ID=1541338137599309
FACEBOOK_APP_SECRET=<your_app_secret_here>
FIREBASE_API_KEY=AIzaSyAQSGCSRuirZbLRdphAVuibXWL91WTprHI
FIREBASE_AUTH_DOMAIN=cretzo-bcb9e.firebaseapp.com
FIREBASE_PROJECT_ID=cretzo-bcb9e
```

## 🔐 Security Recommendations

1. **Never expose App Secret** in frontend code
2. **Use HTTPS only** for production
3. **Implement CSRF protection** for form submissions
4. **Validate user input** server-side
5. **Store sensitive data** on backend, not localStorage
6. **Set secure cookies** with HttpOnly flag
7. **Implement rate limiting** on authentication endpoints
8. **Use Firebase Security Rules** to protect data

## 📝 Next Steps

1. ✅ Test the current setup locally
2. ✅ Configure Facebook App properly
3. ✅ Enable Facebook provider in Firebase
4. ✅ Test all authentication flows
5. ✅ Create backend API to store user data
6. ✅ Implement email verification
7. ✅ Add password reset functionality
8. ✅ Deploy to production with HTTPS

## 📞 Support Resources

- Firebase Docs: https://firebase.google.com/docs/auth
- Facebook Login: https://developers.facebook.com/docs/facebook-login
- JavaScript SDK: https://developers.facebook.com/docs/javascript/reference/v18.0

---

**Last Updated:** June 5, 2026
**Project:** Cretzo E-Commerce Platform
