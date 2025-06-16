import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import axios from 'axios';
import { API_BASE_URL } from './config';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import './assets/css/style.css';
import './assets/css/home.css';
import './assets/css/product.css';
import './assets/css/product-detail.css';
import './assets/css/auth.css';
import './assets/css/cart.css';
import './assets/css/purchases.css';

// Set base URL for API requests
axios.defaults.baseURL = '';

const app = createApp(App);
app.use(router);
app.mount('#app');

// Add interceptor
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 401) {
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);