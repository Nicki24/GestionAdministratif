<template>
  <div class="dossiers-page">
    <!-- En-tête -->
    <div class="page-header">
      <h2 v-if="id_bordereau">Gestion des Dossiers pour Bordereau #{{ id_bordereau }}</h2>
      <h2 v-else>Erreur : ID du Bordereau manquant</h2>
      <button class="btn-primary" @click="showAddModal = true" :disabled="loading || !id_bordereau">
        <span class="btn-icon">📁</span>
        Nouveau Dossier
      </button>
    </div>

    <!-- Filtres (recherche par matricule) -->
    <div class="filters-section">
      <div class="search-box">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher par matricule..."
          class="search-input"
          :disabled="loading || !id_bordereau"
        />
        <span class="search-icon">🔍</span>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="content-section">
      <!-- État de chargement -->
      <div v-if="loading" class="loading-section">
        <div class="spinner"></div>
        <p>Chargement des dossiers...</p>
      </div>

      <!-- État d'erreur -->
      <div v-if="error" class="error-section">
        <div class="error-icon">❌</div>
        <p>{{ error }}</p>
        <button @click="loadDossiers" class="btn-retry">Réessayer</button>
      </div>

      <!-- État vide -->
      <div v-if="!loading && !error && filteredDossiers.length === 0 && id_bordereau" class="empty-state">
        <div class="empty-icon">📁</div>
        <h3>Aucun dossier trouvé</h3>
        <p>Créez un nouveau dossier pour ce bordereau.</p>
      </div>

      <!-- Tableau des dossiers -->
      <div v-if="!loading && !error && filteredDossiers.length > 0" class="dossiers-table">
        <table>
          <thead>
            <tr>
              <th class="sortable" @click="sortBy('id_dossier')">
                ID Dossier
                <span class="sort-icon" v-if="sortField === 'id_dossier'">
                  {{ sortOrder === 'asc' ? '▲' : '▼' }}
                </span>
              </th>
              <th class="sortable" @click="sortBy('matricule')">
                Matricule
                <span class="sort-icon" v-if="sortField === 'matricule'">
                  {{ sortOrder === 'asc' ? '▲' : '▼' }}
                </span>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="dossier in paginatedDossiers" :key="dossier.id_dossier">
              <td>{{ dossier.id_dossier }}</td>
              <td>{{ dossier.matricule }}</td>
              <td>
                <button class="btn-action edit-btn" @click="editDossier(dossier)" title="Modifier" :disabled="loading">
                  ✏️
                </button>
                <button class="btn-action delete-btn" @click="confirmDelete(dossier)" title="Supprimer" :disabled="loading">
                  🗑️
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination" v-if="filteredDossiers.length > itemsPerPage">
          <button @click="prevPage" :disabled="currentPage === 1 || loading" class="pagination-btn">
            ← Précédent
          </button>
          <span class="page-info">Page {{ currentPage }} sur {{ totalPages }}</span>
          <button @click="nextPage" :disabled="currentPage === totalPages || loading" class="pagination-btn">
            Suivant →
          </button>
        </div>
        <div class="pagination-info">
          Affichage de {{ startIndex + 1 }} à {{ endIndex }} sur {{ filteredDossiers.length }} dossiers
        </div>
      </div>
    </div>

    <!-- Modal d'ajout/modification -->
    <div v-if="showAddModal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ isEditing ? 'Modifier' : 'Ajouter' }} un dossier</h3>
          <button class="modal-close" @click="closeModal">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveDossier">
            <div class="form-group">
              <label>Matricule *</label>
              <input v-model="formData.matricule" type="text" required placeholder="Ex: 501111034760" class="form-input" :disabled="saving" />
            </div>
            <div class="form-actions">
              <button type="button" @click="closeModal" class="btn-cancel" :disabled="saving">Annuler</button>
              <button type="submit" class="btn-submit" :disabled="saving || !id_bordereau">{{ saving ? 'Enregistrement...' : (isEditing ? 'Modifier' : 'Créer') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div v-if="showDeleteModal" class="modal-overlay">
      <div class="modal delete-modal">
        <div class="modal-header">
          <h3>Confirmer la suppression</h3>
          <button class="modal-close" @click="showDeleteModal = false">×</button>
        </div>
        <div class="modal-body">
          <p>Êtes-vous sûr de vouloir supprimer le dossier #{{ dossierToDelete?.id_dossier }} (Matricule: {{ dossierToDelete?.matricule }}) ?</p>
          <p class="warning-text">⚠️ Cette action est irréversible !</p>
          <div class="delete-actions">
            <button @click="showDeleteModal = false" class="btn-cancel" :disabled="deleting">Annuler</button>
            <button @click="deleteDossier" class="btn-delete" :disabled="deleting">{{ deleting ? 'Suppression...' : 'Supprimer' }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Notifications -->
    <notifications position="top right" />
  </div>
</template>

<script>
import { dossierService } from '../services/api';
import { useNotification } from '@kyvg/vue3-notification';

export default {
  name: 'DossierView',
  props: {
    id_bordereau: {
      type: [String, Number],
      required: true
    }
  },
  setup() {
    const { notify } = useNotification();
    return { notify };
  },
  data() {
    return {
      loading: true,
      error: null,
      dossiers: [],
      searchQuery: '',
      sortField: 'id_dossier',
      sortOrder: 'desc',
      currentPage: 1,
      itemsPerPage: 10,
      showAddModal: false,
      showDeleteModal: false,
      isEditing: false,
      saving: false,
      deleting: false,
      formData: {
        id_dossier: null,
        matricule: '',
      },
      dossierToDelete: null,
    };
  },
  computed: {
    filteredDossiers() {
      let filtered = [...this.dossiers];
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(d => d.matricule.toLowerCase().includes(query));
      }
      return filtered.sort((a, b) => {
        let modifier = this.sortOrder === 'asc' ? 1 : -1;
        if (a[this.sortField] < b[this.sortField]) return -1 * modifier;
        if (a[this.sortField] > b[this.sortField]) return 1 * modifier;
        return 0;
      });
    },
    paginatedDossiers() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      return this.filteredDossiers.slice(start, start + this.itemsPerPage);
    },
    totalPages() {
      return Math.ceil(this.filteredDossiers.length / this.itemsPerPage);
    },
    startIndex() {
      return (this.currentPage - 1) * this.itemsPerPage;
    },
    endIndex() {
      return Math.min(this.startIndex + this.itemsPerPage, this.filteredDossiers.length);
    }
  },
  watch: {
    searchQuery() {
      this.currentPage = 1;
    }
  },
  async mounted() {
    console.log('DossierView mounted with id_bordereau:', this.id_bordereau); // Débogage
    await this.loadDossiers();
  },
  methods: {
    async loadDossiers() {
      if (!this.id_bordereau) {
        this.error = "ID du bordereau manquant. Veuillez utiliser une URL comme /dossier/1.";
        this.notify({
          title: 'Erreur',
          text: this.error,
          type: 'error'
        });
        this.loading = false;
        return;
      }
      try {
        this.loading = true;
        this.error = null;
        console.log(`Loading dossiers for id_bordereau: ${this.id_bordereau}`); // Débogage
        const response = await dossierService.getDossiersByBordereau(this.id_bordereau);
        console.log('API Response:', response); // Débogage
        if (response.status === 'success') {
          this.dossiers = response.data || [];
        } else {
          this.error = response.message || 'Erreur lors du chargement des dossiers';
          this.notify({
            title: 'Erreur',
            text: this.error,
            type: 'error'
          });
        }
      } catch (error) {
        this.error = error.message || 'Erreur de connexion à l\'API';
        this.notify({
          title: 'Erreur',
          text: this.error,
          type: 'error'
        });
      } finally {
        this.loading = false;
      }
    },
    sortBy(field) {
      if (this.sortField === field) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortOrder = 'asc';
      }
    },
    editDossier(dossier) {
      this.formData = { ...dossier };
      this.isEditing = true;
      this.showAddModal = true;
    },
    confirmDelete(dossier) {
      this.dossierToDelete = dossier;
      this.showDeleteModal = true;
    },
    async saveDossier() {
      if (!this.id_bordereau) {
        this.error = "ID du bordereau manquant pour l'enregistrement.";
        this.notify({
          title: 'Erreur',
          text: this.error,
          type: 'error'
        });
        return;
      }
      try {
        this.saving = true;
        const data = { id_bordereau: this.id_bordereau, matricule: this.formData.matricule };
        let response;
        if (this.isEditing) {
          response = await dossierService.updateDossier(this.formData.id_dossier, data);
        } else {
          response = await dossierService.addDossier(data);
        }
        if (response.status === 'success') {
          this.closeModal();
          await this.loadDossiers();
          this.notify({
            title: 'Succès',
            text: response.message || `Dossier ${this.isEditing ? 'modifié' : 'créé'} avec succès`,
            type: 'success'
          });
        } else {
          this.error = response.message || 'Erreur lors de l\'enregistrement';
          this.notify({
            title: 'Erreur',
            text: this.error,
            type: 'error'
          });
        }
      } catch (error) {
        this.error = error.message || 'Erreur lors de l\'enregistrement';
        this.notify({
          title: 'Erreur',
          text: this.error,
          type: 'error'
        });
      } finally {
        this.saving = false;
      }
    },
    async deleteDossier() {
      try {
        this.deleting = true;
        const response = await dossierService.deleteDossier(this.dossierToDelete.id_dossier);
        if (response.status === 'success') {
          this.showDeleteModal = false;
          await this.loadDossiers();
          this.notify({
            title: 'Succès',
            text: response.message || 'Dossier supprimé avec succès',
            type: 'success'
          });
        } else {
          this.error = response.message || 'Erreur lors de la suppression';
          this.notify({
            title: 'Erreur',
            text: this.error,
            type: 'error'
          });
        }
      } catch (error) {
        this.error = error.message || 'Erreur lors de la suppression';
        this.notify({
          title: 'Erreur',
          text: this.error,
          type: 'error'
        });
      } finally {
        this.deleting = false;
      }
    },
    closeModal() {
      this.showAddModal = false;
      this.isEditing = false;
      this.formData = { id_dossier: null, matricule: '' };
      this.error = null;
    },
    nextPage() {
      if (this.currentPage < this.totalPages && !this.loading) {
        this.currentPage++;
      }
    },
    prevPage() {
      if (this.currentPage > 1 && !this.loading) {
        this.currentPage--;
      }
    }
  },
};
</script>

<style scoped>
/* Le style reste inchangé, copié de la version précédente pour cohérence */
.dossiers-page {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
  background-color: #f5f7fa;
  min-height: 100vh;
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

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background-color: #007bff;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.btn-primary:hover:not(:disabled) {
  background-color: #0056b3;
}

.btn-primary:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}

.btn-icon {
  font-size: 16px;
}

.filters-section {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 200px;
}

.search-input {
  width: 100%;
  padding: 10px 40px 10px 12px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 14px;
  background-color: #ffffff;
  transition: border-color 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.search-input:disabled {
  background-color: #f8f9fa;
  cursor: not-allowed;
}

.search-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 16px;
  color: #6c757d;
}

.content-section {
  background-color: #ffffff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.loading-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-section {
  text-align: center;
  padding: 40px;
  color: #dc3545;
}

.error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.btn-retry {
  padding: 10px 16px;
  background-color: #dc3545;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.btn-retry:hover {
  background-color: #c82333;
}

.empty-state {
  text-align: center;
  padding: 40px;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
  color: #6c757d;
}

.dossiers-table {
  width: 100%;
  border-collapse: collapse;
  background-color: #ffffff;
}

.dossiers-table th,
.dossiers-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e0e0e0;
}

.dossiers-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: #1a3c34;
}

.sortable {
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.sortable:hover {
  background-color: #e9ecef;
}

.sort-icon {
  margin-left: 4px;
}

.btn-action {
  padding: 6px;
  border: none;
  border-radius: 4px;
  background-color: #f8f9fa;
  cursor: pointer;
  font-size: 16px;
  transition: background-color 0.3s ease;
}

.btn-action.edit-btn:hover:not(:disabled) {
  background-color: #ffc107;
  color: #000;
}

.btn-action.delete-btn:hover:not(:disabled) {
  background-color: #dc3545;
  color: #ffffff;
}

.btn-action:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
  color: #fff;
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.pagination-btn {
  padding: 8px 16px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  background-color: #ffffff;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.pagination-btn:disabled {
  background-color: #f8f9fa;
  cursor: not-allowed;
  opacity: 0.6;
}

.pagination-btn:hover:not(:disabled) {
  background-color: #007bff;
  color: #ffffff;
  border-color: #007bff;
}

.page-info {
  font-size: 14px;
  color: #6c757d;
}

.pagination-info {
  margin-top: 16px;
  font-size: 14px;
  color: #6c757d;
  text-align: center;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal {
  background-color: #ffffff;
  border-radius: 8px;
  width: 100%;
  max-width: 500px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background-color: #f8f9fa;
  border-bottom: 1px solid #e0e0e0;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  color: #1a3c34;
}

.modal-close {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: #6c757d;
}

.modal-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 16px;
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

.form-input:disabled {
  background-color: #f8f9fa;
  cursor: not-allowed;
}

.form-actions,
.delete-actions {
  display: flex;
  gap: 16px;
  justify-content: flex-end;
  margin-top: 24px;
}

.btn-cancel {
  padding: 10px 16px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  background-color: #ffffff;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.btn-cancel:hover:not(:disabled) {
  background-color: #f8f9fa;
}

.btn-cancel:disabled {
  background-color: #f8f9fa;
  cursor: not-allowed;
}

.btn-submit {
  padding: 10px 16px;
  background-color: #007bff;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.btn-submit:hover:not(:disabled) {
  background-color: #0056b3;
}

.btn-submit:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}

.btn-delete {
  padding: 10px 16px;
  background-color: #dc3545;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.btn-delete:hover:not(:disabled) {
  background-color: #c82333;
}

.btn-delete:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}

.warning-text {
  color: #dc3545;
  margin-top: 8px;
}

@media (max-width: 768px) {
  .dossiers-page {
    padding: 16px;
  }

  .filters-section {
    flex-direction: column;
  }

  .search-box {
    min-width: 100%;
  }
}

@media (max-width: 480px) {
  .dossiers-page {
    padding: 12px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .modal {
    max-width: 90%;
  }
}
</style>