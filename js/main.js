/**
 * APM Portal — Client SPA Orchestrator (UNIFICADO Design)
 * Handles sidebar drawer, theme controls, SPA navigation, and clock.
 */

// Expose theme controllers globally
window.C = {
    toggleThemeMenu(e) {
        if (e) e.stopPropagation();
        const drop = document.getElementById("tbThemeDropdown");
        if (drop) drop.classList.toggle("show");
    },
    selectThemeDirect(t, e) {
        if (e) e.stopPropagation();
        this.setTheme(t);
        const drop = document.getElementById("tbThemeDropdown");
        if (drop) drop.classList.remove("show");
    },
    setTheme(t) {
        document.body.classList.remove('t1', 't2', 't3');
        document.body.classList.add('t' + t);
        localStorage.setItem('apm_theme', t);
        document.querySelectorAll('[data-theme]').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-theme') === String(t));
        });
    }
};

document.addEventListener("DOMContentLoaded", function() {
    // Initialize theme
    const savedTheme = localStorage.getItem('apm_theme') || '1';
    window.C.setTheme(savedTheme);

    // Close topbar theme menu on outside click
    document.addEventListener("click", function(e) {
        const drop = document.getElementById("tbThemeDropdown");
        if (drop && drop.classList.contains("show") && !e.target.closest(".theme-dropdown-container")) {
            drop.classList.remove("show");
        }
    });

    // 1. Sidebar Drawer Toggle (UNIFICADO style)
    const toggleBtn = document.getElementById("toggle-sidebar-btn");
    const closeBtn = document.getElementById("sidebar-close-btn");
    const sidebar = document.getElementById("sidebar");

    // Open sidebar automatically on desktop viewports on load
    if (sidebar && window.innerWidth > 1024) {
        sidebar.classList.remove("collapsed");
        const icon = document.getElementById("hamburgerIcon");
        if (icon) {
            icon.setAttribute("data-lucide", "x");
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function toggleSidebar() {
        if (!sidebar) return;
        const isCollapsed = sidebar.classList.contains("collapsed");
        sidebar.classList.toggle("collapsed");
        
        // Update hamburger icon
        const icon = document.getElementById("hamburgerIcon");
        if (icon) {
            icon.setAttribute("data-lucide", isCollapsed ? "x" : "menu");
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    if (toggleBtn) toggleBtn.addEventListener("click", toggleSidebar);
    if (closeBtn) closeBtn.addEventListener("click", function() {
        if (sidebar) sidebar.classList.add("collapsed");
        const icon = document.getElementById("hamburgerIcon");
        if (icon) {
            icon.setAttribute("data-lucide", "menu");
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    });

    // 2. User Dropdown & Notification Toggle
    const userMenuBtn   = document.getElementById("user-menu-btn");
    const userDropdown  = document.getElementById("user-dropdown");
    const notifBtn      = document.getElementById("notif-btn");
    const notifDropdown = document.getElementById("notif-dropdown");

    function closeAllMenus() {
        if (userDropdown)  userDropdown.setAttribute("hidden", "");
        if (notifDropdown) notifDropdown.setAttribute("hidden", "");
    }

    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            const isOpen = !userDropdown.hasAttribute("hidden");
            closeAllMenus();
            if (!isOpen) userDropdown.removeAttribute("hidden");
        });
    }

    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            const isOpen = !notifDropdown.hasAttribute("hidden");
            closeAllMenus();
            if (!isOpen) notifDropdown.removeAttribute("hidden");
        });
    }

    document.addEventListener("click", function(e) {
        if (!e.target.closest(".topbar-user-wrap") && !e.target.closest(".topbar-notif-wrap")) {
            closeAllMenus();
        }
    });

    // 3. Breadcrumb updater helper (exposed for SPA use)
    function updateBreadcrumb(title) {
        const bc = document.getElementById("breadcrumb-text");
        if (bc) bc.textContent = title;
    }

    // 4. SPA Navigation Handler
    const mainContainer = document.getElementById("main-spa-container");

    document.addEventListener("click", function(e) {
        const link = e.target.closest("a");
        if (!link) return;

        // data-no-spa: navegación completa (módulos con layout propio, ej. Portuaria)
        if (link.hasAttribute("data-no-spa")) return;

        // Sistemas origen embebidos (/apps/...): SIEMPRE navegación completa —
        // nunca SPA, aunque el enlace venga de sidebar/flyout con data-spa.
        var __href = link.getAttribute("href") || "";
        if (__href.indexOf("/apps/") !== -1) return;

        const isSpaLink = link.hasAttribute("data-spa") ||
                          link.closest(".sidebar-mods") !== null ||
                          link.closest(".sidebar") !== null ||
                          link.classList.contains("btn-module");

        if (!isSpaLink || link.getAttribute("target") === "_blank" || e.ctrlKey || e.metaKey || e.shiftKey) return;

        e.preventDefault();
        navigateToSpa(link.getAttribute("href"));
    });

    // Handle form submissions
    document.addEventListener("submit", function(e) {
        const form = e.target;
        if (form.id === "login-form" || form.hasAttribute("data-bypass")) return;
        
        if (form.closest("#main-spa-container") || form.hasAttribute("data-spa-form") || form.id === "th-filter-form" || form.closest(".main-content")) {
            e.preventDefault();
            
            const method = (form.getAttribute("method") || "GET").toUpperCase();
            const action = form.getAttribute("action") || window.location.pathname;
            const formData = new FormData(form);
            let fetchUrl = action;
            let options = { method, headers: { 'X-Requested-With': 'XMLHttpRequest' } };
            
            if (method === "GET") {
                const params = new URLSearchParams();
                for (const [key, val] of formData.entries()) params.append(key, val);
                const qs = params.toString();
                if (qs) fetchUrl += (fetchUrl.includes("?") ? "&" : "?") + qs;
            } else {
                options.body = formData;
            }
            
            executeSpaFetch(fetchUrl, options, true);
        }
    });

    // Browser back/forward
    window.addEventListener("popstate", function(e) {
        if (e.state && e.state.path) {
            executeSpaFetch(e.state.path, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }, false);
        } else {
            window.location.reload();
        }
    });

    function navigateToSpa(path) {
        executeSpaFetch(path, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }, true);
    }

    function executeSpaFetch(url, options, pushState = true) {
        if (!mainContainer) return;
        mainContainer.style.opacity = "0.6";
        
        fetch(url, options)
            .then(response => {
                if (response.status === 401 || response.url.includes("/login")) {
                    window.location.href = APP_URL + '/login';
                    return;
                }
                const ct = response.headers.get("content-type");
                if (ct && ct.includes("text/csv")) {
                    return response.blob().then(blob => {
                        const a = document.createElement("a");
                        a.href = window.URL.createObjectURL(blob);
                        a.download = "reporte.csv";
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        mainContainer.style.opacity = "1";
                    });
                }
                return response.text();
            })
            .then(html => {
                if (!html) return;
                mainContainer.style.opacity = "0";
                
                setTimeout(() => {
                    mainContainer.innerHTML = html;
                    
                    // Sequential script loader to preserve execution order and wait for external dependencies
                    const scripts = Array.from(mainContainer.querySelectorAll("script"));
                    
                    function executeScriptsSequentially(list, index = 0) {
                        if (index >= list.length) {
                            // Run icon updates and dispatch event after all scripts complete
                            if (typeof lucide !== 'undefined') lucide.createIcons();
                            window.dispatchEvent(new Event('spa-content-loaded'));
                            return;
                        }
                        
                        const script = list[index];
                        const newScript = document.createElement("script");
                        Array.from(script.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        
                        if (script.src) {
                            if (document.querySelector(`script[src="${script.src}"]`)) {
                                // Script already loaded globally, skip and run next immediately
                                executeScriptsSequentially(list, index + 1);
                                return;
                            }
                            newScript.src = script.src;
                            newScript.onload = () => executeScriptsSequentially(list, index + 1);
                            newScript.onerror = () => executeScriptsSequentially(list, index + 1);
                            document.body.appendChild(newScript);
                        } else {
                            newScript.textContent = script.textContent;
                            document.body.appendChild(newScript);
                            executeScriptsSequentially(list, index + 1);
                        }
                    }
                    
                    executeScriptsSequentially(scripts);

                    if (pushState) history.pushState({ path: url }, "", url);
                    updateSidebarHighlights();
                    
                    const newTitle = mainContainer.querySelector("h2, h3, .welcome-title");
                    if (newTitle) document.title = newTitle.textContent.trim() + " — Portal APM";
                    
                    mainContainer.style.opacity = "1";
                }, 150);
            })
            .catch(error => {
                console.error("SPA Navigation Error:", error);
                mainContainer.style.opacity = "1";
            });
    }

    function updateSidebarHighlights() {
        const currentPath = window.location.pathname;
        document.querySelectorAll(".sidebar a.sb-item, .sidebar a.sm-header").forEach(link => {
            const linkPath = new URL(link.href).pathname;
            link.classList.toggle("active", currentPath.startsWith(linkPath) && linkPath !== new URL(APP_URL).pathname + '/');
        });
    }
});
