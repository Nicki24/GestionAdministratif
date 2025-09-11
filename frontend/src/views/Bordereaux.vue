<template>
  <div class="bordereaux-page">
    <!-- En-tête de page -->
    <div class="page-header">
      <h2>📋 Gestion des Bordereaux</h2>
      <button class="btn-primary" @click="showAddModal = true" :disabled="loading">
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
          placeholder="Rechercher par référence, matricule ou objet..."
          class="search-input"
          :disabled="loading"
        />
        <span class="search-icon">🔍</span>
      </div>
      
      <div class="filter-group">
        <select v-model="statusFilter" class="filter-select" :disabled="loading">
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
      <div v-else-if="filteredBordereaux.length === 0" class="empty-state">
        <div class="empty-icon">📋</div>
        <h3>Aucun bordereau trouvé</h3>
        <p v-if="searchQuery || statusFilter">Aucun résultat pour vos critères de recherche</p>
        <p v-else>Commencez par créer votre premier bordereau</p>
        <button class="btn-primary" @click="showAddModal = true" :disabled="loading">
          ➕ Créer un bordereau
        </button>
      </div>

      <!-- Tableau des bordereaux -->
      <div v-else class="table-container">
        <table class="bordereaux-table">
          <thead>
            <tr>
              <th @click="sortBy('id_bordereau')" class="sortable">
                ID 📊 <span v-if="sortField === 'id_bordereau'" class="sort-icon">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th @click="sortBy('reference')" class="sortable">
                Référence 📝 <span v-if="sortField === 'reference'" class="sort-icon">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th>Matricule(s)</th>
              <th>Description</th>
              <th @click="sortBy('statut')" class="sortable">
                Statut 🏷️ <span v-if="sortField === 'statut'" class="sort-icon">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th @click="sortBy('date_creation')" class="sortable">
                Date 📅 <span v-if="sortField === 'date_creation'" class="sort-icon">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="bordereau in paginatedBordereaux" :key="bordereau.id_bordereau + '-' + bordereau.matricule">
              <td class="id-cell">#{{ bordereau.id_bordereau }}</td>
              <td class="reference-cell"><strong>{{ bordereau.reference }}</strong></td>
              <td class="matricule-cell">{{ bordereau.matricule }}</td>
              <td class="objet-cell"><div class="objet-text" :title="bordereau.objet">{{ truncateText(bordereau.objet, 60) }}</div></td>
              <td class="status-cell"><span class="status-badge" :class="bordereau.statut.toLowerCase()">{{ bordereau.statut }}</span></td>
              <td class="date-cell">{{ formatDate(bordereau.date_creation) }}<div class="time-text">{{ formatTime(bordereau.date_creation) }}</div></td>
              <td class="actions-cell">
                <button class="btn-action edit-btn" @click="editBordereau(bordereau)" title="Modifier" :disabled="loading">✏️</button>
                <button class="btn-action delete-btn" @click="confirmDelete(bordereau)" title="Supprimer" :disabled="loading">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination" v-if="filteredBordereaux.length > itemsPerPage">
          <button @click="prevPage" :disabled="currentPage === 1 || loading" class="pagination-btn">← Précédent</button>
          <span class="page-info">Page {{ currentPage }} sur {{ totalPages }}</span>
          <button @click="nextPage" :disabled="currentPage === totalPages || loading" class="pagination-btn">Suivant →</button>
        </div>

        <!-- Informations de pagination -->
        <div class="pagination-info">Affichage de {{ startIndex + 1 }} à {{ endIndex }} sur {{ filteredBordereaux.length }} bordereaux</div>
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
              <label>ID Bordereau *</label>
              <input v-model="formData.id_bordereau" type="number" required class="form-input" :disabled="isEditing || saving" />
            </div>
            <div class="form-group">
              <label>Référence *</label>
              <input v-model="formData.reference" type="text" required placeholder="Ex: 206/MEC" class="form-input" :disabled="saving" />
            </div>
            <div class="form-group">
              <label>Matricule(s) * (séparés par des virgules)</label>
              <input v-model="formData.matricules" type="text" required placeholder="Ex: 501111099999,501111088888" class="form-input" :disabled="isEditing || saving" />
            </div>
            <div class="form-group">
              <label>Description *</label>
              <textarea v-model="formData.objet" required placeholder="Description du bordereau..." rows="3" class="form-textarea" :disabled="saving"></textarea>
            </div>
            <div class="form-group">
              <label>Statut *</label>
              <select v-model="formData.statut" required class="form-select" :disabled="saving">
                <option value="">Sélectionner un statut</option>
                <option value="Mandatement">Mandatement</option>
                <option value="Secours">Secours</option>
                <option value="VISA">VISA</option>
              </select>
            </div>
            <div class="form-actions">
              <button type="button" @click="closeModal" class="btn-cancel" :disabled="saving">Annuler</button>
              <button type="submit" class="btn-submit" :disabled="saving">{{ saving ? 'Enregistrement...' : (isEditing ? 'Modifier' : 'Créer') }}</button>
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
          <p>Êtes-vous sûr de vouloir supprimer l'entrée du bordereau <strong>{{ bordereauToDelete?.reference }}</strong> pour le matricule <strong>{{ bordereauToDelete?.matricule }}</strong> ?</p>
          <p class="warning-text">⚠️ Cette action est irréversible !</p>
          <div class="delete-actions">
            <button @click="showDeleteModal = false" class="btn-cancel" :disabled="deleting">Annuler</button>
            <button @click="deleteBordereau" class="btn-delete" :disabled="deleting">{{ deleting ? 'Suppression...' : 'Supprimer' }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Composant de notifications -->
    <notifications position="top right" />
  </div>
</template>

<script>
import { bordereauService } from '../services/api';
import { useNotification } from '@kyvg/vue3-notification';

export default {
  name: 'BordereauxView',
  setup() {
    const { notify } = useNotification();
    return { notify };
  },
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
      showAddModal: false,
      showDeleteModal: false,
      isEditing: false,
      saving: false,
      deleting: false,
      formData: {
        id_bordereau: null,
        reference: '',
        matricules: '',
        objet: '',
        statut: ''
      },
      bordereauToDelete: null
    };
  },
  computed: {
    filteredBordereaux() {
      let filtered = [...this.bordereaux];
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(b => 
          b.reference.toLowerCase().includes(query) ||
          b.matricule.toLowerCase().includes(query) ||
          (b.objet && b.objet.toLowerCase().includes(query))
        );
      }
      if (this.statusFilter) {
        filtered = filtered.filter(b => b.statut === this.statusFilter);
      }
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
    console.log('BordereauxView mounted');
    await this.loadBordereaux();
  },
  methods: {
    async loadBordereaux() {
      console.log('Loading bordereaux...');
      try {
        this.loading = true;
        this.error = null;
        this.bordereaux = await bordereauService.getBordereaux();
        console.log('Bordereaux chargés:', this.bordereaux);
      } catch (error) {
        console.error('Erreur détaillée:', error);
        this.error = error.message || 'Erreur de connexion à l\'API. Vérifiez que le serveur est démarré.';
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
    editBordereau(bordereau) {
      this.isEditing = true;
      this.formData = {
        id_bordereau: bordereau.id_bordereau,
        reference: bordereau.reference,
        matricules: bordereau.matricule,
        objet: bordereau.objet,
        statut: bordereau.statut
      };
      this.showAddModal = true;
    },
    confirmDelete(bordereau) {
      this.bordereauToDelete = bordereau;
      this.showDeleteModal = true;
    },
    async deleteBordereau() {
      try {
        this.deleting = true;
        console.log('Requête DELETE:', this.bordereauToDelete.id_bordereau, this.bordereauToDelete.matricule);
        await bordereauService.deleteBordereau(this.bordereauToDelete.id_bordereau, this.bordereauToDelete.matricule);
        await this.loadBordereaux();
        this.showDeleteModal = false;
        this.notify({
          title: 'Succès',
          text: 'Entrée supprimée avec succès',
          type: 'success'
        });
      } catch (error) {
        console.error('Erreur suppression:', error.response?.data || error.message);
        this.error = error.response?.data?.error || error.message || 'Erreur lors de la suppression';
        this.notify({
          title: 'Erreur',
          text: this.error,
          type: 'error'
        });
      } finally {
        this.deleting = false;
      }
    },
    async saveBordereau() {
      try {
        this.saving = true;
        // Valider les matricules pour la création
        let matricules = this.isEditing ? [this.formData.matricules] : this.formData.matricules.split(',').map(m => m.trim()).filter(m => m);
        if (!this.isEditing && matricules.length === 0) {
          throw new Error('Veuillez entrer au moins un matricule valide');
        }
        const data = {
          id_bordereau: parseInt(this.formData.id_bordereau),
          reference: this.formData.reference,
          matricules: matricules,
          objet: this.formData.objet,
          statut: this.formData.statut
        };
        console.log('Données envoyées à l\'API:', data);
        if (this.isEditing) {
          console.log('Requête PUT:', this.formData.id_bordereau, this.formData.matricules);
          await bordereauService.updateBordereau(this.formData.id_bordereau, this.formData.matricules, {
            reference: this.formData.reference,
            objet: this.formData.objet,
            statut: this.formData.statut
          });
        } else {
          console.log('Requête POST:', data);
          await bordereauService.createBordereau(data);
        }
        await this.loadBordereaux();
        this.closeModal();
        this.notify({
          title: 'Succès',
          text: `Bordereau ${this.isEditing ? 'modifié' : 'créé'} avec succès`,
          type: 'success'
        });
      } catch (error) {
        console.error('Erreur sauvegarde:', error.response?.data || error.message);
        this.error = error.response?.data?.error || error.message || 'Erreur lors de la sauvegarde';
        this.notify({
          title: 'Erreur',
          text: this.error,
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
        matricules: '',
        objet: '',
        statut: ''
      };
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
      if (!text) return '';
      return text.length > length ? text.substring(0, length) + '...' : text;
    }
  }
};
</script>

<style scoped>
/* CSS inchangé, repris tel quel pour préserver le design */
.bordereaux-page {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
  background-color: #f5f7fa;
  min-height: 100vh;
}

/* En-tête */
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

/* Filtres */
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

.filter-group {
  min-width: 150px;
}

.filter-select {
  width: 100%;
  padding: 10px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 14px;
  background-color: #ffffff;
  transition: border-color 0.3s ease;
}

.filter-select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.filter-select:disabled {
  background-color: #f8f9fa;
  cursor: not-allowed;
}

/* Contenu principal */
.content-section {
  background-color: #ffffff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

/* État de chargement */
.loading {
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

/* État d'erreur */
.error {
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

/* État vide */
.empty-state {
  text-align: center;
  padding: 40px;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
  color: #6c757d;
}

/* Tableau */
.table-container {
  overflow-x: auto;
}

.bordereaux-table {
  width: 100%;
  border-collapse: collapse;
  background-color: #ffffff;
}

.bordereaux-table th,
.bordereaux-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e0e0e0;
}

.bordereaux-table th {
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

.status-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
}

.status-badge.mandatement {
  background-color: #d4edda;
  color: #155724;
}

.status-badge.secours {
  background-color: #fff3cd;
  color: #856404;
}

.status-badge.visa {
  background-color: #d1ecf1;
  color: #0c5460;
}

.actions-cell {
  display: flex;
  gap: 8px;
}

.btn-action {
  padding: 6px;
  border: none;
  border-radius: 4px;
  background-color: #f8f9fa;
  cursor: pointer;
  font-size: 16px;
  transition: background-color 0.3s ease;
  text-decoration: none;
  display: inline-block;
}

.btn-action:hover:not(:disabled) {
  background-color: #007bff;
  color: #ffffff;
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

/* Pagination */
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

/* Modal */
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

.form-input,
.form-textarea,
.form-select {
  width: 100%;
  padding: 10px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 14px;
  background-color: #ffffff;
  transition: border-color 0.3s ease;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-input:disabled,
.form-textarea:disabled,
.form-select:disabled {
  background-color: #f8f9fa;
  cursor: not-allowed;
}

.form-textarea {
  resize: vertical;
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

/* Responsive */
@media (max-width: 768px) {
  .bordereaux-page {
    padding: 16px;
  }

  .filters-section {
    flex-direction: column;
  }

  .search-box,
  .filter-group {
    min-width: 100%;
  }
}

@media (max-width: 480px) {
  .bordereaux-page {
    padding: 12px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .actions-cell {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .btn-action {
    width: 100%;
    text-align: center;
  }

  .modal {
    max-width: 90%;
  }
}
</style>