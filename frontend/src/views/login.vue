<template>
  <div class="login-container">
    <!-- Section gauche avec l'image de fond -->
    <div class="login-left">
      <div class="background-overlay"></div>
      <div class="welcome-content">
        <h1>Bienvenue sur CoachPro</h1>
        <p>Gérez vos bordereaux financiers en toute simplicité</p>
      </div>
    </div>

    <!-- Section droite avec le formulaire -->
    <div class="login-right">
      <div class="login-form-container">
        <!-- En-tête du formulaire -->
        <div class="form-header">
          <h2>Connexion</h2>
          <p>Accédez à votre espace personnel</p>
        </div>

        <!-- Formulaire -->
        <form @submit.prevent="handleLogin" class="login-form">
          <!-- Champ Email -->
          <div class="input-group">
            <label for="email" class="input-label">Adresse Email</label>
            <div class="input-wrapper" :class="{ 'focused': emailFocused }">
              <span class="input-icon">📧</span>
              <input
                type="email"
                id="email"
                v-model="loginData.email"
                class="form-input"
                placeholder="Entrez votre email"
                required
                @focus="emailFocused = true"
                @blur="emailFocused = false"
                ref="emailInput"
              >
              <div class="input-border"></div>
            </div>
          </div>

          <!-- Champ Mot de passe -->
          <div class="input-group">
            <label for="password" class="input-label">Mot de passe</label>
            <div class="input-wrapper" :class="{ 'focused': passwordFocused }">
              <span class="input-icon">🔒</span>
              <input
                :type="showPassword ? 'text' : 'password'"
                id="password"
                v-model="loginData.password"
                class="form-input"
                placeholder="Entrez votre mot de passe"
                required
                @focus="passwordFocused = true"
                @blur="passwordFocused = false"
                ref="passwordInput"
              >
              <button 
                type="button" 
                class="password-toggle"
                @click="showPassword = !showPassword"
                :title="showPassword ? 'Cacher le mot de passe' : 'Afficher le mot de passe'"
              >
                {{ showPassword ? '👁️' : '👁️‍🗨️' }}
              </button>
              <div class="input-border"></div>
            </div>
          </div>

          <!-- Options -->
          <div class="form-options">
            <label class="checkbox-container">
              <input type="checkbox" v-model="rememberMe">
              <span class="checkmark"></span>
              Se souvenir de moi
            </label>
            <a href="#" class="forgot-password">Mot de passe oublié ?</a>
          </div>

          <!-- Bouton de connexion -->
          <button type="submit" class="login-btn" :disabled="loading">
            <span v-if="loading" class="btn-spinner"></span>
            {{ loading ? 'CONNEXION...' : 'SE CONNECTER' }}
          </button>

          <!-- Message d'erreur -->
          <div v-if="errorMessage" class="error-message">
            {{ errorMessage }}
          </div>

          <!-- Lien de démonstration -->
          <div class="demo-section">
            <button type="button" @click="goToDashboard" class="demo-btn">
              🚀 Accéder au tableau de bord (démo)
            </button>
          </div>
        </form>

        <!-- Pied de page -->
        <div class="login-footer">
          <p>&copy; 2024 CoachPro. Tous droits réservés.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

export default {
  name: 'LoginPage',
  setup() {
    const router = useRouter();
    
    const loginData = ref({
      email: '',
      password: ''
    });
    
    const rememberMe = ref(false);
    const loading = ref(false);
    const showPassword = ref(false);
    const emailFocused = ref(false);
    const passwordFocused = ref(false);
    const errorMessage = ref('');

    // Références pour les champs input
    const emailInput = ref(null);
    const passwordInput = ref(null);

    // Charger les identifiants sauvegardés au montage du composant
    onMounted(() => {
      loadSavedCredentials();
      
      // Focus automatique sur le champ email si vide
      if (!loginData.value.email && emailInput.value) {
        emailInput.value.focus();
      }
    });

    // Charger les identifiants depuis le localStorage
    const loadSavedCredentials = () => {
      const savedRememberMe = localStorage.getItem('rememberMe') === 'true';
      const savedEmail = localStorage.getItem('savedEmail');
      const savedPassword = localStorage.getItem('savedPassword');

      if (savedRememberMe && savedEmail) {
        rememberMe.value = true;
        loginData.value.email = savedEmail;
        
        // Pré-remplir le mot de passe si disponible
        if (savedPassword) {
          loginData.value.password = savedPassword;
        }
        
        console.log('🔐 Identifiants chargés depuis la mémoire');
      }
    };

    // Sauvegarder les identifiants dans le localStorage
    const saveCredentials = () => {
      if (rememberMe.value) {
        localStorage.setItem('rememberMe', 'true');
        localStorage.setItem('savedEmail', loginData.value.email);
        localStorage.setItem('savedPassword', loginData.value.password);
        console.log('💾 Identifiants sauvegardés');
      } else {
        // Supprimer les identifiants sauvegardés si "Se souvenir de moi" est décoché
        localStorage.removeItem('rememberMe');
        localStorage.removeItem('savedEmail');
        localStorage.removeItem('savedPassword');
        console.log('🗑️ Identifiants supprimés de la mémoire');
      }
    };

    const handleLogin = async () => {
      // Réinitialiser le message d'erreur
      errorMessage.value = '';

      if (!loginData.value.email || !loginData.value.password) {
        errorMessage.value = 'Veuillez remplir tous les champs';
        return;
      }

      loading.value = true;
      
      try {
        // Appel à l'API PHP réelle
        const response = await fetch('http://localhost/bordereau/backend/login_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            email: loginData.value.email,
            password: loginData.value.password
          })
        });

        const data = await response.json();

        if (data.success) {
          // Authentification réussie
          localStorage.setItem('isAuthenticated', 'true');
          localStorage.setItem('userEmail', loginData.value.email);
          localStorage.setItem('userData', JSON.stringify(data.user));
          
          // Sauvegarder ou supprimer les identifiants selon le choix "Se souvenir de moi"
          saveCredentials();
          
          // Rediriger vers le tableau de bord
          router.push('/home');
        } else {
          // Échec de l'authentification
          errorMessage.value = data.error || 'Email ou mot de passe incorrect';
        }
      } catch (error) {
        console.error('Erreur de connexion:', error);
        errorMessage.value = 'Erreur de connexion au serveur. Vérifiez que le serveur PHP est démarré.';
      } finally {
        loading.value = false;
      }
    };

    const goToDashboard = () => {
      localStorage.setItem('isAuthenticated', 'true');
      localStorage.setItem('userEmail', 'demo@coachpro.com');
      localStorage.setItem('userData', JSON.stringify({
        id: 0,
        email: 'demo@coachpro.com',
        name: 'Utilisateur Démo'
      }));
      router.push('/home');
    };

    return {
      loginData,
      rememberMe,
      loading,
      showPassword,
      emailFocused,
      passwordFocused,
      errorMessage,
      emailInput,
      passwordInput,
      handleLogin,
      goToDashboard
    };
  }
};
</script>

<style scoped>
/* Ajouter box-sizing global pour éviter les problèmes de calcul de taille */
* {
  box-sizing: border-box;
}

.login-container {
  display: flex;
  min-height: 90vh; /* Hauteur minimale pour desktop */
  max-height: 100vh; /* Limite la hauteur à la fenêtre */
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
  overflow: hidden; /* Pas de défilement par défaut */
  border-radius: 20px;
  margin: 10px; /* Réduire la marge pour éviter le débordement */
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
}

/* Effet de fond animé */
.login-container::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
  background-size: 50px 50px;
  animation: floatBackground 20s linear infinite;
  pointer-events: none;
  border-radius: 20px;
}

@keyframes floatBackground {
  0% { transform: translate(0, 0) rotate(0deg); }
  100% { transform: translate(-50px, -50px) rotate(360deg); }
}

/* Section gauche avec fond */
.login-left {
  flex: 1;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
  backdrop-filter: blur(10px);
}

.background-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.2);
}

.welcome-content {
  position: relative;
  z-index: 2;
  color: white;
  text-align: center;
  max-width: 500px;
  animation: slideInLeft 0.8s ease-out;
}

.welcome-content h1 {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 20px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.welcome-content p {
  font-size: 1.2rem;
  opacity: 0.9;
  line-height: 1.6;
}

/* Section droite avec formulaire */
.login-right {
  flex: 1;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
  position: relative;
  animation: slideInRight 0.8s ease-out;
}

.login-form-container {
  width: 100%;
  max-width: 400px;
  position: relative;
  z-index: 2;
}

.form-header {
  text-align: center;
  margin-bottom: 40px;
  animation: fadeInUp 0.6s ease-out 0.2s both;
}

.form-header h2 {
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 10px;
}

.form-header p {
  color: #7f8c8d;
  font-size: 1rem;
}

/* Formulaire */
.login-form {
  width: 100%;
  animation: fadeInUp 0.6s ease-out 0.4s both;
}

.input-group {
  margin-bottom: 25px;
}

.input-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  border-radius: 12px;
  background: #f8f9fa;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.input-wrapper.focused {
  background: white;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  transform: translateY(-2px);
}

.input-icon {
  position: absolute;
  left: 15px;
  font-size: 1.1rem;
  color: #7f8c8d;
  z-index: 2;
  transition: color 0.3s ease;
}

.input-wrapper.focused .input-icon {
  color: #667eea;
}

.form-input {
  width: 100%;
  padding: 15px 15px 15px 45px;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: transparent;
  font-family: inherit;
  outline: none;
  color: #2c3e50;
}

.form-input::placeholder {
  color: #aab7c4;
}

.input-border {
  position: absolute;
  bottom: -2px;
  left: 0;
  width: 0;
  height: 2px;
  background: #667eea;
  transition: width 0.3s ease;
  border-radius: 2px;
}

.input-wrapper.focused .input-border {
  width: 100%;
}

.password-toggle {
  position: absolute;
  right: 15px;
  background: none;
  border: none;
  font-size: 1.1rem;
  cursor: pointer;
  padding: 5px;
  color: #7f8c8d;
  transition: all 0.3s ease;
  border-radius: 4px;
}

.password-toggle:hover {
  color: #667eea;
  background: rgba(102, 126, 234, 0.1);
}

/* Options du formulaire */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  font-size: 0.9rem;
  animation: fadeInUp 0.6s ease-out 0.6s both;
}

.checkbox-container {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  color: #5a6c7d;
  font-weight: 500;
  transition: color 0.3s ease;
}

.checkbox-container:hover {
  color: #667eea;
}

.checkbox-container input {
  display: none;
}

.checkmark {
  width: 18px;
  height: 18px;
  border: 2px solid #ddd;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  background: white;
}

.checkbox-container input:checked + .checkmark {
  background: #667eea;
  border-color: #667eea;
  transform: scale(1.1);
}

.checkbox-container input:checked + .checkmark::after {
  content: '✓';
  color: white;
  font-size: 12px;
  font-weight: bold;
}

.forgot-password {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
  padding: 4px 8px;
  border-radius: 4px;
}

.forgot-password:hover {
  color: #5a6fd8;
  text-decoration: underline;
  background: rgba(102, 126, 234, 0.1);
}

/* Bouton de connexion */
.login-btn {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 1px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 20px;
  position: relative;
  overflow: hidden;
  animation: fadeInUp 0.6s ease-out 0.8s both;
}

.login-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s;
}

.login-btn:hover:not(:disabled)::before {
  left: 100%;
}

.login-btn:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

.login-btn:active:not(:disabled) {
  transform: translateY(-1px);
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.btn-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid transparent;
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Message d'erreur */
.error-message {
  background: #fee;
  color: #c33;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 20px;
  text-align: center;
  border: 1px solid #fcc;
  animation: fadeInUp 0.6s ease-out;
}

/* Section démo */
.demo-section {
  text-align: center;
  margin-bottom: 30px;
  animation: fadeInUp 0.6s ease-out 1s both;
}

.demo-btn {
  width: 100%;
  padding: 14px;
  background: #27ae60;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
  position: relative;
  overflow: hidden;
}

.demo-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s;
}

.demo-btn:hover::before {
  left: 100%;
}

.demo-btn:hover {
  background: #219a52;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(39, 174, 96, 0.3);
}

/* Pied de page */
.login-footer {
  text-align: center;
  padding-top: 20px;
  border-top: 1px solid #e1e8ed;
  color: #7f8c8d;
  font-size: 0.8rem;
  animation: fadeInUp 0.6s ease-out 1.2s both;
}

/* Animations */
@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-50px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(50px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive Design - Améliorations */
@media (max-width: 1200px) {
  .login-left {
    padding: 30px;
  }
  .welcome-content h1 {
    font-size: 2.2rem;
  }
  .welcome-content p {
    font-size: 1.1rem;
  }
  .login-right {
    padding: 30px;
  }
}

@media (max-width: 1024px) {
  .login-left {
    padding: 25px;
  }
  .welcome-content h1 {
    font-size: 2rem;
  }
  .welcome-content p {
    font-size: 1rem;
  }
  .login-right {
    padding: 25px;
  }
  .login-form-container {
    max-width: 350px;
  }
  .form-header h2 {
    font-size: 1.8rem;
  }
}

@media (max-width: 768px) {
  .login-container {
    flex-direction: column;
    height: auto;
    min-height: auto;
    margin: 5px;
    overflow: hidden;
  }
  .login-left {
    min-height: 100px;
    padding: 10px;
    width: 100%;
  }
  .login-right {
    padding: 10px;
    width: 100%;
  }
  .login-form-container {
    max-width: 100%;
    padding: 0 8px;
  }
  .form-header {
    margin-bottom: 20px;
  }
  .form-options {
    flex-direction: column;
    gap: 6px;
    align-items: flex-start;
    margin-bottom: 15px;
  }
  .login-btn, .demo-btn {
    font-size: 0.85rem;
    padding: 10px;
    margin-bottom: 10px;
  }
  .login-container::before {
    animation: none;
    background: none;
  }
}

@media (max-width: 480px) {
  .login-left {
    min-height: 80px;
    padding: 8px;
  }
  .welcome-content h1 {
    font-size: 1.3rem;
    margin-bottom: 8px;
  }
  .welcome-content p {
    font-size: 0.75rem;
    line-height: 1.4;
  }
  .login-form-container {
    padding: 0 5px;
  }
  .form-header {
    margin-bottom: 15px;
  }
  .form-header h2 {
    font-size: 1.3rem;
  }
  .form-header p {
    font-size: 0.8rem;
  }
  .input-group {
    margin-bottom: 10px;
  }
  .input-label {
    font-size: 0.8rem;
    margin-bottom: 5px;
  }
  .form-input {
    font-size: 0.85rem;
    padding: 8px 8px 8px 30px;
  }
  .input-icon {
    left: 8px;
    font-size: 0.85rem;
  }
  .password-toggle {
    right: 8px;
    font-size: 0.85rem;
  }
  .login-btn, .demo-btn {
    padding: 8px;
    font-size: 0.8rem;
  }
  .login-footer {
    font-size: 0.65rem;
    padding-top: 8px;
  }
  .login-container::before {
    background-size: 20px 20px;
  }
}
</style>