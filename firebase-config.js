var config = {
	apiKey: "AIzaSyAQSGCSRuirZbLRdphAVuibXWL91WTprHI",
	authDomain: "cretzo-bcb9e.firebaseapp.com",
	databaseURL: "databaseURL",
	projectId: "cretzo-bcb9e",
	storageBucket: "cretzo-bcb9e.firebasestorage.app",
	messagingSenderId: "779321578408",
    appId: "1:779321578408:web:fc4689d0a812b7ef5a839d",
    measurementId: "G-FZ09D33LC4",
};

if (typeof firebase !== 'undefined') {
    if (!firebase.apps || !firebase.apps.length) {
        firebase.initializeApp(config);
    }

    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        try {
            firebase.auth().settings.appVerificationDisabledForTesting = true;
        } catch (e) { /* auth SDK not present on this page */ }
    }
}