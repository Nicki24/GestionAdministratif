<template>
  <div class="dossiers-page">
    <div class="page-header">
      <h2>Gestion des Dossiers</h2>
      <button class="btn-primary">
        <span class="btn-icon">📁</span>
        Nouveau Dossier
      </button>
    </div>

    <div class="content-section">
      <div class="info-box">
        <h3>📋 Fonctionnalité à venir</h3>
        <p>La gestion des dossiers sera bientôt disponible. Cette page permettra de :</p>
        <ul>
          <li>Consulter tous les dossiers</li>
          <li>Filtrer par bordereau</li>
          <li>Rechercher par matricule</li>
          <li>Exporter les données</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DossiersView'
}
</script>

<style scoped>
.dossiers-page {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.page-header h2 {
  color: #2c3e50;
}

.btn-primary {
  background: #3498db;
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
}

.btn-primary:hover {
  background: #2980b9;
}

.content-section {
  background: white;
  border-radius: 15px;
  padding: 25px;
  min-height: 400px;
}

.info-box {
  background: #f8f9fa;
  border-left: 4px solid #3498db;
  padding: 20px;
  border-radius: 8px;
}

.info-box h3 {
  color: #2c3e50;
  margin-bottom: 15px;
}

.info-box p {
  color: #7f8c8d;
  margin-bottom: 15px;
}

.info-box ul {
  color: #7f8c8d;
  padding-left: 20px;
}

.info-box li {
  margin-bottom: 8px;
}
</style>