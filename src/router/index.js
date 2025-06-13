import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('../views/HomePage.vue')
  },
  {
    path: '/products',
    name: 'Products',
    component: () => import('../views/ProductPage.vue')
  },
  {
    path: '/products/:id',
    name: 'ProductDetail',
    component: () => import('../views/ProductDetailPage.vue')
  },
  {
    path: '/cart',
    name: 'ShoppingCart',
    component: () => import('../views/ShoppingCartPage.vue')
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/RegisterPage.vue')
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginPage.vue')
  },
  {
    path: '/account',
    name: 'Account',
    component: () => import('../views/AccountPage.vue')
  },
  {
    path: '/purchases',
    name: 'Purchases',
    component: () => import('../views/PurchasePage.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router