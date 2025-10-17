@echo off
REM ===========================================================
REM 🚀 Script de déploiement local pour le projet Bordereau
REM ===========================================================

echo.
echo ===========================================================
echo 🔧 DÉPLOIEMENT LOCAL EN COURS...
echo ===========================================================
echo.

REM Étape 1 : aller dans le dossier frontend
cd frontend

REM Étape 2 : exécuter la construction Vue
echo 🧱 Construction du projet Vue...
npm run build

REM Vérifier si le build a réussi
if %errorlevel% neq 0 (
    echo ❌ Erreur lors du build Vue.js !
    pause
    exit /b
)

REM Étape 3 : suppression des anciens fichiers du déploiement
echo 🧹 Nettoyage de l'ancien déploiement...
cd ..
rmdir /s /q "C:\wamp64\www\bordereau\js"
rmdir /s /q "C:\wamp64\www\bordereau\css"
del /q "C:\wamp64\www\bordereau\index.html"
del /q "C:\wamp64\www\bordereau\favicon.ico"

REM Étape 4 : copie du nouveau build
echo 📦 Copie du nouveau build...
xcopy "C:\wamp64\www\bordereau\frontend\dist" "C:\wamp64\www\bordereau" /E /H /C /I /Y

echo.
echo ✅ Déploiement terminé avec succès !
echo Ouvre ton navigateur et visite : http://localhost/bordereau/
echo.

pause