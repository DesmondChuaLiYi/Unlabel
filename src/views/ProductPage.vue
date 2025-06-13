<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import '../assets/css/product.css'

// State
const products = ref([])
const isLoading = ref(true)
const error = ref(null)
const currentPage = ref(1)
const itemsPerPage = ref(8)
const searchQuery = ref('')
const selectedCategory = ref('All')
const sortOption = ref('default')

// Access route
const route = useRoute()

// Category mapping from URL (kebab-case) to display names
const categoryMap = {
  'clothing': 'Clothing',
  'accessories': 'Accessories',
  'stationery': 'Stationery',
  'household-goods': 'Household Goods',
  'audio-electronics': 'Audio & Electronics',
  'storage-organization': 'Storage & Organization'
}

// Load products
const loadProducts = async () => {
  isLoading.value = true
  error.value = null
  try {
    const module = await import('../assets/data/products.json')
    const data = module.default
    if (!Array.isArray(data) || data.length === 0) {
      throw new Error('No products found in data')
    }
    products.value = data
  } catch (err) {
    error.value = 'Failed to load products. Please try again later.'
    console.error('Error loading products.json:', err)
    products.value = []
  } finally {
    isLoading.value = false
  }
}

// Computed properties
const categories = computed(() => {
  const cats = ['All', ...new Set(products.value.map(p => p.category))]
  return cats
})

const filteredProducts = computed(() => {
  let result = [...products.value]
  
  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(p => 
      p.name.toLowerCase().includes(query) || 
      p.description.toLowerCase().includes(query)
    )
  }
  
  // Apply category filter
  if (selectedCategory.value !== 'All') {
    result = result.filter(p => p.category === selectedCategory.value)
  }
  
  // Apply sorting
  switch (sortOption.value) {
    case 'price-asc':
      result.sort((a, b) => a.price - b.price)
      break
    case 'price-desc':
      result.sort((a, b) => b.price - a.price)
      break
    case 'name-asc':
      result.sort((a, b) => a.name.localeCompare(b.name))
      break
    case 'name-desc':
      result.sort((a, b) => b.name.localeCompare(a.name))
      break
    case 'rating':
      result.sort((a, b) => b.rating - a.rating)
      break
  }
  
  return result
})

const paginatedProducts = computed(() => {
  const startIndex = (currentPage.value - 1) * itemsPerPage.value
  return filteredProducts.value.slice(startIndex, startIndex + itemsPerPage.value)
})

const totalPages = computed(() => {
  return Math.ceil(filteredProducts.value.length / itemsPerPage.value)
})

const pageNumbers = computed(() => {
  const pages = []
  const maxPagesToShow = 5
  
  if (totalPages.value <= maxPagesToShow) {
    for (let i = 1; i <= totalPages.value; i++) {
      pages.push(i)
    }
  } else {
    pages.push(1)
    let startPage = Math.max(2, currentPage.value - 1)
    let endPage = Math.min(totalPages.value - 1, currentPage.value + 1)
    
    if (currentPage.value <= 3) {
      endPage = 4
    }
    
    if (currentPage.value >= totalPages.value - 2) {
      startPage = totalPages.value - 3
    }
    
    if (startPage > 2) {
      pages.push('...')
    }
    
    for (let i = startPage; i <= endPage; i++) {
      pages.push(i)
    }
    
    if (endPage < totalPages.value - 1) {
      pages.push('...')
    }
    
    pages.push(totalPages.value)
  }
  
  return pages
})

// Methods
const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    document.getElementById('products-top')?.scrollIntoView({ behavior: 'smooth' })
  }
}

const addToCart = (product) => {
  alert(`Added ${product.name} to cart!`)
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(price)
}

// Initialize category and search from URL
const setCategoryFromRoute = () => {
  const urlCategory = route.query.category
  if (urlCategory && categoryMap[urlCategory]) {
    selectedCategory.value = categoryMap[urlCategory]
  } else {
    selectedCategory.value = 'All'
  }
  
  // Set search query from URL if present
  if (route.query.search) {
    searchQuery.value = route.query.search
  } else {
    searchQuery.value = ''
  }
  
  // Reset to first page when filters change
  currentPage.value = 1
}

// Load products and set category
onMounted(async () => {
  await loadProducts()
  setCategoryFromRoute()
})

watch(() => route.query.category, () => {
  setCategoryFromRoute()
})
</script>

<template>
  <div class="product-page">
    <div class="section-header">
      <h1 class="section-title">Products</h1>
    </div>
    
    <!-- Loading State -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3">Loading products...</p>
    </div>
    
    <!-- Error State -->
    <div v-else-if="error" class="text-center py-5">
      <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
      <p class="mt-3 text-danger">{{ error }}</p>
      <button class="btn btn-primary" @click="loadProducts">Retry</button>
    </div>
    
    <!-- Content -->
    <div v-else>
      <!-- Filters and Search -->
      <div class="filters-container">
        <div class="row">
          <div class="col-md-4 mb-3">
            <div class="input-group">
              <input 
                type="text" 
                class="form-control" 
                placeholder="Search products..." 
                v-model="searchQuery"
              >
              <button class="btn btn-outline-secondary" type="button">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </div>
          
          <div class="col-md-3 mb-3">
            <select class="form-select" v-model="selectedCategory">
              <option v-for="category in categories" :key="category" :value="category">
                {{ category }}
              </option>
            </select>
          </div>
          
          <div class="col-md-3 mb-3">
            <select class="form-select" v-model="sortOption">
              <option value="default">Default Sorting</option>
              <option value="price-asc">Price: Low to High</option>
              <option value="price-desc">Price: High to Low</option>
              <option value="name-asc">Name: A to Z</option>
              <option value="name-desc">Name: Z to A</option>
              <option value="rating">Top Rated</option>
            </select>
          </div>
          
          <div class="col-md-2 mb-3">
            <select class="form-select" v-model="itemsPerPage">
              <option :value="4">4 per page</option>
              <option :value="8">8 per page</option>
              <option :value="12">12 per page</option>
            </select>
          </div>
        </div>
      </div>
      
      <!-- Results Summary -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="mb-0" id="products-top">
          Showing {{ filteredProducts.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0 }} - 
          {{ Math.min(currentPage * itemsPerPage, filteredProducts.length) }} 
          of {{ filteredProducts.length }} products
        </p>
      </div>
      
      <!-- Products Grid -->
      <div v-if="filteredProducts.length === 0" class="text-center py-5">
        <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
        <p class="mt-3 text-danger">No products available.</p>
        <button class="btn btn-primary" @click="loadProducts">Retry</button>
      </div>
      
      <div v-else class="product-grid">
        <div 
          v-for="product in paginatedProducts" 
          :key="product.id" 
          class="product-card"
        >
          <router-link :to="`/products/${product.id}`" class="product-link">
            <div class="placeholder-img">
              <img v-if="product.image && !product.image.includes('placeholder')" :src="product.image" :alt="product.name" />
              <template v-else>placeholder</template>
            </div>
            <div class="product-info">
              <h5 class="product-title">{{ product.name }}</h5>
              <p class="product-category">{{ product.category }}</p>
              <div class="rating">
                <i 
                  v-for="i in 5" 
                  :key="i" 
                  :class="[
                    'bi', 
                    i <= Math.floor(product.rating) ? 'bi-star-fill' : 
                    i - 0.5 <= product.rating ? 'bi-star-half' : 'bi-star'
                  ]"
                ></i>
                <small class="ms-1 text-muted">{{ product.rating }}</small>
              </div>
              <p class="product-price">{{ formatPrice(product.price) }}</p>
            </div>
          </router-link>
          
          <div class="product-actions">
            <button 
              @click="addToCart(product)" 
              class="btn btn-sm btn-outline-dark"
              :disabled="product.stock === 0"
            >
              <i class="bi bi-cart-plus"></i> Add
            </button>
            <router-link :to="`/products/${product.id}`" class="btn btn-sm btn-dark">
              <i class="bi bi-eye"></i> Details
            </router-link>
          </div>
          
          <span 
            v-if="product.stock < 20" 
            class="badge bg-danger mt-2"
          >
            Low Stock
          </span>
        </div>
      </div>
      
      <!-- Pagination -->
      <nav v-if="totalPages > 1" class="mt-4">
        <ul class="pagination justify-content-center">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>
          
          <li 
            v-for="page in pageNumbers" 
            :key="page" 
            class="page-item" 
            :class="{ 
              active: page === currentPage,
              disabled: page === '...' 
            }"
          >
            <a 
              class="page-link" 
              href="#" 
              @click.prevent="page !== '...' && changePage(page)"
            >
              {{ page }}
            </a>
          </li>
          
          <li class="page-item" :class="{ disabled: currentPage === totalPages }">
            <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

// Watch for changes in route query parameters
watch(() => route.query, () => {
  setCategoryFromRoute()
}, { deep: true })