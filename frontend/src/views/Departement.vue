<template>
  <div class="departement-page">
    <!-- En-tête de page -->
    <div class="page-header">
      <h2>📋 Gestion des Départements</h2>
      <button class="btn-primary" @click="showAddModal = true" :disabled="loading">
        <span class="btn-icon">➕</span>
        Nouveau Département
      </button>
    </div>

    <!-- Filtres et recherche -->
    <div class="filters-section">
      <!-- Contrôle de navigation par mois -->
      <div class="month-nav">
        <button @click="prevMonth" :disabled="loading" class="nav-btn">⬅ Précédent</button>
        <span class="month-label">{{ currentMonthYear }}</span>
        <button @click="nextMonth" :disabled="loading" class="nav-btn">Suivant ➡</button>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="content-section">
      <!-- États de chargement et d'erreur -->
      <div class="loading" v-if="loading">
        <div class="spinner"></div>
        <p>Chargement des départements...</p>
      </div>
      
      <div class="error" v-else-if="error">
        <div class="error-icon">❌</div>
        <h3>Erreur de chargement</h3>
        <p>{{ error }}</p>
        <button class="btn-retry" @click="loadDepartements">Réessayer</button>
      </div>

      <!-- État vide -->
      <div v-else-if="filteredDepartements.length === 0" class="empty-state">
        <div class="empty-icon">📋</div>
        <h3>Aucun département trouvé</h3>
        <p v-if="currentMonthYear">Aucun résultat pour {{ currentMonthYear }}</p>
        <p v-else>Commencez par créer votre premier département</p>
        <button class="btn-primary" @click="showAddModal = true" :disabled="loading">
          ➕ Créer un département
        </button>
      </div>

      <!-- Tableau des départements -->
      <div v-else class="table-container">
        <table class="departements-table">
          <thead>
            <tr>
              <th @click="sortBy('id_departement')" class="sortable">
                ID 📊 <span v-if="sortField === 'id_departement'" class="sort-icon">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th @click="sortBy('expediteur')" class="sortable">
                Expéditeur 📤 <span v-if="sortField === 'expediteur'" class="sort-icon">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th @click="sortBy('destination')" class="sortable">
                Destination 📍 <span v-if="sortField === 'destination'" class="sort-icon">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th @click="sortBy('nature')" class="sortable">
                Nature 📝 <span v-if="sortField === 'nature'" class="sort-icon">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th @click="sortBy('date_creation')" class="sortable">
                Date 📅 <span v-if="sortField === 'date_creation'" class="sort-icon">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="departement in paginatedDepartements" :key="departement.id_departement">
              <td class="id-cell" data-label="ID">#{{ departement.id_departement }}</td>
              <td class="expediteur-cell" data-label="Expéditeur">{{ departement.expediteur }}</td>
              <td class="destination-cell" data-label="Destination">{{ departement.destination }}</td>
              <td class="nature-cell" data-label="Nature">{{ departement.nature }}</td>
              <td class="date-cell" data-label="Date">{{ formatDate(departement.date_creation) }}</td>
              <td class="actions-cell" data-label="Actions">
                <button class="btn-action edit-btn" @click="editDepartement(departement)" title="Modifier" :disabled="loading">✏️</button>
                <button class="btn-action delete-btn" @click="confirmDelete(departement)" title="Supprimer" :disabled="loading">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination" v-if="filteredDepartements.length > itemsPerPage">
          <button @click="prevPage" :disabled="currentPage === 1 || loading" class="pagination-btn">← Précédent</button>
          <span class="page-info">Page {{ currentPage }} sur {{ totalPages }}</span>
          <button @click="nextPage" :disabled="currentPage === totalPages || loading" class="pagination-btn">Suivant →</button>
        </div>

        <!-- Informations de pagination -->
        <div class="pagination-info">Affichage de {{ startIndex + 1 }} à {{ endIndex }} sur {{ filteredDepartements.length }} départements</div>
      </div>
    </div>

    <!-- Modal d'ajout/modification -->
    <div v-if="showAddModal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ isEditing ? 'Modifier' : 'Ajouter' }} un département</h3>
          <button class="modal-close" @click="closeModal">×</button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="saveDepartement">
            <div class="form-group">
              <label>ID Département *</label>
              <input
                v-model.number="formData.id_departement"
                type="number"
                class="form-input"
                required
                :disabled="isEditing || saving"
                min="1"
                placeholder="Entrez un ID unique"
              />
            </div>
            <div class="form-group">
              <label>Expéditeur *</label>
              <select
                v-model="formData.expediteur"
                class="form-input"
                required
                :disabled="saving"
              >
                <option value="" disabled>Sélectionner un expéditeur</option>
                <option value="SRSP Atsimo Andrefana">SRSP Atsimo Andrefana</option>
              </select>
            </div>
            <div class="form-group">
              <label>Destination *</label>
              <input
                v-model="formData.destination"
                type="text"
                placeholder="Ex: Antananarivo"
                class="form-input"
                required
                :disabled="saving"
                maxlength="100"
              />
            </div>
            <div class="form-group">
              <label>Nature *</label>
              <input
                v-model="formData.nature"
                type="text"
                placeholder="Ex: Rapport financier"
                class="form-input"
                required
                :disabled="saving"
                maxlength="100"
              />
            </div>
            <div class="form-actions">
              <button type="button" @click="closeModal" class="btn-cancel" :disabled="saving">Annuler</button>
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
          <p>Êtes-vous sûr de vouloir supprimer le département <strong>{{ departementToDelete?.destination }}</strong> (ID: <strong>{{ departementToDelete?.id_departement }}</strong>) ?</p>
          <p class="warning-text">⚠️ Cette action est irréversible !</p>
          <div class="delete-actions">
            <button @click="showDeleteModal = false" class="btn-cancel" :disabled="deleting">Annuler</button>
            <button @click="deleteDepartement" class="btn-delete" :disabled="deleting">{{ deleting ? 'Suppression...' : 'Supprimer' }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Composant de notifications -->
    <notifications position="top right" />
  </div>
</template>

<script>
import { departementService } from '../services/api';
import { useNotification } from '@kyvg/vue3-notification';

export default {
  name: 'DepartementList',
  setup() {
    const { notify } = useNotification();
    return { notify };
  },
  data() {
    return {
      departements: [],
      loading: true,
      error: null,
      sortField: 'id_departement',
      sortOrder: 'desc',
      currentPage: 1,
      itemsPerPage: 10,
      showAddModal: false,
      showDeleteModal: false,
      isEditing: false,
      saving: false,
      deleting: false,
      formData: {
        id_departement: null,
        expediteur: 'SRSP Atsimo Andrefana', // Valeur par défaut
        destination: '',
        nature: ''
      },
      departementToDelete: null,
      currentMonth: new Date().getMonth() + 1, // Mois courant (1-12)
      currentYear: new Date().getFullYear(),   // Année courante
      interval: null
    };
  },
  computed: {
    filteredDepartements() {
      return this.departements.filter(d => {
        const date = new Date(d.date_creation);
        return date.getMonth() + 1 === this.currentMonth && date.getFullYear() === this.currentYear;
      }).sort((a, b) => {
        let modifier = this.sortOrder === 'asc' ? 1 : -1;
        if (a[this.sortField] < b[this.sortField]) return -1 * modifier;
        if (a[this.sortField] > b[this.sortField]) return 1 * modifier;
        return 0;
      });
    },
    paginatedDepartements() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      return this.filteredDepartements.slice(start, start + this.itemsPerPage);
    },
    totalPages() {
      return Math.ceil(this.filteredDepartements.length / this.itemsPerPage);
    },
    startIndex() {
      return (this.currentPage - 1) * this.itemsPerPage;
    },
    endIndex() {
      return Math.min(this.startIndex + this.itemsPerPage, this.filteredDepartements.length);
    },
    currentMonthYear() {
      const months = [
        'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
      ];
      return `${months[this.currentMonth - 1]} ${this.currentYear}`;
    }
  },
  watch: {
    currentMonth(newMonth, oldMonth) {
      if (newMonth !== oldMonth) {
        this.currentPage = 1; // Réinitialiser la pagination
      }
    },
    currentYear(newYear, oldYear) {
      if (newYear !== oldYear) {
        this.currentPage = 1; // Réinitialiser la pagination
      }
    }
  },
  async mounted() {
    this.updateCurrentDate();
    await this.loadDepartements();
    this.interval = setInterval(this.updateCurrentDate, 60000);
  },
  beforeUnmount() {
    if (this.interval) clearInterval(this.interval);
  },
  methods: {
    async loadDepartements() {
      try {
        this.loading = true;
        this.error = null;
        this.departements = await departementService.getDepartements();
      } catch (error) {
        this.error = error.response?.data?.error || error.message || 'Erreur de connexion à l\'API';
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
    editDepartement(departement) {
      this.isEditing = true;
      this.formData = { ...departement };
      this.showAddModal = true;
    },
    confirmDelete(departement) {
      this.departementToDelete = departement;
      this.showDeleteModal = true;
    },
    async deleteDepartement() {
      try {
        this.deleting = true;
        await departementService.deleteDepartement(this.departementToDelete.id_departement);
        await this.loadDepartements();
        this.showDeleteModal = false;
        this.notify({
          title: 'Succès',
          text: 'Département supprimé avec succès',
          type: 'success'
        });
      } catch (error) {
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
    async saveDepartement() {
      try {
        this.saving = true;

        if (!this.formData.id_departement || this.formData.id_departement < 1) {
          throw new Error('L\'ID département doit être un nombre positif');
        }

        if (this.formData.destination.length > 100) {
          throw new Error('La destination dépasse la limite de 100 caractères');
        }

        if (this.formData.nature.length > 100) {
          throw new Error('La nature dépasse la limite de 100 caractères');
        }

        if (this.formData.expediteur.length > 100) {
          throw new Error('L\'expéditeur dépasse la limite de 100 caractères');
        }

        const data = { ...this.formData };
        if (this.isEditing) {
          await departementService.updateDepartement(data.id_departement, data);
          this.notify({
            title: 'Succès',
            text: 'Département modifié avec succès',
            type: 'success'
          });
        } else {
          await departementService.createDepartement(data);
          this.notify({
            title: 'Succès',
            text: `Département créé avec succès (ID: ${data.id_departement})`,
            type: 'success'
          });
        }
        await this.loadDepartements();
        this.closeModal();
      } catch (error) {
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
        id_departement: null,
        expediteur: 'SRSP Atsimo Andrefana', // Réinitialiser à la valeur par défaut
        destination: '',
        nature: ''
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
    updateCurrentDate() {
      const now = new Date();
      const newMonth = now.getMonth() + 1;
      const newYear = now.getFullYear();
      if (this.currentMonth !== newMonth || this.currentYear !== newYear) {
        this.currentMonth = newMonth;
        this.currentYear = newYear;
      }
    },
    prevMonth() {
      if (this.currentMonth === 1) {
        this.currentMonth = 12;
        this.currentYear--;
      } else {
        this.currentMonth--;
      }
      this.currentPage = 1;
    },
    nextMonth() {
      if (this.currentMonth === 12) {
        this.currentMonth = 1;
        this.currentYear++;
      } else {
        this.currentMonth++;
      }
      this.currentPage = 1;
    }
  }
};
</script>

<style scoped>
/* Réutilise les styles de Bordereaux.vue avec des ajustements */
.departement-page {
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

.month-nav {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 200px;
}

.month-label {
  font-weight: 500;
  color: #1a3c34;
  font-size: 14px;
}

.nav-btn {
  padding: 8px 12px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  background-color: #ffffff;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.nav-btn:hover:not(:disabled) {
  background-color: #007bff;
  color: #ffffff;
  border-color: #007bff;
}

.nav-btn:disabled {
  background-color: #f8f9fa;
  cursor: not-allowed;
  opacity: 0.6;
}

.content-section {
  background-color: #ffffff;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

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

.empty-state {
  text-align: center;
  padding: 40px;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
  color: #6c757d;
}

.table-container {
  overflow-x: auto;
}

.departements-table {
  width: 100%;
  border-collapse: collapse;
  background-color: #ffffff;
}

.departements-table th,
.departements-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e0e0e0;
}

.departements-table th {
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

/* Ajout d'une classe spécifique pour le select */
.form-input.select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
  padding-right: 30px; /* Espace pour la flèche */
}

.form-actions {
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

.delete-modal .modal-body {
  padding: 24px;
}

.warning-text {
  color: #dc3545;
  margin-top: 8px;
}

.delete-actions {
  display: flex;
  gap: 16px;
  justify-content: flex-end;
  margin-top: 24px;
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

/* Responsive */
@media (max-width: 1024px) {
  .departements-table th,
  .departements-table td {
    font-size: 14px;
    padding: 8px;
  }
}

@media (max-width: 768px) {
  .departement-page {
    padding: 16px;
  }

  .filters-section {
    flex-direction: column;
  }

  .modal {
    max-width: 90%;
    height: 90vh;
    overflow-y: auto;
  }
}

@media (max-width: 480px) {
  .departement-page {
    padding: 12px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .filters-section {
    flex-direction: column;
  }

  .month-nav {
    min-width: 100%;
  }

  .departements-table {
    display: block;
    overflow-x: auto;
  }

  .departements-table thead {
    display: none;
  }

  .departements-table tbody,
  .departements-table tr {
    display: block;
    width: 100%;
  }

  .departements-table td {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    border-bottom: 1px solid #e0e0e0;
    position: relative;
  }

  .departements-table td::before {
    content: attr(data-label);
    font-weight: bold;
    width: 120px;
    min-width: 120px;
    margin-right: 10px;
    color: #1a3c34;
  }

  .departements-table td[data-label="ID"]::before { content: "ID: "; }
  .departements-table td[data-label="Expéditeur"]::before { content: "Expéditeur: "; }
  .departements-table td[data-label="Destination"]::before { content: "Destination: "; }
  .departements-table td[data-label="Nature"]::before { content: "Nature: "; }
  .departements-table td[data-label="Date"]::before { content: "Date: "; }
  .departements-table td[data-label="Actions"]::before { content: "Actions: "; }
}
</style>