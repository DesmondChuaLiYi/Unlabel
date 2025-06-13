<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import '../assets/css/auth.css'

const router = useRouter()

const form = reactive({
  firstName: '',
  lastName: '',
  email: '',
  password: '',
  confirmPassword: '',
  agreeTerms: false
})

const errors = reactive({
  firstName: '',
  lastName: '',
  email: '',
  password: '',
  confirmPassword: '',
  agreeTerms: ''
})

const isSubmitting = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)

const validateField = (fieldName) => {
  errors[fieldName] = ''
  if (fieldName === 'firstName' && form.firstName.trim()) {
    if (!/^[a-zA-Z\s-]+$/.test(form.firstName)) {
      errors.firstName = 'First name can only contain letters, spaces, or hyphens'
    }
  } else if (fieldName === 'lastName' && form.lastName.trim()) {
    if (!/^[a-zA-Z\s-]+$/.test(form.lastName)) {
      errors.lastName = 'Last name can only contain letters, spaces, or hyphens'
    }
  } else if (fieldName === 'email' && form.email.trim()) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(form.email)) {
      errors.email = 'Please enter a valid email address'
    }
  } else if (fieldName === 'password' && form.password) {
    if (form.password.length < 8) {
      errors.password = 'Password must be at least 8 characters'
    } else if (!/(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*_])/.test(form.password)) {
      errors.password = 'Password must contain at least one letter, one number, and one symbol (!@#$%^&*_)'
    }
  } else if (fieldName === 'confirmPassword' && form.confirmPassword && form.password) {
    if (form.password !== form.confirmPassword) {
      errors.confirmPassword = 'Passwords do not match'
    }
  }
}

const validateForm = () => {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')
  
  if (!form.firstName.trim()) {
    errors.firstName = 'First name is required'
    isValid = false
  } else if (!/^[a-zA-Z\s-]+$/.test(form.firstName)) {
    errors.firstName = 'First name can only contain letters, spaces, or hyphens'
    isValid = false
  }
  
  if (!form.lastName.trim()) {
    errors.lastName = 'Last name is required'
    isValid = false
  } else if (!/^[a-zA-Z\s-]+$/.test(form.lastName)) {
    errors.lastName = 'Last name can only contain letters, spaces, or hyphens'
    isValid = false
  }
  
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
  
  if (!form.confirmPassword) {
    errors.confirmPassword = 'Please confirm your password'
    isValid = false
  } else if (form.password !== form.confirmPassword) {
    errors.confirmPassword = 'Passwords do not match'
    isValid = false
  }
  
  if (!form.agreeTerms) {
    errors.agreeTerms = 'You must agree to the terms and conditions'
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
    alert('Registration successful! You can now log in.')
    router.push('/login')
  } catch (error) {
    alert('Registration failed. Please try again.')
    console.error('Registration error:', error)
  } finally {
    isSubmitting.value = false
  }
}

const goToLogin = () => {
  router.push('/login')
}

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const toggleConfirmPasswordVisibility = () => {
  showConfirmPassword.value = !showConfirmPassword.value
}
</script>

<template>
  <div class="auth-container">
    <div class="auth-box">
      <div class="auth-header">
        <div class="auth-logo">Unlabel - Ecommerce Shop</div>
        <h1 class="auth-title">New user registration</h1>
      </div>
      <div class="auth-body">
        <form @submit.prevent="handleSubmit" novalidate class="auth-form">
          <h5 class="mb-3">Personal Information</h5>
          <div class="row mb-3">
            <div class="col-md-6 mb-3 mb-md-0">
              <label for="firstName" class="form-label required-field">First Name</label>
              <input 
                type="text" 
                class="form-control" 
                :class="{ 'is-invalid': errors.firstName }" 
                id="firstName" 
                v-model="form.firstName"
                @blur="validateField('firstName')"
              >
              <div class="invalid-feedback">{{ errors.firstName }}</div>
            </div>
            <div class="col-md-6">
              <label for="lastName" class="form-label required-field">Last Name</label>
              <input 
                type="text" 
                class="form-control" 
                :class="{ 'is-invalid': errors.lastName }" 
                id="lastName" 
                v-model="form.lastName"
                @blur="validateField('lastName')"
              >
              <div class="invalid-feedback">{{ errors.lastName }}</div>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="email" class="form-label required-field">Email Address</label>
            <input 
              type="email" 
              class="form-control" 
              :class="{ 'is-invalid': errors.email }" 
              id="email" 
              v-model="form.email"
              autocomplete="email"
              @blur="validateField('email')"
            >
            <div class="invalid-feedback">{{ errors.email }}</div>
          </div>
          
          <div class="mb-3">
            <label for="password" class="form-label required-field">Password</label>
            <div class="input-group">
              <input 
                :type="showPassword ? 'text' : 'password'" 
                class="form-control" 
                :class="{ 'is-invalid': errors.password }" 
                id="password" 
                v-model="form.password"
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
          
          <div class="mb-4">
            <label for="confirmPassword" class="form-label required-field">Confirm Password</label>
            <div class="input-group">
              <input 
                :type="showConfirmPassword ? 'text' : 'password'" 
                class="form-control" 
                :class="{ 'is-invalid': errors.confirmPassword }" 
                id="confirmPassword" 
                v-model="form.confirmPassword"
                @blur="validateField('confirmPassword')"
              >
              <button 
                class="btn btn-outline-primary password-toggle" 
                type="button"
                @click="toggleConfirmPasswordVisibility"
              >
                <i :class="showConfirmPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
              </button>
            </div>
            <div class="invalid-feedback">{{ errors.confirmPassword }}</div>
          </div>
          <p class="mt-1">Already have an account? <a href="#" @click.prevent="goToLogin">Log in</a></p>
          <div class="mb-4 form-check">
            <input 
              type="checkbox" 
              class="form-check-input" 
              :class="{ 'is-invalid': errors.agreeTerms }" 
              id="agreeTerms" 
              v-model="form.agreeTerms"
            >
            <label class="form-check-label" for="agreeTerms">
              I agree to the <a href="#">Terms and Conditions</a> and <a href="#">Privacy Policy</a>
            </label>
            <div class="invalid-feedback">{{ errors.agreeTerms }}</div>
          </div>
          
          <div class="d-grid gap-2">
            <button 
              type="submit" 
              class="btn btn-primary" 
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              {{ isSubmitting ? 'Creating Account...' : 'Create Account' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>