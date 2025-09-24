<template>
  <div class="statistics-page">
    <h1>📊 Statistiques</h1>
    <div class="page-content">
      <!-- Section des totaux -->
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Bordereaux (historique)</h3>
          <p class="stat-number">{{ totalBordereauxHistorical }}</p>
          <small>Depuis le début</small>
        </div>
        <div class="stat-card">
          <h3>Total Banques (historique)</h3>
          <p class="stat-number">{{ totalBanquesHistorical }}</p>
          <small>Depuis le début</small>
        </div>
        <div class="stat-card">
          <h3>Bordereaux ({{ selectedPeriod }}j)</h3>
          <p class="stat-number">{{ totalBordereauxPeriod }}</p>
          <small>Période récente</small>
        </div>
        <div class="stat-card">
          <h3>Banques ({{ selectedPeriod }}j)</h3>
          <p class="stat-number">{{ totalBanquesPeriod }}</p>
          <small>Période récente</small>
        </div>
      </div>

      <!-- Graphique -->
      <div class="chart-section">
        <h3>Évolution sur {{ selectedPeriod }} jours</h3>
        <StatisticsWidget 
          @data-loaded="handleDataLoaded" 
          @historical-data-loaded="handleHistoricalDataLoaded" 
        />
      </div>

      <!-- Debug info -->
      <div class="debug-info" v-if="debugMode">
        <h4>📋 Informations de débogage :</h4>
        <p>Bordereaux historiques: {{ totalBordereauxHistorical }}</p>
        <p>Banques historiques: {{ totalBanquesHistorical }}</p>
        <p>Bordereaux période: {{ totalBordereauxPeriod }}</p>
        <p>Banques période: {{ totalBanquesPeriod }}</p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import StatisticsWidget from '../components/Statistics.vue';

export default {
  name: 'StatisticsView',
  components: {
    StatisticsWidget
  },
  setup() {
    const totalBordereauxHistorical = ref(0);
    const totalBanquesHistorical = ref(0);
    const totalBordereauxPeriod = ref(0);
    const totalBanquesPeriod = ref(0);
    const selectedPeriod = ref(7);
    const debugMode = ref(true); // Mettre à false en production

    const handleDataLoaded = (data) => {
      console.log('📊 Données de période reçues:', data);
      
      if (data.bordereauData && Array.isArray(data.bordereauData)) {
        totalBordereauxPeriod.value = data.bordereauData.reduce((sum, item) => sum + (parseInt(item.count) || 0), 0);
      }
      
      if (data.banqueData && Array.isArray(data.banqueData)) {
        totalBanquesPeriod.value = data.banqueData.reduce((sum, item) => sum + (parseInt(item.count) || 0), 0);
      }
    };

    const handleHistoricalDataLoaded = (data) => {
      console.log('📊 Données historiques reçues:', data);
      
      if (data.bordereauHistorical && Array.isArray(data.bordereauHistorical)) {
        totalBordereauxHistorical.value = data.bordereauHistorical.length;
      }
      
      if (data.banqueHistorical && Array.isArray(data.banqueHistorical)) {
        totalBanquesHistorical.value = data.banqueHistorical.length;
      }
    };

    return {
      totalBordereauxHistorical,
      totalBanquesHistorical,
      totalBordereauxPeriod,
      totalBanquesPeriod,
      selectedPeriod,
      debugMode,
      handleDataLoaded,
      handleHistoricalDataLoaded
    };
  }
};
</script>

<style scoped>
.statistics-page {
  padding: 20px;
  min-height: 100vh;
  background: #f5f6fa;
}

.page-content {
  display: flex;
  flex-direction: column;
  gap: 30px;
  max-width: 1200px;
  margin: 0 auto;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.stat-card {
  background: white;
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.08);
  text-align: center;
  border-left: 4px solid #3498db;
  transition: transform 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
}

.stat-card h3 {
  margin: 0 0 10px 0;
  color: #2c3e50;
  font-size: 1rem;
  font-weight: 600;
}

.stat-number {
  font-size: 2.5rem;
  font-weight: bold;
  color: #3498db;
  margin: 10px 0;
}

.stat-card small {
  color: #7f8c8d;
  font-size: 0.85rem;
}

.chart-section {
  background: white;
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.chart-section h3 {
  margin: 0 0 20px 0;
  color: #2c3e50;
  font-size: 1.2rem;
  font-weight: 600;
}

.debug-info {
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 8px;
  padding: 15px;
  margin-top: 20px;
}

.debug-info h4 {
  margin: 0 0 10px 0;
  color: #856404;
}

.debug-info p {
  margin: 5px 0;
  font-family: monospace;
  font-size: 12px;
}
</style>