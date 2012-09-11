keytool -genkey -keystore myKeyStore -alias me
keytool -selfcert -keystore myKeyStore -alias me
jarsigner -keystore myKeyStore complexTask.jar me
