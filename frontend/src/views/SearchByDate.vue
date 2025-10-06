<template>
  <div class="search-by-date-page">
    <!-- En-tête de page -->
    <div class="page-header">
      <h2>🔍 Recherche par Date</h2>
    </div>

    <!-- Formulaire de recherche -->
    <div class="search-form">
      <div class="form-group">
        <label for="search-date">Choisir une date :</label>
        <input
          id="search-date"
          v-model="selectedDate"
          type="date"
          class="form-input"
          :max="maxDate"
          required
        />
      </div>
      <div class="form-group">
        <label for="search-type">Type de recherche :</label>
        <select
          id="search-type"
          v-model="searchType"
          class="form-input"
          required
        >
          <option value="" disabled>Sélectionner un type</option>
          <option value="bordereaux">Bordereaux</option>
          <option value="banques">Banques</option>
        </select>
      </div>
      <button
        class="btn-search"
        @click="searchByDate"
        :disabled="!selectedDate || !searchType"
      >
        Rechercher
      </button>
    </div>

    <!-- Résultats -->
    <div class="results-section" v-if="loading">
      <div class="spinner"></div>
      <p>Chargement des résultats...</p>
    </div>
    <div class="results-section" v-else-if="error">
      <p class="error-message">{{ error }}</p>
    </div>
    <div class="results-section" v-else-if="results.length === 0 && !loading">
      <p>Aucun résultat trouvé pour la date sélectionnée.</p>
    </div>
    <div class="results-section" v-else>
      <h3>Résultats pour le {{ formattedDate }}</h3>
      <table class="results-table">
        <thead>
          <tr>
            <th v-if="searchType === 'bordereaux'">ID Bordereau</th>
            <th v-if="searchType === 'bordereaux'">Matricule</th>
            <th v-if="searchType === 'bordereaux'">Référence</th>
            <th v-if="searchType === 'bordereaux'">Objet</th>
            <th v-if="searchType === 'bordereaux'">Statut</th>
            <th v-if="searchType === 'banques'">ID Banque</th>
            <th v-if="searchType === 'banques'">Nom</th>
            <th v-if="searchType === 'banques'">Section</th>
            <th>Date Création</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(result, index) in results" :key="index">
            <td v-if="searchType === 'bordereaux'">{{ result.id_bordereau }}</td>
            <td v-if="searchType === 'bordereaux'">{{ result.matricule }}</td>
            <td v-if="searchType === 'bordereaux'">{{ result.reference }}</td>
            <td v-if="searchType === 'bordereaux'">{{ result.objet }}</td>
            <td v-if="searchType === 'bordereaux'">{{ result.statut }}</td>
            <td v-if="searchType === 'banques'">{{ result.id_banque }}</td>
            <td v-if="searchType === 'banques'">{{ result.nom }}</td>
            <td v-if="searchType === 'banques'">{{ result.section }}</td>
            <td>{{ formatDate(result.date_creation) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { searchService } from '../services/api';
import { useNotification } from '@kyvg/vue3-notification';

export default {
  name: 'SearchByDate',
  setup() {
    const { notify } = useNotification();
    return { notify };
  },
  data() {
    return {
      selectedDate: '',
      searchType: '',
      results: [],
      loading: false,
      error: null,
      maxDate: new Date().toISOString().split('T')[0], // Date maximale = aujourd'hui
    };
  },
  computed: {
    formattedDate() {
      if (!this.selectedDate) return '';
      const date = new Date(this.selectedDate);
      return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      });
    },
  },
  methods: {
    async searchByDate() {
      try {
        this.loading = true;
        this.error = null;
        this.results = await searchService.searchByDate(this.selectedDate, this.searchType);
        if (this.results.length === 0) {
          this.notify({
            title: 'Avertissement',
            text: 'Aucun résultat trouvé pour cette date.',
            type: 'warning',
          });
        } else {
          this.notify({
            title: 'Succès',
            text: `${this.results.length} résultat(s) trouvé(s).`,
            type: 'success',
          });
        }
      } catch (error) {
        this.error = error.response?.data?.error || error.message || 'Erreur lors de la recherche';
        this.notify({
          title: 'Erreur',
          text: this.error,
          type: 'error',
        });
      } finally {
        this.loading = false;
      }
    },
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
      });
    },
  },
};
</script>

<style scoped>
.search-by-date-page {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
  background-color: #f5f7fa;
  min-height: calc(100vh - 130px);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
  background-color: #ffffff;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.page-header h2 {
  color: #1a3c34;
  font-size: 24px;
  font-weight: 600;
  margin: 0;
}

.search-form {
  background-color: #ffffff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  margin-bottom: 24px;
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.form-group {
  flex: 1;
  min-width: 200px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #1a3c34;
}

.form-input {
  width: 100%;
  padding: 10px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 14px;
  background-color: #ffffff;
  transition: border-color 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.btn-search {
  padding: 10px 16px;
  background-color: #007bff;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.3s ease;
  font-size: 14px;
  font-weight: 500;
}

.btn-search:hover:not(:disabled) {
  background-color: #0056b3;
}

.btn-search:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}

.results-section {
  background-color: #ffffff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.results-section h3 {
  color: #1a3c34;
  font-size: 18px;
  margin-bottom: 16px;
}

.results-table {
  width: 100%;
  border-collapse: collapse;
}

.results-table th,
.results-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e0e0e0;
}

.results-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: #1a3c34;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 10px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  color: #dc3545;
  text-align: center;
}

@media (max-width: 768px) {
  .search-form {
    flex-direction: column;
  }
  .form-group {
    min-width: 100%;
  }
}
</style>