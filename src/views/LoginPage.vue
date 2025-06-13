<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import '../assets/css/auth.css'

const router = useRouter()

const form = reactive({
  email: '',
  password: '',
  rememberMe: false
})

const errors = reactive({
  email: '',
  password: ''
})

const isSubmitting = ref(false)
const showPassword = ref(false)

const validateField = (fieldName) => {
  errors[fieldName] = ''
  if (fieldName === 'email') {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (form.email.trim()) {
      if (!emailRegex.test(form.email)) {
        errors.email = 'Please enter a valid email address'
      }
    }
  } else if (fieldName === 'password') {
    if (form.password) {
      if (form.password.length < 8) {
        errors.password = 'Password must be at least 8 characters'
      } else if (!/(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*_])/.test(form.password)) {
        errors.password = 'Password must contain at least one letter, one number, and one symbol (!@#$%^&*_)'
      }
    }
  }
}

const validateForm = () => {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')
  
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!form.email.trim()) {
    errors.email = 'Email is required'
    isValid = false
  } else if (!emailRegex.test(form.email)) {
    errors.email = 'Please enter a valid email address'
    isValid = false
  }
  
  if (!form.password) {
    errors.password = 'Password is required'
    isValid = false
  } else if (form.password.length < 8) {
    errors.password = 'Password must be at least 8 characters'
    isValid = false
  } else if (!/(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*_])/.test(form.password)) {
    errors.password = 'Password must contain at least one letter, one number, and one symbol (!@#$%^&*_)'
    isValid = false
  }
  
  return isValid
}

const handleSubmit = async () => {
  if (!validateForm()) {
    const firstError = document.querySelector('.is-invalid')
    if (firstError) {
      firstError.scrollIntoView({ behavior: 'smooth', block: 'center' })
    }
    return
  }
  
  isSubmitting.value = true
  
  try {
    await new Promise(resolve => setTimeout(resolve, 1500))
    router.push('/account')
  } catch (error) {
    alert('Login failed. Please check your credentials and try again.')
    console.error('Login error:', error)
  } finally {
    isSubmitting.value = false
  }
}

const goToRegister = () => {
  router.push('/register')
}

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}
</script>

<template>
  <div class="auth-container">
    <div class="auth-box">
      <div class="auth-header">
        <div class="auth-logo">Unlabel - Ecommerce Shop</div>
        <h1 class="auth-title">User log in</h1>
      </div>
      <div class="auth-body">
        <form @submit.prevent="handleSubmit" novalidate class="auth-form">
          <div class="mb-4">
            <label for="email" class="form-label required-field">Email and Password</label>
            <input 
              type="email" 
              class="form-control" 
              :class="{ 'is-invalid': errors.email }" 
              id="email" 
              v-model="form.email"
              autocomplete="email"
              placeholder="Email address"
              @blur="validateField('email')"
            >
            <div class="invalid-feedback">{{ errors.email }}</div>
          </div>
          
          <div class="mb-4">
            <div class="input-group">
              <input 
                :type="showPassword ? 'text' : 'password'" 
                class="form-control" 
                :class="{ 'is-invalid': errors.password }" 
                id="password" 
                v-model="form.password"
                autocomplete="current-password"
                placeholder="Password"
                @blur="validateField('password')"
              >
              <button 
                class="btn btn-outline-primary password-toggle" 
                type="button"
                @click="togglePasswordVisibility"
              >
                <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
              </button>
            </div>
            <div class="invalid-feedback">{{ errors.password }}</div>
            <div class="form-text">Password must be at least 8 characters and include a letter, a number, and a symbol (!@#$%^&*_)</div>
          </div>
          
          <div class="mb-4 form-check">
            <input 
              type="checkbox" 
              class="form-check-input" 
              id="rememberMe" 
              v-model="form.rememberMe"
            >
            <label class="form-check-label" for="rememberMe">Remember me</label>
          </div>
          
          <div class="d-grid gap-2 mb-3">
            <button 
              type="submit" 
              class="btn btn-primary" 
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              {{ isSubmitting ? 'Logging in...' : 'Log in' }}
            </button>
          </div>
          
          <div class="text-center mb-3">
            <a href="#" class="text-decoration-none">Forgot your password?</a>
          </div>
        </form>
      </div>
      <div class="auth-footer">
        <p class="mb-2">New user registration</p>
        <button @click="goToRegister" class="btn btn-outline-secondary w-100">
          Register with email
        </button>
      </div>
    </div>
  </div>
</template>