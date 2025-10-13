import { createRouter, createWebHistory } from "vue-router";
import Home from "../views/Home.vue";
import Bordereaux from "../views/Bordereaux.vue";
import Banque from "../views/Banque.vue";
import Statistics from "../views/Statistics.vue";
import DepartementList from "../views/Departement.vue";
import Login from "../views/login.vue";
import SearchByDate from "../views/SearchByDate.vue";

const routes = [
  {
    path: "/",
    redirect: "/login",
  },
  
  {
    path: "/login",
    name: "Login",
    component: Login,
    meta: { 
      title: "Connexion - CoachPro",
      layout: 'auth',
      requiresAuth: false
    }
  },
  
  { 
    path: "/home", 
    name: "Home", 
    component: Home,
    meta: { 
      title: "Accueil - CoachPro",
      layout: 'default',
      requiresAuth: true
    }
  },
  { 
    path: "/bordereaux", 
    name: "Bordereaux", 
    component: Bordereaux,
    meta: { 
      title: "Bordereaux - CoachPro",
      layout: 'default',
      requiresAuth: true
    }
  },
  { 
    path: "/banque", 
    name: "Banque", 
    component: Banque,
    meta: { 
      title: "Banques - CoachPro",
      layout: 'default',
      requiresAuth: true
    }
  },
  { 
    path: "/statistiques", 
    name: "Statistiques", 
    component: Statistics,
    meta: { 
      title: "Statistiques - CoachPro",
      layout: 'default',
      requiresAuth: true
    }
  },
  { 
    path: "/departement", 
    name: "Departement", 
    component: DepartementList,
    meta: { 
      title: "Départements - CoachPro",
      layout: 'default',
      requiresAuth: true
    }
  },
  { 
    path: "/search-by-date", 
    name: "SearchByDate", 
    component: SearchByDate,
    meta: { 
      title: "Recherche par Date - CoachPro",
      layout: 'default',
      requiresAuth: true
    }
  }
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes,
});

router.beforeEach((to, from, next) => {
  const isAuthenticated = localStorage.getItem('isAuthenticated') === 'true';
  
  console.log('🔄 Navigation:', {
    from: from.path,
    to: to.path,
    authenticated: isAuthenticated,
    requiresAuth: to.meta.requiresAuth
  });
  
  document.title = to.meta.title || 'CoachPro - Dashboard';
  
  if (to.meta.requiresAuth && !isAuthenticated) {
    console.log('❌ Accès refusé - Redirection vers /login');
    next('/login');
  } else if (to.path === '/login' && isAuthenticated) {
    console.log('✅ Déjà authentifié - Redirection vers /home');
    next('/home');
  } else {
    console.log('✅ Accès autorisé');
    next();
  }
});

export default router;