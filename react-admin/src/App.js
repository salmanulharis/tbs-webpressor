import React, { useEffect, useRef, useState } from 'react';
import './App.css';

function App({ wpData = {} }) {
  const [count, setCount] = useState(0);
  const [pendingCount, setPendingCount] = useState(0);
  const [isConverting, setIsConverting] = useState(false);
  const stopConversion = useRef(true);
  const [progress, setProgress] = useState(0);
  
  // Calculate completed items and progress percentage
  const completedCount = Math.max(0, count - pendingCount);
  const progressPercentage = count > 0 ? Math.round((completedCount / count) * 100) : 0;

  const startConverter = () => {
    setIsConverting(true);
    stopConversion.current = false;
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
        
        // Check if we need to continue processing more pages
        if (data.hasMorePages && !stopConversion.current) {
          setTimeout(() => startConversion(page + 1), 1000);
        } else {
          setIsConverting(false);
        }
        await fetchMediaCount();
        await fetchPendingMediaCount();
      } catch (error) {
        // console.error('Error during conversion:', error);
        setIsConverting(false);
      }
    };

    startConversion();
  };

  const resetConvertedMedia = () => {
    // Show confirmation dialog before resetting
    if (!window.confirm('Are you sure you want to reset all converted media? This action cannot be undone.')) {
      return;
    }

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
        fetchMediaCount(); // Refresh the media count after reset
        fetchPendingMediaCount(); // Refresh the pending media count after reset
      } catch (error) {
        // console.error('Error during reset conversion:', error);
      }
    };

    resetConversion();
  };

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
      setCount(data.count);
      updateProgress(data.count);
    } catch (error) {
      // console.error('Error fetching media count:', error);
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
      setPendingCount(data.count);
      updateProgress();
    } catch (error) {
      // console.error('Error fetching pending media count:', error);
    }
  };

  // Update progress calculation
  const updateProgress = () => {
    if (count > 0) {
      const completed = Math.max(0, count - pendingCount);
      setProgress(Math.round((completed / count) * 100));
    } else {
      setProgress(0);
    }
  };

  useEffect(() => {
    fetchMediaCount();
    fetchPendingMediaCount();
  }, [wpData.ajax_url, wpData.nonce]);
  
  return (
    <div className="wrap tbsw-dashboard">
      <h1>WebPressor - WebP Image Converter</h1>
      
      <div className="tbsw-card">
        <div className="tbsw-progress-container">
          <div className="tbsw-progress-header">
            <h2>Conversion Progress</h2>
            <span className="tbsw-progress-percentage">{progressPercentage}%</span>
          </div>
          
          <div className="tbsw-progress-bar">
            <div 
              className="tbsw-progress-fill" 
              style={{ width: `${progressPercentage}%` }}
              aria-valuenow={progressPercentage}
              aria-valuemin="0"
              aria-valuemax="100"
            ></div>
          </div>
          
          <div className="tbsw-stats">
            <div className="tbsw-stat-item">
              <span className="dashicons dashicons-images-alt"></span>
              <span className="tbsw-stat-label">Total Images:</span>
              <span className="tbsw-stat-value">{count}</span>
            </div>
            
            <div className="tbsw-stat-item">
              <span className="dashicons dashicons-yes-alt"></span>
              <span className="tbsw-stat-label">Converted:</span>
              <span className="tbsw-stat-value">{completedCount}</span>
            </div>
            
            <div className="tbsw-stat-item">
              <span className="dashicons dashicons-clock"></span>
              <span className="tbsw-stat-label">Pending:</span>
              <span className="tbsw-stat-value">{pendingCount}</span>
            </div>
          </div>
        </div>
        
        <div className="tbsw-button-container">
          <button 
            className={`button button-primary${isConverting ? ' tbsw-disabled' : ''}`}
            onClick={startConverter}
            disabled={isConverting}
          >
            {isConverting ? (
              <>
                <span className="tbsw-spinner"></span>
                Converting...
              </>
            ) : 'Start Conversion'}
          </button>
          
          <button 
            className="button button-secondary" 
            onClick={resetConvertedMedia}
            disabled={isConverting || completedCount === 0}
          >
            Reset Conversions
          </button>

          <button 
            className="button button-secondary"
            onClick={() => {
              if (window.confirm('Are you sure you want to stop the conversion process?')) {
                stopConversion.current = true;
                setIsConverting(false);
              }
            }}
            disabled={!isConverting}
          >
            Stop Conversion
          </button>
        </div>
      </div>
      
      {isConverting && (
        <div className="tbsw-notice notice-info">
          <p>Conversion in progress. Please do not close this page until completion.</p>
        </div>
      )}
    </div>
  );
}

export default App;