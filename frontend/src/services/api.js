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
      // Le serveur a répondu avec un code d'erreur
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
      // La requête a été faite mais aucune réponse n'a été reçue
      error.message = "Impossible de se connecter au serveur. Vérifiez que le serveur est démarré.";
    } else {
      // Une erreur s'est produite lors de la configuration de la requête
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
      
      // Vérifier la structure de réponse de votre API
      if (response.data.status === "success") {
        return { 
          data: response.data.data,
          status: "success"
        };
      } else {
        throw new Error(response.data.message || "Erreur inconnue de l'API");
      }
    } catch (error) {
      console.error("Erreur dans getBordereaux:", error);
      throw error;
    }
  },

  // Récupérer un bordereau spécifique
  async getBordereau(id) {
    try {
      const response = await api.get(`/bordereau_api.php?id_bordereau=${id}`);
      
      if (response.data.status === "success") {
        return { 
          data: response.data.data,
          status: "success"
        };
      } else {
        throw new Error(response.data.message || "Bordereau non trouvé");
      }
    } catch (error) {
      console.error("Erreur dans getBordereau:", error);
      throw error;
    }
  },

  // Créer un nouveau bordereau
  async addBordereau(data) {
    try {
      const response = await api.post("/bordereau_api.php", data);
      
      if (response.data.status === "success") {
        return { 
          data: response.data.data,
          message: response.data.message,
          status: "success"
        };
      } else {
        throw new Error(response.data.message || "Erreur lors de la création");
      }
    } catch (error) {
      console.error("Erreur dans addBordereau:", error);
      throw error;
    }
  },

  // Modifier un bordereau existant
  async updateBordereau(id, data) {
    try {
      const response = await api.put(`/bordereau_api.php?id_bordereau=${id}`, data);
      
      if (response.data.status === "success") {
        return { 
          data: response.data.data,
          message: response.data.message,
          status: "success"
        };
      } else {
        throw new Error(response.data.message || "Erreur lors de la modification");
      }
    } catch (error) {
      console.error("Erreur dans updateBordereau:", error);
      throw error;
    }
  },

  // Supprimer un bordereau
  async deleteBordereau(id) {
    try {
      const response = await api.delete(`/bordereau_api.php?id_bordereau=${id}`);
      
      if (response.data.status === "success") {
        return { 
          data: response.data.data,
          message: response.data.message,
          status: "success"
        };
      } else {
        throw new Error(response.data.message || "Erreur lors de la suppression");
      }
    } catch (error) {
      console.error("Erreur dans deleteBordereau:", error);
      throw error;
    }
  }
};

// Service pour les dossiers
export const dossierService = {
  // Récupérer tous les dossiers
  async getDossiers() {
    try {
      const response = await api.get("/dossier_api.php");
      return response.data;
    } catch (error) {
      console.error("Erreur dans getDossiers:", error);
      throw error;
    }
  },

  // Récupérer les dossiers d'un bordereau spécifique
  async getDossiersByBordereau(id_bordereau) {
    try {
      const response = await api.get(`/dossier_api.php?id_bordereau=${id_bordereau}`);
      return response.data;
    } catch (error) {
      console.error("Erreur dans getDossiersByBordereau:", error);
      throw error;
    }
  },

  // Ajouter un dossier
  async addDossier(data) {
    try {
      const response = await api.post("/dossier_api.php", data);
      return response.data;
    } catch (error) {
      console.error("Erreur dans addDossier:", error);
      throw error;
    }
  },

  // Modifier un dossier
  async updateDossier(id, data) {
    try {
      const response = await api.put(`/dossier_api.php?id_dossier=${id}`, data);
      return response.data;
    } catch (error) {
      console.error("Erreur dans updateDossier:", error);
      throw error;
    }
  },

  // Supprimer un dossier
  async deleteDossier(id) {
    try {
      const response = await api.delete(`/dossier_api.php?id_dossier=${id}`);
      return response.data;
    } catch (error) {
      console.error("Erreur dans deleteDossier:", error);
      throw error;
    }
  }
};

export default api;