import React, { useEffect, useState } from 'react';
import './App.css';

function App({ wpData = {} }) {
  const [count, setCount] = useState(0);
  const [pendingCount, setPendingCount] = useState(0);

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
          fetchMediaCount(); // Refresh the media count after conversion
          fetchPendingMediaCount(); // Refresh the pending media count after conversion
          // Handle completion
        }
      } catch (error) {
        console.error('Error during conversion:', error);
        // Handle error
      }
    };

    startConversion();
  }

  const resetConvertedMedia = () => {
    // ajax call to the server to reset the converted media
    const resetConversion = async () => {
      try {
        const response = await fetch(wpData.ajax_url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'tbsw_reset_conversion',
            nonce: wpData.nonce,
          })
        });

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        const data = await response.json();
        console.log('Reset conversion:', data);
        fetchMediaCount(); // Refresh the media count after reset
        fetchPendingMediaCount(); // Refresh the pending media count after reset
      } catch (error) {
        console.error('Error during reset conversion:', error);
      }
    };

    resetConversion();
  }

  // Fetch the total media count from the server
  const fetchMediaCount = async () => {
    try {
      const response = await fetch(wpData.ajax_url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'tbsw_get_media_count',
          nonce: wpData.nonce,
        })
      });

      if (!response.ok) {
        throw new Error('Network response was not ok');
      }

      const result = await response.json();
      const data = result.data;
      console.log('Total media count:', data.count);
      setCount(data.count);
    } catch (error) {
      console.error('Error fetching media count:', error);
    }
  };

  // Fetch the pending media count from the server
  const fetchPendingMediaCount = async () => {
    try {
      const response = await fetch(wpData.ajax_url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'tbsw_get_pending_media_count',
          nonce: wpData.nonce,
        })
      });

      if (!response.ok) {
        throw new Error('Network response was not ok');
      }

      const result = await response.json();
      const data = result.data;
      console.log('Pending media count:', data.count);
      setPendingCount(data.count);
    } catch (error) {
      console.error('Error fetching pending media count:', error);
    }
  };

  useEffect(() => {
    fetchMediaCount();
    fetchPendingMediaCount();
  }
  , [wpData.ajax_url, wpData.nonce]);
  
  return (
    <div className="tbsw-dasboard-container">
      <div className="tbsw-total-media-count">
        <h2>Total Media Count</h2>
        <p>{count}</p>
      </div>
      <div className="tbsw-pending-media-count">
        <h2>Pending Media Count</h2>
        <p>{pendingCount}</p>
      </div>
      <div className='tbsw-button-container'>
        <button onClick={startConverter}>
          Start Conversion
        </button>
        <button onClick={resetConvertedMedia}>
          Reset
        </button>
      </div>

    </div>
  );
}

export default App;