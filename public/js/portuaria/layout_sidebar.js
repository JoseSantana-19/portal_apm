/**
 * Sidebar: escritorio (≥992px) = push (clase sidebar-expanded en body) sin offcanvas de Bootstrap.
 * Móvil = offcanvas Bootstrap con backdrop.
 * El menú recuerda estados principales y cierra el menú de usuario al abrir otros apartados.
 */
(function () {
    'use strict';

    var MQ_DESKTOP = '(min-width: 992px)';
    var TRANSITION_MS = 300;
    var STORAGE_KEY = 'apmSidebarOpen';
    var STORAGE_ADMIN_GROUP_KEY = 'apmAdminGroupOpen';
    var STORAGE_SEGOP_GROUP_KEY = 'apmSegOpGroupOpen';
    var STORAGE_CCTV_GROUP_KEY = 'apmCctvGroupOpen';

    function isDesktop() {
        return window.matchMedia(MQ_DESKTOP).matches;
    }

    function getStoredOpen() {
        try {
            return window.sessionStorage.getItem(STORAGE_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function setStoredOpen(open) {
        try {
            if (open) {
                window.sessionStorage.setItem(STORAGE_KEY, '1');
            } else {
                window.sessionStorage.removeItem(STORAGE_KEY);
            }
        } catch (e) {
            /* privado / deshabilitado */
        }
    }

    function getStoredAdminGroupOpen() {
        try {
            return window.sessionStorage.getItem(STORAGE_ADMIN_GROUP_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function setStoredAdminGroupOpen(open) {
        try {
            if (open) {
                window.sessionStorage.setItem(STORAGE_ADMIN_GROUP_KEY, '1');
            } else {
                window.sessionStorage.removeItem(STORAGE_ADMIN_GROUP_KEY);
            }
        } catch (e) {
            /* privado / deshabilitado */
        }
    }

    function getStoredSegOpGroupOpen() {
        try {
            return window.sessionStorage.getItem(STORAGE_SEGOP_GROUP_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function setStoredSegOpGroupOpen(open) {
        try {
            if (open) {
                window.sessionStorage.setItem(STORAGE_SEGOP_GROUP_KEY, '1');
            } else {
                window.sessionStorage.removeItem(STORAGE_SEGOP_GROUP_KEY);
            }
        } catch (e) {
            /* privado / deshabilitado */
        }
    }

    function getStoredCctvGroupOpen() {
        try {
            return window.sessionStorage.getItem(STORAGE_CCTV_GROUP_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function setStoredCctvGroupOpen(open) {
        try {
            if (open) {
                window.sessionStorage.setItem(STORAGE_CCTV_GROUP_KEY, '1');
            } else {
                window.sessionStorage.removeItem(STORAGE_CCTV_GROUP_KEY);
            }
        } catch (e) {
            /* privado / deshabilitado */
        }
    }

    function adjustDataTables() {
        if (typeof window.jQuery === 'undefined' || !window.jQuery.fn.dataTable) {
            return;
        }

        try {
            window.jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().draw(false);
        } catch (e) {
            /* sin tablas DataTables en esta vista */
        }
    }

    function scheduleAdjust() {
        window.setTimeout(function () {
            adjustDataTables();

            try {
                window.dispatchEvent(new CustomEvent('apm:sidebar-layout-changed'));
            } catch (e) {
                /* noop */
            }
        }, TRANSITION_MS);
    }

    function closeDesktopSidebar() {
        document.body.classList.remove('sidebar-expanded');
        setStoredOpen(false);

        var btn = document.getElementById('btnMenuPrincipal');

        if (btn) {
            btn.setAttribute('aria-expanded', 'false');
        }

        scheduleAdjust();
    }

    function openDesktopSidebar() {
        document.body.classList.add('sidebar-expanded');
        setStoredOpen(true);

        var btn = document.getElementById('btnMenuPrincipal');

        if (btn) {
            btn.setAttribute('aria-expanded', 'true');
        }

        scheduleAdjust();
    }

    function init() {
        var btn = document.getElementById('btnMenuPrincipal');
        var sidebar = document.getElementById('sidebarMenu');

        if (!sidebar) {
            return;
        }

        var adminToggle = document.getElementById('adminGroupToggle');
        var adminItems = document.getElementById('adminGroupItems');
        var adminArrow = document.getElementById('adminGroupArrow');

        var segOpToggle = document.getElementById('segOpGroupToggle');
        var segOpItems = document.getElementById('segOpItems');
        var segOpArrow = document.getElementById('segOpGroupArrow');

        var cctvToggle = document.getElementById('cctvGroupToggle');
        var cctvItems = document.getElementById('cctvItems');
        var cctvArrow = document.getElementById('cctvGroupArrow');

        var userActionsToggle = document.getElementById('userActionsToggle');
        var userActionsItems = document.getElementById('userActionsItems');
        var userActionsArrow = document.getElementById('userActionsArrow');

        /*
         * IMPORTANTE:
         * No usamos la clase user-actions-open porque en pruebas anteriores
         * podía ocultar opciones del sidebar. Solo controlamos el collapse normal.
         */
        sidebar.classList.remove('user-actions-open');

        function actualizarFlechaUsuario(open) {
            if (userActionsArrow) {
                userActionsArrow.classList.toggle('is-open', open);
            }

            if (userActionsToggle) {
                userActionsToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
        }

        function cerrarMenuUsuario() {
            if (!userActionsItems) {
                return;
            }

            if (window.bootstrap && window.bootstrap.Collapse) {
                var collapseUser = window.bootstrap.Collapse.getOrCreateInstance(userActionsItems, {
                    toggle: false
                });

                collapseUser.hide();
            } else {
                userActionsItems.classList.remove('show');
            }

            actualizarFlechaUsuario(false);
            sidebar.classList.remove('user-actions-open');
        }

        /*
         * Menú inferior de usuario:
         * Cambiar contraseña / Salir.
         */
        if (userActionsToggle && userActionsItems) {
            if (userActionsItems.classList.contains('show')) {
                actualizarFlechaUsuario(true);
            } else {
                actualizarFlechaUsuario(false);
            }

            userActionsItems.addEventListener('show.bs.collapse', function () {
                actualizarFlechaUsuario(true);
            });

            userActionsItems.addEventListener('shown.bs.collapse', function () {
                actualizarFlechaUsuario(true);
            });

            userActionsItems.addEventListener('hide.bs.collapse', function () {
                actualizarFlechaUsuario(false);
            });

            userActionsItems.addEventListener('hidden.bs.collapse', function () {
                actualizarFlechaUsuario(false);
            });

            userActionsToggle.addEventListener('click', function () {
                window.setTimeout(function () {
                    var open =
                        userActionsItems.classList.contains('show') ||
                        userActionsItems.classList.contains('collapsing') ||
                        userActionsToggle.getAttribute('aria-expanded') === 'true';

                    actualizarFlechaUsuario(open);
                }, 80);
            });
        }

        /*
         * Edificio Administrativo.
         */
        if (adminToggle && adminItems && adminArrow) {
            var adminShouldOpen = getStoredAdminGroupOpen();

            adminArrow.classList.toggle('is-open', adminShouldOpen);
            adminToggle.setAttribute('aria-expanded', adminShouldOpen ? 'true' : 'false');

            adminItems.classList.add('apm-collapse-no-anim');
            adminItems.classList.toggle('show', adminShouldOpen);

            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    adminItems.classList.remove('apm-collapse-no-anim');
                });
            });

            adminItems.addEventListener('show.bs.collapse', function () {
                cerrarMenuUsuario();
                adminArrow.classList.add('is-open');
                setStoredAdminGroupOpen(true);
            });

            adminItems.addEventListener('hide.bs.collapse', function () {
                adminArrow.classList.remove('is-open');
                setStoredAdminGroupOpen(false);
            });

            adminToggle.addEventListener('click', function () {
                cerrarMenuUsuario();

                window.setTimeout(function () {
                    var open = adminToggle.getAttribute('aria-expanded') === 'true';
                    adminArrow.classList.toggle('is-open', open);
                    setStoredAdminGroupOpen(open);
                }, 0);
            });
        }

        /*
         * Seguridad Operativa.
         */
        if (segOpToggle && segOpItems && segOpArrow) {
            var segOpShouldOpen = getStoredSegOpGroupOpen() || segOpItems.classList.contains('show');

            segOpArrow.classList.toggle('is-open', segOpShouldOpen);
            segOpToggle.setAttribute('aria-expanded', segOpShouldOpen ? 'true' : 'false');

            segOpItems.classList.add('apm-collapse-no-anim');
            segOpItems.classList.toggle('show', segOpShouldOpen);

            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    segOpItems.classList.remove('apm-collapse-no-anim');
                });
            });

            segOpItems.addEventListener('show.bs.collapse', function () {
                cerrarMenuUsuario();
                segOpArrow.classList.add('is-open');
                setStoredSegOpGroupOpen(true);
            });

            segOpItems.addEventListener('hide.bs.collapse', function () {
                segOpArrow.classList.remove('is-open');
                setStoredSegOpGroupOpen(false);
            });

            segOpToggle.addEventListener('click', function () {
                cerrarMenuUsuario();

                window.setTimeout(function () {
                    var open = segOpToggle.getAttribute('aria-expanded') === 'true';
                    segOpArrow.classList.toggle('is-open', open);
                    setStoredSegOpGroupOpen(open);
                }, 0);
            });
        }

        /*
         * CCTV Cámaras.
         */
        if (cctvToggle && cctvItems && cctvArrow) {
            var cctvShouldOpen = getStoredCctvGroupOpen() || cctvItems.classList.contains('show');

            cctvArrow.classList.toggle('is-open', cctvShouldOpen);
            cctvToggle.setAttribute('aria-expanded', cctvShouldOpen ? 'true' : 'false');

            cctvItems.classList.add('apm-collapse-no-anim');
            cctvItems.classList.toggle('show', cctvShouldOpen);

            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    cctvItems.classList.remove('apm-collapse-no-anim');
                });
            });

            cctvItems.addEventListener('show.bs.collapse', function () {
                cerrarMenuUsuario();
                cctvArrow.classList.add('is-open');
                setStoredCctvGroupOpen(true);
            });

            cctvItems.addEventListener('hide.bs.collapse', function () {
                cctvArrow.classList.remove('is-open');
                setStoredCctvGroupOpen(false);
            });

            cctvToggle.addEventListener('click', function () {
                cerrarMenuUsuario();

                window.setTimeout(function () {
                    var open = cctvToggle.getAttribute('aria-expanded') === 'true';
                    cctvArrow.classList.toggle('is-open', open);
                    setStoredCctvGroupOpen(open);
                }, 0);
            });
        }

        /*
         * Si se hace clic en cualquier enlace normal del menú,
         * se cierra el menú de Usuario Seguridad Integral.
         */
        var enlacesMenu = sidebar.querySelectorAll('a.nav-link');

        enlacesMenu.forEach(function (link) {
            if (link.id === 'menu-password' || link.id === 'menu-logout') {
                return;
            }

            link.addEventListener('click', function () {
                cerrarMenuUsuario();
            });
        });

        /*
         * Restaurar sidebar abierto en escritorio.
         */
        if (btn && isDesktop() && getStoredOpen()) {
            if (!document.body.classList.contains('sidebar-expanded')) {
                document.body.classList.add('sidebar-expanded');
            }

            btn.setAttribute('aria-expanded', 'true');
            scheduleAdjust();
        }

        /*
         * Botón hamburguesa.
         */
        if (btn) {
            btn.addEventListener('click', function () {
                if (isDesktop()) {
                    var open = !document.body.classList.contains('sidebar-expanded');

                    document.body.classList.toggle('sidebar-expanded', open);
                    setStoredOpen(open);
                    btn.setAttribute('aria-expanded', open ? 'true' : 'false');

                    scheduleAdjust();
                    return;
                }

                if (!window.bootstrap || !window.bootstrap.Offcanvas) {
                    return;
                }

                var inst = window.bootstrap.Offcanvas.getOrCreateInstance(sidebar);
                inst.toggle();
            });
        }

        /*
         * Escritorio: cerrar solo si hacen clic fuera del sidebar.
         */
        document.addEventListener('click', function (e) {
            if (!isDesktop()) return;
            if (!document.body.classList.contains('sidebar-expanded')) return;
            if (!btn) return;
            if (btn.contains(e.target)) return;
            if (sidebar.contains(e.target)) return;

            closeDesktopSidebar();
        });

        sidebar.addEventListener('shown.bs.offcanvas', function () {
            if (btn) {
                btn.setAttribute('aria-expanded', 'true');
            }

            scheduleAdjust();
        });

        sidebar.addEventListener('hidden.bs.offcanvas', function () {
            if (btn) {
                btn.setAttribute('aria-expanded', 'false');
            }

            scheduleAdjust();
        });

        window.addEventListener('resize', function () {
            if (!isDesktop()) {
                document.body.classList.remove('sidebar-expanded');
                setStoredOpen(false);

                if (btn) {
                    btn.setAttribute('aria-expanded', 'false');
                }

                var inst = window.bootstrap && window.bootstrap.Offcanvas
                    ? window.bootstrap.Offcanvas.getInstance(sidebar)
                    : null;

                if (inst) {
                    inst.hide();
                }
            }
        });

        /*
         * Confirmación antes de cerrar sesión desde el menú.
         */
        var logoutLink = document.getElementById('menu-logout');

        if (logoutLink) {
            logoutLink.addEventListener('click', function (e) {
                e.preventDefault();

                var go = function () {
                    window.location.href = logoutLink.getAttribute('href') || 'logout';
                };

                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'question',
                        title: 'Cerrar sesión',
                        text: '¿Está seguro de que desea cerrar sesión?',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, cerrar sesión',
                        cancelButtonText: 'Cancelar'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            go();
                        }
                    });

                    return;
                }

                if (window.confirm('¿Está seguro de que desea cerrar sesión?')) {
                    go();
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();