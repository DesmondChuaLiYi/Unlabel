<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import '../assets/css/cart.css'

const router = useRouter()
const cartItems = ref([])
const isLoading = ref(true)
const error = ref(null)
const shippingOptions = ref([
  { id: 1, name: 'Standard Shipping', price: 5.99, days: '5-7 business days' },
  { id: 2, name: 'Express Shipping', price: 15.99, days: '2-3 business days' },
  { id: 3, name: 'Next Day Delivery', price: 29.99, days: '1 business day' }
])
const selectedShipping = ref(1)

// Load cart
const loadCart = async () => {
  isLoading.value = true
  error.value = null
  try {
    const response = await fetch('/api/cart_get.php')
    const data = await response.json()
    if (!response.ok) throw new Error(data.error || 'Failed to load cart')
    cartItems.value = data.cartItems
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

// Computed properties
const subtotal = computed(() => {
  return cartItems.value.reduce((total, item) => total + (item.product_price * item.quantity), 0)
})

const shippingCost = computed(() => {
  const selected = shippingOptions.value.find(option => option.id === selectedShipping.value)
  return selected ? selected.price : 0
})

const total = computed(() => {
  return subtotal.value + shippingCost.value
})

const isEmpty = computed(() => {
  return cartItems.value.length === 0
})

// Methods
const updateQuantity = async (item, newQuantity) => {
  if (newQuantity < 1) return
  try {
    const response = await fetch('/api/cart_update.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cart_item_id: item.id, quantity: newQuantity })
    })
    const data = await response.json()
    if (!response.ok) throw new Error(data.error || 'Failed to update quantity')
    await loadCart()
  } catch (err) {
    alert(`Error: ${err.message}`)
  }
}

const removeItem = async (cartItemId) => {
  try {
    const response = await fetch('/api/cart_remove.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cart_item_id: cartItemId })
    })
    const data = await response.json()
    if (!response.ok) throw new Error(data.error || 'Failed to remove item')
    await loadCart()
  } catch (err) {
    alert(`Error: ${err.message}`)
  }
}

const clearCart = async () => {
  try {
    for (const item of cartItems.value) {
      await fetch('/api/cart_remove.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart_item_id: item.id })
      })
    }
    await loadCart()
  } catch (err) {
    alert(`Error: ${err.message}`)
  }
}

const checkout = async () => {
  try {
    const response = await fetch('/api/cart_checkout.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ shipping_option_id: selectedShipping.value })
    })
    const data = await response.json()
    if (!response.ok) throw new Error(data.error || 'Failed to checkout')
    alert('Checkout successful!')
    router.push('/purchases')
  } catch (err) {
    alert(`Error: ${err.message}`)
  }
}

const continueShopping = () => {
  router.push('/products')
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(price)
}

onMounted(loadCart)
</script>

<template>
  <div class="cart-page page-container">
    <h1 class="mb-4">Shopping Cart</h1>
    
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-3">Loading cart...</p>
    </div>
    
    <div v-else-if="error" class="text-center py-5">
      <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
      <p class="mt-3 text-danger">{{ error }}</p>
      <button class="btn btn-primary" @click="loadCart">Retry</button>
    </div>
    
    <div v-else-if="isEmpty" class="text-center py-5 empty-cart">
      <i class="bi bi-cart-x fs-1 text-muted"></i>
      <h3 class="mt-3">Your cart is empty</h3>
      <p class="text-muted">Looks like you haven't added any products to your cart yet.</p>
      <button @click="continueShopping" class="btn btn-primary mt-3 cart-action-btn">
        <i class="bi bi-shop"></i> Continue Shopping
      </button>
    </div>
    
    <div v-else class="row">
      <div class="col-lg-8 mb-4">
        <div class="card">
          <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Cart Items ({{ cartItems.length }})</h5>
              <button @click="clearCart" class="btn btn-sm btn-outline-danger cart-action-btn">
                <i class="bi bi-trash"></i> Clear Cart
              </button>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-borderless mb-0">
                <thead class="table-light">
                  <tr>
                    <th scope="col">Product</th>
                    <th scope="col" class="text-center">Quantity</th>
                    <th scope="col" class="text-end">Price</th>
                    <th scope="col" class="text-end">Total</th>
                    <th scope="col"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in cartItems" :key="item.id" class="cart-item">
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="placeholder-img">
                          <img v-if="item.product_image && !item.product_image.includes('placeholder')" :src="item.product_image" :alt="item.product_name" />
                          <template v-else>product</template>
                        </div>
                        <div>
                          <h6 class="mb-0">{{ item.product_name }}</h6>
                        </div>
                      </div>
                    </td>
                    <td class="text-center">
                      <div class="input-group input-group-sm" style="width: 120px;">
                        <button 
                          class="btn btn-outline-secondary" 
                          type="button"
                          @click="updateQuantity(item, item.quantity - 1)"
                        >
                          <i class="bi bi-dash"></i>
                        </button>
                        <input 
                          type="number" 
                          class="form-control text-center" 
                          :value="item.quantity"
                          @change="e => updateQuantity(item, parseInt(e.target.value) || 1)"
                          min="1"
                        >
                        <button 
                          class="btn btn-outline-secondary" 
                          type="button"
                          @click="updateQuantity(item, item.quantity + 1)"
                        >
                          <i class="bi bi-plus"></i>
                        </button>
                      </div>
                    </td>
                    <td class="text-end">{{ formatPrice(item.product_price) }}</td>
                    <td class="text-end">{{ formatPrice(item.product_price * item.quantity) }}</td>
                    <td class="text-end">
                      <button 
                        @click="removeItem(item.id)" 
                        class="btn btn-sm btn-link text-danger"
                        title="Remove item"
                      >
                        <i class="bi bi-x-circle"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4">
        <div class="card">
          <div class="card-header bg-white">
            <h5 class="mb-0">Order Summary</h5>
          </div>
          <div class="card-body">
            <div class="mb-4">
              <h6 class="mb-3">Shipping Options</h6>
              <div class="form-check mb-2" v-for="option in shippingOptions" :key="option.id">
                <input 
                  class="form-check-input" 
                  type="radio" 
                  :id="`shipping-${option.id}`" 
                  :value="option.id"
                  v-model="selectedShipping"
                >
                <label class="form-check-label d-flex justify-content-between" :for="`shipping-${option.id}`">
                  <div>
                    <span>{{ option.name }}</span>
                    <p class="text-muted small mb-0">{{ option.days }}</p>
                  </div>
                  <span>{{ formatPrice(option.price) }}</span>
                </label>
              </div>
            </div>
            
            <hr>
            
            <div class="d-flex justify-content-between mb-2">
              <span>Subtotal</span>
              <span>{{ formatPrice(subtotal) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Shipping</span>
              <span>{{ formatPrice(shippingCost) }}</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-4">
              <strong>Total</strong>
              <strong>{{ formatPrice(total) }}</strong>
            </div>
            
            <button @click="checkout" class="btn btn-primary w-100 mb-3 cart-action-btn">
              <i class="bi bi-credit-card me-2"></i> Proceed to Checkout
            </button>
            <button @click="continueShopping" class="btn btn-outline-secondary w-100 cart-action-btn">
              <i class="bi bi-arrow-left me-2"></i> Continue Shopping
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>