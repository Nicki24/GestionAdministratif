import { createRouter, createWebHistory } from "vue-router";
import Home from "../views/Home.vue";
import Bordereaux from "../views/Bordereaux.vue";
import Banque from "../views/Banque.vue";
import Statistics from "../views/Statistics.vue";
import DepartementList from "../views/Departement.vue";
import Login from "../views/login.vue";
import SearchByDate from "../views/SearchByDate.vue"; // Ajout du nouveau composant

const routes = [
  // Route par défaut redirigeant vers /login
  {
    path: "/",
    redirect: "/login", // Redirige automatiquement vers la page de connexion au démarrage
    meta: { 
      title: "Redirection - CoachPro",
    },
  },
  
  // Routes avec layout d'authentification (sans sidebar)
  {
    path: "/login",
    name: "Login",
    component: Login,
    meta: { 
      title: "Connexion - CoachPro",
      layout: 'auth'
    }
  },
  
  // Routes avec layout par défaut (avec sidebar)
  { 
    path: "/home", 
    name: "Home", 
    component: Home,
    meta: { 
      title: "Accueil - CoachPro",
      layout: 'default'
    }
  },
  { 
    path: "/bordereaux", 
    name: "Bordereaux", 
    component: Bordereaux,
    meta: { 
      title: "Bordereaux - CoachPro",
      layout: 'default'
    }
  },
  { 
    path: "/banque", 
    name: "Banque", 
    component: Banque,
    meta: { 
      title: "Banques - CoachPro",
      layout: 'default'
    }
  },
  { 
    path: "/statistiques", 
    name: "Statistiques", 
    component: Statistics,
    meta: { 
      title: "Statistiques - CoachPro",
      layout: 'default'
    }
  },
  { 
    path: "/departement", 
    name: "Departement", 
    component: DepartementList,
    meta: { 
      title: "Départements - CoachPro",
      layout: 'default'
    }
  },
  { 
    path: "/search-by-date", 
    name: "SearchByDate", 
    component: SearchByDate,
    meta: { 
      title: "Recherche par Date - CoachPro",
      layout: 'default'
    }
  }
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes,
});

router.beforeEach((to, from, next) => {
  document.title = to.meta.title || 'CoachPro - Dashboard';
  
  // Vérifier si l'utilisateur est authentifié (basé sur localStorage)
  const isAuthenticated = localStorage.getItem('isAuthenticated') === 'true';
  
  // Si l'utilisateur est authentifié et tente d'accéder à "/", rediriger vers /home
  if (to.path === "/" && isAuthenticated) {
    next('/home');
  } else {
    next();
  }
});

export default router;