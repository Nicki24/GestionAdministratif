import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import Notifications from '@kyvg/vue3-notification'

// Vérification du statut d'authentification au démarrage
const isAuthenticated = localStorage.getItem('isAuthenticated') === 'true';
console.log('🔐 Statut authentification au démarrage:', isAuthenticated);

// Créer l'application
const app = createApp(App);

// Utiliser le router
app.use(router);
app.use(Notifications);

console.log("🚀 Application Vue montée");

// Monter l'application
app.mount("#app");