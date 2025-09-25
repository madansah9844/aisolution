/**
 * AI-Solution Website Analytics
 * Tracks page visits, events, and user behavior
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Track page view
    trackPageView();

    // Track clicks on CTA buttons
    trackCTAClicks();

    // Track form submissions
    trackFormSubmissions();

    // Track time spent on page
    trackTimeSpent();
});

/**
 * Function to track page views
 */
function trackPageView() {
    // Get current page information
    const pageData = {
        page: window.location.pathname,
        referrer: document.referrer || 'direct',
        timestamp: new Date().toISOString()
    };

    // Send data to backend
    sendAnalyticsData('pageview', pageData);
}

/**
 * Function to track CTA button clicks
 */
function trackCTAClicks() {
    // Find all CTA buttons (primary buttons and links with specific classes)
    const ctaButtons = document.querySelectorAll('.btn-primary, .cta-btn, .btn-get-started');

    // Add click event listener to each button
    ctaButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            // Get button data
            const buttonData = {
                text: button.innerText,
                page: window.location.pathname,
                buttonType: button.classList.toString(),
                timestamp: new Date().toISOString()
            };

            // Send data to backend
            sendAnalyticsData('button_click', buttonData);
        });
    });
}

/**
 * Function to track form submissions
 */
function trackFormSubmissions() {
    // Find all forms
    const forms = document.querySelectorAll('form');

    // Add submit event listener to each form
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            // Get form data
            const formData = {
                formId: form.id || 'unknown',
                formAction: form.action,
                page: window.location.pathname,
                timestamp: new Date().toISOString()
            };

            // Send data to backend
            sendAnalyticsData('form_submit', formData);
        });
    });
}

/**
 * Function to track time spent on page
 */
function trackTimeSpent() {
    // Record page load time
    const startTime = new Date();

    // Track when user leaves the page
    window.addEventListener('beforeunload', function() {
        // Calculate time spent
        const endTime = new Date();
        const timeSpentMs = endTime - startTime;
        const timeSpentSec = Math.floor(timeSpentMs / 1000);

        // Get time data
        const timeData = {
            page: window.location.pathname,
            timeSpentSeconds: timeSpentSec,
            timestamp: endTime.toISOString()
        };

        // Use navigator.sendBeacon to ensure data is sent even when page is closing
        if (navigator.sendBeacon) {
            const blob = new Blob([JSON.stringify({
                event: 'time_spent',
                data: timeData
            })], { type: 'application/json' });

            navigator.sendBeacon('track_analytics.php', blob);
        } else {
            // Fallback to synchronous AJAX
            sendAnalyticsData('time_spent', timeData, true);
        }
    });
}

/**
 * Function to send analytics data to the backend
 */
function sendAnalyticsData(eventType, data, sync = false) {
    // Add user agent data
    data.userAgent = navigator.userAgent;

    // Add screen size
    data.screenWidth = window.innerWidth;
    data.screenHeight = window.innerHeight;

    // Create the payload
    const payload = {
        event: eventType,
        data: data
    };

    // Use Fetch API to send data to the server
    fetch('track_analytics.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
        // If sync is true, make request synchronous (for beforeunload event)
        keepalive: sync
    })
    .catch(function(error) {
        console.error('Analytics tracking error:', error);
    });
}
