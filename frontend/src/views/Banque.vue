<template>
  <div class="banque-page">
    <!-- En-tête de page -->
    <div class="page-header">
      <h2>🏦 Gestion des Banques</h2>
      <div class="user-info" v-if="currentUser">
        <span class="user-email">{{ currentUser.email }}</span>
        <span class="user-type" :class="currentUser.type_utilisateur">{{ currentUser.type_utilisateur }}</span>
      </div>
      <button 
        class="btn-primary" 
        @click="openAddModal" 
        :disabled="loading || !canCreate"
        v-if="canCreate"
      >
        <span class="btn-icon">➕</span>
        Nouvelle Banque
      </button>
    </div>

    <!-- Filtres et recherche -->
    <div class="filters-section">
      <div class="search-box">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher par ID ou nom..."
          class="search-input"
          :disabled="loading"
        />
        <span class="search-icon">🔍</span>
      </div>
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
        <p>Chargement des banques...</p>
      </div>
      
      <div class="error" v-else-if="error">
        <div class="error-icon">❌</div>
        <h3>Erreur de chargement</h3>
        <p>{{ error }}</p>
        <button class="btn-retry" @click="loadBanques">Réessayer</button>
      </div>

      <!-- État vide -->
      <div v-else-if="filteredBanques.length === 0" class="empty-state">
        <div class="empty-icon">🏦</div>
        <h3>Aucune banque trouvée</h3>
        <p v-if="searchQuery">Aucun résultat pour vos critères de recherche</p>
        <p v-else>Commencez par ajouter une banque pour {{ currentMonthYear }}</p>
        <button 
          class="btn-primary" 
          @click="openAddModal" 
          :disabled="loading || !canCreate"
          v-if="canCreate"
        >
          ➕ Ajouter une banque
        </button>
      </div>

      <!-- Tableau des banques -->
      <div v-else class="table-container">
        <table class="banques-table">
          <thead>
            <tr>
              <th @click="sortBy('id_banque')" class="sortable">
                ID 📊 <span v-if="sortField === 'id_banque'" class="sort-icon">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th @click="sortBy('nom')" class="sortable">
                Nom 📝 <span v-if="sortField === 'nom'" class="sort-icon">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th>Section</th>
              <th>Date 📅</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="banque in paginatedBanques" :key="banque.id_banque">
              <td class="id-cell">{{ banque.id_banque }}</td>
              <td class="nom-cell"><strong>{{ banque.nom }}</strong></td>
              <td class="section-cell">{{ banque.section }}</td>
              <td class="date-cell">{{ formatDate(banque.date_creation) }}</td>
              <td class="actions-cell">
                <!-- Bouton Modifier - seulement pour admin -->
                <button 
                  v-if="canUpdate"
                  class="btn-action edit-btn" 
                  @click="editBanque(banque)" 
                  title="Modifier" 
                  :disabled="loading"
                >
                  ✏️
                </button>
                
                <!-- Bouton Supprimer - seulement pour admin -->
                <button 
                  v-if="canDelete"
                  class="btn-action delete-btn" 
                  @click="confirmDelete(banque)" 
                  title="Supprimer" 
                  :disabled="loading"
                >
                  🗑️
                </button>

                <!-- Message pour utilisateurs non-admin -->
                <span v-if="!canUpdate" class="no-permission-text">
                  Lecture seule
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination" v-if="filteredBanques.length > itemsPerPage">
          <button @click="prevPage" :disabled="currentPage === 1 || loading" class="pagination-btn">← Précédent</button>
          <span class="page-info">Page {{ currentPage }} sur {{ totalPages }}</span>
          <button @click="nextPage" :disabled="currentPage === totalPages || loading" class="pagination-btn">Suivant →</button>
        </div>

        <!-- Informations de pagination -->
        <div class="pagination-info">Affichage de {{ startIndex + 1 }} à {{ endIndex }} sur {{ filteredBanques.length }} banques</div>
      </div>
    </div>

    <!-- Modal d'ajout/modification -->
    <div v-if="showAddModal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h3>{{ isEditing ? 'Modifier' : 'Ajouter' }} une banque</h3>
          <button class="modal-close" @click="closeModal">×</button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="saveBanque">
            <div class="form-group">
              <label>ID Banque *</label>
              <input
                v-model.number="formData.id_banque"
                type="number"
                class="form-input"
                required
                :disabled="isEditing || saving"
                min="1"
                placeholder="Entrez un ID unique"
              />
              <small v-if="idError" class="error-text">{{ idError }}</small>
            </div>
            <div class="form-group">
              <label>Nom *</label>
              <input
                v-model="formData.nom"
                type="text"
                placeholder="Ex: Rakoto"
                class="form-input"
                required
                :disabled="saving"
                maxlength="100"
              />
            </div>
            <div class="form-group">
              <label>Section *</label>
              <select v-model="formData.section" required class="form-select" :disabled="saving">
                <option value="" disabled>Sélectionner une section</option>
                <option value="PVO005">PVO005</option>
                <option value="PSC005">PSC005</option>
              </select>
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
          <p>Êtes-vous sûr de vouloir supprimer la banque <strong>{{ banqueToDelete?.nom }}</strong> (ID: <strong>{{ banqueToDelete?.id_banque }}</strong>) ?</p>
          <p class="warning-text">⚠️ Cette action est irréversible !</p>
          <div class="delete-actions">
            <button @click="showDeleteModal = false" class="btn-cancel" :disabled="deleting">Annuler</button>
            <button @click="deleteBanque" class="btn-delete" :disabled="deleting">{{ deleting ? 'Suppression...' : 'Supprimer' }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Composant de notifications -->
    <notifications position="top right" />
  </div>
</template>

<script>
import { banqueService } from '../services/api';
import { useNotification } from '@kyvg/vue3-notification';
import { authService } from '../services/auth'; // IMPORT DU SERVICE AUTH

export default {
  name: 'BanqueView',
  setup() {
    const { notify } = useNotification();
    return { notify };
  },
  data() {
    return {
      banques: [],
      loading: true,
      error: null,
      searchQuery: '',
      sortField: 'id_banque',
      sortOrder: 'asc',
      currentPage: 1,
      itemsPerPage: 10,
      showAddModal: false,
      showDeleteModal: false,
      isEditing: false,
      saving: false,
      deleting: false,
      formData: {
        id_banque: null,
        nom: '',
        section: ''
      },
      banqueToDelete: null,
      idError: '',
      currentMonth: new Date().getMonth() + 1, // Mois courant (1-12)
      currentYear: new Date().getFullYear(),   // Année courante
      interval: null,
      currentUser: null // AJOUT pour stocker l'utilisateur connecté
    };
  },
  computed: {
    filteredBanques() {
      let result = [...this.banques];
      
      // Appliquer le filtre par mois seulement si aucun filtre de recherche n'est actif
      if (!this.searchQuery) {
        result = result.filter(b => {
          const date = new Date(b.date_creation);
          return date.getMonth() + 1 === this.currentMonth && date.getFullYear() === this.currentYear;
        });
      }

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        result = result.filter(b =>
          b.id_banque.toString().includes(query) ||
          b.nom.toLowerCase().includes(query)
        );
      }
      return result.sort((a, b) => {
        let modifier = this.sortOrder === 'asc' ? 1 : -1;
        if (a[this.sortField] < b[this.sortField]) return -1 * modifier;
        if (a[this.sortField] > b[this.sortField]) return 1 * modifier;
        return 0;
      });
    },
    paginatedBanques() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      return this.filteredBanques.slice(start, start + this.itemsPerPage);
    },
    totalPages() {
      return Math.ceil(this.filteredBanques.length / this.itemsPerPage);
    },
    startIndex() {
      return (this.currentPage - 1) * this.itemsPerPage;
    },
    endIndex() {
      return Math.min(this.startIndex + this.itemsPerPage, this.filteredBanques.length);
    },
    currentMonthYear() {
      const months = [
        'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
      ];
      return `${months[this.currentMonth - 1]} ${this.currentYear}`;
    },
    maxId() {
      return this.banques.reduce((max, b) => Math.max(max, b.id_banque || 0), 0);
    },
    // NOUVEAU: Permissions basées sur l'utilisateur
    canCreate() {
      return this.currentUser && this.currentUser.type_utilisateur === 'admin';
    },
    
    canUpdate() {
      return this.currentUser && this.currentUser.type_utilisateur === 'admin';
    },
    
    canDelete() {
      return this.currentUser && this.currentUser.type_utilisateur === 'admin';
    }
  },
  watch: {
    searchQuery() {
      this.currentPage = 1;
    },
    // Détecter un changement de mois automatique
    currentMonth(newMonth, oldMonth) {
      if (newMonth !== oldMonth) {
        this.currentPage = 1; // Réinitialiser la pagination
        this.loadBanques(); // Recharger les données si nécessaire (optionnel)
      }
    },
    currentYear(newYear, oldYear) {
      if (newYear !== oldYear) {
        this.currentPage = 1; // Réinitialiser la pagination
        this.loadBanques(); // Recharger les données si nécessaire (optionnel)
      }
    }
  },
  async mounted() {
    // Récupérer l'utilisateur connecté
    this.currentUser = authService.getCurrentUser();
    
    if (!this.currentUser) {
      this.notify({
        title: 'Erreur',
        text: 'Utilisateur non connecté',
        type: 'error'
      });
      return;
    }
    
    console.log('Utilisateur connecté:', this.currentUser);
    
    this.updateCurrentDate();
    await this.loadBanques();
    // Vérifier le mois toutes les minutes
    this.interval = setInterval(this.updateCurrentDate, 60000);
  },
  beforeUnmount() {
    if (this.interval) clearInterval(this.interval); // Nettoyer l'intervalle
  },
  methods: {
    async loadBanques() {
      try {
        this.loading = true;
        this.error = null;
        const response = await banqueService.getBanques();
        this.banques = response;
      } catch (error) {
        this.error = error.response?.data?.error || 'Erreur de connexion à l\'API';
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

    // MODIFIÉ: Vérifier les permissions avant modification
    editBanque(banque) {
      if (!this.canUpdate) {
        this.notify({
          title: 'Permission refusée',
          text: 'Vous n\'avez pas les droits pour modifier les banques',
          type: 'warning'
        });
        return;
      }
      this.isEditing = true;
      this.formData = { ...banque };
      this.showAddModal = true;
    },

    // MODIFIÉ: Vérifier les permissions avant suppression
    confirmDelete(banque) {
      if (!this.canDelete) {
        this.notify({
          title: 'Permission refusée',
          text: 'Vous n\'avez pas les droits pour supprimer les banques',
          type: 'warning'
        });
        return;
      }
      this.banqueToDelete = banque;
      this.showDeleteModal = true;
    },

    async deleteBanque() {
      try {
        this.deleting = true;
        await banqueService.deleteBanque(this.banqueToDelete.id_banque);
        await this.loadBanques();
        this.showDeleteModal = false;
        this.notify({
          title: 'Succès',
          text: 'Banque supprimée avec succès',
          type: 'success'
        });
      } catch (error) {
        this.error = error.response?.data?.error || 'Erreur lors de la suppression';
        this.notify({
          title: 'Erreur',
          text: this.error,
          type: 'error'
        });
      } finally {
        this.deleting = false;
      }
    },

    // MODIFIÉ: Vérifier les permissions avant sauvegarde
    async saveBanque() {
      if (this.isEditing && !this.canUpdate) {
        this.notify({
          title: 'Permission refusée',
          text: 'Vous n\'avez pas les droits pour modifier les banques',
          type: 'warning'
        });
        return;
      }
      
      if (!this.isEditing && !this.canCreate) {
        this.notify({
          title: 'Permission refusée',
          text: 'Vous n\'avez pas les droits pour créer des banques',
          type: 'warning'
        });
        return;
      }

      try {
        this.saving = true;
        this.idError = '';

        // Vérification côté client si id_banque existe déjà
        if (this.banques.some(b => b.id_banque === this.formData.id_banque && (!this.isEditing || b.id_banque !== this.formData.id_banque))) {
          this.idError = 'Cet ID banque existe déjà';
          return;
        }

        const data = {
          id_banque: this.formData.id_banque,
          nom: this.formData.nom,
          section: this.formData.section
        };
        if (this.isEditing) {
          await banqueService.updateBanque(this.formData.id_banque, data);
          this.notify({
            title: 'Succès',
            text: 'Banque modifiée avec succès',
            type: 'success'
          });
        } else {
          const response = await banqueService.createBanque(data);
          this.notify({
            title: 'Succès',
            text: `Banque créée avec succès (ID: ${response.id_banque})`,
            type: 'success'
          });
        }
        await this.loadBanques();
        this.closeModal();
      } catch (error) {
        this.error = error.response?.data?.error || 'Erreur lors de l\'enregistrement';
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
        id_banque: null,
        nom: '',
        section: ''
      };
      this.idError = '';
      this.error = null;
    },
    openAddModal() {
      this.isEditing = false;
      this.formData = {
        id_banque: this.maxId + 1 || 1,
        nom: '',
        section: ''
      };
      this.idError = '';
      this.error = null;
      this.showAddModal = true;
    },
    nextPage() {
      if (this.currentPage < this.totalPages && !this.loading) this.currentPage++
    },
    prevPage() {
      if (this.currentPage > 1 && !this.loading) this.currentPage--;
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
      const newMonth = now.getMonth() + 1; // Mois en 1-12
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
      this.currentPage = 1; // Réinitialiser la pagination
    },
    nextMonth() {
      if (this.currentMonth === 12) {
        this.currentMonth = 1;
        this.currentYear++;
      } else {
        this.currentMonth++;
      }
      this.currentPage = 1; // Réinitialiser la pagination
    }
  }
};
</script>

<style scoped>
.banque-page {
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

/* NOUVEAUX STYLES POUR LES PERMISSIONS */
.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-left: auto;
  margin-right: 16px;
}

.user-email {
  font-size: 14px;
  color: #6c757d;
}

.user-type {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
}

.user-type.admin {
  background-color: #d4edda;
  color: #155724;
}

.user-type.user {
  background-color: #fff3cd;
  color: #856404;
}

.no-permission-text {
  font-size: 12px;
  color: #6c757d;
  font-style: italic;
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

/* Navigation par mois */
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

.banques-table {
  width: 100%;
  border-collapse: collapse;
  background-color: #ffffff;
}

.banques-table th,
.banques-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e0e0e0;
}

.banques-table th {
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
  align-items: center;
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

.form-input,
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
.form-select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-input:disabled,
.form-select:disabled {
  background-color: #f8f9fa;
  cursor: not-allowed;
}

.error-text {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: #dc3545;
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
  .banque-page {
    padding: 16px;
  }

  .filters-section {
    flex-direction: column;
  }

  .search-box,
  .month-nav {
    min-width: 100%;
  }

  .user-info {
    margin-left: 0;
    margin-right: 0;
    margin-top: 10px;
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .banque-page {
    padding: 12px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .actions-cell {
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