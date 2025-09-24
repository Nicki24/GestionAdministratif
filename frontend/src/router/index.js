import { createRouter, createWebHistory } from "vue-router";
import Home from "../views/Home.vue";
import Bordereaux from "../views/Bordereaux.vue";
import Banque from "../views/Banque.vue";
import Statistics from "../views/Statistics.vue"; // Importez le vrai composant

const routes = [
  { 
    path: "/", 
    name: "Home", 
    component: Home,
    meta: { title: "Accueil - CoachPro" }
  },
  { 
    path: "/bordereaux", 
    name: "Bordereaux", 
    component: Bordereaux,
    meta: { title: "Bordereaux - CoachPro" }
  },
  { 
    path: "/banque", 
    name: "Banque", 
    component: Banque,
    meta: { title: "Banques - CoachPro" }
  },
  { 
    path: "/statistiques", 
    name: "Statistiques", 
    component: Statistics, // Utilisez le vrai composant
    meta: { title: "Statistiques - CoachPro" }
  }
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes,
});

router.beforeEach((to, from, next) => {
  document.title = to.meta.title || 'CoachPro - Dashboard';
  next();
});

export default router;