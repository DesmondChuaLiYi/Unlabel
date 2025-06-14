<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import '../assets/css/purchases.css'

const purchases = ref([])
const isLoading = ref(true)
const error = ref(null)
const selectedPurchase = ref(null)

const filters = reactive({
  status: 'all',
  dateRange: 'all',
  sortBy: 'date',
  sortDirection: 'desc'
})

const statusOptions = [
  { value: 'all', label: 'All Orders' },
  { value: 'Processing', label: 'Processing' },
  { value: 'Shipped', label: 'Shipped' },
  { value: 'Delivered', label: 'Delivered' },
  { value: 'Cancelled', label: 'Cancelled' }
]

const dateRangeOptions = [
  { value: 'all', label: 'All Time' },
  { value: '30days', label: 'Last 30 Days' },
  { value: '6months', label: 'Last 6 Months' },
  { value: '1year', label: 'Last Year' }
]

const sortOptions = [
  { value: 'date', label: 'Order Date' },
  { value: 'total', label: 'Order Total' },
  { value: 'status', label: 'Order Status' }
]

// Load purchases
const loadPurchases = async () => {
  isLoading.value = true
  error.value = null
  try {
    const response = await fetch('/api/purchases_get.php')
    const data = await response.json()
    if (!response.ok) throw new Error(data.error || 'Failed to load purchases')
    purchases.value = data.purchases
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

const filteredPurchases = computed(() => {
  let result = [...purchases.value]
  
  if (filters.status !== 'all') {
    result = result.filter(purchase => purchase.status === filters.status)
  }
  
  if (filters.dateRange !== 'all') {
    const now = new Date()
    let cutoffDate
    
    switch (filters.dateRange) {
      case '30days':
        cutoffDate = new Date(now.setDate(now.getDate() - 30))
        break
      case '6months':
        cutoffDate = new Date(now.setMonth(now.getMonth() - 6))
        break
      case '1year':
        cutoffDate = new Date(now.setFullYear(now.getFullYear() - 1))
        break
      default:
        cutoffDate = null
    }
    
    if (cutoffDate) {
      result = result.filter(purchase => new Date(purchase.date) >= cutoffDate)
    }
  }
  
  result.sort((a, b) => {
    let comparison = 0
    
    switch (filters.sortBy) {
      case 'date':
        comparison = new Date(a.date) - new Date(b.date)
        break
      case 'total':
        comparison = a.total - b.total
        break
      case 'status':
        comparison = a.status.localeCompare(b.status)
        break
    }
    
    return filters.sortDirection === 'asc' ? comparison : -comparison
  })
  
  return result
})

const viewPurchaseDetails = (purchase) => {
  selectedPurchase.value = purchase
}

const closePurchaseDetails = () => {
  selectedPurchase.value = null
}

const toggleSortDirection = () => {
  filters.sortDirection = filters.sortDirection === 'asc' ? 'desc' : 'asc'
}

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric' }
  return new Date(dateString).toLocaleDateString(undefined, options)
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Processing':
      return 'bg-medium-gray text-white'
    case 'Shipped':
      return 'bg-primary text-white'
    case 'Delivered':
      return 'bg-success text-white'
    case 'Cancelled':
      return 'bg-danger text-white'
    default:
      return 'bg-secondary text-white'
  }
}

onMounted(loadPurchases)
</script>

<template>
  <div class="purchases-page page-container">
    <div class="container">
      <h1 class="mb-4">My Purchases</h1>
      
      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3">Loading purchases...</p>
      </div>
      
      <div v-else-if="error" class="text-center py-5">
        <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
        <p class="mt-3 text-danger">{{ error }}</p>
        <button class="btn btn-primary" @click="loadPurchases">Retry</button>
      </div>
      
      <div v-else>
        <div class="filters-container mb-4">
          <div class="section-header mb-3">
            <h2 class="section-title">Filters</h2>
            <span class="badge bg-light text-dark">{{ filteredPurchases.length }} orders</span>
          </div>
          <div class="row g-3">
            <div class="col-md-3">
              <label for="statusFilter" class="form-label">Filter by Status</label>
              <select id="statusFilter" class="form-select" v-model="filters.status">
                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>
            <div class="col-md-3">
              <label for="dateFilter" class="form-label">Filter by Date</label>
              <select id="dateFilter" class="form-select" v-model="filters.dateRange">
                <option v-for="option in dateRangeOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="sortBy" class="form-label">Sort by</label>
              <div class="input-group">
                <select id="sortBy" class="form-select" v-model="filters.sortBy">
                  <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
                <button 
                  class="btn btn-outline-secondary" 
                  type="button"
                  @click="toggleSortDirection"
                >
                  <i :class="filters.sortDirection === 'asc' ? 'bi bi-sort-up' : 'bi bi-sort-down'"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="section-header mb-4">
          <h2 class="section-title">Order History</h2>
        </div>
        
        <div v-if="filteredPurchases.length > 0">
          <div class="card mb-3 purchase-card" v-for="purchase in filteredPurchases" :key="purchase.id">
            <div class="card-header">
              <div>
                <span class="fw-bold">Order #{{ purchase.id }}</span>
                <span class="text-muted ms-3">{{ formatDate(purchase.date) }}</span>
              </div>
              <span class="badge status-badge" :class="getStatusBadgeClass(purchase.status)">
                {{ purchase.status }}
              </span>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-8">
                  <div class="d-flex align-items-center mb-3" v-for="item in purchase.items.slice(0, 2)" :key="item.id">
                    <div class="placeholder-img">
                      <img v-if="item.image && !item.image.includes('placeholder')" :src="item.image" :alt="item.name" />
                      <template v-else>product</template>
                    </div>
                    <div>
                      <div class="fw-medium">{{ item.name }}</div>
                      <div class="text-muted">{{ formatCurrency(item.price) }} × {{ item.quantity }}</div>
                    </div>
                  </div>
                  <div v-if="purchase.items.length > 2" class="text-muted mt-2">
                    + {{ purchase.items.length - 2 }} more items
                  </div>
                </div>
                <div class="col-md-4 text-md-end d-flex flex-column justify-content-between">
                  <div class="fw-bold mb-3">Total: {{ formatCurrency(purchase.total) }}</div>
                  <button class="btn btn-outline-primary" @click="viewPurchaseDetails(purchase)">
                    View Details
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="card empty-purchases">
          <div class="card-body text-center py-5">
            <i class="bi bi-bag-x"></i>
            <h4 class="mt-3">No orders found</h4>
            <p class="text-muted">Try changing your filters or check back later.</p>
          </div>
        </div>
        
        <div class="modal fade show purchase-modal" v-if="selectedPurchase" style="display: block;" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Order Details - #{{ selectedPurchase.id }}</h5>
                <button type="button" class="btn-close" @click="closePurchaseDetails"></button>
              </div>
              <div class="modal-body">
                <div class="row mb-4">
                  <div class="col-md-6">
                    <h6 class="mb-3">Order Information</h6>
                    <p class="mb-1"><strong>Date:</strong> {{ formatDate(selectedPurchase.date) }}</p>
                    <p class="mb-1">
                      <strong>Status:</strong> 
                      <span class="badge status-badge" :class="getStatusBadgeClass(selectedPurchase.status)">
                        {{ selectedPurchase.status }}
                      </span>
                    </p>
                    <p class="mb-1"><strong>Total:</strong> {{ formatCurrency(selectedPurchase.total) }}</p>
                  </div>
                  <div class="col-md-6">
                    <h6 class="mb-3">Shipping Information</h6>
                    <p class="mb-1"><strong>Address:</strong> {{ selectedPurchase.shippingAddress }}</p>
                    <p class="mb-1">
                      <strong>Tracking Number:</strong> 
                      {{ selectedPurchase.trackingNumber || 'Not available yet' }}
                    </p>
                  </div>
                </div>
                
                <h6 class="mb-3">Order Items</h6>
                <div class="table-responsive">
                  <table class="table">
                    <thead class="table-light">
                      <tr>
                        <th>Product</th>
                        <th class="text-center">Price</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in selectedPurchase.items" :key="item.id">
                        <td>
                          <div class="d-flex align-items-center">
                            <div class="placeholder-img">
                              <img v-if="item.image && !item.image.includes('placeholder')" :src="item.image" :alt="item.name" />
                              <template v-else>product</template>
                            </div>
                            <div>{{ item.name }}</div>
                          </div>
                        </td>
                        <td class="text-center">{{ formatCurrency(item.price) }}</td>
                        <td class="text-center">{{ item.quantity }}</td>
                        <td class="text-end">{{ formatCurrency(item.price * item.quantity) }}</td>
                      </tr>
                    </tbody>
                    <tfoot>
                      <tr>
                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                        <td class="text-end">{{ formatCurrency(selectedPurchase.total - (selectedPurchase.shippingCost || 0)) }}</td>
                      </tr>
                      <tr>
                        <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                        <td class="text-end">{{ formatCurrency(selectedPurchase.shippingCost || 0) }}</td>
                      </tr>
                      <tr>
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td class="text-end fw-bold">{{ formatCurrency(selectedPurchase.total) }}</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" @click="closePurchaseDetails">Close</button>
              </div>
            </div>
          </div>
          <div class="modal-backdrop fade show" @click="closePurchaseDetails"></div>
        </div>
      </div>
    </div>
  </div>
</template>