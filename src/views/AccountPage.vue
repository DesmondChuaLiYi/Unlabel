<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import '../assets/css/account.css';

const router = useRouter();

// User data state - will be populated from API
const userData = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  zipCode: '',
  country: '',
  birthDate: '',
});

// Check if user is logged in
const isLoggedIn = ref(false);
const isLoading = ref(true);
const errors = ref('');

// Fetch user profile data on component mount
onMounted(async () => {
  try {
    const response = await fetch('/api/get_profile.php');
    const data = await response.json();

    if (data.error) {
      isLoggedIn.value = false;
      router.push('/login');
    } else {
      isLoggedIn.value = true;
      // Update userData with fetched data
      Object.keys(data.user).forEach((key) => {
        if (key in userData) {
          userData[key] = data.user[key] || '';
        }
      });
    }
  } catch (error) {
    console.error('Failed to fetch profile:', error);
    isLoggedIn.value = false;
  } finally {
    isLoading.value = false;
  }
});

// Logout function
const logout = async () => {
  try {
    const response = await fetch('/api/logout.php');
    const data = await response.json();

    if (data.success) {
      errors.value = 'Logout successful! Redirecting to login...';
      setTimeout(() => router.push('/login'), 2000); // Delay for user to see success message
    } else {
      errors.value = 'Logout failed. Please try again.';
    }
  } catch (error) {
    console.error('Logout error:', error);
    errors.value = 'Logout failed. Please try again.';
  }
};

// Countries list for dropdown
const countries = [
  { code: 'US', name: 'United States' },
  { code: 'CA', name: 'Canada' },
  { code: 'UK', name: 'United Kingdom' },
  { code: 'AU', name: 'Australia' },
  { code: 'JP', name: 'Japan' },
];

// Edit mode state
const isEditing = ref(false);
const isSubmitting = ref(false);
const showPassword = ref(false);
const profilePhoto = ref(null); // For photo upload

// Form for editing
const editForm = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  zipCode: '',
  country: '',
  birthDate: '',
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
});

// Start editing mode
const startEditing = () => {
  // Copy user data to edit form
  Object.keys(userData).forEach((key) => {
    if (key in editForm) {
      editForm[key] = userData[key] || '';
    }
  });
  isEditing.value = true;
};

// Cancel editing
const cancelEditing = () => {
  isEditing.value = false;
  profilePhoto.value = null; // Reset photo
};

// Save changes including photo upload
const saveChanges = async () => {
  try {
    isSubmitting.value = true;
    const formData = new FormData();
    Object.keys(editForm).forEach((key) => {
      formData.append(key, editForm[key]);
    });
    if (profilePhoto.value) {
      formData.append('profile_photo', profilePhoto.value);
    }

    const response = await fetch('/api/update_profile.php', {
      method: 'POST',
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      errors.value = 'Profile updated successfully!';
      // Update userData with new values
      Object.keys(editForm).forEach((key) => {
        if (key in userData) {
          userData[key] = editForm[key];
        }
      });
      if (data.profile_picture) {
        userData.profile_picture = data.profile_picture; // Assuming API returns base64 or URL
      }
      setTimeout(() => (isEditing.value = false), 2000); // Delay to show success
    } else {
      errors.value = data.error || 'Failed to update profile. Please try again.';
    }
  } catch (error) {
    console.error('Update error:', error);
    errors.value = 'Failed to update profile. Please try again.';
  } finally {
    isSubmitting.value = false;
  }
};

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const handlePhotoUpload = (event) => {
  profilePhoto.value = event.target.files[0];
  // Optionally preview the image
  const reader = new FileReader();
  reader.onload = (e) => {
    userData.profile_picture = e.target.result; // Base64 for preview
  };
  reader.readAsDataURL(profilePhoto.value);
};

// Purchase history data (mock)
const purchaseHistory = ref([
  {
    id: 'ORD-12345',
    date: '2023-05-15',
    status: 'Delivered',
    total: 129.99,
  },
  {
    id: 'ORD-67890',
    date: '2023-06-20',
    status: 'Shipped',
    total: 1499.99,
  },
  {
    id: 'ORD-54321',
    date: '2023-07-05',
    status: 'Processing',
    total: 249.97,
  },
]);

// Format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(amount);
};

// Format date
const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};

// Get status badge class
const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Processing':
      return 'bg-warning';
    case 'Shipped':
      return 'bg-info';
    case 'Delivered':
      return 'bg-success';
    case 'Cancelled':
      return 'bg-danger';
    default:
      return 'bg-secondary';
  }
};
</script>

<template>
  <div class="account-page">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Account</h1>
        <button @click="logout" class="btn btn-outline-danger">
          <i class="bi bi-box-arrow-right me-1"></i> Logout
        </button>
      </div>

      <!-- Loading state -->
      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <!-- Not logged in state -->
      <div v-else-if="!isLoggedIn" class="text-center py-5">
        <p>
          You are not logged in. Please <router-link to="/login">login</router-link> to view your account.
        </p>
      </div>

      <!-- Logged in state -->
      <div v-else>
        <div v-if="errors.value" :class="['alert', data && data.success ? 'alert-success' : 'alert-danger']" class="mb-3">
          {{ errors.value }}
        </div>

        <!-- Profile Section -->
        <div class="full-width-container">
          <div class="section-container">
            <div class="section-header">
              <h2 class="section-title">Profile Information</h2>
              <button v-if="!isEditing" class="btn btn-outline-dark btn-sm view-all" @click="startEditing">
                <i class="bi bi-pencil me-1"></i> Edit Profile
              </button>
            </div>

            <!-- View Mode -->
            <div v-if="!isEditing" class="card account-card">
              <div class="card-body">
                <div class="row">
                  <div class="col-md-3 text-center mb-4 mb-md-0">
                    <img v-if="userData.profile_picture" :src="userData.profile_picture" class="profile-img" alt="Profile">
                    <div v-else class="placeholder-img profile-img">profile</div>
                  </div>
                  <div class="col-md-9">
                    <div class="row mb-3">
                      <div class="col-md-6 info-group">
                        <p class="info-label">Name</p>
                        <p class="info-value">{{ userData.firstName }} {{ userData.lastName }}</p>
                      </div>
                      <div class="col-md-6 info-group">
                        <p class="info-label">Email</p>
                        <p class="info-value">{{ userData.email }}</p>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-md-6 info-group">
                        <p class="info-label">Phone</p>
                        <p class="info-value">{{ userData.phone || 'Not provided' }}</p>
                      </div>
                      <div class="col-md-6 info-group">
                        <p class="info-label">Birth Date</p>
                        <p class="info-value">{{ userData.birthDate ? formatDate(userData.birthDate) : 'Not provided' }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Edit Mode -->
            <div v-else class="card account-card">
              <div class="card-body">
                <form @submit.prevent="saveChanges" novalidate>
                  <div class="row">
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                      <img v-if="userData.profile_picture" :src="userData.profile_picture" class="profile-img" alt="Profile">
                      <div v-else class="placeholder-img profile-img">profile</div>
                      <input type="file" class="form-control mt-2" @change="handlePhotoUpload" accept="image/*">
                    </div>
                    <div class="col-md-9">
                      <div class="row mb-3">
                        <div class="col-md-6">
                          <label for="firstName" class="form-label">First Name</label>
                          <input type="text" class="form-control" id="firstName" v-model="editForm.firstName" required>
                        </div>
                        <div class="col-md-6">
                          <label for="lastName" class="form-label">Last Name</label>
                          <input type="text" class="form-control" id="lastName" v-model="editForm.lastName" required>
                        </div>
                      </div>
                      <div class="row mb-3">
                        <div class="col-md-6">
                          <label for="email" class="form-label">Email</label>
                          <input type="email" class="form-control" id="email" v-model="editForm.email" required>
                        </div>
                        <div class="col-md-6">
                          <label for="phone" class="form-label">Phone</label>
                          <input type="tel" class="form-control" id="phone" v-model="editForm.phone">
                        </div>
                      </div>
                      <div class="row mb-3">
                        <div class="col-md-6">
                          <label for="birthDate" class="form-label">Birth Date</label>
                          <input type="date" class="form-control" id="birthDate" v-model="editForm.birthDate">
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-outline-secondary me-2" @click="cancelEditing">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                      <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                      Save Changes
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- Address Section -->
        <div class="full-width-container bg-light">
          <div class="section-container">
            <div class="section-header">
              <h2 class="section-title">Address Information</h2>
              <button v-if="!isEditing" class="btn btn-outline-dark btn-sm view-all" @click="startEditing">
                <i class="bi bi-pencil me-1"></i> Edit Address
              </button>
            </div>

            <!-- View Mode -->
            <div v-if="!isEditing" class="card account-card">
              <div class="card-body">
                <div class="row mb-3">
                  <div class="col-12 info-group">
                    <p class="info-label">Address</p>
                    <p class="info-value">{{ userData.address }}</p>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4 info-group">
                    <p class="info-label">City</p>
                    <p class="info-value">{{ userData.city }}</p>
                  </div>
                  <div class="col-md-4 info-group">
                    <p class="info-label">State</p>
                    <p class="info-value">{{ userData.state }}</p>
                  </div>
                  <div class="col-md-4 info-group">
                    <p class="info-label">Zip Code</p>
                    <p class="info-value">{{ userData.zipCode }}</p>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4 info-group">
                    <p class="info-label">Country</p>
                    <p class="info-value">{{ countries.find((c) => c.code === userData.country)?.name || userData.country }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Edit Mode (Address) -->
            <div v-else class="card account-card">
              <div class="card-body">
                <form @submit.prevent="saveChanges" novalidate>
                  <div class="row mb-3">
                    <div class="col-12">
                      <label for="address" class="form-label">Address</label>
                      <input type="text" class="form-control" id="address" v-model="editForm.address" required>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-4">
                      <label for="city" class="form-label">City</label>
                      <input type="text" class="form-control" id="city" v-model="editForm.city" required>
                    </div>
                    <div class="col-md-4">
                      <label for="state" class="form-label">State</label>
                      <input type="text" class="form-control" id="state" v-model="editForm.state" required>
                    </div>
                    <div class="col-md-4">
                      <label for="zipCode" class="form-label">Zip Code</label>
                      <input type="text" class="form-control" id="zipCode" v-model="editForm.zipCode" required>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-4">
                      <label for="country" class="form-label">Country</label>
                      <select class="form-select" id="country" v-model="editForm.country" required>
                        <option v-for="country in countries" :key="country.code" :value="country.code">
                          {{ country.name }}
                        </option>
                      </select>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- Security Section -->
        <div class="full-width-container bg-light">
          <div class="section-container">
            <div class="section-header">
              <h2 class="section-title">Security</h2>
              <button v-if="!isEditing" class="btn btn-outline-dark btn-sm view-all" @click="startEditing">
                <i class="bi bi-pencil me-1"></i> Change Password
              </button>
            </div>

            <!-- View Mode -->
            <div v-if="!isEditing" class="card account-card">
              <div class="card-body">
                <p class="mb-0">Password: ••••••••</p>
              </div>
            </div>

            <!-- Edit Mode (Password) -->
            <div v-else class="card account-card">
              <div class="card-body">
                <form @submit.prevent="saveChanges" novalidate>
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label for="currentPassword" class="form-label">Current Password</label>
                      <div class="input-group">
                        <input
                          :type="showPassword ? 'text' : 'password'"
                          class="form-control"
                          id="currentPassword"
                          v-model="editForm.currentPassword"
                          required
                        >
                        <button
                          class="btn btn-outline-secondary"
                          type="button"
                          @click="togglePasswordVisibility"
                        >
                          <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label for="newPassword" class="form-label">New Password</label>
                      <input
                        :type="showPassword ? 'text' : 'password'"
                        class="form-control"
                        id="newPassword"
                        v-model="editForm.newPassword"
                        required
                      >
                    </div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label for="confirmPassword" class="form-label">Confirm New Password</label>
                      <input
                        :type="showPassword ? 'text' : 'password'"
                        class="form-control"
                        id="confirmPassword"
                        v-model="editForm.confirmPassword"
                        required
                      >
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>