// navbar.js - Reusable Navbar Component
class CustomNavbar extends HTMLElement {
  connectedCallback() {
    this.innerHTML = `
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <style>
        @font-face {
            font-family: 'Mosvita';
            src: url('./font/mosvita.otf') format('opentype');
            font-weight: normal;
            font-style: normal;
            font-display: swap; /* Optional: improves performance */
        }
        .nav-item {
          transition: all 0.3s ease;
        }
        .nav-item:hover {
          background-color: rgba(255, 255, 255, 0.1);
        }
        .nav-item.active {
          background-color: rgba(255, 255, 255, 0.2);
        }
      </style>
      
      <div class="sidebar flex flex-col h-screen w-64 bg-gray-900 text-white fixed left-0 top-0 shadow-lg">
        <div class="p-4 border-b border-gray-700">
          <h1 class="text-xl font-bold flex items-center">
            <i class="fas fa-chart-line mr-2"></i>
            <span>ClickNCart</span>
          </h1>
        </div>
        
        <nav class="flex-1 p-2">
          <button id="dashboardBtn" class="nav-item w-full flex items-center p-3 rounded-md mb-1">
            <i class="fas fa-home mr-3"></i>
            <span>Dashboard</span>
          </button>
          
          <button id="tradersBtn" class="nav-item w-full flex items-center p-3 rounded-md mb-1">
            <i class="fas fa-users mr-3"></i>
            <span>Traders</span>
          </button>
          
          <button id="usersBtn" class="nav-item w-full flex items-center p-3 rounded-md mb-1">
            <i class="fas fa-user-cog mr-3"></i>
            <span>Users</span>
          </button>

          <button id="profileBtn" class="nav-item w-full flex items-center p-3 rounded-md mb-1">
            <i class="fas fa-user mr-3"></i>
            <span>Profile</span>
          </button>
        </nav>
        
        <div class="p-4 border-t border-gray-700">
          <button id="logoutBtn" class="nav-item w-full flex items-center p-3 rounded-md text-red-400 hover:text-red-300">
            <i class="fas fa-sign-out-alt mr-3"></i>
            <span>Logout</span>
          </button>
        </div>
      </div>
    `;
  }
}

customElements.define('custom-navbar', CustomNavbar);

// Add event listeners after component is loaded
document.addEventListener('DOMContentLoaded', function() {
  // This ensures the navbar is fully loaded before adding event listeners
  setTimeout(() => {
    // Profile button click handler
    const profileBtn = document.getElementById('profileBtn');
    if (profileBtn) {
      profileBtn.addEventListener('click', () => {
        // Set active state
        document.querySelectorAll('.nav-item').forEach(item => {
          item.classList.remove('active');
        });
        profileBtn.classList.add('active');
        
        // Navigate to profile page
        window.location.href = '/profile';
      });
    }

    // Other navigation buttons (keep existing functionality)
    document.getElementById('dashboardBtn')?.addEventListener('click', () => {
      document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
      });
      document.getElementById('dashboardBtn').classList.add('active');
      window.location.href = '/dashboard';
    });

    document.getElementById('tradersBtn')?.addEventListener('click', () => {
      document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
      });
      document.getElementById('tradersBtn').classList.add('active');
      window.location.href = '/traders';
    });

    document.getElementById('usersBtn')?.addEventListener('click', () => {
      document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
      });
      document.getElementById('usersBtn').classList.add('active');
      window.location.href = '/users';
    });

    // Set active nav item based on current page
    const currentPath = window.location.pathname;
    const activeButton = {
      '/dashboard': 'dashboardBtn',
      '/traders': 'tradersBtn',
      '/users': 'usersBtn',
      '/profile': 'profileBtn'
    }[currentPath];

    if (activeButton) {
      document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
      });
      document.getElementById(activeButton)?.classList.add('active');
    }
  }, 100);
});