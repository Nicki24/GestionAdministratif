import axios from "axios";

// Configuration de base d'Axios
const API_BASE_URL = "http://localhost/bordereau/backend";

// Créer une instance axios avec une configuration de base
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    "Content-Type": "application/json",
  },
  timeout: 10000, // 10 secondes de timeout
});

// Intercepteur pour les requêtes
api.interceptors.request.use(
  (config) => {
    console.log(`🔄 Making ${config.method?.toUpperCase()} request to: ${config.url}`);
    return config;
  },
  (error) => {
    console.error("❌ Request error:", error);
    return Promise.reject(error);
  }
);

// Intercepteur pour les réponses
api.interceptors.response.use(
  (response) => {
    console.log("✅ Response received:", response.status, response.data);
    return response;
  },
  (error) => {
    console.error("❌ Response error:", error.response?.status, error.message);
    if (error.response) {
      switch (error.response.status) {
        case 404:
          error.message = "Ressource non trouvée";
          break;
        case 500:
          error.message = "Erreur interne du serveur";
          break;
        default:
          error.message = `Erreur ${error.response.status}: ${error.response.statusText}`;
      }
    } else if (error.request) {
      error.message = "Impossible de se connecter au serveur. Vérifiez que le serveur est démarré.";
    } else {
      error.message = "Erreur de configuration de la requête";
    }
    return Promise.reject(error);
  }
);

// Service pour les bordereaux
export const bordereauService = {
  // Récupérer tous les bordereaux
  async getBordereaux() {
    try {
      const response = await api.get("/bordereau_api.php");
      return response.data; // Retourne un tableau de bordereaux
    } catch (error) {
      console.error("Erreur dans getBordereaux:", error);
      throw error;
    }
  },

  // Récupérer un bordereau spécifique
  async getBordereau(id) {
    try {
      const response = await api.get(`/bordereau_api.php?id_bordereau=${id}`);
      return response.data; // Retourne un tableau de bordereaux pour l'id_bordereau
    } catch (error) {
      console.error("Erreur dans getBordereau:", error);
      throw error;
    }
  },

  // Créer un nouveau bordereau
  async createBordereau(data) {
    try {
      const response = await api.post("/bordereau_api.php", data);
      return response.data; // Retourne { message: "Bordereau créé", id_bordereau: <id>, matricules: [...] }
    } catch (error) {
      console.error("Erreur dans createBordereau:", error);
      throw error;
    }
  },

  // Modifier un bordereau
  async updateBordereau(id_bordereau, data) {
    try {
      const response = await api.put(`/bordereau_api.php?id_bordereau=${id_bordereau}`, data);
      return response.data; // Retourne { message: "Bordereau mis à jour" }
    } catch (error) {
      console.error("Erreur dans updateBordereau:", error);
      throw error;
    }
  },

  // Supprimer un bordereau
  async deleteBordereau(id_bordereau) {
    try {
      const response = await api.delete(`/bordereau_api.php?id_bordereau=${id_bordereau}`);
      return response.data; // Retourne { message: "Bordereau supprimé" }
    } catch (error) {
      console.error("Erreur dans deleteBordereau:", error);
      throw error;
    }
  },

  // Supprimer un bordereau complet
  async deleteBordereauComplet(id_bordereau) {
    try {
      const response = await api.delete(`/bordereau_api.php?id_bordereau=${id_bordereau}`);
      return response.data; // Retourne { message: "Bordereau supprimé" }
    } catch (error) {
      console.error("Erreur dans deleteBordereauComplet:", error);
      throw error;
    }
  }
};

// Service pour les banques
export const banqueService = {
  // Récupérer toutes les banques
  async getBanques() {
    try {
      const response = await api.get("/banque_api.php");
      return response.data; // Retourne un tableau de banques
    } catch (error) {
      console.error("Erreur dans getBanques:", error);
      throw error;
    }
  },

  // Créer une nouvelle banque
  async createBanque(data) {
    try {
      const response = await api.post("/banque_api.php", data);
      return response.data; // Retourne { message: "Banque ajoutée", id_banque: <id> }
    } catch (error) {
      console.error("Erreur dans createBanque:", error);
      throw error;
    }
  },

  // Modifier une banque
  async updateBanque(id_banque, data) {
    try {
      const response = await api.put(`/banque_api.php?id_banque=${id_banque}`, data);
      return response.data; // Retourne { message: "Banque mise à jour" }
    } catch (error) {
      console.error("Erreur dans updateBanque:", error);
      throw error;
    }
  },

  // Supprimer une banque
  async deleteBanque(id_banque) {
    try {
      const response = await api.delete(`/banque_api.php?id_banque=${id_banque}`);
      return response.data; // Retourne { message: "Banque supprimée" }
    } catch (error) {
      console.error("Erreur dans deleteBanque:", error);
      throw error;
    }
  }
};

export default api;