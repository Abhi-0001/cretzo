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

firebase.initializeApp(config);

if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    firebase.auth().settings.appVerificationDisabledForTesting = true;
}