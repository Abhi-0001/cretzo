var config = {
	apiKey: "%APIKEY%",
	authDomain: "%AUTHDOMAIN%",
	databaseURL: "%DATABASEURL%",
	projectId: "%PROJECTID%",
	storageBucket: "%STRORAGEBUCKET%",
	messagingSenderId: "%MESSAGINGSENDERID%",
    appId: "%APPID%",
    measurementId: "%MEASUREMENTID%",
};

firebase.initializeApp(config);

if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    firebase.auth().settings.appVerificationDisabledForTesting = true;
}