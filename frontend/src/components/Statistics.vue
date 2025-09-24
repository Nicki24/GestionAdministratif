<template>
  <div class="statistics-widget">
    <div class="period-selector">
      <label for="period">Période : </label>
      <select id="period" v-model="selectedPeriod" @change="updateStats">
        <option value="7">7 jours</option>
        <option value="30">30 jours</option>
        <option value="90">90 jours</option>
      </select>
    </div>
    
    <div v-if="loading" class="loading">Chargement des données...</div>
    <div v-else-if="errorMessage" class="error">Erreur : {{ errorMessage }}</div>
    <div v-else class="chart-container">
      <canvas ref="chartRef" id="myChart"></canvas>
    </div>
  </div>
</template>

<script>
import { onMounted, ref, nextTick } from 'vue';
import { Chart } from 'chart.js/auto';

export default {
  name: 'StatisticsWidget',
  emits: ['data-loaded', 'historical-data-loaded'],
  setup(props, { emit }) {
    const chartRef = ref(null);
    const chartInstance = ref(null);
    const bordereauData = ref([]);
    const banqueData = ref([]);
    const errorMessage = ref('');
    const loading = ref(true);
    const selectedPeriod = ref(7);

    const fetchStats = async (period) => {
      try {
        loading.value = true;
        errorMessage.value = '';
        console.log('📊 Fetching stats with period:', period);
        
        const bordereauUrl = `http://localhost/bordereau/backend/bordereau_api.php?stats=true&period=${period}`;
        const banqueUrl = `http://localhost/bordereau/backend/banque_api.php?stats=true&period=${period}`;
        
        // URLs pour les totaux historiques
        const bordereauHistoricalUrl = `http://localhost/bordereau/backend/bordereau_api.php`;
        const banqueHistoricalUrl = `http://localhost/bordereau/backend/banque_api.php`;

        console.log('🔗 URLs:', { 
          bordereauUrl, 
          banqueUrl,
          bordereauHistoricalUrl,
          banqueHistoricalUrl 
        });

        const [bordereauResponse, banqueResponse, bordereauHistoricalResponse, banqueHistoricalResponse] = await Promise.all([
          fetch(bordereauUrl),
          fetch(banqueUrl),
          fetch(bordereauHistoricalUrl),
          fetch(banqueHistoricalUrl)
        ]);

        console.log('📨 Status des réponses:', {
          bordereau: bordereauResponse.status,
          banque: banqueResponse.status,
          bordereauHistorical: bordereauHistoricalResponse.status,
          banqueHistorical: banqueHistoricalResponse.status
        });

        // Vérifier les réponses
        if (!bordereauResponse.ok) throw new Error(`Bordereaux période: ${bordereauResponse.status}`);
        if (!banqueResponse.ok) throw new Error(`Banques période: ${banqueResponse.status}`);
        if (!bordereauHistoricalResponse.ok) throw new Error(`Bordereaux historique: ${bordereauHistoricalResponse.status}`);
        if (!banqueHistoricalResponse.ok) throw new Error(`Banques historique: ${banqueHistoricalResponse.status}`);

        const [bordereauJson, banqueJson, bordereauHistoricalJson, banqueHistoricalJson] = await Promise.all([
          bordereauResponse.json(),
          banqueResponse.json(),
          bordereauHistoricalResponse.json(),
          banqueHistoricalResponse.json()
        ]);

        console.log('📊 Données reçues:', {
          bordereau: bordereauJson,
          banque: banqueJson,
          bordereauHistorical: bordereauHistoricalJson,
          banqueHistorical: banqueHistoricalJson
        });

        bordereauData.value = Array.isArray(bordereauJson) ? bordereauJson : [];
        banqueData.value = Array.isArray(banqueJson) ? banqueJson : [];

        // Émettre les données de période vers le parent
        emit('data-loaded', {
          bordereauData: bordereauData.value,
          banqueData: banqueData.value
        });

        // Émettre les données historiques vers le parent
        emit('historical-data-loaded', {
          bordereauHistorical: bordereauHistoricalJson,
          banqueHistorical: banqueHistoricalJson
        });

        await nextTick();
        createChart();

      } catch (error) {
        console.error('💥 Erreur:', error);
        errorMessage.value = error.message;
        createDemoChart();
      } finally {
        loading.value = false;
      }
    };

    const createChart = () => {
      if (!chartRef.value) {
        console.error('❌ Canvas non trouvé!');
        // Réessayer après un délai
        setTimeout(() => {
          if (chartRef.value) {
            createChart();
          }
        }, 100);
        return;
      }

      // Nettoyer l'ancien graphique
      if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
      }

      try {
        // Préparer les données - gérer le cas où un tableau est vide
        const allDays = [...new Set([
          ...bordereauData.value.map(item => item.day),
          ...banqueData.value.map(item => item.day)
        ])].sort();

        console.log('📅 Jours uniques:', allDays);
        console.log('📊 Données bordereau:', bordereauData.value);
        console.log('📊 Données banque:', banqueData.value);

        const bordereauCounts = allDays.map(day => {
          const item = bordereauData.value.find(d => d.day === day);
          return item ? parseInt(item.count) : 0;
        });

        const banqueCounts = allDays.map(day => {
          const item = banqueData.value.find(d => d.day === day);
          return item ? parseInt(item.count) : 0;
        });

        console.log('📈 Données pour graphique:', {
          labels: allDays,
          bordereau: bordereauCounts,
          banque: banqueCounts
        });

        const ctx = chartRef.value.getContext('2d');
        
        // Vérifier que le contexte est valide
        if (!ctx) {
          throw new Error('Contexte Canvas non disponible');
        }

        chartInstance.value = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: allDays,
            datasets: [
              {
                label: 'Bordereaux',
                data: bordereauCounts,
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                borderRadius: 4
              },
              {
                label: 'Banques',
                data: banqueCounts,
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                borderRadius: 4
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'top',
                labels: {
                  font: {
                    size: 12,
                    weight: 'bold'
                  }
                }
              },
              title: {
                display: true,
                text: 'Évolution des enregistrements',
                font: {
                  size: 16,
                  weight: 'bold'
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: "Nombre d'enregistrements",
                  font: {
                    weight: 'bold'
                  }
                },
                grid: {
                  color: 'rgba(0, 0, 0, 0.1)'
                }
              },
              x: {
                title: {
                  display: true,
                  text: 'Date',
                  font: {
                    weight: 'bold'
                  }
                },
                grid: {
                  color: 'rgba(0, 0, 0, 0.1)'
                }
              }
            },
            animation: {
              duration: 1000,
              easing: 'easeInOutQuart'
            }
          }
        });
        
        console.log('✅ Graphique créé avec succès');

      } catch (error) {
        console.error('💥 Erreur création graphique:', error);
        errorMessage.value = 'Erreur création graphique: ' + error.message;
        
        // Essayer de créer un graphique de démonstration
        setTimeout(() => {
          createDemoChart();
        }, 500);
      }
    };

    const createDemoChart = () => {
      if (!chartRef.value) {
        console.warn('❌ Canvas non disponible pour le graphique de démo');
        return;
      }

      if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
      }

      try {
        const ctx = chartRef.value.getContext('2d');
        if (!ctx) {
          throw new Error('Contexte Canvas non disponible pour la démo');
        }

        chartInstance.value = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            datasets: [
              {
                label: 'Bordereaux (demo)',
                data: [12, 19, 3, 5, 2, 3, 7],
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2
              },
              {
                label: 'Banques (demo)',
                data: [8, 15, 7, 12, 6, 9, 4],
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
          }
        });
        console.log('📊 Graphique de démonstration créé');
      } catch (error) {
        console.error('❌ Erreur demo chart:', error);
      }
    };

    const updateStats = () => {
      fetchStats(selectedPeriod.value);
    };

    onMounted(() => {
      console.log('🚀 StatisticsWidget monté');
      console.log('📍 Référence chartRef:', chartRef.value);
      
      // Délai pour s'assurer que le DOM est complètement rendu
      setTimeout(() => {
        console.log('⏰ Après délai - chartRef:', chartRef.value);
        fetchStats(selectedPeriod.value);
      }, 300);
    });

    return { 
      chartRef, 
      errorMessage, 
      loading,
      selectedPeriod, 
      updateStats 
    };
  }
};
</script>

<style scoped>
.statistics-widget {
  width: 100%;
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.period-selector {
  margin-bottom: 20px;
  text-align: center;
  padding: 10px;
  background: #f8f9fa;
  border-radius: 8px;
}

.period-selector label {
  font-weight: 600;
  margin-right: 10px;
  color: #2c3e50;
  font-size: 14px;
}

.period-selector select {
  padding: 8px 16px;
  border-radius: 6px;
  border: 2px solid #e0e0e0;
  background-color: white;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.period-selector select:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.period-selector select:hover {
  border-color: #3498db;
}

.chart-container {
  height: 400px !important;
  width: 100% !important;
  min-height: 400px;
  position: relative;
  border: 2px dashed #e0e0e0;
  border-radius: 8px;
  background: #fafafa;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chart-container:before {
  content: "Zone du graphique";
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: #999;
  font-size: 14px;
  z-index: 1;
}

.chart-container canvas {
  position: relative !important;
  z-index: 2 !important;
  width: 100% !important;
  height: 100% !important;
  display: block !important;
}

.loading, .error {
  text-align: center;
  padding: 60px 20px;
  color: #7f8c8d;
  font-style: italic;
  background: #f8f9fa;
  border-radius: 8px;
  margin: 20px 0;
}

.error {
  color: #e74c3c;
  background: #ffeaea;
  border: 1px solid #e74c3c;
}

/* Styles pour le débogage */
.debug-info {
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 6px;
  padding: 15px;
  margin: 15px 0;
  font-family: monospace;
  font-size: 12px;
}

.debug-info p {
  margin: 5px 0;
}
</style>