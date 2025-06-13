<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import '../assets/css/home.css'

const router = useRouter()
const featuredProducts = ref([])
const clothingProducts = ref([])
const accessories = ref([])
const stationeryProducts = ref([])
const householdGoodsProducts = ref([])
const audioElectronics = ref([])
const storageOrganizationProducts = ref([])
const isLoading = ref(true)
const error = ref(null)
const currentSlide = ref(0)
const bannerImage = ref('/assets/img/everyday_essentials.jpg')

const slides = [
  {
    image: '/assets/img/everyday_simplicity_slide.jpg',
    title: 'Everyday Simplicity',
    description: 'Minimalist essentials for a sustainable lifestyle',
    buttonText: 'Shop Now',
    category: ''
  },
  {
    image: '/assets/img/organic_cotton_tshirt_slide.jpg',
    title: 'Organic Cotton Clothing',
    description: 'Soft, breathable apparel for daily comfort',
    buttonText: 'Explore',
    category: 'clothing'
  },
  {
    image: '/assets/img/home_organization_slide.jpg',
    title: 'Home Organization',
    description: 'Functional storage solutions for a tidy space',
    buttonText: 'Discover',
    category: 'storage-organization'
  }
]

const categories = [
  {
    name: 'Clothing',
    slug: 'clothing',
    image: '/assets/img/clothing_category.jpg'
  },
  {
    name: 'Accessories',
    slug: 'accessories',
    image: '/assets/img/accessories_category.jpg'
  },
  {
    name: 'Stationery',
    slug: 'stationery',
    image: '/assets/img/stationery_category.jpg'
  },
  {
    name: 'Household Goods',
    slug: 'household-goods',
    image: '/assets/img/household_goods_category.jpg'
  },
  {
    name: 'Audio & Electronics',
    slug: 'audio-electronics',
    image: '/assets/img/audio_and_electronics_category.jpg'
  },
  {
    name: 'Storage & Organization',
    slug: 'storage-organization',
    image: '/assets/img/storage_category.jpg'
  }
]

const categoryMap = {
  'Clothing': 'clothing',
  'Accessories': 'accessories',
  'Stationery': 'stationery',
  'Household Goods': 'household-goods',
  'Audio & Electronics': 'audio-electronics',
  'Storage & Organization': 'storage-organization'
}

const nextSlide = () => {
  currentSlide.value = (currentSlide.value + 1) % slides.length
}

const prevSlide = () => {
  currentSlide.value = (currentSlide.value - 1 + slides.length) % slides.length
}

const goToSlide = (index) => {
  currentSlide.value = index
}

const goToProducts = (category = '') => {
  if (category && categoryMap[category]) {
    router.push(`/products?category=${categoryMap[category]}`)
  } else if (category && Object.values(categoryMap).includes(category)) {
    router.push(`/products?category=${category}`)
  } else {
    router.push('/products')
  }
}

const loadProducts = async () => {
  isLoading.value = true
  error.value = null
  try {
    const module = await import('../assets/data/products.json')
    const products = module.default
    featuredProducts.value = [...products]
      .sort(() => 0.5 - Math.random())
      .slice(0, 8)
    clothingProducts.value = products.filter(p => p.category === 'Clothing').slice(0, 8)
    accessories.value = products.filter(p => p.category === 'Accessories').slice(0, 8)
    stationeryProducts.value = products.filter(p => p.category === 'Stationery').slice(0, 8)
    householdGoodsProducts.value = products.filter(p => p.category === 'Household Goods').slice(0, 8)
    audioElectronics.value = products.filter(p => p.category === 'Audio & Electronics').slice(0, 8)
    storageOrganizationProducts.value = products.filter(p => p.category === 'Storage & Organization').slice(0, 8)
  } catch (err) {
    error.value = 'Failed to load products. Please try again later.'
    console.error('Error loading products.json:', err)
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await loadProducts()
  setInterval(() => {
    nextSlide()
  }, 5000)
})
</script>

<template>
  <div class="home-page">
    <!-- Slideshow Section (Full Width) -->
    <div class="slideshow-container">
      <div class="slideshow-inner">
        <div 
          v-for="(slide, index) in slides" 
          :key="index"
          class="slide"
          :class="{ 'active': index === currentSlide }"
        >
          <div v-if="slide.image && slide.image !== 'placeholder'" class="slide-background">
            <img :src="slide.image" :alt="slide.title" class="slide-img">
          </div>
          <div class="slide-content">
            <div class="slide-text">
              <h1>{{ slide.title }}</h1>
              <p>{{ slide.description }}</p>
              <button @click="goToProducts(slide.category)" class="btn btn-dark">{{ slide.buttonText }}</button>
            </div>
          </div>
        </div>
      </div>
      
      <button class="slideshow-prev" @click="prevSlide">
        <i class="fa-solid fa-chevron-left"></i>
      </button>
      <button class="slideshow-next" @click="nextSlide">
        <i class="fa-solid fa-chevron-right"></i>
      </button>
      
      <div class="slideshow-indicators">
        <button 
          v-for="(slide, index) in slides" 
          :key="index"
          @click="goToSlide(index)"
          :class="{ 'active': index === currentSlide }"
        ></button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="full-width-container text-center py-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3">Loading products...</p>
    </div>
    
    <!-- Error State -->
    <div v-else-if="error" class="full-width-container text-center py-5">
      <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
      <p class="mt-3 text-danger">{{ error }}</p>
      <button class="btn btn-primary" @click="loadProducts">Retry</button>
    </div>
    
    <!-- Content -->
    <div v-else>
      <!-- Categories Section (Full Width) -->
      <div class="full-width-container bg-light">
        <div class="section-container">
          <div class="section-header">
            <h2 class="section-title">Shop by Category</h2>
          </div>
          
          <div class="category-grid">
            <div v-for="category in categories" :key="category.slug" class="category-card" @click="goToProducts(category.name)">
              <div class="placeholder-img">
                <img v-if="category.image && !category.image.includes('placeholder')" :src="category.image" :alt="category.name" />
                <template v-else>placeholder</template>
              </div>
              <h5 class="category-title">{{ category.name }}</h5>
            </div>
          </div>
        </div>
      </div>

      <!-- Featured Banner (Full Width) -->
      <div class="full-width-container">
        <div class="featured-banner">
          <div class="featured-banner-content">
            <h2>Everyday Essentials</h2>
            <p>Discover our collection of sustainable, minimalist items for daily living.</p>
            <button @click="goToProducts()" class="btn btn-dark">Shop Collection</button>
          </div>
          <div class="featured-banner-image">
            <img v-if="bannerImage && !bannerImage.includes('placeholder')" :src="bannerImage" alt="Everyday Essentials" />
            <template v-else>placeholder</template>
          </div>
        </div>
      </div>

      <!-- New Arrivals Section (Full Width) -->
      <div class="full-width-container">
        <div class="section-container">
          <div class="section-header">
            <h2 class="section-title">New Arrivals</h2>
            <button @click="goToProducts()" class="btn btn-outline-dark btn-sm view-all">View All</button>
          </div>
          
          <div class="product-grid">
            <div v-for="product in featuredProducts.slice(0, 8)" :key="product.id" class="product-card">
              <router-link :to="`/products/${product.id}`" class="product-link">
                <div class="placeholder-img">
                  <img v-if="product.image && !product.image.includes('placeholder')" :src="product.image" :alt="product.name" />
                  <template v-else>placeholder</template>
                </div>
                <div class="product-info">
                  <h5 class="product-title">{{ product.name }}</h5>
                  <p class="product-price">${{ product.price.toFixed(2) }}</p>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Clothing Products Section (Full Width) -->
      <div class="full-width-container bg-light">
        <div class="section-container">
          <div class="section-header">
            <h2 class="section-title">Clothing</h2>
            <button @click="goToProducts('Clothing')" class="btn btn-outline-dark btn-sm view-all">View All</button>
          </div>
          
          <div class="product-grid">
            <div v-for="product in clothingProducts.slice(0, 8)" :key="product.id" class="product-card">
              <router-link :to="`/products/${product.id}`" class="product-link">
                <div class="placeholder-img">
                  <img v-if="product.image && !product.image.includes('placeholder')" :src="product.image" :alt="product.name" />
                  <template v-else>placeholder</template>
                </div>
                <div class="product-info">
                  <h5 class="product-title">{{ product.name }}</h5>
                  <p class="product-price">${{ product.price.toFixed(2) }}</p>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Accessories Products Section (Full Width) -->
      <div class="full-width-container">
        <div class="section-container">
          <div class="section-header">
            <h2 class="section-title">Accessories</h2>
            <button @click="goToProducts('Accessories')" class="btn btn-outline-dark btn-sm view-all">View All</button>
          </div>
          
          <div class="product-grid">
            <div v-for="product in accessories.slice(0, 8)" :key="product.id" class="product-card">
              <router-link :to="`/products/${product.id}`" class="product-link">
                <div class="placeholder-img">
                  <img v-if="product.image && !product.image.includes('placeholder')" :src="product.image" :alt="product.name" />
                  <template v-else>placeholder</template>
                </div>
                <div class="product-info">
                  <h5 class="product-title">{{ product.name }}</h5>
                  <p class="product-price">${{ product.price.toFixed(2) }}</p>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Stationery Products Section (Full Width) -->
      <div class="full-width-container bg-light">
        <div class="section-container">
          <div class="section-header">
            <h2 class="section-title">Stationery</h2>
            <button @click="goToProducts('Stationery')" class="btn btn-outline-dark btn-sm view-all">View All</button>
          </div>
          
          <div class="product-grid">
            <div v-for="product in stationeryProducts.slice(0, 8)" :key="product.id" class="product-card">
              <router-link :to="`/products/${product.id}`" class="product-link">
                <div class="placeholder-img">
                  <img v-if="product.image && !product.image.includes('placeholder')" :src="product.image" :alt="product.name" />
                  <template v-else>placeholder</template>
                </div>
                <div class="product-info">
                  <h5 class="product-title">{{ product.name }}</h5>
                  <p class="product-price">${{ product.price.toFixed(2) }}</p>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Household Goods Products Section (Full Width) -->
      <div class="full-width-container">
        <div class="section-container">
          <div class="section-header">
            <h2 class="section-title">Household Goods</h2>
            <button @click="goToProducts('Household Goods')" class="btn btn-outline-dark btn-sm view-all">View All</button>
          </div>
          
          <div class="product-grid">
            <div v-for="product in householdGoodsProducts.slice(0, 8)" :key="product.id" class="product-card">
              <router-link :to="`/products/${product.id}`" class="product-link">
                <div class="placeholder-img">
                  <img v-if="product.image && !product.image.includes('placeholder')" :src="product.image" :alt="product.name" />
                  <template v-else>placeholder</template>
                </div>
                <div class="product-info">
                  <h5 class="product-title">{{ product.name }}</h5>
                  <p class="product-price">${{ product.price.toFixed(2) }}</p>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Audio & Electronics Products Section (Full Width) -->
      <div class="full-width-container bg-light">
        <div class="section-container">
          <div class="section-header">
            <h2 class="section-title">Audio & Electronics</h2>
            <button @click="goToProducts('Audio & Electronics')" class="btn btn-outline-dark btn-sm view-all">View All</button>
          </div>
          
          <div class="product-grid">
            <div v-for="product in audioElectronics.slice(0, 8)" :key="product.id" class="product-card">
              <router-link :to="`/products/${product.id}`" class="product-link">
                <div class="placeholder-img">
                  <img v-if="product.image && !product.image.includes('placeholder')" :src="product.image" :alt="product.name" />
                  <template v-else>placeholder</template>
                </div>
                <div class="product-info">
                  <h5 class="product-title">{{ product.name }}</h5>
                  <p class="product-price">${{ product.price.toFixed(2) }}</p>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Storage & Organization Products Section (Full Width) -->
      <div class="full-width-container">
        <div class="section-container">
          <div class="section-header">
            <h2 class="section-title">Storage & Organization</h2>
            <button @click="goToProducts('Storage & Organization')" class="btn btn-outline-dark btn-sm view-all">View All</button>
          </div>
          
          <div class="product-grid">
            <div v-for="product in storageOrganizationProducts.slice(0, 8)" :key="product.id" class="product-card">
              <router-link :to="`/products/${product.id}`" class="product-link">
                <div class="placeholder-img">
                  <img v-if="product.image && !product.image.includes('placeholder')" :src="product.image" :alt="product.name" />
                  <template v-else>placeholder</template>
                </div>
                <div class="product-info">
                  <h5 class="product-title">{{ product.name }}</h5>
                  <p class="product-price">${{ product.price.toFixed(2) }}</p>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>