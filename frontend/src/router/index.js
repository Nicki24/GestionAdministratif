import { createRouter, createWebHistory } from "vue-router";
import Home from "../views/Home.vue";
import Bordereaux from "../views/Bordereaux.vue";

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
  }
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes,
});

// Changement de titre de page
router.beforeEach((to, from, next) => {
  document.title = to.meta.title || 'CoachPro - Dashboard';
  next();
});

export default router;