import axios from "axios";

// Configuration automatique selon l'environnement
const isDevelopment = window.location.hostname === 'localhost' || 
                     window.location.hostname === '127.0.0.1' ||
                     window.location.port === '8080'; // Port de dev Vue.js

const API_BASE_URL = isDevelopment 
    ? "http://localhost/bordereau/backend"  // Développement
    : "/backend";  // Production - chemin relatif

console.log(`🌍 Environnement: ${isDevelopment ? 'Développement' : 'Production'}`);
console.log(`🔗 URL API: ${API_BASE_URL}`);

// Créer une instance axios avec une configuration de base
const api = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        "Content-Type": "application/json",
    },
    timeout: isDevelopment ? 10000 : 30000, // 30s timeout en production
});

// Intercepteur pour les requêtes
api.interceptors.request.use(
    (config) => {
        if (isDevelopment) {
            console.log(`[ ] Making ${config.method?.toUpperCase()} request to: ${config.url}`);
        }
        return config;
    },
    (error) => {
        console.error(" [x] Request error:", error);
        return Promise.reject(error);
    }
);

// Intercepteur pour les réponses
api.interceptors.response.use(
    (response) => {
        if (isDevelopment) {
            console.log(" [✓] Response received:", response.status, response.data);
        }
        return response;
    },
    (error) => {
        console.error(" [x] Response error:", error.response?.status, error.message);
        
        if (error.response) {
            switch (error.response.status) {
                case 400:
                    error.message = "Données invalides";
                    break;
                case 401:
                    error.message = "Email ou mot de passe incorrect";
                    break;
                case 403:
                    error.message = "Accès non autorisé";
                    break;
                case 404:
                    error.message = "Resource non trouvée";
                    break;
                case 500:
                    error.message = "Erreur interne du serveur";
                    break;
                case 502:
                    error.message = "Serveur indisponible";
                    break;
                case 503:
                    error.message = "Service temporairement indisponible";
                    break;
                default:
                    error.message = `Erreur ${error.response.status}: ${error.response.statusText}`;
            }
        } else if (error.request) {
            // Pas de réponse du serveur
            if (isDevelopment) {
                error.message = "Impossible de se connecter au serveur. Vérifiez que WAMP est démarré.";
            } else {
                error.message = "Serveur temporairement indisponible. Veuillez réessayer.";
            }
        } else {
            error.message = "Erreur de configuration de la requête";
        }
        
        return Promise.reject(error);
    }
);

// Service pour l'authentification (login)
export const authService = {
    async login(email, password) {
        try {
            if (isDevelopment) {
                console.log("🔐 Tentative de connexion pour:", email);
            }
            const response = await api.post('/login_api.php', { 
                email: email.trim(), 
                password: password 
            });
            
            // Stocker les infos utilisateur si succès
            if (response.data.success && response.data.user) {
                localStorage.setItem('userData', JSON.stringify(response.data.user));
                localStorage.setItem('isAuthenticated', 'true');
                localStorage.setItem('userEmail', response.data.user.email);
            }
            
            return response.data;
        } catch (error) {
            console.error("Erreur dans login:", error);
            throw error;
        }
    }
};

// Service pour les bordereaux
export const bordereauService = {
    async getBordereaux() {
        try {
            const response = await api.get("/bordereau_api.php");
            return response.data;
        } catch (error) {
            console.error("Erreur dans getBordereaux:", error);
            throw error;
        }
    },

    async getBordereau(id) {
        try {
            const response = await api.get(`/bordereau_api.php?id_bordereau=${id}`);
            return response.data;
        } catch (error) {
            console.error("Erreur dans getBordereau:", error);
            throw error;
        }
    },

    async createBordereau(data) {
        try {
            const response = await api.post("/bordereau_api.php", data);
            return response.data;
        } catch (error) {
            console.error("Erreur dans createBordereau:", error);
            throw error;
        }
    },

    async updateBordereau(id_bordereau, data) {
        try {
            const response = await api.put(`/bordereau_api.php?id_bordereau=${id_bordereau}`, data);
            return response.data;
        } catch (error) {
            console.error("Erreur dans updateBordereau:", error);
            throw error;
        }
    },

    async deleteBordereau(id_bordereau) {
        try {
            const response = await api.delete(`/bordereau_api.php?id_bordereau=${id_bordereau}`);
            return response.data;
        } catch (error) {
            console.error("Erreur dans deleteBordereau:", error);
            throw error;
        }
    },

    async markBordereauxAsSent(bordereauIds) {
        try {
            const response = await api.patch("/bordereau_api.php/mark-as-sent", { bordereauIds });
            return response.data;
        } catch (error) {
            console.error("Erreur dans markBordereauxAsSent:", error);
            throw error;
        }
    },

    async markSingleBordereauAsSent(id) {
        try {
            const response = await api.patch(`/bordereau_api.php/bordereaux/${id}/mark-as-sent`);
            return response.data;
        } catch (error) {
            console.error("Erreur dans markSingleBordereauAsSent:", error);
            throw error;
        }
    }
};

// Service pour les banques
export const banqueService = {
    async getBanques() {
        try {
            const response = await api.get("/banque_api.php");
            return response.data;
        } catch (error) {
            console.error("Erreur dans getBanques:", error);
            throw error;
        }
    },

    async createBanque(data) {
        try {
            const response = await api.post("/banque_api.php", data);
            return response.data;
        } catch (error) {
            console.error("Erreur dans createBanque:", error);
            throw error;
        }
    },

    async updateBanque(id_banque, data) {
        try {
            const response = await api.put(`/banque_api.php?id_banque=${id_banque}`, data);
            return response.data;
        } catch (error) {
            console.error("Erreur dans updateBanque:", error);
            throw error;
        }
    },

    async deleteBanque(id_banque) {
        try {
            const response = await api.delete(`/banque_api.php?id_banque=${id_banque}`);
            return response.data;
        } catch (error) {
            console.error("Erreur dans deleteBanque:", error);
            throw error;
        }
    }
};

// Service pour les départements
export const departementService = {
    async getDepartements() {
        try {
            const response = await api.get("/departement_api.php");
            return response.data;
        } catch (error) {
            console.error("Erreur dans getDepartements:", error);
            throw error;
        }
    },

    async getDepartement(id) {
        try {
            const response = await api.get(`/departement_api.php?id_departement=${id}`);
            return response.data;
        } catch (error) {
            console.error("Erreur dans getDepartement:", error);
            throw error;
        }
    },

    async createDepartement(data) {
        try {
            const response = await api.post("/departement_api.php", data);
            return response.data;
        } catch (error) {
            console.error("Erreur dans createDepartement:", error);
            throw error;
        }
    },

    async updateDepartement(id_departement, data) {
        try {
            const response = await api.put(`/departement_api.php?id_departement=${id_departement}`, data);
            return response.data;
        } catch (error) {
            console.error("Erreur dans updateDepartement:", error);
            throw error;
        }
    },

    async deleteDepartement(id_departement) {
        try {
            const response = await api.delete(`/departement_api.php?id_departement=${id_departement}`);
            return response.data;
        } catch (error) {
            console.error("Erreur dans deleteDepartement:", error);
            throw error;
        }
    }
};

// Service pour la recherche par date
export const searchService = {
    async searchByDate(date, type) {
        try {
            if (!date || !type) {
                throw new Error('La date et le type sont requis');
            }

            if (isDevelopment) {
                console.log(`[ ] Recherche par date: ${date}, type: ${type}`);
            }

            const response = await api.get('/search_api.php', {
                params: { date, type }
            });

            if (isDevelopment) {
                console.log("📞 Résultats recherche:", response.data);
            }
            return response.data;
        } catch (error) {
            console.error("✗ Erreur dans searchByDate:", error);
            throw error;
        }
    }
};

export default api;