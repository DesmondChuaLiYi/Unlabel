<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const isNavCollapsed = ref(true)
const showNavbar = ref(true)
const lastScrollPosition = ref(0)
const showSearch = ref(false)
const searchCategory = ref('all')
const searchQuery = ref('')
const showProfileDropdown = ref(false)
const showSearchOverlay = ref(false)

// Toast notification system
const toast = ref({
  show: false,
  message: '',
  type: 'success', // success, error, warning, info
  timeout: null
})

// Show toast message
const showToast = (message, type = 'success', duration = 5000) => {
  // Clear any existing timeout
  if (toast.value.timeout) {
    clearTimeout(toast.value.timeout)
  }
  
  // Set toast properties
  toast.value.show = true
  toast.value.message = message
  toast.value.type = type
  
  // Auto hide after duration
  toast.value.timeout = setTimeout(() => {
    toast.value.show = false
  }, duration)
}

// Hide toast message
const hideToast = () => {
  toast.value.show = false
  if (toast.value.timeout) {
    clearTimeout(toast.value.timeout)
  }
}

// Expose the showToast function to the global window object
// so it can be accessed from other components
onMounted(() => {
  window.showToast = showToast
})

const toggleNavbar = () => {
  isNavCollapsed.value = !isNavCollapsed.value
}

const toggleSearch = () => {
  showSearch.value = !showSearch.value
  showSearchOverlay.value = !showSearchOverlay.value
}

const toggleProfileDropdown = () => {
  showProfileDropdown.value = !showProfileDropdown.value
}

const handleScroll = () => {
  const currentScrollPosition = window.scrollY
  if (currentScrollPosition < lastScrollPosition.value || currentScrollPosition < 50) {
    showNavbar.value = true
  } else {
    showNavbar.value = false
  }
  lastScrollPosition.value = currentScrollPosition
}

const handleSearch = () => {
  // Close the search overlay
  showSearch.value = false
  showSearchOverlay.value = false
  
  // If search query is empty, just navigate to the category
  if (!searchQuery.value.trim()) {
    if (searchCategory.value === 'all') {
      router.push('/products')
    } else {
      router.push(`/products?category=${searchCategory.value}`)
    }
    return
  }
  
  // Navigate to products page with search parameters
  const query = {
    search: searchQuery.value
  }
  
  // Add category if it's not 'all'
  if (searchCategory.value !== 'all') {
    query.category = searchCategory.value
  }
  
  router.push({ path: '/products', query })
  
  // Clear the search query after search
  searchQuery.value = ''
}

const closeDropdowns = (event) => {
  if (showProfileDropdown.value && !event.target.closest('.profile-dropdown-container')) {
    showProfileDropdown.value = false
  }
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
  document.addEventListener('click', closeDropdowns)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  document.removeEventListener('click', closeDropdowns)
})
</script>

<template>
  <div>
    <!-- Toast Notification -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
      <div 
        class="toast show" 
        :class="{
          'bg-success text-white': toast.type === 'success',
          'bg-danger text-white': toast.type === 'error',
          'bg-warning': toast.type === 'warning',
          'bg-info': toast.type === 'info'
        }" 
        role="alert" 
        aria-live="assertive" 
        aria-atomic="true"
        v-if="toast.show"
      >
        <div class="d-flex">
          <div class="toast-body">
            {{ toast.message }}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="hideToast"></button>
        </div>
      </div>
    </div>
    
    <!-- Search Overlay -->
    <div class="search-overlay" v-if="showSearchOverlay" @click="toggleSearch"></div>
    
    <!-- Navigation Bar -->
    <nav class="navbar navbar-dark navbar-expand-lg fixed-top" :class="{ 'navbar-visible': showNavbar, 'navbar-hidden': !showNavbar }">
      <div class="container">
        <router-link class="navbar-brand" to="/">
          <span class="fw-bold">Unlabel</span>
        </router-link>
        <button 
          class="navbar-toggler" 
          type="button" 
          @click="toggleNavbar"
          aria-controls="navbarNav" 
          :aria-expanded="!isNavCollapsed" 
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" :class="{ 'show': !isNavCollapsed }" id="navbarNav">
          <ul class="navbar-nav me-auto">
            <li class="nav-item">
              <router-link class="nav-link" to="/">Home</router-link>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                Products
              </a>
              <ul class="dropdown-menu">
                <li><router-link class="dropdown-item" to="/products">All Products</router-link></li>
                <li><hr class="dropdown-divider"></li>
                <li><router-link class="dropdown-item" to="/products?category=clothing">Clothing</router-link></li>
                <li><router-link class="dropdown-item" to="/products?category=accessories">Accessories</router-link></li>
                <li><router-link class="dropdown-item" to="/products?category=stationery">Stationery</router-link></li>
                <li><router-link class="dropdown-item" to="/products?category=household-goods">Household Goods</router-link></li>
                <li><router-link class="dropdown-item" to="/products?category=audio-electronics">Audio & Electronics</router-link></li>
                <li><router-link class="dropdown-item" to="/products?category=storage-organization">Storage & Organization</router-link></li>
              </ul>
            </li>
          </ul>
          
          <!-- Search Bar (Inline) -->
          <div class="search-bar-container" :class="{ 'show': showSearch }">
            <div class="search-bar-inner">
              <select class="form-select" id="searchCategory" v-model="searchCategory">
                <option value="all">All</option>
                <option value="clothing">Clothing</option>
                <option value="accessories">Accessories</option>
                <option value="stationery">Stationery</option>
                <option value="household-goods">Household Goods</option>
                <option value="audio-electronics">Audio & Electronics</option>
                <option value="storage-organization">Storage & Organization</option>
              </select>
              <input 
                type="text" 
                class="form-control" 
                id="searchQuery" 
                v-model="searchQuery" 
                placeholder="What are you looking for?" 
                @keyup.enter="handleSearch"
              >
              <button class="btn btn-outline-secondary" type="button" @click="handleSearch">
                <i class="fa-solid fa-magnifying-glass"></i>
              </button>
            </div>
          </div>
          
          <ul class="navbar-nav nav-icons">
            <li class="nav-item">
              <a class="nav-link" href="#" @click.prevent="toggleSearch">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span class="d-lg-none ms-2">Search</span>
              </a>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" to="/cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="d-lg-none ms-2">Cart</span>
              </router-link>
            </li>
            <li class="nav-item">
              <router-link class="nav-link" to="/purchases">
                <i class="fa-solid fa-box"></i>
                <span class="d-lg-none ms-2">My Purchases</span>
              </router-link>
            </li>
            <li class="nav-item profile-dropdown-container">
              <router-link class="nav-link" to="/account">
                <i class="fa-solid fa-user"></i>
                <span class="d-lg-none ms-2">Profile</span>
              </router-link>
              <!-- Profile Dropdown -->
              <div class="profile-dropdown">
                <div class="profile-dropdown-item">
                  <router-link to="/login" class="profile-link">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> Login
                  </router-link>
                </div>
                <div class="profile-dropdown-item">
                  <router-link to="/register" class="profile-link">
                    <i class="fa-solid fa-user-plus me-2"></i> Sign Up
                  </router-link>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="content-wrapper">
      <div class="main-container">
        <router-view />
      </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <div class="row">
          <div class="col-lg-3 col-md-6 footer-section">
            <h5 class="footer-title">Unlabel</h5>
            <p>Minimalist designs for modern living. Quality products that speak for themselves.</p>
            <div class="social-icons mt-3">
              <a href="#"><i class="fa-brands fa-facebook"></i></a>
              <a href="#"><i class="fa-brands fa-instagram"></i></a>
              <a href="#"><i class="fa-brands fa-twitter"></i></a>
              <a href="#"><i class="fa-brands fa-pinterest"></i></a>
            </div>
          </div>
          
          <div class="col-lg-3 col-md-6 footer-section">
            <h5 class="footer-title">Shop</h5>
            <ul class="footer-links">
              <li><router-link to="/products?category=clothing">Clothing</router-link></li>
              <li><router-link to="/products?category=accessories">Accessories</router-link></li>
              <li><router-link to="/products?category=stationery">Stationery</router-link></li>
              <li><router-link to="/products?category=household-goods">Household Goods</router-link></li>
              <li><router-link to="/products?category=audio-electronics">Audio & Electronics</router-link></li>
              <li><router-link to="/products?category=storage-organization">Storage & Organization</router-link></li>
              <li><router-link to="/products">All Products</router-link></li>
            </ul>
          </div>
          
          <div class="col-lg-3 col-md-6 footer-section">
            <h5 class="footer-title">Customer Service</h5>
            <ul class="footer-links">
              <li><a href="#">Contact Us</a></li>
              <li><a href="#">FAQs</a></li>
              <li><a href="#">Shipping & Returns</a></li>
              <li><a href="#">Size Guide</a></li>
              <li><a href="#">Privacy Policy</a></li>
            </ul>
          </div>
          
          <div class="col-lg-3 col-md-6 footer-section">
            <h5 class="footer-title">About Us</h5>
            <ul class="footer-links">
              <li><a href="#">Our Story</a></li>
              <li><a href="#">Sustainability</a></li>
              <li><a href="#">Careers</a></li>
              <li><a href="#">Press</a></li>
              <li><a href="#">Store Locations</a></li>
            </ul>
          </div>
        </div>
        
        <div class="footer-bottom text-center">
          <p>© {{ new Date().getFullYear() }} Unlabel. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<style>
/* Add these styles to your existing CSS */
.toast {
  min-width: 350px;
}

.toast-container {
  z-index: 1100;
}
</style>