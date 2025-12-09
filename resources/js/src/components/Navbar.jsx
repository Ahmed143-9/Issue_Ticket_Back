import React from 'react';
import { useAuth } from '../context/AuthContext';

export default function Navbar() {
  const { user } = useAuth();

  return (
    <nav className="navbar navbar-expand-lg navbar-dark bg-primary">
      <div className="container">
        <a className="navbar-brand" href="/">Issue Ticket System</a>
        
        <div className="navbar-nav ms-auto">
          {user ? (
            <>
              <span className="navbar-text me-3">
                Welcome, {user.name} ({user.role})
              </span>
              <a className="nav-link" href="/logout">Logout</a>
            </>
          ) : (
            <a className="nav-link" href="/login">Login</a>
          )}
        </div>
      </div>
    </nav>
  );
}