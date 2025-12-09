import './bootstrap';
import React from 'react';
import ReactDOM from 'react-dom/client';
import { AuthProvider } from './src/context/AuthContext';
import App from './src/App';

const root = ReactDOM.createRoot(document.getElementById('app'));
root.render(
  <AuthProvider>
    <App />
  </AuthProvider>
);