import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import FirstFaceAssignment from './components/FirstFaceAssignment';
import UserManagement from './components/UserManagement';

function App() {
  const [user, setUser] = useState(null);
  
  useEffect(() => {
    // Check if user data exists in localStorage
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      setUser(JSON.parse(storedUser));
    }
  }, []);

  return (
    <Router>
      <div className="App">
        <Routes>
          <Route path="/" element={<UserManagement />} />
          <Route path="/first-face-assignments" element={<FirstFaceAssignment />} />
          <Route path="/user-management" element={<UserManagement />} />
        </Routes>
      </div>
    </Router>
  );
}

export default App;