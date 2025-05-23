// navbar.js - Reusable Navbar Component
class CustomNavbar extends HTMLElement {
    connectedCallback() {
        this.innerHTML = `
      <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <style>
        @font-face {
          font-family: 'Mosvita';
          src: url('./font/mosvita.otf') format('opentype');
        }
        .nav-item {
          transition: all 0.3s ease;
        }
        .nav-item:hover {
          background-color: rgba(255,255,255,0.1);
        }
        .nav-item.active {
          background-color: rgba(255,255,255,0.2);
        }
        .user-info {
          padding: 1rem;
          border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .user-info img {
          width: 40px;
          height: 40px;
          border-radius: 50%;
          object-fit: cover;
        }
      </style>
      
      <div class="sidebar flex flex-col h-screen w-64 bg-gray-900 text-white fixed top-0 left-0 shadow-lg">
        <div class="p-4 border-b border-gray-700">
          <h1 class="text-xl font-bold flex items-center">
            <i class="fas fa-chart-line mr-2"></i>
            <span>ClickNCart</span>
          </h1>
        </div>
        <div id="userInfo" class="user-info">
          <!-- User info will be dynamically inserted here -->
        </div>
        <nav id="navMenu" class="flex-1 p-2 space-y-1">
          <!-- Navigation items will be dynamically inserted here -->
        </nav>
        <div class="p-4 border-t border-gray-700">
          <button id="logoutBtn" class="nav-item w-full flex items-center p-3 rounded-md text-red-400 hover:text-red-300">
            <i class="fas fa-sign-out-alt mr-3"></i><span>Logout</span>
          </button>
        </div>
      </div>
    `;
    }
}

customElements.define("custom-navbar", CustomNavbar);

// Navigation configuration for different roles
const roleNavigation = {
    admin: [
        { id: 'approveTradersBtn', icon: 'fa-user-check', text: 'Approve Traders', path: '/approve-traders' },
        { id: 'manageUsersBtn', icon: 'fa-users-cog', text: 'Manage Users', path: '/manage-users' },
        { id: 'manageProductsBtn', icon: 'fa-box', text: 'Manage Products', path: '/manage-products' },
        { id: 'profileBtn', icon: 'fa-user', text: 'Profile', path: '/profile' }
    ],
    retailer: [
        { id: 'shopsBtn', icon: 'fa-store', text: 'My Shops', path: '/shops' },
        { id: 'ordersBtn', icon: 'fa-shopping-cart', text: 'Orders', path: '/trader-orders' },
        { id: 'profileBtn', icon: 'fa-user', text: 'Profile', path: '/profile' }
    ],
    user: [
        { id: 'profileBtn', icon: 'fa-user', text: 'Profile', path: '/profile' }
    ]
};

// after the component's markup is in the DOM:
document.addEventListener("DOMContentLoaded", () => {
    setTimeout(() => {
        // Get user data from localStorage
        const userData = JSON.parse(localStorage.getItem('user'));
        if (!userData) {
            window.location.href = '/login';
            return;
        }

        // Update user info section
        const userInfo = document.getElementById('userInfo');
        userInfo.innerHTML = `
            <div class="flex items-center space-x-3">
                <img src="${userData.profile_picture || 'https://i.pinimg.com/736x/6e/59/95/6e599501252c23bcf02658617b29c894.jpg'}" 
                     alt="${userData.name}" 
                     onerror="this.src='https://i.pinimg.com/736x/6e/59/95/6e599501252c23bcf02658617b29c894.jpg'">
                <div>
                    <div class="font-medium">${userData.name}</div>
                    <div class="text-sm text-gray-400">${userData.business_name || userData.email}</div>
                </div>
            </div>
        `;

        const navMenu = document.getElementById('navMenu');
        
        // Clear existing navigation items
        navMenu.innerHTML = '';
        
        // Get navigation items for the user's role
        const navItems = roleNavigation[userData.role] || roleNavigation.user;
        
        // Create and append navigation items
        navItems.forEach(item => {
            const button = document.createElement('button');
            button.id = item.id;
            button.className = 'nav-item w-full flex items-center p-3 rounded-md';
            button.innerHTML = `<i class="fas ${item.icon} mr-3"></i><span>${item.text}</span>`;
            navMenu.appendChild(button);
        });

        const clearActive = () =>
            document
                .querySelectorAll(".nav-item")
                .forEach((i) => i.classList.remove("active"));

        // Create navigation map from the items
        const navMap = navItems.reduce((acc, item) => {
            acc[item.id] = item.path;
            return acc;
        }, {});

        // attach clicks
        Object.entries(navMap).forEach(([btnId, path]) => {
            document.getElementById(btnId)?.addEventListener("click", () => {
                clearActive();
                document.getElementById(btnId).classList.add("active");
                window.location.href = path;
            });
        });

        // logout
        document.getElementById("logoutBtn")?.addEventListener("click", () => {
            localStorage.clear();
            window.location.href = "/login";
        });

        // highlight current
        const current = Object.entries(navMap).find(
            ([, p]) => p === window.location.pathname
        );
        if (current) {
            clearActive();
            document.getElementById(current[0])?.classList.add("active");
        }
    }, 100);
});
