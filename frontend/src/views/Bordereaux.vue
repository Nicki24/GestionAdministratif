<template>
  <div class="bordereaux-page">
    <!-- En-tête de page -->
    <div class="page-header">
      <h2>📋 Gestion des Bordereaux</h2>
      <button class="btn-primary" @click="showAddModal = true">
        <span class="btn-icon">➕</span>
        Nouveau Bordereau
      </button>
    </div>

    <!-- Filtres et recherche -->
    <div class="filters-section">
      <div class="search-box">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher par référence ou objet..."
          class="search-input"
        />
        <span class="search-icon">🔍</span>
      </div>
      
      <div class="filter-group">
        <select v-model="statusFilter" class="filter-select">
          <option value="">Tous les statuts</option>
          <option value="Mandatement">Mandatement</option>
          <option value="Secours">Secours</option>
          <option value="VISA">VISA</option>
        </select>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="content-section">
      <!-- États de chargement et d'erreur -->
      <div class="loading" v-if="loading">
        <div class="spinner"></div>
        <p>Chargement des bordereaux...</p>
      </div>
      
      <div class="error" v-else-if="error">
        <div class="error-icon">❌</div>
        <h3>Erreur de chargement</h3>
        <p>{{ error }}</p>
        <button class="btn-retry" @click="loadBordereaux">Réessayer</button>
      </div>

      <!-- État vide -->
      <div v-else-if="filteredBordereaux.length === 0">
        <div class="empty-state">
          <div class="empty-icon">📋</div>
          <h3>Aucun bordereau trouvé</h3>
          <p v-if="searchQuery || statusFilter">Aucun résultat pour vos critères de recherche</p>
          <p v-else>Commencez par créer votre premier bordereau</p>
          <button class="btn-primary" @click="showAddModal = true">
            ➕ Créer un bordereau
          </button>
        </div>
      </div>

      <!-- Tableau des bordereaux -->
      <div v-else class="table-container">
        <table class="bordereaux-table">
          <thead>
            <tr>
              <th @click="sortBy('id_bordereau')" class="sortable">
                ID 📊
                <span v-if="sortField === 'id_bordereau'" class="sort-icon">
                  {{ sortOrder === 'asc' ? '↑' : '↓' }}
                </span>
              </th>
              <th @click="sortBy('reference')" class="sortable">
                Référence 📝
                <span v-if="sortField === 'reference'" class="sort-icon">
                  {{ sortOrder === 'asc' ? '↑' : '↓' }}
                </span>
              </th>
              <th>Description</th>
              <th @click="sortBy('statut')" class="sortable">
                Statut 🏷️
                <span v-if="sortField === 'statut'" class="sort-icon">
                  {{ sortOrder === 'asc' ? '↑' : '↓' }}
                </span>
              </th>
              <th @click="sortBy('date_creation')" class="sortable">
                Date 📅
                <span v-if="sortField === 'date_creation'" class="sort-icon">
                  {{ sortOrder === 'asc' ? '↑' : '↓' }}
                </span>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="bordereau in paginatedBordereaux" :key="bordereau.id_bordereau">
              <td class="id-cell">#{{ bordereau.id_bordereau }}</td>
              <td class="reference-cell">
                <strong>{{ bordereau.reference }}</strong>
              </td>
              <td class="objet-cell">
                <div class="objet-text" :title="bordereau.objet">
                  {{ truncateText(bordereau.objet, 60) }}
                </div>
              </td>
              <td class="status-cell">
                <span class="status-badge" :class="bordereau.statut.toLowerCase()">
                  {{ bordereau.statut }}
                </span>
              </td>
              <td class="date-cell">
                {{ formatDate(bordereau.date_creation) }}
                <div class="time-text">
                  {{ formatTime(bordereau.date_creation) }}
                </div>
              </td>
              <td class="actions-cell">
                <button 
                  class="btn-action view-btn"
                  @click="viewBordereau(bordereau)"
                  title="Voir détails"
                >
                  👀
                </button>
                <button 
                  class="btn-action edit-btn"
                  @click="editBordereau(bordereau)"
                  title="Modifier"
                >
                  ✏️
                </button>
                <button 
                  class="btn-action delete-btn"
                  @click="confirmDelete(bordereau)"
                  title="Supprimer"
                >
                  🗑️
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination" v-if="filteredBordereaux.length > itemsPerPage">
          <button 
            @click="prevPage" 
            :disabled="currentPage === 1"
            class="pagination-btn"
          >
            ← Précédent
          </button>
          
          <span class="page-info">
            Page {{ currentPage }} sur {{ totalPages }}
          </span>
          
          <button 
            @click="nextPage" 
            :disabled="currentPage === totalPages"
            class="pagination-btn"
          >
            Suivant →
          </button>
        </div>

        <!-- Informations de pagination -->
        <div class="pagination-info">
          Affichage de {{ startIndex + 1 }} à {{ endIndex }} sur {{ filteredBordereaux.length }} bordereaux
        </div>
      </div>
    </div>

    <!-- Modal d'ajout/modification -->
    <div v-if="showAddModal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ isEditing ? 'Modifier' : 'Ajouter' }} un bordereau</h3>
          <button class="modal-close" @click="closeModal">×</button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="saveBordereau">
            <div class="form-group">
              <label>Référence *</label>
              <input
                v-model="formData.reference"
                type="text"
                required
                placeholder="Ex: 206/MEC"
                class="form-input"
              />
            </div>
            
            <div class="form-group">
              <label>Description *</label>
              <textarea
                v-model="formData.objet"
                required
                placeholder="Description du bordereau..."
                rows="3"
                class="form-textarea"
              ></textarea>
            </div>
            
            <div class="form-group">
              <label>Statut *</label>
              <select v-model="formData.statut" required class="form-select">
                <option value="">Sélectionner un statut</option>
                <option value="Mandatement">Mandatement</option>
                <option value="Secours">Secours</option>
                <option value="VISA">VISA</option>
              </select>
            </div>
            
            <div class="form-actions">
              <button type="button" @click="closeModal" class="btn-cancel">
                Annuler
              </button>
              <button type="submit" class="btn-submit" :disabled="saving">
                {{ saving ? 'Enregistrement...' : (isEditing ? 'Modifier' : 'Créer') }}
              </button>
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
          <p>Êtes-vous sûr de vouloir supprimer le bordereau <strong>{{ bordereauToDelete?.reference }}</strong> ?</p>
          <p class="warning-text">⚠️ Cette action est irréversible !</p>
          
          <div class="delete-actions">
            <button @click="showDeleteModal = false" class="btn-cancel">
              Annuler
            </button>
            <button @click="deleteBordereau" class="btn-delete" :disabled="deleting">
              {{ deleting ? 'Suppression...' : 'Supprimer' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { bordereauService } from '../services/api';

export default {
  name: 'BordereauxView',
  data() {
    return {
      bordereaux: [],
      loading: true,
      error: null,
      searchQuery: '',
      statusFilter: '',
      sortField: 'id_bordereau',
      sortOrder: 'desc',
      currentPage: 1,
      itemsPerPage: 10,
      
      // Modal states
      showAddModal: false,
      showDeleteModal: false,
      isEditing: false,
      saving: false,
      deleting: false,
      
      // Form data
      formData: {
        id_bordereau: null,
        reference: '',
        objet: '',
        statut: ''
      },
      
      bordereauToDelete: null
    };
  },
  computed: {
    filteredBordereaux() {
      let filtered = this.bordereaux;

      // Filtre par recherche
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(b => 
          b.reference.toLowerCase().includes(query) ||
          b.objet.toLowerCase().includes(query)
        );
      }

      // Filtre par statut
      if (this.statusFilter) {
        filtered = filtered.filter(b => b.statut === this.statusFilter);
      }

      // Tri
      return filtered.sort((a, b) => {
        let modifier = this.sortOrder === 'asc' ? 1 : -1;
        
        if (this.sortField === 'date_creation') {
          return (new Date(a[this.sortField]) - new Date(b[this.sortField])) * modifier;
        }
        
        if (a[this.sortField] < b[this.sortField]) return -1 * modifier;
        if (a[this.sortField] > b[this.sortField]) return 1 * modifier;
        return 0;
      });
    },

    paginatedBordereaux() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      return this.filteredBordereaux.slice(start, start + this.itemsPerPage);
    },

    totalPages() {
      return Math.ceil(this.filteredBordereaux.length / this.itemsPerPage);
    },

    startIndex() {
      return (this.currentPage - 1) * this.itemsPerPage;
    },

    endIndex() {
      return Math.min(this.startIndex + this.itemsPerPage, this.filteredBordereaux.length);
    }
  },
  watch: {
    searchQuery() {
      this.currentPage = 1;
    },
    statusFilter() {
      this.currentPage = 1;
    }
  },
  async mounted() {
    await this.loadBordereaux();
  },
  methods: {
    async loadBordereaux() {
      try {
        this.loading = true;
        this.error = null;
        
        // Utilisation correcte du service API
        const response = await bordereauService.getBordereaux();
        
        // Vérifier la structure de réponse
        if (response.status === "success") {
          this.bordereaux = response.data;
        } else {
          this.error = response.message || "Erreur lors du chargement";
        }
      } catch (error) {
        console.error("Erreur détaillée:", error);
        this.error = error.message || "Erreur de connexion à l'API. Vérifiez que le serveur est démarré.";
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

    viewBordereau(bordereau) {
      alert(`Détails du bordereau:\n\nID: ${bordereau.id_bordereau}\nRérérence: ${bordereau.reference}\nStatut: ${bordereau.statut}\n\nDescription: ${bordereau.objet}`);
    },

    editBordereau(bordereau) {
      this.isEditing = true;
      this.formData = { ...bordereau };
      this.showAddModal = true;
    },

    confirmDelete(bordereau) {
      this.bordereauToDelete = bordereau;
      this.showDeleteModal = true;
    },

    async deleteBordereau() {
      try {
        this.deleting = true;
        const response = await bordereauService.deleteBordereau(this.bordereauToDelete.id_bordereau);
        
        if (response.status === "success") {
          // Recharger la liste
          await this.loadBordereaux();
          this.showDeleteModal = false;
          
          this.$notify({
            title: 'Succès',
            text: response.message || 'Bordereau supprimé avec succès',
            type: 'success'
          });
        } else {
          throw new Error(response.message || "Erreur lors de la suppression");
        }
      } catch (error) {
        console.error('Erreur suppression:', error);
        this.error = error.message || "Erreur lors de la suppression";
        
        this.$notify({
          title: 'Erreur',
          text: error.message || "Erreur lors de la suppression",
          type: 'error'
        });
      } finally {
        this.deleting = false;
      }
    },

    async saveBordereau() {
      try {
        this.saving = true;
        
        let response;
        if (this.isEditing) {
          response = await bordereauService.updateBordereau(
            this.formData.id_bordereau,
            this.formData
          );
        } else {
          response = await bordereauService.addBordereau(this.formData);
        }
        
        if (response.status === "success") {
          // Recharger la liste et fermer le modal
          await this.loadBordereaux();
          this.closeModal();
          
          this.$notify({
            title: 'Succès',
            text: response.message || `Bordereau ${this.isEditing ? 'modifié' : 'créé'} avec succès`,
            type: 'success'
          });
        } else {
          throw new Error(response.message || "Erreur lors de la sauvegarde");
        }
      } catch (error) {
        console.error('Erreur sauvegarde:', error);
        this.error = error.message || "Erreur lors de la sauvegarde";
        
        this.$notify({
          title: 'Erreur',
          text: error.message || "Erreur lors de la sauvegarde",
          type: 'error'
        });
      } finally {
        this.saving = false;
      }
    },

    closeModal() {
      this.showAddModal = false;
      this.isEditing = false;
      this.formData = {
        id_bordereau: null,
        reference: '',
        objet: '',
        statut: ''
      };
    },

    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
      }
    },

    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
      }
    },

    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      });
    },

    formatTime(dateString) {
      return new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    truncateText(text, length) {
      return text.length > length ? text.substring(0, length) + '...' : text;
    }
  }
};
</script>

<style scoped>
.bordereaux-page {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  flex-wrap: wrap;
  gap: 15px;
}

.page-header h2 {
  color: #2c3e50;
  margin: 0;
}

/* Filtres et recherche */
.filters-section {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  flex-wrap: wrap;
  align-items: center;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 250px;
}

.search-input {
  width: 100%;
  padding: 12px 40px 12px 15px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: #3498db;
}

.search-icon {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #7f8c8d;
}

.filter-select {
  padding: 12px 15px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 14px;
  background: white;
  min-width: 180px;
}

/* Tableau */
.table-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.bordereaux-table {
  width: 100%;
  border-collapse: collapse;
}

.bordereaux-table th {
  background: #f8f9fa;
  padding: 15px;
  text-align: left;
  font-weight: 600;
  color: #2c3e50;
  border-bottom: 2px solid #e9ecef;
}

.bordereaux-table td {
  padding: 15px;
  border-bottom: 1px solid #e9ecef;
}

.bordereaux-table tr:hover {
  background: #f8f9fa;
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background: #e9ecef;
}

.sort-icon {
  margin-left: 5px;
  font-weight: bold;
}

/* Cellules spécifiques */
.id-cell {
  font-weight: 600;
  color: #7f8c8d;
  width: 80px;
}

.reference-cell {
  font-weight: 600;
  color: #2c3e50;
  width: 120px;
}

.objet-cell {
  max-width: 300px;
}

.objet-text {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.mandatement {
  background: #ffeaa7;
  color: #d35400;
}

.status-badge.secours {
  background: #d6eaf8;
  color: #2980b9;
}

.status-badge.visa {
  background: #d5f5e3;
  color: #27ae60;
}

.date-cell {
  white-space: nowrap;
  width: 140px;
}

.time-text {
  font-size: 11px;
  color: #7f8c8d;
  margin-top: 2px;
}

.actions-cell {
  white-space: nowrap;
  width: 150px;
}

.btn-action {
  background: none;
  border: none;
  padding: 8px;
  margin: 0 2px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.2s ease;
}

.view-btn:hover { background: #e3f2fd; }
.edit-btn:hover { background: #fff3e0; }
.delete-btn:hover { background: #ffebee; }

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  padding: 20px;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
}

.pagination-btn {
  padding: 8px 16px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pagination-btn:hover:not(:disabled) {
  background: #3498db;
  color: white;
  border-color: #3498db;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-weight: 600;
  color: #2c3e50;
}

.pagination-info {
  text-align: center;
  padding: 10px;
  background: #f8f9fa;
  color: #7f8c8d;
  font-size: 14px;
}

/* Modals */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
  margin: 0;
  color: #2c3e50;
}

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #7f8c8d;
}

.modal-body {
  padding: 20px;
}

/* Formulaires */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 600;
  color: #2c3e50;
}

.form-input,
.form-textarea,
.form-select {
  width: 100%;
  padding: 12px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.3s ease;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  outline: none;
  border-color: #3498db;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 30px;
}

.btn-cancel {
  padding: 12px 20px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  color: #7f8c8d;
}

.btn-submit {
  padding: 12px 20px;
  border: none;
  background: #3498db;
  color: white;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

.btn-submit:hover:not(:disabled) {
  background: #2980b9;
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Modal de suppression */
.delete-modal {
  max-width: 400px;
}

.warning-text {
  color: #e74c3c;
  font-weight: 600;
  margin: 10px 0;
}

.delete-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 20px;
}

.btn-delete {
  padding: 12px 20px;
  border: none;
  background: #e74c3c;
  color: white;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

.btn-delete:hover:not(:disabled) {
  background: #c0392b;
}

/* États */
.loading {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error {
  text-align: center;
  padding: 40px 20px;
  color: #e74c3c;
}

.error-icon {
  font-size: 3rem;
  margin-bottom: 15px;
}

.btn-retry {
  margin-top: 15px;
  padding: 10px 20px;
  background: #3498db;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 20px;
  opacity: 0.5;
}

.empty-state h3 {
  color: #2c3e50;
  margin-bottom: 10px;
}

.empty-state p {
  color: #7f8c8d;
  margin-bottom: 20px;
}

/* Responsive */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filters-section {
    flex-direction: column;
  }
  
  .search-box {
    min-width: auto;
  }
  
  .bordereaux-table {
    display: block;
    overflow-x: auto;
  }
  
  .form-actions,
  .delete-actions {
    flex-direction: column;
  }
  
  .modal {
    margin: 20px;
    width: auto;
  }
}

@media (max-width: 480px) {
  .bordereaux-page {
    padding: 10px;
  }
  
  .actions-cell {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }
  
  .btn-action {
    margin: 2px 0;
  }
}
</style>