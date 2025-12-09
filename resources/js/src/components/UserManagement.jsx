import React, { useState, useEffect } from 'react';
import { toast } from 'react-toastify';
import { userAPI, firstFaceAPI } from '../utils/api';
import { loadUsers, isHiddenUser } from '../utils/userLoader';
import { FaTrash, FaPlus, FaInfoCircle, FaSync, FaUsers, FaEdit, FaEye } from 'react-icons/fa';

export default function UserManagement() {
  const [users, setUsers] = useState([]);
  const [activeUsers, setActiveUsers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [assignments, setAssignments] = useState([]);
  const [newAssignment, setNewAssignment] = useState({
    department: '',
    user_id: '',
    type: 'specific'
  });
  const [teamMembers, setTeamMembers] = useState([]);
  const [assignmentLoading, setAssignmentLoading] = useState(false);
  const [assignmentSyncing, setAssignmentSyncing] = useState(false);

  // Load users and assignments when component mounts
  useEffect(() => {
    loadAllUsers();
    loadAssignments();
    loadTeamMembers();
  }, []);

  const loadAllUsers = async () => {
    setLoading(true);
    try {
      const loadedUsers = await loadUsers(setUsers, setActiveUsers, null);
      console.log('✅ Users loaded:', loadedUsers.length);
    } catch (error) {
      console.error('❌ Error loading users:', error);
      toast.error('Failed to load users');
    } finally {
      setLoading(false);
    }
  };

  const loadAssignments = async () => {
    setAssignmentLoading(true);
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
      setAssignmentLoading(false);
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

    setAssignmentLoading(true);
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
      setAssignmentLoading(false);
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
    setAssignmentSyncing(true);
    try {
      await loadAssignments();
      toast.success('Assignments synced with server!');
    } catch (error) {
      // Even if API fails, we still have localStorage data
      toast.info('Using local data. API sync failed.');
    } finally {
      setAssignmentSyncing(false);
    }
  };

  return (
    <div className="container-fluid mt-4">
      <div className="row">
        {/* User List Section */}
        <div className="col-lg-8">
          <div className="card shadow-sm">
            <div className="card-header bg-primary text-white d-flex justify-content-between align-items-center">
              <h4 className="mb-0">
                <FaUsers className="me-2" />
                User Management
              </h4>
              <button 
                className="btn btn-light btn-sm"
                onClick={loadAllUsers}
                disabled={loading}
              >
                <FaSync className={`me-1 ${loading ? 'fa-spin' : ''}`} />
                {loading ? 'Loading...' : 'Refresh'}
              </button>
            </div>
            
            <div className="card-body">
              {loading ? (
                <div className="text-center">
                  <div className="spinner-border text-primary" role="status">
                    <span className="visually-hidden">Loading...</span>
                  </div>
                </div>
              ) : users.length === 0 ? (
                <div className="text-center py-5">
                  <FaInfoCircle className="text-muted mb-3" size={48} />
                  <h5>No users found</h5>
                  <p className="text-muted">Try refreshing the user list.</p>
                </div>
              ) : (
                <div className="table-responsive">
                  <table className="table table-striped table-hover">
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {users.map(user => (
                        <tr key={user.id}>
                          <td>
                            <strong>{user.name}</strong>
                            {user.username && <><br /><small className="text-muted">@{user.username}</small></>}
                          </td>
                          <td>{user.email}</td>
                          <td>
                            <span className="badge bg-info">{user.role}</span>
                          </td>
                          <td>{user.department}</td>
                          <td>
                            <span className={`badge ${user.status === 'active' ? 'bg-success' : 'bg-secondary'}`}>
                              {user.status}
                            </span>
                          </td>
                          <td>
                            <div className="btn-group" role="group">
                              <button className="btn btn-outline-primary btn-sm" title="View Details">
                                <FaEye />
                              </button>
                              <button className="btn btn-outline-secondary btn-sm" title="Edit User">
                                <FaEdit />
                              </button>
                            </div>
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

        {/* First Face Assignment Section */}
        <div className="col-lg-4">
          <div className="card shadow-sm">
            <div className="card-header bg-success text-white d-flex justify-content-between align-items-center">
              <h4 className="mb-0">
                <FaUsers className="me-2" />
                First Face Assignments
              </h4>
              <button 
                className="btn btn-light btn-sm"
                onClick={syncWithLaravel}
                disabled={assignmentSyncing}
              >
                <FaSync className={`me-1 ${assignmentSyncing ? 'fa-spin' : ''}`} />
                {assignmentSyncing ? 'Syncing...' : 'Sync'}
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
                  <div className="mb-3">
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
                  
                  <div className="mb-3">
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
                  
                  <div className="mb-3">
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
                  
                  <button 
                    className="btn btn-success w-100"
                    onClick={handleAddAssignment}
                    disabled={assignmentLoading || !newAssignment.department || !newAssignment.user_id}
                  >
                    <FaPlus className="me-1" />
                    {assignmentLoading ? 'Adding...' : 'Add Assignment'}
                  </button>
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
                  {assignmentLoading ? (
                    <div className="text-center">
                      <div className="spinner-border text-primary" role="status">
                        <span className="visually-hidden">Loading...</span>
                      </div>
                    </div>
                  ) : assignments.length === 0 ? (
                    <div className="text-center py-3">
                      <FaInfoCircle className="text-muted mb-2" size={32} />
                      <small className="text-muted">No assignments found</small>
                    </div>
                  ) : (
                    <div className="list-group">
                      {assignments.map(assignment => (
                        <div key={assignment.id} className="list-group-item">
                          <div className="d-flex justify-content-between align-items-start">
                            <div>
                              <strong>{assignment.user?.name || assignment.userName}</strong>
                              <br />
                              <small className="text-muted">{assignment.department}</small>
                            </div>
                            <div className="d-flex align-items-center">
                              <span className={`badge me-2 ${assignment.type === 'all' ? 'bg-info' : 'bg-warning'}`}>
                                {assignment.type === 'all' ? 'All' : 'Specific'}
                              </span>
                              <button
                                className="btn btn-danger btn-sm"
                                onClick={() => handleRemoveAssignment(assignment.id)}
                                disabled={assignmentLoading}
                              >
                                <FaTrash size={12} />
                              </button>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}