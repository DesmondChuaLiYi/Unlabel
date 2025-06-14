<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import '../assets/css/product-detail.css'

// State
const product = ref(null)
const isLoading = ref(true)
const error = ref(null)
const selectedColor = ref('')
const quantity = ref(1)
const selectedImage = ref(0)
const recommendedProducts = ref([])

// Access route and router
const route = useRoute()
const router = useRouter()

// Load product data
const loadProduct = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const productId = parseInt(route.params.id)
    if (!productId) throw new Error('Invalid product ID')
    
    const module = await import('../assets/data/products.json')
    const products = module.default
    
    const foundProduct = products.find(p => p.id === productId)
    if (!foundProduct) throw new Error('Product not found')
    
    product.value = foundProduct
    
    if (product.value.colors && product.value.colors.length > 0) {
      selectedColor.value = product.value.colors[0]
    }
    
    const otherProducts = products.filter(p => p.id !== productId)
    const shuffled = [...otherProducts].sort(() => 0.5 - Math.random())
    recommendedProducts.value = shuffled.slice(0, 4)
  } catch (err) {
    error.value = 'Failed to load product. Please try again later.'
    console.error('Error loading product:', err)
  } finally {
    isLoading.value = false
  }
}

// Format price
const formatPrice = (price) => {
  return `$${price.toFixed(2)}`
}

// Increase quantity
const increaseQuantity = () => {
  if (product.value && quantity.value < product.value.stock) {
    quantity.value++
  }
}

// Decrease quantity
const decreaseQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

// Add to cart
const addToCart = async () => {
  try {
    const response = await fetch('/api/cart_add.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ product_id: product.value.id, quantity: quantity.value })
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Failed to add to cart');
    alert(`Added ${quantity.value} ${product.value.name} to cart`);
  } catch (err) {
    alert(`Error: ${err.message}`);
  }
}

// Change selected image
const changeImage = (index) => {
  selectedImage.value = index
}

// Navigate to product
const goToProduct = (productId) => {
  if (route.name === 'ProductDetail') {
    router.push(`/products/${productId}`).then(() => {
      loadProduct()
      window.scrollTo(0, 0)
    })
  } else {
    router.push(`/products/${productId}`)
  }
}

// Get stock status
const stockStatus = computed(() => {
  if (!product.value) return ''
  
  if (product.value.stock > 50) return 'In Stock'
  if (product.value.stock > 0) return `Low Stock (${product.value.stock} left)`
  return 'Out of Stock'
})

const stockStatusClass = computed(() => {
  if (!product.value) return ''
  
  if (product.value.stock > 50) return 'text-success'
  if (product.value.stock > 0) return 'text-warning'
  return 'text-danger'
})

onMounted(() => {
  loadProduct()
})
</script>

<template>
  <div class="page-container product-detail-page">
    <div class="container">
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><router-link to="/">Home</router-link></li>
          <li class="breadcrumb-item"><router-link to="/products">Products</router-link></li>
          <li v-if="product" class="breadcrumb-item"><router-link :to="`/products?category=${product.category.toLowerCase().replace(' ', '-')}`">{{ product.category }}</router-link></li>
          <li v-if="product" class="breadcrumb-item active" aria-current="page">{{ product.name }}</li>
        </ol>
      </nav>
      
      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3">Loading product details...</p>
      </div>
      
      <div v-else-if="error" class="text-center py-5">
        <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
        <p class="mt-3 text-danger">{{ error }}</p>
        <button class="btn btn-primary" @click="loadProduct">Retry</button>
      </div>
      
      <div v-else-if="product" class="row">
        <div class="col-lg-6 mb-4 mb-lg-0">
          <div class="product-images">
            <div class="main-image">
              <img 
                v-if="product.image && !product.image.includes('placeholder')" 
                :src="product.image" 
                :alt="product.name" 
                class="img-fluid"
              />
              <div v-else class="placeholder-img">No image available</div>
            </div>
            
            <div class="thumbnail-container mt-3">
              <div 
                class="thumbnail" 
                :class="{ active: selectedImage === 0 }"
                @click="changeImage(0)"
              >
                <img 
                  v-if="product.image && !product.image.includes('placeholder')" 
                  :src="product.image" 
                  :alt="product.name" 
                />
                <div v-else class="placeholder-img small">No image</div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-lg-6">
          <div class="product-info-container">
            <div class="product-id mb-2">SKU #{{ product.id.toString().padStart(6, '0') }}</div>
            <h1 class="product-title mb-3">{{ product.name }}</h1>
            <div class="rating mb-3">
              <i 
                v-for="i in 5" 
                :key="i" 
                :class="[
                  'bi', 
                  i <= Math.floor(product.rating) ? 'bi-star-fill' : 
                  i - 0.5 <= product.rating ? 'bi-star-half' : 'bi-star'
                ]"
              ></i>
              <span class="ms-2 text-muted">{{ product.rating }} ({{ Math.floor(product.rating * 10) }} reviews)</span>
            </div>
            <div class="product-price mb-4">{{ formatPrice(product.price) }}</div>
            <div class="product-description mb-4">
              <p>{{ product.description }}</p>
            </div>
            <div v-if="product.colors && product.colors.length > 0" class="color-selection mb-4">
              <h6 class="mb-2">Color: {{ selectedColor }}</h6>
              <div class="color-options">
                <button 
                  v-for="color in product.colors" 
                  :key="color"
                  class="color-option"
                  :class="{ active: selectedColor === color }"
                  @click="selectedColor = color"
                  :style="{ backgroundColor: color.toLowerCase() }"
                  :title="color"
                ></button>
              </div>
            </div>
            <div class="stock-status mb-4">
              <span :class="stockStatusClass">{{ stockStatus }}</span>
            </div>
            <div class="quantity-selector mb-4">
                <h6 class="mb-2">Quantity</h6>
                <div class="quantity-controls">
                <button 
                    class="btn btn-outline-secondary" 
                    @click="decreaseQuantity"
                    :disabled="quantity <= 1"
                >
                    <i class="bi bi-dash"></i>
                </button>
                <input 
                    type="text" 
                    class="form-control quantity-input" 
                    :value="quantity"
                    readonly
                />
                <button 
                    class="btn btn-outline-secondary" 
                    @click="increaseQuantity"
                    :disabled="quantity >= product.stock"
                >
                    <i class="bi bi-plus"></i>
                </button>
                </div>
            </div>
            <div class="add-to-cart mb-4">
              <button 
                class="btn btn-dark w-100 py-3" 
                @click="addToCart"
                :disabled="product.stock === 0"
              >
                <i class="bi bi-cart-plus me-2"></i>
                Add to Cart
              </button>
            </div>
            <div class="additional-info">
              <div class="shipping-info mb-2">
                <i class="bi bi-truck me-2"></i>
                Free shipping on orders over $80
              </div>
              <div class="returns-info mb-2">
                <i class="bi bi-arrow-return-left me-2"></i>
                30-day returns
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div v-else class="text-center py-5">
        <i class="bi bi-question-circle fs-1 text-muted"></i>
        <p class="mt-3 text-muted">Product not found</p>
        <router-link to="/products" class="btn btn-primary">Browse Products</router-link>
      </div>
      
      <div v-if="product" class="product-details-tabs mt-5">
        <ul class="nav nav-tabs" id="productTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button 
              class="nav-link active" 
              id="details-tab" 
              data-bs-toggle="tab" 
              data-bs-target="#details" 
              type="button" 
              role="tab" 
              aria-controls="details" 
              aria-selected="true"
            >
              Product Details
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button 
              class="nav-link" 
              id="shipping-tab" 
              data-bs-toggle="tab" 
              data-bs-target="#shipping" 
              type="button" 
              role="tab" 
              aria-controls="shipping" 
              aria-selected="false"
            >
              Shipping & Returns
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button 
              class="nav-link" 
              id="reviews-tab" 
              data-bs-toggle="tab" 
              data-bs-target="#reviews" 
              type="button" 
              role="tab" 
              aria-controls="reviews" 
              aria-selected="false"
            >
              Reviews
            </button>
          </li>
        </ul>
        
        <div class="tab-content p-4 border border-top-0" id="productTabsContent">
          <div 
            class="tab-pane fade show active" 
            id="details" 
            role="tabpanel" 
            aria-labelledby="details-tab"
          >
            <h5>Product Specifications</h5>
            <table class="table table-striped">
              <tbody>
                <tr>
                  <th scope="row">Material</th>
                  <td>Premium Quality</td>
                </tr>
                <tr>
                  <th scope="row">Category</th>
                  <td>{{ product.category }}</td>
                </tr>
                <tr>
                  <th scope="row">SKU</th>
                  <td>{{ product.id.toString().padStart(6, '0') }}</td>
                </tr>
              </tbody>
            </table>
            <h5 class="mt-4">Care Instructions</h5>
            <p>Please refer to the product label for specific care instructions.</p>
          </div>
          
          <div 
            class="tab-pane fade" 
            id="shipping" 
            role="tabpanel" 
            aria-labelledby="shipping-tab"
          >
            <h5>Shipping Information</h5>
            <p>We offer free standard shipping on all orders over $80.</p>
            <ul>
              <li>Standard Shipping (3-5 business days): $5.99</li>
              <li>Express Shipping (1-2 business days): $12.99</li>
            </ul>
            <h5 class="mt-4">Return Policy</h5>
            <p>We accept returns within 30 days of purchase. Items must be unused and in original packaging.</p>
          </div>
          
          <div 
            class="tab-pane fade" 
            id="reviews" 
            role="tabpanel" 
            aria-labelledby="reviews-tab"
          >
            <h5>Customer Reviews</h5>
            <p>This product has an average rating of {{ product.rating }} out of 5 stars.</p>
            <div class="review-item p-3 border-bottom">
              <div class="d-flex justify-content-between">
                <h6>Great product!</h6>
                <div>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                </div>
              </div>
              <p class="text-muted small mb-2">By John D. on March 15, 2023</p>
              <p>This product exceeded my expectations. The quality is excellent and it looks even better in person.</p>
            </div>
            <div class="review-item p-3 border-bottom">
              <div class="d-flex justify-content-between">
                <h6>Good value</h6>
                <div>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star-fill text-warning"></i>
                  <i class="bi bi-star text-warning"></i>
                </div>
              </div>
              <p class="text-muted small mb-2">By Sarah M. on February 28, 2023</p>
              <p>Good quality for the price. Would recommend.</p>
            </div>
          </div>
        </div>
      </div>
      
      <div v-if="product" class="related-products mt-5">
        <h3 class="section-title mb-4">You May Also Like</h3>
        <div class="row">
          <div 
            v-for="relatedProduct in recommendedProducts" 
            :key="relatedProduct.id"
            class="col-6 col-md-3 mb-4"
            @click="goToProduct(relatedProduct.id)"
            style="cursor: pointer;"
          >
            <div class="product-card">
              <div class="placeholder-img">
                <img 
                  v-if="relatedProduct.image && !relatedProduct.image.includes('placeholder')" 
                  :src="relatedProduct.image" 
                  :alt="relatedProduct.name" 
                  class="img-fluid"
                  style="width: 100%; height: 100%; object-fit: cover;"
                />
                <span v-else>No image available</span>
              </div>
              <div class="product-info">
                <h5 class="product-title">{{ relatedProduct.name }}</h5>
                <p class="product-price">{{ formatPrice(relatedProduct.price) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>