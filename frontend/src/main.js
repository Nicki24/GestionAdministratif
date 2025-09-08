import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";

// Créer l'application
const app = createApp(App);

// Utiliser le router
app.use(router);

// Monter l'application
app.mount("#app");

console.log("Vue app mounted with router");