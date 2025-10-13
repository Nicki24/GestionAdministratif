<template>
  <component :is="currentLayout">
    <router-view />
  </component>
</template>

<script>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import DefaultLayout from './layouts/DefaultLayout.vue';
import AuthLayout from './layouts/authLayout.vue';

export default {
  name: 'App',
  components: {
    DefaultLayout,
    AuthLayout
  },
  setup() {
    const route = useRoute();
    
    const currentLayout = computed(() => {
      const layout = route.meta.layout === 'auth' ? 'AuthLayout' : 'DefaultLayout';
      console.log('🎨 App.vue - Layout sélectionné:', layout);
      return layout;
    });

    return {
      currentLayout
    };
  },
  mounted() {
    console.log('🏗️ App.vue monté');
    console.log('📊 État initial auth:', localStorage.getItem('isAuthenticated'));
  }
};
</script>

<style>
/* Styles globaux */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f5f6fa;
  color: #2c3e50;
}
</style>