<template>
  <div class="home-page">
    <div class="welcome-section">
      <h2>Bienvenue dans CoachPro</h2>
      <p>Gestion complète des bordereaux et dossiers</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-info">
          <h3>24</h3>
          <p>Bordereaux Actifs</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">📁</div>
        <div class="stat-info">
          <h3>156</h3>
          <p>Dossiers Traités</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-info">
          <h3>92%</h3>
          <p>Taux de Réussite</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">⚡</div>
        <div class="stat-info">
          <h3>2.4h</h3>
          <p>Temps Moyen</p>
        </div>
      </div>
    </div>

    <div class="recent-activity">
      <h3>Activité Récente</h3>
      <div class="activity-list">
        <div class="activity-item">
          <span class="activity-icon">➕</span>
          <div class="activity-content">
            <p>Nouveau bordereau créé</p>
            <span class="activity-time">Il y a 2 minutes</span>
          </div>
        </div>
        <div class="activity-item">
          <span class="activity-icon">📊</span>
          <div class="activity-content">
            <p>Rapport mensuel généré</p>
            <span class="activity-time">Il y a 1 heure</span>
          </div>
        </div>
        <div class="activity-item">
          <span class="activity-icon">✅</span>
          <div class="activity-content">
            <p>Bordereau #456 approuvé</p>
            <span class="activity-time">Il y a 3 heures</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'HomeView'
}
</script>

<style scoped>
.home-page {
  padding: 20px;
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
</style>