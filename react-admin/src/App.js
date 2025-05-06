import React, { useState } from 'react';
import './App.css';

function App({ wpData = {} }) {
  const [count, setCount] = useState(0);

  const startConverter = () => {
    // ajax call to the server to start the conversion
    const startConversion = async (page = 1) => {
      try {
        const response = await fetch(wpData.ajax_url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'tbsw_start_conversion',
            nonce: wpData.nonce,
            page: page
          })
        });

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        const data = await response.json();
        console.log('Conversion progress:', data);
        
        // Check if we need to continue processing more pages
        if (data.hasMorePages) {
          // Continue with next page
          setTimeout(() => startConversion(page + 1), 1000); // Add delay between requests
        } else {
          console.log('Conversion completed!');
          // Handle completion
        }
      } catch (error) {
        console.error('Error during conversion:', error);
        // Handle error
      }
    };

    startConversion();
  }
  
  return (
    <div className="tbsw-dasboard-container">
      <button onClick={startConverter}>
        Convert Now
      </button>
    </div>
  );
}

export default App;