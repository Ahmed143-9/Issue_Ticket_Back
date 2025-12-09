import axios from 'axios';

// Set up axios defaults
const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Log the baseURL to verify it's set correctly
console.log('API baseURL:', api.defaults.baseURL);

// Add a request interceptor to include the CSRF token
api.interceptors.request.use(config => {
  const token = document.head.querySelector('meta[name="csrf-token"]');
  if (token) {
    config.headers['X-CSRF-TOKEN'] = token.content;
  }
  
  // Log the full request URL for debugging
  console.log('Making request to:', config.baseURL + config.url);
  
  return config;
});

// User API methods
export const userAPI = {
  getAllUsers: () => api.get('/users'),
  getActiveUsers: () => api.get('/users/active'),
  getAssignableUsers: () => api.get('/users/assignable'), // New endpoint
  createUser: (userData) => api.post('/users', userData),
  updateUser: (id, userData) => api.put(`/users/${id}`, userData),
  deleteUser: (id) => api.delete(`/users/${id}`),
  toggleUserStatus: (id) => api.patch(`/users/${id}/toggle-status`),
};

// First Face Assignment API methods
export const firstFaceAPI = {
  getAssignments: () => api.get('/first-face-assignments'),
  createAssignment: (assignmentData) => api.post('/first-face-assignments', assignmentData),
  updateAssignment: (id, assignmentData) => api.put(`/first-face-assignments/${id}`, assignmentData),
  deleteAssignment: (id) => api.delete(`/first-face-assignments/${id}`),
  toggleAssignment: (id) => api.patch(`/first-face-assignments/${id}/toggle`),
};

// Problem API methods
export const problemAPI = {
  getAllProblems: () => api.get('/problems'),
  getProblem: (id) => api.get(`/problems/${id}`),
  createProblem: (problemData) => api.post('/problems', problemData),
  updateProblem: (id, problemData) => api.put(`/problems/${id}`, problemData),
  deleteProblem: (id) => api.delete(`/problems/${id}`),
  getProblemsByStatus: (status) => api.get(`/problems/status/${status}`),
  getProblemsByDepartment: (department) => api.get(`/problems/department/${department}`),
  getProblemsByAssignmentType: (type) => api.get(`/problems/assignment-type/${type}`),
  getAssignedProblems: (userId) => api.get(`/problems/assigned-to/${userId}`),
  getUnassignedProblems: () => api.get('/problems/unassigned/all'),
  manualAssignProblem: (id, assignmentData) => api.post(`/problems/${id}/assign`, assignmentData),
  updateProblemStatus: (id, statusData) => api.patch(`/problems/${id}/status`, statusData),
  getStatistics: () => api.get('/problems/statistics/summary'),
};

// Auth API methods
export const authAPI = {
  login: (credentials) => api.post('/login', credentials),
  logout: () => api.post('/logout'),
  getUser: () => api.get('/user'),
};

// Dashboard API methods
export const dashboardAPI = {
  getDepartments: () => api.get('/departments'),
  getStats: () => api.get('/dashboard/stats'),
};

export default api;