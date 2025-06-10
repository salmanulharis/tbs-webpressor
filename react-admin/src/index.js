import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
  // Find all React root elements
  const reactRootElements = document.querySelectorAll('.tbswebpressor-root');
  
  // Initialize React on each container found
  reactRootElements.forEach((container, index) => {
    // Get any data attributes passed from WordPress
    const wpData = window.tbswData || {};
    // const wpData = container.dataset.wpData ? JSON.parse(container.dataset.wpData) : {};
    
    const root = createRoot(container);
    root.render(
      <React.StrictMode>
        <App wpData={wpData} />
      </React.StrictMode>
    );
  });
});