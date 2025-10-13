<template>
  <div id="app" v-if="isAuthenticated">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
      <div class="logo">
        <h2>📋 CoachPro</h2>
      </div>
      <nav class="nav-menu">
        <router-link to="/home" class="nav-item" exact-active-class="router-link-active">
          <span class="icon">🏠</span>
          <span class="text">Accueil</span>
        </router-link>
        <router-link to="/bordereaux" class="nav-item" active-class="router-link-active">
          <span class="icon">📋</span>
          <span class="text">Bordereaux</span>
        </router-link>
        <router-link to="/banque" class="nav-item" active-class="router-link-active">
          <span class="icon">🏦</span>
          <span class="text">Banques</span>
        </router-link>
        <div class="nav-section">Gestion</div>
        <router-link to="/statistiques" class="nav-item" active-class="router-link-active">
          <span class="icon">📊</span>
          <span class="text">Statistiques</span>
        </router-link>
        <router-link to="/departement" class="nav-item" active-class="router-link-active">
          <span class="icon">📖</span>
          <span class="text">Département</span>
        </router-link>
        <router-link to="/search-by-date" class="nav-item" active-class="router-link-active">
          <span class="icon">🔍</span>
          <span class="text">Recherche par date</span>
        </router-link>
        
        <!-- Bouton Déconnexion -->
        <div class="nav-item logout-item" @click="confirmLogout">
          <span class="icon">🚪</span>
          <span class="text">Déconnexion</span>
        </div>
      </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Header -->
      <header class="header">
        <div class="header-left">
          <h1>{{ currentPageTitle }}</h1>
        </div>
        <div class="header-right">
          <div class="user-info">
            <span class="user-name">{{ userEmail || 'Admin' }}</span>
            <div class="user-avatar">👤</div>
          </div>
        </div>
      </header>

      <!-- Dashboard Content -->
      <main class="content">
        <div class="dashboard-grid">
          <!-- Main Content Area -->
          <div class="main-area">
            <router-view v-slot="{ Component }">
              <transition name="fade" mode="out-in">
                <component :is="Component" />
              </transition>
            </router-view>
          </div>

          <!-- Sidebar Widgets -->
          <div class="sidebar-widgets">
            <!-- Next Report Card -->
            <div class="widget-card">
              <h3>📊 Prochain Rapport</h3>
              <div class="widget-content">
                <div class="game-info">
                  <span class="league">Rapport Financier</span>
                  <span class="date">{{ nextReportDate }}</span>
                </div>
              </div>
            </div>

            <!-- Server Status -->
            <div class="widget-card">
              <h3>🖥️ Statut Serveur</h3>
              <div class="widget-content">
                <div class="status-item">
                  <span class="status-dot active"></span>
                  <span>API Bordereaux</span>
                </div>
                <div class="status-item">
                  <span class="status-dot active"></span>
                  <span>API Banques</span>
                </div>
                <div class="status-item">
                  <span class="status-dot active"></span>
                  <span>Base de données</span>
                </div>
                <div class="status-item">
                  <span class="status-dot"></span>
                  <span>Backup Service</span>
                </div>
              </div>
            </div>

            <!-- Statistics -->
            <div class="widget-card">
              <StatisticsWidget />
            </div>

            <!-- Quick Actions -->
            <div class="widget-card">
              <h3>⚡ Actions Rapides</h3>
              <div class="widget-content">
                <router-link to="/bordereaux" class="action-btn">
                  <span class="action-icon">➕</span>
                  Nouveau Bordereau
                </router-link>
                <router-link to="/banque" class="action-btn">
                  <span class="action-icon">🏦</span>
                  Nouvelle Banque
                </router-link>
                <button class="action-btn" @click="generateReport">
                  <span class="action-icon">📊</span>
                  Générer Rapport
                </button>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Modal de confirmation de déconnexion -->
    <div v-if="showLogoutModal" class="modal-overlay" @click="cancelLogout">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>🔐 Confirmation de déconnexion</h3>
        </div>
        <div class="modal-body">
          <p>Êtes-vous sûr de vouloir vous déconnecter ?</p>
          <div class="user-info-modal">
            <div class="user-avatar-modal">👤</div>
            <div class="user-details">
              <strong>{{ userEmail || 'norlandehubery@gmail.com' }}</strong>
              <span>Utilisateur connecté</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-cancel" @click="cancelLogout">
            Annuler
          </button>
          <button class="btn-confirm" @click="confirmLogoutAction">
            Se déconnecter
          </button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Redirection si non authentifié -->
  <div v-else class="redirect-overlay">
    <div class="redirect-content">
      <div class="spinner"></div>
      <p>Vérification de l'authentification...</p>
      <p class="redirect-message">Redirection vers la page de connexion</p>
    </div>
  </div>
</template>

<script>
import { computed, ref, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import StatisticsWidget from '../components/Statistics.vue';

export default {
  name: 'DefaultLayout',
  components: {
    StatisticsWidget,
  },
  setup() {
    const route = useRoute();
    const router = useRouter();

    // État pour le modal de confirmation
    const showLogoutModal = ref(false);

    // Vérification de l'authentification
    const isAuthenticated = computed(() => {
      const auth = localStorage.getItem('isAuthenticated') === 'true';
      return auth;
    });

    const userEmail = computed(() => {
      return localStorage.getItem('userEmail') || 'norlandehubery@gmail.com';
    });

    const currentPageTitle = computed(() => {
      switch (route.name) {
        case 'Home': return 'Tableau de Bord';
        case 'Bordereaux': return 'Gestion des Bordereaux';
        case 'Banque': return 'Gestion des Banques';
        case 'Statistiques': return 'Statistiques';
        case 'Departement': return 'Gestion des Départements';
        case 'SearchByDate': return 'Recherche par Date';
        default: return 'Système de Gestion';
      }
    });

    // Rediriger si non authentifié
    watch(isAuthenticated, (newVal) => {
      if (!newVal) {
        router.push('/login');
      }
    });

    // Vérifier l'authentification au montage
    onMounted(() => {
      if (!isAuthenticated.value) {
        router.push('/login');
      }
    });

    // Afficher la confirmation de déconnexion
    const confirmLogout = () => {
      showLogoutModal.value = true;
    };

    // Annuler la déconnexion
    const cancelLogout = () => {
      showLogoutModal.value = false;
    };

    // Confirmer et exécuter la déconnexion
    const confirmLogoutAction = () => {
      console.log('🚪 Déconnexion confirmée...');
      localStorage.removeItem('isAuthenticated');
      localStorage.removeItem('userEmail');
      localStorage.removeItem('userData');
      showLogoutModal.value = false;
      router.push('/login');
    };

    const generateReport = () => {
      console.log('Génération du rapport...');
    };

    const nextReportDate = ref('');

    const updateNextReportDate = () => {
      const today = new Date();
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      nextReportDate.value = today.toLocaleDateString('fr-FR', options);
    };

    onMounted(() => {
      updateNextReportDate();
      setInterval(updateNextReportDate, 60 * 60 * 1000);
    });

    return {
      isAuthenticated,
      userEmail,
      currentPageTitle,
      generateReport,
      nextReportDate,
      showLogoutModal,
      confirmLogout,
      cancelLogout,
      confirmLogoutAction
    };
  },
};
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f5f6fa;
  color: #2c3e50;
}

#app {
  display: flex;
  min-height: 100vh;
}

/* Overlay de redirection */
.redirect-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.redirect-content {
  text-align: center;
  color: white;
  background: rgba(255, 255, 255, 0.1);
  padding: 40px;
  border-radius: 15px;
  backdrop-filter: blur(10px);
}

.redirect-content p {
  margin: 10px 0;
  font-size: 1.1rem;
}

.redirect-message {
  font-size: 0.9rem !important;
  opacity: 0.8;
}

.spinner {
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid white;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Modal de confirmation de déconnexion */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  animation: fadeIn 0.3s ease;
}

.modal-content {
  background: white;
  border-radius: 15px;
  padding: 0;
  width: 90%;
  max-width: 400px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  animation: slideIn 0.3s ease;
}

.modal-header {
  background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
  color: white;
  padding: 20px;
  border-radius: 15px 15px 0 0;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.2rem;
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-body {
  padding: 25px;
}

.modal-body p {
  margin-bottom: 20px;
  color: #2c3e50;
  font-size: 1rem;
}

.user-info-modal {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 10px;
  border-left: 4px solid #3498db;
}

.user-avatar-modal {
  width: 50px;
  height: 50px;
  background: #3498db;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.user-details {
  display: flex;
  flex-direction: column;
}

.user-details strong {
  color: #2c3e50;
  font-size: 1rem;
}

.user-details span {
  color: #7f8c8d;
  font-size: 0.8rem;
}

.modal-footer {
  padding: 20px;
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  border-top: 1px solid #e1e8ed;
}

.btn-cancel {
  padding: 10px 20px;
  background: #95a5a6;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: background 0.3s ease;
}

.btn-cancel:hover {
  background: #7f8c8d;
}

.btn-confirm {
  padding: 10px 20px;
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 600;
  transition: background 0.3s ease;
}

.btn-confirm:hover {
  background: #c0392b;
}

/* Animations pour le modal */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from { 
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to { 
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Sidebar Styles (fixe à gauche) */
.sidebar {
  width: 250px;
  background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
  color: white;
  padding: 20px 0;
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  z-index: 1000;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

.logo {
  padding: 0 20px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 20px;
}

.logo h2 {
  font-size: 1.5rem;
  font-weight: 600;
}

.nav-menu {
  padding: 0 10px;
}

.nav-section {
  padding: 15px 15px 5px;
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.6);
  text-transform: uppercase;
  font-weight: 600;
}

.nav-item {
  display: flex;
  align-items: center;
  padding: 12px 15px;
  text-decoration: none;
  color: white;
  border-radius: 8px;
  margin: 5px 0;
  transition: all 0.3s ease;
  cursor: pointer;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.1);
}

.nav-item.router-link-active {
  background: rgba(255, 255, 255, 0.2);
  font-weight: 600;
}

.nav-item .icon {
  margin-right: 12px;
  font-size: 1.2rem;
}

.nav-item .text {
  font-size: 0.95rem;
}

/* Bouton déconnexion */
.logout-item {
  margin-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding-top: 15px;
  background: rgba(231, 76, 60, 0.2);
}

.logout-item:hover {
  background: rgba(231, 76, 60, 0.3);
}

/* Main Content Styles (ajusté pour sidebar fixe) */
.main-content {
  flex: 1;
  margin-left: 250px;
  display: flex;
  flex-direction: column;
}

/* Header Styles (fixe en haut) */
.header {
  background: white;
  padding: 20px 30px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: fixed;
  top: 0;
  left: 250px;
  right: 0;
  z-index: 1000;
}

.header-left h1 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2c3e50;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-name {
  font-weight: 500;
}

.user-avatar {
  width: 40px;
  height: 40px;
  background: #3498db;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

/* Content Area (ajusté pour en-tête fixe) */
.content {
  flex: 1;
  padding: 30px;
  margin-top: 80px;
  overflow-y: auto;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 30px;
  max-width: 1400px;
  margin: 0 auto;
}

.main-area {
  background: white;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
  max-height: calc(100vh - 130px);
  overflow-y: auto;
}

/* Widget Styles */
.sidebar-widgets {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.widget-card {
  background: white;
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
}

.widget-card h3 {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 15px;
  color: #2c3e50;
  display: flex;
  align-items: center;
  gap: 8px;
}

.widget-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Game Info */
.game-info {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.league {
  font-weight: 600;
  color: #2c3e50;
}

.date {
  font-size: 0.9rem;
  color: #7f8c8d;
}

/* Status Items */
.status-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #e74c3c;
}

.status-dot.active {
  background: #2ecc71;
}

/* Stat Items */
.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-label {
  font-size: 0.9rem;
  color: #7f8c8d;
}

.stat-value {
  font-weight: 600;
  color: #2c3e50;
}

/* Action Buttons */
.action-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 12px;
  background: #3498db;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.3s ease;
  font-size: 0.9rem;
  text-decoration: none;
  justify-content: center;
}

.action-btn:hover {
  background: #2980b9;
  text-decoration: none;
  color: white;
}

/* Animation de transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
  
  .sidebar {
    width: 200px;
  }
  
  .main-content {
    margin-left: 200px;
  }
  
  .header {
    left: 200px;
  }
}

@media (max-width: 768px) {
  .sidebar {
    width: 60px;
  }
  
  .nav-item .text,
  .logo h2,
  .nav-section {
    display: none;
  }
  
  .nav-item {
    justify-content: center;
    padding: 15px;
  }
  
  .nav-item .icon {
    margin-right: 0;
    font-size: 1.4rem;
  }
  
  .main-content {
    margin-left: 60px;
  }
  
  .header {
    left: 60px;
  }

  .modal-content {
    width: 95%;
    margin: 20px;
  }

  .modal-footer {
    flex-direction: column;
  }

  .btn-cancel,
  .btn-confirm {
    width: 100%;
  }
}
</style>