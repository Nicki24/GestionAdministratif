const { defineConfig } = require('@vue/cli-service')
module.exports = defineConfig({
  transpileDependencies: true,
  publicPath: '/bordereau/', // 👈 chemin du projet sur Wamp
  outputDir: '../bordereau', // 👈 build directement dans ton dossier Wamp
})