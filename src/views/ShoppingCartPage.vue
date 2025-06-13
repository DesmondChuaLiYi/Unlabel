<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import '../assets/css/cart.css'

const router = useRouter()

// Sample cart items - in a real app, this would come from a store or API
const cartItems = ref([
  {
    id: 1,
    name: 'Smartphone X',
    price: 999.99,
    image: 'https://via.placeholder.com/100',
    quantity: 1
  },
  {
    id: 3,
    name: 'Wireless Headphones',
    price: 249.99,
    image: 'https://via.placeholder.com/100',
    quantity: 2
  }
])

const shippingOptions = ref([
  { id: 1, name: 'Standard Shipping', price: 5.99, days: '5-7 business days' },
  { id: 2, name: 'Express Shipping', price: 15.99, days: '2-3 business days' },
  { id: 3, name: 'Next Day Delivery', price: 29.99, days: '1 business day' }
])

const selectedShipping = ref(1)

// Computed properties
const subtotal = computed(() => {
  return cartItems.value.reduce((total, item) => {
    return total + (item.price * item.quantity)
  }, 0)
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
const updateQuantity = (item, newQuantity) => {
  if (newQuantity < 1) return
  
  const index = cartItems.value.findIndex(i => i.id === item.id)
  if (index !== -1) {
    cartItems.value[index].quantity = newQuantity
  }
}

const removeItem = (itemId) => {
  cartItems.value = cartItems.value.filter(item => item.id !== itemId)
}

const clearCart = () => {
  cartItems.value = []
}

const checkout = () => {
  alert('Proceeding to checkout...')
  // In a real app, this would navigate to a checkout page or process
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
</script>

<template>
  <div class="cart-page page-container">
    <h1 class="mb-4">Shopping Cart</h1>
    
    <div v-if="isEmpty" class="text-center py-5 empty-cart">
      <i class="bi bi-cart-x fs-1 text-muted"></i>
      <h3 class="mt-3">Your cart is empty</h3>
      <p class="text-muted">Looks like you haven't added any products to your cart yet.</p>
      <button @click="continueShopping" class="btn btn-primary mt-3 cart-action-btn">
        <i class="bi bi-shop"></i> Continue Shopping
      </button>
    </div>
    
    <div v-else class="row">
      <!-- Cart Items -->
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
                        <div class="placeholder-img">product</div>
                        <div>
                          <h6 class="mb-0">{{ item.name }}</h6>
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
                    <td class="text-end">{{ formatPrice(item.price) }}</td>
                    <td class="text-end">{{ formatPrice(item.price * item.quantity) }}</td>
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
      
      <!-- Order Summary -->
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