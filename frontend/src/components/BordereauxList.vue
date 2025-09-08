<template>
  <div>
    <h1>Liste des Bordereaux</h1>
    <div v-if="loading">Chargement...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <div v-else>
      <ul>
        <li v-for="bordereau in bordereaux" :key="bordereau.id_bordereau">
          <strong>{{ bordereau.reference }}</strong> - 
          {{ bordereau.objet }} ({{ bordereau.statut }})
          <ul>
            <li v-for="d in bordereau.dossiers" :key="d.id_dossier">
              Matricule : {{ d.matricule }}
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import api from "../services/api";

export default {
  name: "BordereauxList",
  data() {
    return {
      bordereaux: [],
      loading: true,
      error: null
    };
  },
  async mounted() {
    await this.loadBordereaux();
  },
  methods: {
    async loadBordereaux() {
      try {
        this.loading = true;
        const response = await api.getBordereaux();
        
        // Vérifier la structure de réponse de votre API
        if (response.data.status === "success") {
          this.bordereaux = response.data.data;
        } else {
          this.error = response.data.message || "Erreur inconnue";
        }
      } catch (error) {
        this.error = "Erreur de connexion à l'API";
        console.error("Erreur:", error);
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style>
.error {
  color: red;
  font-weight: bold;
}
</style>