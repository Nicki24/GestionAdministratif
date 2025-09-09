<template>
  <div class="dossiers-page">
    <div class="page-header">
      <h2>Gestion des Dossiers</h2>
      <button class="btn-primary" @click="showAddModal = true" :disabled="loading">
        <span class="btn-icon">📁</span>
        Nouveau Dossier
      </button>
    </div>

    <div class="content-section">
      <div v-if="loading" class="loading-section">
        <div class="spinner"></div>
        <p>Chargement des dossiers...</p>
      </div>

      <div v-if="error" class="error-section">
        <div class="error-icon">❌</div>
        <p>{{ error }}</p>
        <button @click="loadDossiers" class="btn-retry">Réessayer</button>
      </div>

      <div v-if="!loading && !error && dossiers.length === 0" class="empty-state">
        <div class="empty-icon">📁</div>
        <h3>Aucun dossier trouvé</h3>
        <p>Créez un nouveau dossier pour ce bordereau.</p>
      </div>

      <div v-if="!loading && !error && dossiers.length > 0" class="dossiers-table">
        <table>
          <thead>
            <tr>
              <th>ID Dossier</th>
              <th>Matricule</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="dossier in dossiers" :key="dossier.id_dossier">
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
          <p>Êtes-vous sûr de vouloir supprimer le dossier #{{ dossierToDelete?.id_dossier }} (Matricule: {{ dossierToDelete?.matricule }}) ?</p>
          <p class="warning-text">⚠️ Cette action est irréversible !</p>
          <div class="delete-actions">
            <button @click="showDeleteModal = false" class="btn-cancel" :disabled="deleting">Annuler</button>
            <button @click="deleteDossier" class="btn-delete" :disabled="deleting">{{ deleting ? 'Suppression...' : 'Supprimer' }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { dossierService } from '../services/api';

export default {
  name: 'DossierView',
  props: {
    id_bordereau: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      loading: true,
      error: null,
      dossiers: [],
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
  async mounted() {
    await this.loadDossiers();
  },
  methods: {
    async loadDossiers() {
      try {
        this.loading = true;
        this.error = null;
        const response = await dossierService.getDossiersByBordereau(this.id_bordereau);
        if (response.status === 'success') {
          this.dossiers = response.data || [];
        } else {
          this.error = response.message || 'Erreur lors du chargement des dossiers';
        }
      } catch (error) {
        this.error = error.message || 'Erreur de connexion à l\'API';
      } finally {
        this.loading = false;
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
        } else {
          this.error = response.message || 'Erreur lors de l\'enregistrement';
        }
      } catch (error) {
        this.error = error.message || 'Erreur lors de l\'enregistrement';
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
        } else {
          this.error = response.message || 'Erreur lors de la suppression';
        }
      } catch (error) {
        this.error = error.message || 'Erreur lors de la suppression';
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
  },
};
</script>

<style scoped>
.dossiers-page {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.page-header h2 {
  color: #2c3e50;
}

.btn-primary {
  background: #3498db;
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
}

.btn-primary:hover:not(:disabled) {
  background: #2980b9;
}

.btn-primary:disabled {
  background: #95c9e6;
  cursor: not-allowed;
}

.content-section {
  background: white;
  border-radius: 15px;
  padding: 25px;
  min-height: 400px;
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

.empty-state {
  text-align: center;
  padding: 20px;
  color: #7f8c8d;
}

.empty-icon {
  font-size: 2rem;
  margin-bottom: 10px;
}

.dossiers-table table {
  width: 100%;
  border-collapse: collapse;
}

.dossiers-table th,
.dossiers-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.dossiers-table th {
  background: #f8f9fa;
  color: #2c3e50;
}

.btn-action {
  background: none;
  border: none;
  cursor: pointer;
  margin-right: 8px;
  font-size: 1rem;
}

.edit-btn { color: #3498db; }
.delete-btn { color: #e74c3c; }

.btn-action:disabled {
  color: #95c9e6;
  cursor: not-allowed;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal {
  background: white;
  padding: 20px;
  border-radius: 10px;
  width: 400px;
  max-width: 90%;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.modal-header h3 {
  color: #2c3e50;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
}

.modal-body {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  color: #2c3e50;
}

.form-input {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 5px;
}

.form-input:disabled {
  background: #f0f0f0;
  cursor: not-allowed;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn-cancel {
  background: #ddd;
  border: none;
  padding: 8px 16px;
  border-radius: 5px;
  cursor: pointer;
}

.btn-cancel:hover:not(:disabled) {
  background: #ccc;
}

.btn-cancel:disabled {
  background: #f0f0f0;
  cursor: not-allowed;
}

.btn-submit,
.btn-delete {
  background: #3498db;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 5px;
  cursor: pointer;
}

.btn-delete {
  background: #e74c3c;
}

.btn-submit:hover:not(:disabled),
.btn-delete:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-submit:disabled,
.btn-delete:disabled {
  background: #95c9e6;
  cursor: not-allowed;
}

.delete-modal .warning-text {
  color: #e74c3c;
  margin: 10px 0;
}
</style>