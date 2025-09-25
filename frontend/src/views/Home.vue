<template>
  <div class="home-page">
    <div class="welcome-section">
      <h2>Bienvenue dans CoachPro</h2>
      <p>Gestion complète des bordereaux</p>
    </div>

    <div class="stats-grid" v-if="!loading && !error">
      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-info">
          <h3>{{ stats.bordereauxCount }}</h3>
          <p>Bordereaux Actifs</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-info">
          <h3>{{ stats.successRate }}%</h3>
          <p>Taux de Réussite</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">⚡</div>
        <div class="stat-info">
          <h3>{{ stats.averageTime }}</h3>
          <p>Temps Moyen</p>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-section">
      <div class="spinner"></div>
      <p>Chargement des statistiques...</p>
    </div>

    <div v-if="error" class="error-section">
      <div class="error-icon">❌</div>
      <p>{{ error }}</p>
      <button @click="loadStats" class="btn-retry">Réessayer</button>
    </div>

    <div class="recent-activity">
      <h3>Activité Récente</h3>
      <div class="activity-list">
        <div class="activity-item" v-for="(activity, index) in recentActivities" :key="index">
          <span class="activity-icon">{{ activity.icon }}</span>
          <div class="activity-content">
            <p>{{ activity.text }}</p>
            <span class="activity-time">{{ activity.time }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { bordereauService } from '../services/api';

export default {
  name: 'HomeView',
  data() {
    return {
      loading: true,
      error: null,
      stats: {
        bordereauxCount: 0,
        successRate: 0,
        averageTime: '0h',
      },
      recentActivities: [
        { icon: '➕', text: 'Nouveau bordereau créé', time: 'Il y a 2 minutes' },
        { icon: '📊', text: 'Rapport mensuel généré', time: 'Il y a 1 heure' },
        { icon: '✅', text: 'Bordereau #456 approuvé', time: 'Il y a 3 heures' },
      ],
    };
  },
  async mounted() {
    await this.loadStats();
  },
  methods: {
    async loadStats() {
      try {
        this.loading = true;
        this.error = null;

        // Récupérer les bordereaux
        const bordereaux = await bordereauService.getBordereaux();
        this.stats.bordereauxCount = bordereaux.length;

        // Calculs fictifs (à ajuster selon tes besoins réels)
        this.stats.successRate = 92; // Exemple statique
        this.stats.averageTime = '2.4h'; // Exemple statique
      } catch (error) {
        console.error('Erreur dans loadStats:', error);
        this.error = error.message || 'Erreur lors du chargement des statistiques';
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.home-page {
  padding: 20px; 
  max-width: 1200px;
  margin: 0 auto;
}

.welcome-section {
  text-align: center;
  margin-bottom: 40px;
}

.welcome-section h2 {
  font-size: 2rem;
  color: #2c3e50;
  margin-bottom: 10px;
}

.welcome-section p {
  color: #7f8c8d;
  font-size: 1.1rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 40px;
}

.stat-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 25px;
  border-radius: 15px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.stat-icon {
  font-size: 2.5rem;
}

.stat-info h3 {
  font-size: 2rem;
  font-weight: 600;
  margin-bottom: 5px;
}

.stat-info p {
  opacity: 0.9;
  font-size: 0.9rem;
}

.loading-section {
  text-align: center;
  padding: 20px;
}

.spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  animation: spin 1s linear infinite;
  margin: 0 auto 10px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-section {
  text-align: center;
  padding: 20px;
  color: #e74c3c;
}

.error-icon {
  font-size: 2rem;
  margin-bottom: 10px;
}

.btn-retry {
  background: #3498db;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 5px;
  cursor: pointer;
}

.btn-retry:hover {
  background: #2980b9;
}

.recent-activity h3 {
  margin-bottom: 20px;
  color: #2c3e50;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 10px;
  border-left: 4px solid #3498db;
}

.activity-icon {
  font-size: 1.5rem;
}

.activity-content p {
  font-weight: 500;
  margin-bottom: 5px;
}

.activity-time {
  font-size: 0.8rem;
  color: #7f8c8d;
}

/* Responsive */
@media (max-width: 768px) {
  .home-page {
    padding: 15px;
  }

  .welcome-section h2 {
    font-size: 1.5rem;
  }

  .welcome-section p {
    font-size: 1rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .stat-card {
    padding: 15px;
  }

  .stat-icon {
    font-size: 2rem;
  }

  .stat-info h3 {
    font-size: 1.5rem;
  }

  .stat-info p {
    font-size: 0.8rem;
  }

  .activity-item {
    padding: 10px;
  }

  .activity-icon {
    font-size: 1.2rem;
  }

  .activity-content p {
    font-size: 0.9rem;
  }

  .activity-time {
    font-size: 0.7rem;
  }
}

@media (max-width: 480px) {
  .home-page {
    padding: 10px;
  }

  .welcome-section h2 {
    font-size: 1.2rem;
  }

  .welcome-section p {
    font-size: 0.9rem;
  }

  .stat-card {
    flex-direction: column;
    text-align: center;
    padding: 10px;
  }

  .stat-icon {
    font-size: 1.8rem;
  }

  .stat-info h3 {
    font-size: 1.2rem;
  }

  .stat-info p {
    font-size: 0.7rem;
  }

  .activity-item {
    flex-direction: column;
    text-align: center;
    padding: 8px;
  }

  .activity-icon {
    font-size: 1rem;
  }

  .activity-content p {
    font-size: 0.8rem;
  }

  .activity-time {
    font-size: 0.6rem;
  }
}
</style>