import React, { useState, useEffect } from 'react';
import { toast } from 'react-toastify';
import { useAuth } from '../context/AuthContext';
import Navbar from './Navbar';
import { FaTrash, FaPlus, FaInfoCircle, FaExclamationTriangle, FaSync, FaUsers } from 'react-icons/fa';
import { firstFaceAPI, userAPI } from '../utils/api';
import { loadUsers, isHiddenUser } from '../utils/userLoader';

export default function FirstFaceAssignment() {
  const { user } = useAuth();
  const [assignments, setAssignments] = useState([]);
  const [newAssignment, setNewAssignment] = useState({
    department: '',
    user_id: '',
    type: 'specific'
  });
  const [teamMembers, setTeamMembers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [syncing, setSyncing] = useState(false);

  // Load existing assignments and team members
  useEffect(() => {
    loadAssignments();
    loadTeamMembers();
  }, []);

  const loadAssignments = async () => {
    setLoading(true);
    try {
      const result = await firstFaceAPI.getAssignments();
      setAssignments(result.data.firstFaceAssignments || result.data);
      // Also save to localStorage for auto-assignment logic
      localStorage.setItem('firstFace_assignments', JSON.stringify(result.data.firstFaceAssignments || result.data));
      console.log('Assignments loaded:', result.data.firstFaceAssignments || result.data);
    } catch (error) {
      console.error('❌ Error loading assignments:', error);
      // Fallback to localStorage
      try {
        const savedAssignments = JSON.parse(localStorage.getItem('firstFace_assignments') || '[]');
        setAssignments(savedAssignments);
        toast.info('Using local assignments data');
      } catch (localError) {
        console.error('❌ Local storage error:', localError);
        toast.error('Failed to load assignments');
      }
    } finally {
      setLoading(false);
    }
  };

  const loadTeamMembers = async () => {
    try {
      // Use our new utility function to load users
      const users = await loadUsers(setTeamMembers, () => {}, null);
      if (users.length > 0) {
        // Filter for assignable users (all except hidden users)
        const assignableUsers = users.filter(user => !isHiddenUser(user));
        setTeamMembers(assignableUsers);
        localStorage.setItem('system_users', JSON.stringify(assignableUsers));
      }
      return users;
    } catch (error) {
      console.error('❌ Error loading team members:', error);
      // Fallback to localStorage
      try {
        const users = JSON.parse(localStorage.getItem('system_users') || '[]');
        // For backward compatibility, we still filter for active if needed
        // But now we show all assignable users
        setTeamMembers(users);
        return users;
      } catch (localError) {
        console.error('❌ Local storage error:', localError);
        toast.error('Failed to load team members');
        return [];
      }
    }
  };

  const handleAddAssignment = async () => {
    if (!newAssignment.department || !newAssignment.user_id) {
      toast.error('Please fill all fields');
      return;
    }

    setLoading(true);
    try {
      // Make sure we have fresh user data
      const users = await loadTeamMembers();
      
      // Verify the selected user exists (convert to same type for comparison)
      const userToAssign = users.find(u => String(u.id) === String(newAssignment.user_id));
      if (!userToAssign) {
        throw new Error('Selected user not found');
      }

      const result = await firstFaceAPI.createAssignment(newAssignment);
      toast.success(`First Face assignment added for ${newAssignment.department}!`);
      await loadAssignments(); // Refresh the list
      setNewAssignment({ department: '', user_id: '', type: 'specific' });
    } catch (error) {
      console.error('❌ Error creating assignment:', error);
      // Fallback to localStorage
      try {
        // Find the selected user (convert to same type for comparison)
        const users = JSON.parse(localStorage.getItem('system_users') || '[]');
        const userToAssign = users.find(u => String(u.id) === String(newAssignment.user_id));
        
        if (!userToAssign) {
          throw new Error('Selected user not found');
        }

        // Create assignment object
        const assignment = {
          id: Date.now(), // Simple ID generation
          userId: newAssignment.user_id,
          userName: userToAssign.name,
          department: newAssignment.department,
          type: newAssignment.type,
          assignedAt: new Date().toISOString(),
          assignedBy: 'System (Local)',
          isActive: true
        };

        // Get existing assignments from localStorage
        const existingAssignments = JSON.parse(localStorage.getItem('firstFace_assignments') || '[]');
        
        // Add new assignment
        const updatedAssignments = [...existingAssignments, assignment];
        
        // Save to localStorage
        localStorage.setItem('firstFace_assignments', JSON.stringify(updatedAssignments));
        
        // Update state
        setAssignments(updatedAssignments);
        
        // Reset form
        setNewAssignment({ department: '', user_id: '', type: 'specific' });
        
        toast.success(`First Face assignment added for ${newAssignment.department}!`);
      } catch (localError) {
        console.error('❌ Local storage error:', localError);
        toast.error(localError.message || 'Failed to create assignment');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleRemoveAssignment = async (assignmentId) => {
    if (!window.confirm('Are you sure you want to remove this assignment?')) {
      return;
    }

    try {
      await firstFaceAPI.deleteAssignment(assignmentId);
      toast.success('First Face assignment removed!');
      await loadAssignments(); // Refresh the list
    } catch (error) {
      console.error('❌ Error removing assignment:', error);
      // Fallback to localStorage
      try {
        // Get existing assignments from localStorage
        const existingAssignments = JSON.parse(localStorage.getItem('firstFace_assignments') || '[]');
        
        // Filter out the assignment to remove
        const updatedAssignments = existingAssignments.filter(assignment => assignment.id !== assignmentId);
        
        // Save to localStorage
        localStorage.setItem('firstFace_assignments', JSON.stringify(updatedAssignments));
        
        // Update state
        setAssignments(updatedAssignments);
        
        toast.success('First Face assignment removed!');
      } catch (localError) {
        console.error('❌ Local storage error:', localError);
        toast.error(localError.message || 'Failed to remove assignment');
      }
    }
  };

  const syncWithLaravel = async () => {
    setSyncing(true);
    try {
      await loadAssignments();
      toast.success('Assignments synced with server!');
    } catch (error) {
      // Even if API fails, we still have localStorage data
      toast.info('Using local data. API sync failed.');
    } finally {
      setSyncing(false);
    }
  };

  // For testing purposes, let's show all users if no user is authenticated
  if (!user && !localStorage.getItem('system_users')) {
    return (
      <div>
        <Navbar />
        <div className="container mt-4">
          <div className="alert alert-warning">
            <h4>User Data Not Available</h4>
            <p>Please log in to view user data. For testing purposes, you can manually add users to localStorage.</p>
            <button className="btn btn-primary" onClick={() => {
              // Simulate adding sample users to localStorage
              const sampleUsers = [
                { id: 1, name: 'John Smith', email: 'john@example.com', role: 'admin', department: 'IT', status: 'active' },
                { id: 2, name: 'Jane Doe', email: 'jane@example.com', role: 'user', department: 'HR', status: 'active' },
                { id: 3, name: 'Bob Johnson', email: 'bob@example.com', role: 'team_leader', department: 'Finance', status: 'inactive' }
              ];
              localStorage.setItem('system_users', JSON.stringify(sampleUsers));
              window.location.reload();
            }}>
              Load Sample Users
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div>
      <Navbar />
      <div className="container mt-4">
        <div className="card shadow-sm">
          <div className="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
              <h4 className="mb-1">
                <FaUsers className="me-2" />
                First Face Assignments
              </h4>
              <small>Assign users who will automatically get problems from specific departments</small>
            </div>
            <button 
              className="btn btn-light btn-sm"
              onClick={syncWithLaravel}
              disabled={syncing}
            >
              <FaSync className={`me-1 ${syncing ? 'fa-spin' : ''}`} />
              {syncing ? 'Syncing...' : 'Sync'}
            </button>
          </div>
          
          <div className="card-body">
            {/* Add New Assignment Form */}
            <div className="card mb-4">
              <div className="card-header">
                <h5 className="mb-0">
                  <FaPlus className="me-2" />
                  Add New Assignment
                </h5>
              </div>
              <div className="card-body">
                <div className="row">
                  <div className="col-md-4">
                    <label className="form-label">Department</label>
                    <select
                      className="form-select"
                      value={newAssignment.department}
                      onChange={(e) => setNewAssignment({...newAssignment, department: e.target.value})}
                    >
                      <option value="">Select Department</option>
                      <option value="Enterprise Business Solutions">Enterprise Business Solutions</option>
                      <option value="Board Management">Board Management</option>
                      <option value="Support Stuff">Support Stuff</option>
                      <option value="Administration and Human Resources">Administration and Human Resources</option>
                      <option value="Finance and Accounts">Finance and Accounts</option>
                      <option value="Business Dev and Operations">Business Dev and Operations</option>
                      <option value="Implementation and Support">Implementation and Support</option>
                      <option value="Technical and Networking Department">Technical and Networking Department</option>
                      <option value="all">All Departments</option>
                    </select>
                  </div>
                  
                  <div className="col-md-4">
                    <label className="form-label">Assign to User</label>
                    <select
                      className="form-select"
                      value={newAssignment.user_id}
                      onChange={(e) => setNewAssignment({...newAssignment, user_id: e.target.value})}
                      disabled={teamMembers.length === 0}
                    >
                      <option value="">{teamMembers.length === 0 ? 'Loading users...' : 'Select User'}</option>
                      {teamMembers.map(member => (
                        <option key={member.id} value={String(member.id)}>
                          {member.name} ({member.department}) - {member.role} ({member.status})
                        </option>
                      ))}
                    </select>
                  </div>
                  
                  <div className="col-md-4">
                    <label className="form-label">Type</label>
                    <select
                      className="form-select"
                      value={newAssignment.type}
                      onChange={(e) => setNewAssignment({...newAssignment, type: e.target.value})}
                    >
                      <option value="specific">Department Specific</option>
                      <option value="all">All Departments</option>
                    </select>
                  </div>
                </div>
                
                <div className="mt-3">
                  <button 
                    className="btn btn-success"
                    onClick={handleAddAssignment}
                    disabled={loading || !newAssignment.department || !newAssignment.user_id}
                  >
                    <FaPlus className="me-1" />
                    {loading ? 'Adding...' : 'Add Assignment'}
                  </button>
                </div>
              </div>
            </div>

            {/* Current Assignments */}
            <div className="card">
              <div className="card-header d-flex justify-content-between align-items-center">
                <h5 className="mb-0">
                  <FaUsers className="me-2" />
                  Current Assignments
                </h5>
                <span className="badge bg-secondary">
                  {assignments.length} Total
                </span>
              </div>
              <div className="card-body">
                {loading ? (
                  <div className="text-center">
                    <div className="spinner-border text-primary" role="status">
                      <span className="visually-hidden">Loading...</span>
                    </div>
                  </div>
                ) : assignments.length === 0 ? (
                  <div className="text-center py-5">
                    <FaInfoCircle className="text-muted mb-3" size={48} />
                    <h5>No assignments found</h5>
                    <p className="text-muted">Create your first assignment using the form above.</p>
                  </div>
                ) : (
                  <div className="table-responsive">
                    <table className="table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>User</th>
                          <th>Department</th>
                          <th>Type</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {assignments.map(assignment => (
                          <tr key={assignment.id}>
                            <td>
                              <strong>{assignment.user?.name || assignment.userName}</strong>
                              <br />
                              <small className="text-muted">{assignment.user?.email || 'N/A'}</small>
                            </td>
                            <td>{assignment.department}</td>
                            <td>
                              <span className="badge bg-info">
                                {assignment.type === 'all' ? 'All Departments' : 'Specific'}
                              </span>
                            </td>
                            <td>
                              <span className={`badge ${assignment.is_active ? 'bg-success' : 'bg-secondary'}`}>
                                {assignment.is_active ? 'Active' : 'Inactive'}
                              </span>
                            </td>
                            <td>
                              <button
                                className="btn btn-danger btn-sm"
                                onClick={() => handleRemoveAssignment(assignment.id)}
                                disabled={loading}
                              >
                                <FaTrash />
                              </button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}