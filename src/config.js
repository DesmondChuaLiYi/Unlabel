export const API_BASE_URL = process.env.NODE_ENV === 'production' 
    ? '' // Root URL in production
    : ''; // Root URL in development