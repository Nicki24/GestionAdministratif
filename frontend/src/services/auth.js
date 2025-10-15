// Gestion centralisée de l'authentification et des permissions
export const authService = {
    // Récupérer l'utilisateur connecté
    getCurrentUser() {
        const userData = localStorage.getItem('userData');
        return userData ? JSON.parse(userData) : null;
    },

    // Vérifier si l'utilisateur est connecté
    isAuthenticated() {
        return localStorage.getItem('isAuthenticated') === 'true';
    },

    // Vérifier si l'utilisateur est admin
    isAdmin() {
        const user = this.getCurrentUser();
        return user && user.type_utilisateur === 'admin';
    },

    // Vérifier si c'est l'utilisateur restreint (ornellaclaudia)
    isRestrictedUser() {
        const user = this.getCurrentUser();
        return user && user.email === 'ornellaclaudia0@gmail.com';
    },

    // Vérifier les permissions CRUD
    canCreate() {
        return this.isAdmin();
    },

    canUpdate() {
        return this.isAdmin();
    },

    canDelete() {
        return this.isAdmin();
    },

    // Déconnexion
    logout() {
        localStorage.removeItem('isAuthenticated');
        localStorage.removeItem('userData');
        localStorage.removeItem('userEmail');
        localStorage.removeItem('token');
    },

    // Stocker les données utilisateur après login
    setUserData(userData) {
        localStorage.setItem('userData', JSON.stringify(userData));
        localStorage.setItem('isAuthenticated', 'true');
    }
};