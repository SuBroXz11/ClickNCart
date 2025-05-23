// navbar.js – Reusable Sidebar + Admin “Messages” link
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
        .nav-item { transition: all 0.3s ease; }
        .nav-item:hover { background-color: rgba(255,255,255,0.1); }
        .nav-item.active { background-color: rgba(255,255,255,0.2); }
      </style>

      <div class="sidebar flex flex-col h-screen w-64 bg-gray-900 text-white fixed top-0 left-0 shadow-lg">
        <div class="p-4 border-b border-gray-700">
          <h1 class="text-xl font-bold flex items-center">
            <i class="fas fa-chart-line mr-2"></i><span>ClickNCart</span>
          </h1>
        </div>
        <nav class="flex-1 p-2 space-y-1">
          <button id="dashboardBtn" class="nav-item w-full flex items-center p-3 rounded-md">
            <i class="fas fa-home mr-3"></i><span>Dashboard</span>
          </button>
          <button id="tradersBtn" class="nav-item w-full flex items-center p-3 rounded-md">
            <i class="fas fa-users mr-3"></i><span>Traders</span>
          </button>
          <button id="usersBtn" class="nav-item w-full flex items-center p-3 rounded-md">
            <i class="fas fa-user-cog mr-3"></i><span>Users</span>
          </button>
          <button id="shopsBtn" class="nav-item w-full flex items-center p-3 rounded-md">
            <i class="fas fa-store mr-3"></i><span>Shops</span>
          </button>
          <button id="messagesBtn" class="nav-item w-full flex items-center p-3 rounded-md">
            <i class="fas fa-envelope-open-text mr-3"></i><span>Messages</span>
          </button>
          <button id="profileBtn" class="nav-item w-full flex items-center p-3 rounded-md">
            <i class="fas fa-user mr-3"></i><span>Profile</span>
          </button>
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

document.addEventListener("DOMContentLoaded", () => {
    const user = JSON.parse(localStorage.getItem("user") || "{}");

    // hide Messages link if not admin
    if (user.role !== "admin") {
        document.getElementById("messagesBtn")?.remove();
    }

    const clearActive = () =>
        document
            .querySelectorAll(".nav-item")
            .forEach((i) => i.classList.remove("active"));

    // map IDs → paths
    const navMap = {
        dashboardBtn: "/dashboard",
        tradersBtn: "/traders",
        usersBtn: "/users",
        shopsBtn: "/shops",
        profileBtn: "/profile",
    };

    // only add messages route if admin
    if (user.role === "admin") {
        navMap.messagesBtn = "/user-messages";
    }

    // attach click handlers
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

    // highlight the current
    const current = Object.entries(navMap).find(
        ([, p]) => p === window.location.pathname
    );
    if (current) {
        clearActive();
        document.getElementById(current[0])?.classList.add("active");
    }
});
