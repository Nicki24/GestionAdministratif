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

          <!-- Notification personnalisée -->
          <Transition name="notification-slide">
            <div v-if="showNotification" class="custom-notification">
              <div class="notification-header">
                <span class="notification-icon">🔒</span>
                <span class="notification-title">Accès Anticipé</span>
                <button @click="closeNotification" class="notification-close">&times;</button>
              </div>
              <div class="notification-message">
                Veuillez vous connecter avec vos identifiants pour accéder au tableau de bord. 
                La démo est temporairement désactivée pour des raisons de sécurité.
              </div>
              <div class="notification-progress"></div>
            </div>
          </Transition>

          <!-- Bouton démo -->
          <div class="demo-section">
            <button type="button" @click="handleDemoClick" class="demo-btn">
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
import { ref, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { authService } from '@/services/api';

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
    const showNotification = ref(false);

    // Références pour les champs input
    const emailInput = ref(null);
    const passwordInput = ref(null);

    // Charger les identifiants sauvegardés au montage du composant
    onMounted(() => {
      loadSavedCredentials();
      
      // Focus automatique sur le champ email si vide
      if (!loginData.value.email && emailInput.value) {
        nextTick(() => {
          emailInput.value.focus();
        });
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
        localStorage.removeItem('rememberMe');
        localStorage.removeItem('savedEmail');
        localStorage.removeItem('savedPassword');
        console.log('🗑️ Identifiants supprimés de la mémoire');
      }
    };

    const handleLogin = async () => {
      errorMessage.value = '';

      if (!loginData.value.email || !loginData.value.password) {
        errorMessage.value = 'Veuillez remplir tous les champs';
        return;
      }

      loading.value = true;
      
      try {
        const data = await authService.login(loginData.value.email, loginData.value.password);

        if (data.success) {
          localStorage.setItem('isAuthenticated', 'true');
          localStorage.setItem('userEmail', loginData.value.email);
          localStorage.setItem('userData', JSON.stringify(data.user));
          saveCredentials();
          router.push('/home');
        } else {
          errorMessage.value = data.error || 'Email ou mot de passe incorrect';
        }
      } catch (error) {
        console.error('Erreur de connexion:', error);
        errorMessage.value = error.message || 'Erreur de connexion au serveur.';
      } finally {
        loading.value = false;
      }
    };

    const handleDemoClick = () => {
      // Afficher la notification personnalisée
      showNotification.value = true;
      
      // Effet visuel sur le bouton
      const demoBtn = document.querySelector('.demo-btn');
      if (demoBtn) {
        demoBtn.style.transform = 'scale(0.95)';
        setTimeout(() => {
          demoBtn.style.transform = '';
        }, 150);
      }

      // Auto-close après 5 secondes
      setTimeout(() => {
        closeNotification();
      }, 5000);

      console.log('🚫 Tentative d\'accès démo bloquée avec notification personnalisée');
    };

    const closeNotification = () => {
      showNotification.value = false;
    };

    return {
      loginData,
      rememberMe,
      loading,
      showPassword,
      emailFocused,
      passwordFocused,
      errorMessage,
      showNotification,
      emailInput,
      passwordInput,
      handleLogin,
      handleDemoClick,
      closeNotification
    };
  }
};
</script>

<style scoped>
/* Styles existants inchangés */
* {
  box-sizing: border-box;
}

.login-container {
  display: flex;
  min-height: 90vh;
  max-height: 100vh;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
  overflow: hidden;
  border-radius: 20px;
  margin: 10px;
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

/* Section gauche */
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

/* Section droite */
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

/* Formulaire - styles inchangés */
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

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
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

/* Notification personnalisée */
.custom-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  width: 350px;
  max-width: 90vw;
  background: linear-gradient(135deg, #f39c12, #e67e22);
  color: white;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(243, 156, 18, 0.4);
  z-index: 10000;
  overflow: hidden;
  animation: notificationSlideIn 0.3s ease-out;
}

.notification-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px 20px 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.notification-icon {
  font-size: 1.2rem;
  margin-right: 10px;
}

.notification-title {
  font-weight: 600;
  font-size: 1.1rem;
}

.notification-close {
  background: none;
  border: none;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: background 0.2s ease;
}

.notification-close:hover {
  background: rgba(255, 255, 255, 0.2);
}

.notification-message {
  padding: 10px 20px 20px;
  font-size: 0.95rem;
  line-height: 1.5;
}

.notification-progress {
  height: 3px;
  background: rgba(255, 255, 255, 0.3);
  animation: progressBar 5s linear forwards;
}

@keyframes progressBar {
  from { width: 100%; background: rgba(255, 255, 255, 0.8); }
  to { width: 0%; }
}

/* Animations de transition */
.notification-slide-enter-active,
.notification-slide-leave-active {
  transition: all 0.3s ease;
}

.notification-slide-enter-from,
.notification-slide-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

@keyframes notificationSlideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Bouton démo - aspect original */
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

.login-footer {
  text-align: center;
  padding-top: 20px;
  border-top: 1px solid #e1e8ed;
  color: #7f8c8d;
  font-size: 0.8rem;
  animation: fadeInUp 0.6s ease-out 1.2s both;
}

/* Animations existantes */
@keyframes slideInLeft {
  from { opacity: 0; transform: translateX(-50px); }
  to { opacity: 1; transform: translateX(0); }
}

@keyframes slideInRight {
  from { opacity: 0; transform: translateX(50px); }
  to { opacity: 1; transform: translateX(0); }
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Responsive - simplifié */
@media (max-width: 768px) {
  .custom-notification {
    top: 10px;
    right: 10px;
    left: 10px;
    width: auto;
  }
  
  .login-container {
    flex-direction: column;
    margin: 5px;
  }
}
</style>