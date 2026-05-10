import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'

// CSS imports — order matters
import './assets/css/fonts.css'
import './assets/css/variables.css'
import './assets/css/base.css'
import './assets/css/utilities.css'
import './assets/css/components.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
