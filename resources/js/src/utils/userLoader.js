import { userAPI } from './api';
import { toast } from 'react-toastify';

// Helper function to determine if a user should be hidden
export const isHiddenUser = (user) => {
  // Hide super admin or any user with specific criteria
  return user.email === 'admin@example.com';
};

// Load users from API
export const loadUsers = async (setUsers, setActiveUsers, token) => {
  try {
    // Use our existing API utility which has the correct baseURL
    const response = await userAPI.getAllUsers();
    
    const data = response.data;

    if (data.success) {
      // FILTER OUT HIDDEN USERS (Super Admin)
      const filteredUsers = data.users.filter(user => !isHiddenUser(user));
      setUsers(filteredUsers);
      console.log('✅ Users loaded successfully (filtered):', filteredUsers.length);
      
      // Also set active users for First Face assignment
      const activeUsersList = filteredUsers.filter(u => u.status === 'active');
      setActiveUsers(activeUsersList);
      
      // Save users to localStorage for name resolution
      localStorage.setItem('system_users', JSON.stringify(data.users));
      
      return filteredUsers;
    } else {
      toast.error(data.error || 'Failed to load users');
      return [];
    }
  } catch (error) {
    console.error('Failed to load users:', error);
    toast.error('Network error while loading users');
    return [];
  }
};