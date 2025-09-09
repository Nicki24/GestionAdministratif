import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import Notifications from '@kyvg/vue3-notification'

// Créer l'application
const app = createApp(App);

// Utiliser le router
app.use(router);
app.use(Notifications); // Ajouter les notifications

// Monter l'application
app.mount("#app");

console.log("Vue app mounted with router and notifications");