<template>
  <div id="app">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
      <div class="logo">
        <h2>📋 Finance</h2>
      </div>
      <nav class="nav-menu">
        <router-link to="/" class="nav-item" exact-active-class="router-link-active">
          <span class="icon">🏠</span>
          <span class="text">Accueil</span>
        </router-link>
        <router-link to="/bordereaux" class="nav-item" active-class="router-link-active">
          <span class="icon">📋</span>
          <span class="text">Bordereaux</span>
        </router-link>
        <router-link to="/dossiers" class="nav-item" active-class="router-link-active">
          <span class="icon">📁</span>
          <span class="text">Dossiers</span>
        </router-link>
        <div class="nav-section">Gestion</div>
        <a href="#" class="nav-item" @click.prevent>
          <span class="icon">📊</span>
          <span class="text">Statistiques</span>
        </a>
        <a href="#" class="nav-item" @click.prevent>
          <span class="icon">💰</span>
          <span class="text">Finance</span>
        </a>
        <a href="#" class="nav-item" @click.prevent>
          <span class="icon">🔄</span>
          <span class="text">Transfers</span>
        </a>
        <a href="#" class="nav-item" @click.prevent>
          <span class="icon">👦</span>
          <span class="text">Youth Academy</span>
        </a>
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
            <span class="user-name">Admin</span>
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
            <!-- Next Game Card -->
            <div class="widget-card">
              <h3>📅 Prochain Match</h3>
              <div class="widget-content">
                <div class="game-info">
                  <span class="league">Serie A</span>
                  <span class="date">11 Novembre 2024</span>
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
              <h3>📈 Statistiques</h3>
              <div class="widget-content">
                <div class="stat-item">
                  <span class="stat-label">Bordereaux actifs</span>
                  <span class="stat-value">24</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Dossiers traités</span>
                  <span class="stat-value">156</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Taux de réussite</span>
                  <span class="stat-value">92%</span>
                </div>
              </div>
            </div>

            <!-- Quick Actions -->
            <div class="widget-card">
              <h3>⚡ Actions Rapides</h3>
              <div class="widget-content">
                <router-link to="/bordereaux" class="action-btn">
                  <span class="action-icon">➕</span>
                  Nouveau Bordereau
                </router-link>
                <button class="action-btn" @click="importDossiers">
                  <span class="action-icon">📁</span>
                  Importer Dossiers
                </button>
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
  </div>
</template>

<script>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

export default {
  name: 'App',
  setup() {
    const route = useRoute();
    
    const currentPageTitle = computed(() => {
      switch (route.name) {
        case 'Home': return 'Tableau de Bord';
        case 'Bordereaux': return 'Gestion des Bordereaux';
        case 'Dossiers': return 'Gestion des Dossiers';
        default: return 'Système de Gestion';
      }
    });

    const importDossiers = () => {
      console.log('Import des dossiers...');
      // Implémentez la logique d'import ici
    };

    const generateReport = () => {
      console.log('Génération du rapport...');
      // Implémentez la logique de rapport ici
    };

    return {
      currentPageTitle,
      importDossiers,
      generateReport
    };
  }
}
</script>

<style>
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

/* Sidebar Styles */
.sidebar {
  width: 250px;
  background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
  color: white;
  padding: 20px 0;
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

/* Main Content Styles */
.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.header {
  background: white;
  padding: 20px 30px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
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

/* Content Area */
.content {
  flex: 1;
  padding: 30px;
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
}
</style>