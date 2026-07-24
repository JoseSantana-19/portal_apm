<?php
/**
 * Control de Acceso Administrative Panel.
 * Fully responsive Slate-Navy dashboard with full-fidelity tabbed interface for:
 * 1. Users management (CRUD)
 * 2. Roles & Profiles (CRUD)
 * 3. Menu Access Permissions (Lectura / Escritura)
 * 4. Form Access Permissions (1 to 4)
 * Contains daily logging handles, adaptive widths for 21" screens, and Windows auth details.
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = dirname($scriptName);
$baseUrl = str_replace('\\', '/', $baseUrl);
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}
?>

<!-- Custom CSS isolated to this module for premium slate-navy visuals and wide-screen responsiveness -->
<style>
    .acceso-admin-wrapper {
        max-width: 1500px;
        width: 100%;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 24px;
        animation: fadeSlideIn 0.3s ease-out;
    }

    @keyframes fadeSlideIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Tab Controls */
    .admin-tabs {
        display: flex;
        gap: 12px;
        border-bottom: 2px solid var(--border-app, rgba(255,255,255,0.08));
        padding-bottom: 2px;
        margin-top: 10px;
        overflow-x: auto;
    }

    .admin-tab-btn {
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 12px 20px;
        font-family: 'Sora', sans-serif;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-muted, #64748b);
        cursor: pointer;
        transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    .admin-tab-btn:hover {
        color: var(--primary-hover, #38bdf8);
        background: rgba(56, 189, 248, 0.04);
        border-radius: 6px 6px 0 0;
    }

    .admin-tab-btn.active {
        color: var(--primary-hover, #38bdf8);
        border-bottom-color: var(--primary-hover, #38bdf8);
    }

    /* Search and Action Row */
    .action-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .search-input-wrap {
        position: relative;
        flex: 1;
        max-width: 380px;
        min-width: 250px;
    }

    .search-input-wrap i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        width: 16px;
        height: 16px;
    }

    .search-control {
        width: 100%;
        padding: 10px 12px 10px 38px;
        background: var(--surface-app, #ffffff);
        border: 1.5px solid var(--border-app, #e2e8f0);
        border-radius: 8px;
        color: var(--text-app, #0f172a);
        font-family: inherit;
        font-size: 13.5px;
        outline: none;
        transition: all 0.2s ease;
    }

    .search-control:focus {
        border-color: var(--primary-hover, #38bdf8);
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
    }

    /* High Fidelity Table */
    .admin-table-container {
        background: var(--surface-app, #ffffff);
        border: 1.5px solid var(--border-app, #e2e8f0);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--shadow, 0 1px 3px rgba(0,0,0,0.05));
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .admin-table th {
        background: rgba(15, 23, 42, 0.02);
        padding: 14px 18px;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-muted, #475569);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1.5px solid var(--border-app, #e2e8f0);
    }

    .admin-table td {
        padding: 14px 18px;
        font-size: 13.5px;
        border-bottom: 1px solid var(--border-app, #e2e8f0);
        color: var(--text-app, #0f172a);
    }

    .admin-table tr:last-child td {
        border-bottom: none;
    }

    .admin-table tr:hover td {
        background: rgba(56, 189, 248, 0.02);
    }

    /* Modal Glassmorphism */
    .admin-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(8px);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .admin-modal.show {
        display: flex;
    }

    .modal-content {
        background: var(--surface-app, #ffffff);
        border: 1.5px solid var(--border-app, #e2e8f0);
        border-radius: 16px;
        width: 100%;
        max-width: 600px;
        box-shadow: var(--shadow-xl, 0 20px 25px -5px rgba(0,0,0,0.1));
        overflow: hidden;
        animation: modalFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.96) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .modal-header {
        padding: 18px 24px;
        background: rgba(15, 23, 42, 0.02);
        border-bottom: 1.5px solid var(--border-app, #e2e8f0);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        font-size: 16px;
        font-weight: 700;
        margin: 0;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .modal-close-btn {
        background: transparent;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4px;
        border-radius: 4px;
        transition: background 0.2s;
    }

    .modal-close-btn:hover {
        background: rgba(15, 23, 42, 0.05);
    }

    .modal-body {
        padding: 24px;
        max-height: 70vh;
        overflow-y: auto;
    }

    /* Forms */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    .form-span-2 {
        grid-column: span 2;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-app);
    }

    .form-control {
        width: 100%;
        padding: 10px 12px;
        background: var(--surface-app, #ffffff);
        border: 1.5px solid var(--border-app, #e2e8f0);
        border-radius: 8px;
        color: var(--text-app);
        font-family: inherit;
        font-size: 13.5px;
        outline: none;
        transition: border 0.2s;
    }

    .form-control:focus {
        border-color: var(--primary-hover);
    }

    .roles-checkbox-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        background: rgba(15, 23, 42, 0.01);
        padding: 12px;
        border-radius: 8px;
        border: 1.5px solid var(--border-app);
        max-height: 150px;
        overflow-y: auto;
    }

    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12.5px;
        cursor: pointer;
    }

    .checkbox-item input {
        cursor: pointer;
    }

    .modal-footer {
        padding: 16px 24px;
        border-top: 1.5px solid var(--border-app, #e2e8f0);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: rgba(15, 23, 42, 0.02);
    }

    /* Permission Grid Visualizer */
    .perm-grid-wrapper {
        background: var(--surface-app);
        border: 1.5px solid var(--border-app);
        border-radius: 12px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .perm-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        align-items: center;
        gap: 16px;
        padding: 10px 12px;
        border-bottom: 1px solid var(--border-app);
    }

    .perm-row:last-child {
        border-bottom: none;
    }

    .perm-mod-group {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--primary-hover);
        padding: 10px 12px 4px;
        background: rgba(56, 189, 248, 0.04);
        border-radius: 6px;
        margin-top: 12px;
    }

    /* Alert / Flash Message */
    .alert-notice {
        padding: 14px 18px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13.5px;
        font-weight: 500;
    }

    .alert-notice-success {
        background: #F0FDF4;
        border: 1.5px solid #BBF7D0;
        color: #166534;
    }

    .alert-notice-error {
        background: #FEF2F2;
        border: 1.5px solid #FCA5A5;
        color: #991B1B;
    }

    /* Dual Panel Permissions Cockpit Styles */
    .perm-grid {
        display: grid;
        grid-template-columns: 310px 1fr;
        gap: 24px;
        align-items: start;
        margin-top: 16px;
    }

    @media (max-width: 900px) {
        .perm-grid {
            grid-template-columns: 1fr;
        }
    }

    .perm-role-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .perm-role-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 12px;
        cursor: pointer;
        border: 2px solid var(--border-app, #e2e8f0);
        background: var(--surface-app, #ffffff);
        transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .perm-role-card:hover {
        border-color: var(--primary-hover, #38bdf8);
        background: rgba(56, 189, 248, 0.02);
        transform: translateY(-2px);
    }

    .perm-role-card.selected {
        border-color: var(--primary-hover, #38bdf8);
        background: rgba(56, 189, 248, 0.05);
        box-shadow: 0 4px 12px rgba(56, 189, 248, 0.08);
    }

    .perm-role-card::after {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--primary-hover, #38bdf8);
        transform: scaleY(0);
        transition: transform 0.2s;
    }

    .perm-role-card.selected::after {
        transform: scaleY(1);
    }

    .perm-avatar {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Outfit', sans-serif;
        font-weight: 800;
        font-size: 13.5px;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    /* Gradients matching sequential hierarchy levels */
    .perm-avatar.lvl3 { background: linear-gradient(135deg, #f59e0b, #d97706); } /* Level 3: Gold/Bronze */
    .perm-avatar.lvl2 { background: linear-gradient(135deg, #3b82f6, #1d4ed8); } /* Level 2: Sapphire */
    .perm-avatar.lvl1 { background: linear-gradient(135deg, #10b981, #047857); } /* Level 1: Emerald */
    .perm-avatar.lvl0 { background: linear-gradient(135deg, #64748b, #475569); } /* Level 0: Muted Slate */

    .perm-role-info {
        flex: 1;
        min-width: 0;
    }

    .perm-role-info h4 {
        margin: 0;
        font-family: 'Outfit', sans-serif;
        font-size: 13.5px;
        font-weight: 700;
        color: var(--text-app, #0f172a);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .perm-role-info p {
        margin: 2px 0 0 0;
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        color: var(--text-muted, #64748b);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .perm-count-badge {
        padding: 3px 8px;
        border-radius: 20px;
        font-family: 'Inter', sans-serif;
        font-size: 10.5px;
        font-weight: 700;
        background: rgba(15, 23, 42, 0.05);
        border: 1px solid var(--border-app, #e2e8f0);
        color: var(--text-app, #0f172a);
        flex-shrink: 0;
        transition: all 0.2s;
    }

    .perm-role-card.selected .perm-count-badge {
        background: var(--primary-hover, #38bdf8);
        border-color: var(--primary-hover, #38bdf8);
        color: white;
    }

    .perm-placeholder-card {
        background: var(--surface-app, #ffffff);
        border: 1.5px dashed var(--border-app, #e2e8f0);
        border-radius: 16px;
        padding: 60px 40px;
        text-align: center;
        color: var(--text-muted);
        box-shadow: var(--shadow, 0 1px 3px rgba(0,0,0,0.05));
    }

    /* Segmented Option Controls */
    .segmented-control {
        display: inline-flex;
        background: rgba(15, 23, 42, 0.04);
        border: 1px solid var(--border-app, #e2e8f0);
        padding: 2px;
        border-radius: 30px;
        gap: 2px;
        position: relative;
    }

    .segmented-btn {
        background: transparent;
        border: none;
        padding: 5px 12px;
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-muted, #64748b);
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .segmented-btn:hover {
        color: var(--text-app, #0f172a);
        background: rgba(15, 23, 42, 0.02);
    }

    /* Segments Selected Themes */
    .segmented-btn.active.no-access {
        background: #EF4444 !important;
        color: #fff !important;
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.25);
    }

    .segmented-btn.active.read-access {
        background: #2563EB !important;
        color: #fff !important;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }

    .segmented-btn.active.write-access {
        background: #10B981 !important;
        color: #fff !important;
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
    }

    .segmented-btn.active.edit-access {
        background: #F59E0B !important;
        color: #fff !important;
        box-shadow: 0 2px 6px rgba(245, 158, 11, 0.25);
    }

    .segmented-btn.active.delete-access {
        background: #DC2626 !important;
        color: #fff !important;
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
    }

    /* Loading Overlay inside the Cockpit matrix */
    .matrix-pane-container {
        position: relative;
        background: var(--surface-app, #ffffff);
        border: 1.5px solid var(--border-app, #e2e8f0);
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--shadow, 0 1px 3px rgba(0,0,0,0.05));
    }

    .matrix-loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border-radius: 16px;
        backdrop-filter: blur(4px);
    }

    .matrix-loading-overlay.show {
        display: flex;
    }

    /* Live telemetric checkmark badge */
    .saving-indicator-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        background: #DCFCE7;
        color: #15803D;
        border: 1px solid #BBF7D0;
        opacity: 0;
        transform: translateY(-5px);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .saving-indicator-badge.show {
        opacity: 1;
        transform: translateY(0);
    }

    /* Custom Multi-Select with Search Dropdown */
    .multiselect-dropdown {
        position: relative;
        width: 100%;
    }
    .multiselect-header {
        min-height: 42px;
        padding: 8px 36px 8px 12px;
        background: var(--bg-app, #F8FAFC);
        border: 1.5px solid var(--border-app, #DDE4EF);
        border-radius: 10px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        position: relative;
        transition: all 0.22s ease;
    }
    body.t1 .multiselect-header {
        background: #F8FAFC;
        border-color: #E2E8F0;
    }
    .multiselect-header:hover {
        border-color: var(--primary-hover, #0284c7);
    }
    .multiselect-dropdown.open .multiselect-header {
        border-color: var(--primary-hover, #0284c7);
        background: var(--surface-app, #ffffff);
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }
    .multiselect-placeholder {
        color: var(--text-muted, #94A3B8);
        font-size: 13px;
        user-select: none;
    }
    .multiselect-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .multiselect-tag {
        background: rgba(2, 132, 199, 0.08);
        color: var(--primary-hover, #0284c7);
        border: 1px solid rgba(2, 132, 199, 0.2);
        border-radius: 6px;
        padding: 2px 8px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .multiselect-tag-close {
        cursor: pointer;
        font-size: 10px;
        opacity: 0.6;
        transition: opacity 0.15s ease;
    }
    .multiselect-tag-close:hover {
        opacity: 1;
        color: #EF4444;
    }
    .multiselect-arrow {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        color: var(--text-muted, #94A3B8);
        transition: transform 0.22s ease;
    }
    .multiselect-dropdown.open .multiselect-arrow {
        transform: translateY(-50%) rotate(180deg);
    }
    .multiselect-body {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        background: var(--surface-app, #ffffff);
        border: 1.5px solid var(--border-app, #DDE4EF);
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.15);
        z-index: 999;
        display: none;
        flex-direction: column;
        overflow: hidden;
        animation: multiselectFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes multiselectFadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .multiselect-dropdown.open .multiselect-body {
        display: flex;
    }
    .multiselect-search-wrap {
        padding: 10px 12px;
        border-bottom: 1px solid var(--border-app, #E2E8F0);
        display: flex;
        align-items: center;
        position: relative;
    }
    .multiselect-search-wrap i {
        position: absolute;
        left: 20px;
        color: var(--text-muted, #94A3B8);
        font-size: 12px;
    }
    .multiselect-search-wrap input {
        width: 100%;
        padding: 8px 12px 8px 28px;
        border: 1.5px solid var(--border-app, #DDE4EF);
        border-radius: 8px;
        font-size: 13px;
        background: var(--bg-app, #F8FAFC);
        color: var(--text-app, #0f172a);
        outline: none;
        transition: border-color 0.15s ease;
    }
    .multiselect-search-wrap input:focus {
        border-color: var(--primary-hover, #0284c7);
        background: var(--surface-app, #ffffff);
    }
    .multiselect-options {
        max-height: 180px;
        overflow-y: auto;
        padding: 6px;
    }
    .multiselect-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        color: var(--text-app, #0f172a);
        user-select: none;
        transition: background 0.15s ease;
    }
    .multiselect-option:hover {
        background: rgba(15, 23, 42, 0.04);
    }
    .multiselect-option input {
        cursor: pointer;
    }
</style>


<div class="acceso-admin-wrapper">
    <!-- Welcome Header -->
    <div class="welcome-banner" style="background: linear-gradient(135deg, #0f1c30, #071220); border-bottom: 4px solid var(--accent-color, #0284c7);">
        <div class="welcome-text">
            <h2 class="welcome-title"><i data-lucide="shield-check" style="display:inline-block; vertical-align:middle; margin-right:10px; color:#38bdf8;"></i>Control de Acceso Centralizado</h2>
            <p class="welcome-desc">Administración unificada de perfiles, permisos y control de colaboradores de la Autoridad Portuaria de Manta. Asigne accesos precisos sobre menús y formularios operativos.</p>
        </div>
        <div class="welcome-visual" style="opacity: 0.22;">
            <i data-lucide="key-round" class="visual-icon" style="color: #38bdf8;"></i>
        </div>
    </div>

    <!-- Alert Notices -->
    <?php if (isset($_SESSION['success_flash'])): ?>
        <div class="alert-notice alert-notice-success">
            <i data-lucide="check-circle" style="color:#166534;"></i>
            <span><?= htmlspecialchars($_SESSION['success_flash']) ?></span>
            <?php unset($_SESSION['success_flash']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_flash'])): ?>
        <div class="alert-notice alert-notice-error">
            <i data-lucide="alert-triangle" style="color:#991B1B;"></i>
            <span><?= htmlspecialchars($_SESSION['error_flash']) ?></span>
            <?php unset($_SESSION['error_flash']); ?>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <div class="admin-tabs">
        <button class="admin-tab-btn active" onclick="switchTab('tab-users')">
            <i data-lucide="users"></i> Usuarios
        </button>
        <button class="admin-tab-btn" onclick="switchTab('tab-roles')">
            <i data-lucide="shield"></i> Perfiles y Roles
        </button>
        <button class="admin-tab-btn" onclick="switchTab('tab-menus')">
            <i data-lucide="menu"></i> Permisos de Menú
        </button>
        <button class="admin-tab-btn" onclick="switchTab('tab-forms')">
            <i data-lucide="file-check-2"></i> Permisos en Formularios
        </button>
    </div>

    <!-- TAB 1: USERS -->
    <div id="tab-users" class="tab-pane active">
        <div class="action-row">
            <div class="search-input-wrap">
                <i data-lucide="search"></i>
                <input type="text" class="search-control" placeholder="Buscar usuarios..." onkeyup="filterTable('users-tbody', this.value)">
            </div>
            <button class="btn btn-primary" onclick="openUserModal()">
                <i data-lucide="user-plus"></i> Agregar Usuario
            </button>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nombre Completo</th>
                        <th>Usuario</th>
                        <th>Correo Electrónico</th>
                        <th>Departamento Principal</th>
                        <th>Roles Asignados</th>
                        <th style="text-align: center;">Estado</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="users-tbody">
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($u['nombre_completo']) ?></td>
                            <td style="font-family: 'JetBrains Mono', monospace; font-size: 12.5px;"><?= htmlspecialchars($u['nombre_usuario']) ?></td>
                            <td><?= htmlspecialchars($u['correo']) ?></td>
                            <td><?= htmlspecialchars($u['departamento'] ?? 'Ninguno') ?></td>
                            <td style="color:#0284c7; font-weight: 500; font-size:12.5px;"><?= htmlspecialchars($u['roles'] ?? 'Sin Roles') ?></td>
                            <td style="text-align: center;">
                                <?php if ($u['activo']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <button class="btn btn-secondary" style="padding: 4px 8px; font-size:12px;" onclick="openUserModal(<?= htmlspecialchars(json_encode($u)) ?>)">
                                    <i data-lucide="edit-3" style="width:13px; height:13px;"></i> Editar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: ROLES -->
    <div id="tab-roles" class="tab-pane" style="display: none;">
        <div class="action-row">
            <div class="search-input-wrap">
                <i data-lucide="search"></i>
                <input type="text" class="search-control" placeholder="Buscar perfiles..." onkeyup="filterTable('roles-tbody', this.value)">
            </div>
            <button class="btn btn-primary" onclick="openRolModal()">
                <i data-lucide="shield-alert"></i> Agregar Perfil/Rol
            </button>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nombre Perfil / Rol</th>
                        <th>Descripción</th>
                        <th>Área / Módulo</th>
                        <th style="text-align: center;">Jerarquía</th>
                        <th style="text-align: center;">Estado</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="roles-tbody">
                    <?php foreach ($roles as $r): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($r['nombre_grupo_rol']) ?></td>
                            <td><?= htmlspecialchars($r['descripcion']) ?></td>
                            <td><?= htmlspecialchars($r['departamento'] ?? 'General') ?></td>
                            <td style="text-align: center; font-weight:700;"><?= $r['nivel_jerarquia'] ?></td>
                            <td style="text-align: center;">
                                <?php if ($r['activo']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <button class="btn btn-secondary" style="padding: 4px 8px; font-size:12px;" onclick="openRolModal(<?= htmlspecialchars(json_encode($r)) ?>)">
                                    <i data-lucide="edit-3" style="width:13px; height:13px;"></i> Editar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: MENU PERMISSIONS -->
    <div id="tab-menus" class="tab-pane" style="display: none;">
        <div class="perm-grid">
            <!-- Left panel: Roles List -->
            <div class="matrix-pane-container" style="padding:16px;">
                <h4 style="margin:0 0 14px 0; font-family:'Outfit',sans-serif; font-size:12px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.08em; font-weight:700;">
                    <i data-lucide="shield" style="width:13px; height:13px; display:inline-block; vertical-align:middle; margin-right:4px;"></i> Perfiles del Sistema
                </h4>
                <div class="perm-role-list">
                    <?php foreach ($roles as $r): 
                        $avatarLvlClass = 'lvl' . min(3, max(0, (int)($r['nivel_jerarquia'] ?? 0)));
                    ?>
                        <div class="perm-role-card menu-role-card" 
                             id="menu-role-card-<?= $r['id_grupo_rol'] ?>" 
                             onclick="selectMenuRole(<?= $r['id_grupo_rol'] ?>, '<?= htmlspecialchars($r['nombre_grupo_rol'], ENT_QUOTES) ?>', <?= (int)($r['id_grupo_rol'] == 1) ?>)">
                            <div class="perm-avatar <?= $avatarLvlClass ?>">
                                <?= strtoupper(substr($r['nombre_grupo_rol'], 0, 2)) ?>
                            </div>
                            <div class="perm-role-info">
                                <h4><?= htmlspecialchars($r['nombre_grupo_rol']) ?></h4>
                                <p><?= htmlspecialchars($r['departamento'] ?? 'General') ?> &bull; Jerarquía <?= $r['nivel_jerarquia'] ?></p>
                            </div>
                            <span class="perm-count-badge" id="menu-badge-<?= $r['id_grupo_rol'] ?>">0</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right panel: Matrix visualizer -->
            <div class="matrix-pane-container" id="menu-matrix-pane">
                <div class="matrix-loading-overlay" id="menu-matrix-overlay">
                    <div class="spinner" style="border-top-color: var(--primary-hover); width:36px; height:36px;"></div>
                </div>

                <!-- Empty placeholder -->
                <div class="perm-placeholder-card" id="menu-matrix-placeholder">
                    <i data-lucide="shield-alert" style="width:40px; height:40px; margin: 0 auto 16px; opacity:0.3; color:var(--text-muted);"></i>
                    <strong style="display:block; margin-bottom:6px; font-family:'Outfit',sans-serif; font-size:15px; color:var(--text-app);">Selecciona un Perfil</strong>
                    <span style="font-size:13px;">Elige uno de los perfiles de la columna izquierda para ver y ajustar su matriz de permisos sobre opciones del menú.</span>
                </div>

                <!-- Live Matrix Dashboard Form -->
                <div id="menu-matrix-form-wrap" style="display:none;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; border-bottom:1.5px solid var(--border-app); padding-bottom:14px;">
                        <div>
                            <h3 id="menu-matrix-rol-name" style="margin:0; font-family:'Outfit',sans-serif; font-size:16px; font-weight:700; color:var(--text-app);"></h3>
                            <p style="margin:2px 0 0 0; font-size:12px; color:var(--text-muted);">Configure la accesibilidad en tiempo real para las opciones jerárquicas.</p>
                        </div>
                        <div class="saving-indicator-badge" id="menu-saving-indicator">
                            <i data-lucide="check-circle" style="width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:4px;"></i>
                            <span>Cambio Guardado</span>
                        </div>
                    </div>

                    <!-- Admin Warning banner -->
                    <div id="menu-admin-warning" style="display:none; padding:12px 16px; background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.2); border-radius:10px; margin-bottom:16px; font-size:12.5px; color:#b45309; line-height:1.4;">
                        <i data-lucide="crown" style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:6px; color:#f59e0b;"></i>
                        <strong>Bypass de Administrador Activo:</strong> Este rol cuenta con privilegios de superusuario universal implícitos. No es necesario ni posible modificar sus accesos individuales.
                    </div>

                    <div id="menu-matrix-rows-list" style="max-height: 55vh; overflow-y: auto; padding-right:6px;">
                        <?php 
                        $currentModule = '';
                        foreach ($menuOpciones as $mo): 
                            if ($currentModule !== $mo['modulo']) {
                                $currentModule = $mo['modulo'];
                                echo "<div class='perm-mod-group' style='margin-top:20px; margin-bottom:8px;'><i data-lucide='folder' style='width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:4px;'></i> MÓDULO: " . htmlspecialchars($currentModule ?? '') . "</div>";
                            }
                            $dottedCode = $mo['codigo_secuencial'] ?? 'X.X.X.X';
                        ?>
                            <div class="perm-row" style="grid-template-columns: 1fr auto; padding: 12px 14px; border:1px solid transparent; border-radius:10px; margin-bottom:4px; transition:background 0.15s;">
                                <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                                    <span style="font-family: 'Fira Code', monospace; font-size:10.5px; font-weight:700; color:var(--primary-hover); background:rgba(56,189,248,0.08); padding:2px 8px; border-radius:20px; border:1px solid rgba(56,189,248,0.15); flex-shrink:0;" title="Código Secuencial de Nivel"><?= $dottedCode ?></span>
                                    <span style="font-family:'Inter',sans-serif; font-size: 13.5px; font-weight: 500; color:var(--text-app); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($mo['descripcion_interfaz']) ?></span>
                                </div>
                                <div>
                                    <div class="segmented-control" id="control-menu-<?= $mo['id_menu_op'] ?>">
                                        <button type="button" class="segmented-btn no-access active" onclick="saveMenuSegment(<?= $mo['id_menu_op'] ?>, 0)" data-val="0">Sin Acceso</button>
                                        <button type="button" class="segmented-btn read-access" onclick="saveMenuSegment(<?= $mo['id_menu_op'] ?>, 1)" data-val="1">Ver</button>
                                        <button type="button" class="segmented-btn write-access" onclick="saveMenuSegment(<?= $mo['id_menu_op'] ?>, 2)" data-val="2">Operar</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 4: FORM PERMISSIONS -->
    <div id="tab-forms" class="tab-pane" style="display: none;">
        <div class="perm-grid">
            <!-- Left panel: Roles List -->
            <div class="matrix-pane-container" style="padding:16px;">
                <h4 style="margin:0 0 14px 0; font-family:'Outfit',sans-serif; font-size:12px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.08em; font-weight:700;">
                    <i data-lucide="shield" style="width:13px; height:13px; display:inline-block; vertical-align:middle; margin-right:4px;"></i> Perfiles del Sistema
                </h4>
                <div class="perm-role-list">
                    <?php foreach ($roles as $r): 
                        $avatarLvlClass = 'lvl' . min(3, max(0, (int)($r['nivel_jerarquia'] ?? 0)));
                    ?>
                        <div class="perm-role-card form-role-card" 
                             id="form-role-card-<?= $r['id_grupo_rol'] ?>" 
                             onclick="selectFormRole(<?= $r['id_grupo_rol'] ?>, '<?= htmlspecialchars($r['nombre_grupo_rol'], ENT_QUOTES) ?>', <?= (int)($r['id_grupo_rol'] == 1) ?>)">
                            <div class="perm-avatar <?= $avatarLvlClass ?>">
                                <?= strtoupper(substr($r['nombre_grupo_rol'], 0, 2)) ?>
                            </div>
                            <div class="perm-role-info">
                                <h4><?= htmlspecialchars($r['nombre_grupo_rol']) ?></h4>
                                <p><?= htmlspecialchars($r['departamento'] ?? 'General') ?> &bull; Jerarquía <?= $r['nivel_jerarquia'] ?></p>
                            </div>
                            <span class="perm-count-badge" id="form-badge-<?= $r['id_grupo_rol'] ?>">0</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right panel: Matrix visualizer -->
            <div class="matrix-pane-container" id="form-matrix-pane">
                <div class="matrix-loading-overlay" id="form-matrix-overlay">
                    <div class="spinner" style="border-top-color: var(--primary-hover); width:36px; height:36px;"></div>
                </div>

                <!-- Empty placeholder -->
                <div class="perm-placeholder-card" id="form-matrix-placeholder">
                    <i data-lucide="shield-alert" style="width:40px; height:40px; margin: 0 auto 16px; opacity:0.3; color:var(--text-muted);"></i>
                    <strong style="display:block; margin-bottom:6px; font-family:'Outfit',sans-serif; font-size:15px; color:var(--text-app);">Selecciona un Perfil</strong>
                    <span style="font-size:13px;">Elige uno de los perfiles de la columna izquierda para ver y ajustar su matriz de permisos y nivel de acción sobre formularios.</span>
                </div>

                <!-- Live Matrix Dashboard Form -->
                <div id="form-matrix-form-wrap" style="display:none;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; border-bottom:1.5px solid var(--border-app); padding-bottom:14px;">
                        <div>
                            <h3 id="form-matrix-rol-name" style="margin:0; font-family:'Outfit',sans-serif; font-size:16px; font-weight:700; color:var(--text-app);"></h3>
                            <p style="margin:2px 0 0 0; font-size:12px; color:var(--text-muted);">Configure la severidad del nivel de privilegios sobre formularios del sistema.</p>
                        </div>
                        <div class="saving-indicator-badge" id="form-saving-indicator">
                            <i data-lucide="check-circle" style="width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:4px;"></i>
                            <span>Cambio Guardado</span>
                        </div>
                    </div>

                    <!-- Admin Warning banner -->
                    <div id="form-admin-warning" style="display:none; padding:12px 16px; background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.2); border-radius:10px; margin-bottom:16px; font-size:12.5px; color:#b45309; line-height:1.4;">
                        <i data-lucide="crown" style="width:14px; height:14px; display:inline-block; vertical-align:middle; margin-right:6px; color:#f59e0b;"></i>
                        <strong>Bypass de Administrador Activo:</strong> Este rol cuenta con privilegios de superusuario universal implícitos. No es necesario ni posible modificar sus accesos individuales.
                    </div>

                    <div id="form-matrix-rows-list" style="max-height: 55vh; overflow-y: auto; padding-right:6px;">
                        <?php 
                        $currentFormModule = '';
                        foreach ($formularios as $f): 
                            if ($currentFormModule !== $f['modulo']) {
                                $currentFormModule = $f['modulo'];
                                echo "<div class='perm-mod-group' style='margin-top:20px; margin-bottom:8px;'><i data-lucide='file-text' style='width:12px; height:12px; display:inline-block; vertical-align:middle; margin-right:4px;'></i> MÓDULO: " . htmlspecialchars($currentFormModule ?? '') . "</div>";
                            }
                        ?>
                            <div class="perm-row" style="grid-template-columns: 1fr auto; padding: 12px 14px; border:1px solid transparent; border-radius:10px; margin-bottom:4px; transition:background 0.15s;">
                                <div style="display:flex; flex-direction:column; gap:2px; min-width:0;">
                                    <span style="font-family:'Inter',sans-serif; font-size: 13.5px; font-weight: 700; color:var(--text-app); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($f['nombre_formulario']) ?></span>
                                    <span style="font-size: 11px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($f['descripcion'] ?? 'Sin descripción asociada') ?></span>
                                </div>
                                <div>
                                    <div class="segmented-control" id="control-form-<?= $f['idform'] ?>">
                                        <button type="button" class="segmented-btn no-access active" onclick="saveFormSegment(<?= $f['idform'] ?>, 0)" data-val="0">Ninguno</button>
                                        <button type="button" class="segmented-btn read-access" onclick="saveFormSegment(<?= $f['idform'] ?>, 1)" data-val="1">Ver</button>
                                        <button type="button" class="segmented-btn write-access" onclick="saveFormSegment(<?= $f['idform'] ?>, 2)" data-val="2">Crear</button>
                                        <button type="button" class="segmented-btn edit-access" onclick="saveFormSegment(<?= $f['idform'] ?>, 3)" data-val="3">Editar</button>
                                        <button type="button" class="segmented-btn delete-access" onclick="saveFormSegment(<?= $f['idform'] ?>, 4)" data-val="4">Borrar</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================================
     MODALES MODULARES DE EDICIÓN
     ============================================================================ -->

<!-- User Modal -->
<div class="admin-modal" id="user-modal">
    <div class="modal-content">
        <form action="<?= $baseUrl ?>/control-acceso/usuario" method="POST" id="user-form">
            <div class="modal-header">
                <h3 id="user-modal-title"><i data-lucide="user-plus"></i> Registrar Nuevo Usuario</h3>
                <button type="button" class="modal-close-btn" onclick="closeUserModal()"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="user-id" name="id_usuario">
                <div class="form-grid">
                    <div class="form-group form-span-2">
                        <label for="user-nombre">Nombre Completo</label>
                        <input type="text" id="user-nombre" name="nombre_completo" class="form-control" placeholder="Ej: Abg. Maria Solis Intriago" required>
                    </div>

                    <div class="form-group">
                        <label for="user-nombre-usuario">Usuario</label>
                        <input type="text" id="user-nombre-usuario" name="nombre_usuario" class="form-control" placeholder="Ej: m.solis" required>
                    </div>

                    <div class="form-group">
                        <label for="user-correo">Correo Institucional</label>
                        <input type="email" id="user-correo" name="correo" class="form-control" placeholder="m.solis@apm.gob.ec" required>
                    </div>

                    <div class="form-group" id="user-pass-group">
                        <label for="user-contrasena">Contraseña Temporal</label>
                        <input type="password" id="user-contrasena" name="contrasena" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label for="user-telefono">Teléfono / Ext.</label>
                        <input type="text" id="user-telefono" name="telefono" class="form-control" placeholder="Ej: 593-1-002">
                    </div>

                    <div class="form-group">
                        <label for="user-cargo">Cargo Institucional</label>
                        <input type="text" id="user-cargo" name="cargo_institucional" class="form-control" placeholder="Ej: Abogado Senior">
                    </div>

                    <div class="form-group">
                        <label for="user-depto">Departamento Principal</label>
                        <select id="user-depto" name="id_dep_principal" class="form-control">
                            <option value="">-- Ninguno --</option>
                            <?php foreach ($departamentos as $d): ?>
                                <option value="<?= $d['id_dep_modulo'] ?>"><?= htmlspecialchars($d['nombre_departamento']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="user-activo">Estado</label>
                        <select id="user-activo" name="activo" class="form-control">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="form-group form-span-2" style="margin-top: 6px; position: relative;">
                        <label>Roles / Perfiles de Acceso Asignados</label>
                        <div class="multiselect-dropdown" id="user-roles-multiselect">
                            <div class="multiselect-header" onclick="toggleMultiselectDropdown(event)">
                                <span class="multiselect-placeholder">Seleccione los roles...</span>
                                <div class="multiselect-tags" id="multiselect-tags-container"></div>
                                <i class="fa-solid fa-chevron-down multiselect-arrow"></i>
                            </div>
                            <div class="multiselect-body" onclick="event.stopPropagation()">
                                <div class="multiselect-search-wrap">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" placeholder="Buscar rol..." onkeyup="filterMultiselectOptions(this.value)">
                                </div>
                                <div class="multiselect-options">
                                    <?php foreach ($roles as $r): ?>
                                        <label class="multiselect-option">
                                            <input type="checkbox" name="roles[]" class="user-role-chk" value="<?= $r['id_grupo_rol'] ?>" onchange="updateMultiselectTags()">
                                            <span><?= htmlspecialchars($r['nombre_grupo_rol']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Guardar Usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- Role Modal -->
<div class="admin-modal" id="rol-modal">
    <div class="modal-content">
        <form action="<?= $baseUrl ?>/control-acceso/rol" method="POST" id="rol-form">
            <div class="modal-header">
                <h3 id="rol-modal-title"><i data-lucide="shield-alert"></i> Crear Perfil/Rol</h3>
                <button type="button" class="modal-close-btn" onclick="closeRolModal()"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rol-id" name="id_grupo_rol">
                <div class="form-grid">
                    <div class="form-group form-span-2">
                        <label for="rol-nombre">Nombre del Perfil/Rol</label>
                        <input type="text" id="rol-nombre" name="nombre_grupo_rol" class="form-control" placeholder="Ej: Analista CCTV Senior" required>
                    </div>

                    <div class="form-group form-span-2">
                        <label for="rol-descripcion">Descripción del Perfil</label>
                        <textarea id="rol-descripcion" name="descripcion" class="form-control" style="height: 80px; resize: none;" placeholder="Defina el alcance y responsabilidades del perfil de seguridad..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="rol-depto">Departamento Asignado</label>
                        <select id="rol-depto" name="id_dep_modulo" class="form-control">
                            <option value="">-- General / Ninguno --</option>
                            <?php foreach ($departamentos as $d): ?>
                                <option value="<?= $d['id_dep_modulo'] ?>"><?= htmlspecialchars($d['nombre_departamento']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="rol-jerarquia">Nivel Jerarquía (0-3)</label>
                        <input type="number" id="rol-jerarquia" name="nivel_jerarquia" class="form-control" min="0" max="3" value="0" required>
                    </div>

                    <div class="form-group">
                        <label for="rol-activo">Estado</label>
                        <select id="rol-activo" name="activo" class="form-control">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRolModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Guardar Perfil</button>
            </div>
        </form>
    </div>
</div>

<!-- Tab Switcher and Filter JavaScript Scripts -->
<script>
    // Tab controller
    function switchTab(tabId) {
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.style.display = 'none';
            pane.classList.remove('active');
        });
        document.querySelectorAll('.admin-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        const activePane = document.getElementById(tabId);
        if (activePane) {
            activePane.style.display = 'block';
            activePane.classList.add('active');
        }

        // Highlight matching button
        const clickedBtn = Array.from(document.querySelectorAll('.admin-tab-btn')).find(btn => btn.getAttribute('onclick').includes(tabId));
        if (clickedBtn) clickedBtn.classList.add('active');
    }

    // Instant Client Side Search filter
    function filterTable(tbodyId, query) {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        const rows = tbody.getElementsByTagName('tr');
        const cleanQuery = query.toLowerCase().trim();

        for (let i = 0; i < rows.length; i++) {
            let rowText = rows[i].textContent.toLowerCase();
            if (rowText.includes(cleanQuery)) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }

    /* ============================================================================
       MODAL CONTROLLERS & DATA FILL
       ============================================================================ */

    // Custom Multiselect helpers
    function toggleMultiselectDropdown(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('user-roles-multiselect');
        dropdown.classList.toggle('open');
    }

    // Close on click outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('user-roles-multiselect');
        if (dropdown && !dropdown.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });

    function filterMultiselectOptions(query) {
        const cleanQuery = query.toLowerCase().trim();
        const options = document.querySelectorAll('.multiselect-option');
        options.forEach(opt => {
            const text = opt.querySelector('span').textContent.toLowerCase();
            if (text.includes(cleanQuery)) {
                opt.style.display = 'flex';
            } else {
                opt.style.display = 'none';
            }
        });
    }

    function updateMultiselectTags() {
        const container = document.getElementById('multiselect-tags-container');
        const placeholder = document.querySelector('.multiselect-placeholder');
        if (!container || !placeholder) return;
        container.innerHTML = '';
        
        const checkedOptions = document.querySelectorAll('.user-role-chk:checked');
        if (checkedOptions.length === 0) {
            placeholder.style.display = 'block';
        } else {
            placeholder.style.display = 'none';
            checkedOptions.forEach(chk => {
                const label = chk.closest('.multiselect-option').querySelector('span').textContent;
                const roleId = chk.value;
                
                const tag = document.createElement('div');
                tag.className = 'multiselect-tag';
                tag.innerHTML = `
                    <span>${label}</span>
                    <i class="fa-solid fa-xmark multiselect-tag-close" onclick="removeMultiselectTag(${roleId}, event)"></i>
                `;
                container.appendChild(tag);
            });
        }
    }

    function removeMultiselectTag(roleId, event) {
        event.stopPropagation();
        const chk = document.querySelector(`.user-role-chk[value="${roleId}"]`);
        if (chk) {
            chk.checked = false;
            updateMultiselectTags();
        }
    }

    function openUserModal(user = null) {
        const modal = document.getElementById('user-modal');
        const form = document.getElementById('user-form');
        const title = document.getElementById('user-modal-title');
        const passGroup = document.getElementById('user-pass-group');
        const passInput = document.getElementById('user-contrasena');

        // Clear checkboxes
        document.querySelectorAll('.user-role-chk').forEach(chk => chk.checked = false);

        if (user) {
            // EDIT MODE
            title.innerHTML = '<i data-lucide="edit-3"></i> Editar Colaborador APM';
            document.getElementById('user-id').value = user.id_usuario;
            document.getElementById('user-nombre').value = user.nombre_completo;
            document.getElementById('user-nombre-usuario').value = user.nombre_usuario;
            document.getElementById('user-correo').value = user.correo;
            document.getElementById('user-telefono').value = user.telefono || '';
            document.getElementById('user-cargo').value = user.cargo_institucional || '';
            document.getElementById('user-depto').value = user.id_dep_principal || '';
            document.getElementById('user-activo').value = user.activo;

            // Password is not required when editing
            passGroup.style.display = 'none';
            passInput.removeAttribute('required');

            // Set checkboxes for active roles
            if (user.roles) {
                const assignedRoles = user.roles.split(' | ');
                document.querySelectorAll('.user-role-chk').forEach(chk => {
                    const roleLabel = chk.closest('.multiselect-option').querySelector('span').textContent;
                    if (assignedRoles.includes(roleLabel)) {
                        chk.checked = true;
                    }
                });
            }
        } else {
            // NEW MODE
            title.innerHTML = '<i data-lucide="user-plus"></i> Registrar Nuevo Usuario';
            form.reset();
            document.getElementById('user-id').value = '';
            passGroup.style.display = 'flex';
            passInput.setAttribute('required', 'required');
        }

        // Draw visual tags
        updateMultiselectTags();

        modal.classList.add('show');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeUserModal() {
        document.getElementById('user-modal').classList.remove('show');
    }

    function openRolModal(rol = null) {
        const modal = document.getElementById('rol-modal');
        const form = document.getElementById('rol-form');
        const title = document.getElementById('rol-modal-title');

        if (rol) {
            // EDIT MODE
            title.innerHTML = '<i data-lucide="edit-3"></i> Modificar Perfil de Seguridad';
            document.getElementById('rol-id').value = rol.id_grupo_rol;
            document.getElementById('rol-nombre').value = rol.nombre_grupo_rol;
            document.getElementById('rol-descripcion').value = rol.descripcion || '';
            document.getElementById('rol-depto').value = rol.id_dep_modulo || '';
            document.getElementById('rol-jerarquia').value = rol.nivel_jerarquia;
            document.getElementById('rol-activo').value = rol.activo;
        } else {
            // NEW MODE
            title.innerHTML = '<i data-lucide="shield-alert"></i> Crear Perfil/Rol';
            form.reset();
            document.getElementById('rol-id').value = '';
        }

        modal.classList.add('show');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeRolModal() {
        document.getElementById('rol-modal').classList.remove('show');
    }

    /* ============================================================================
       AJAX PERMISSION LOADER, SEGMENTED MATRIX SYNC & TELEMETRY
       ============================================================================ */

    let currentMenuRolId = null;
    let currentFormRolId = null;
    let menuPermissionsState = {};
    let formPermissionsState = {};

    // Select Menu Role
    function selectMenuRole(rolId, nombre, isAdmin) {
        currentMenuRolId = rolId;
        
        // Highlight active role card in UI
        document.querySelectorAll('.menu-role-card').forEach(card => card.classList.remove('selected'));
        const selectedCard = document.getElementById('menu-role-card-' + rolId);
        if (selectedCard) selectedCard.classList.add('selected');
        
        // Toggle view container visibilities
        document.getElementById('menu-matrix-placeholder').style.display = 'none';
        document.getElementById('menu-matrix-form-wrap').style.display = 'block';
        
        // Update header info text
        document.getElementById('menu-matrix-rol-name').innerText = nombre;
        
        const warning = document.getElementById('menu-admin-warning');
        const rowList = document.getElementById('menu-matrix-rows-list');
        
        if (isAdmin) {
            warning.style.display = 'block';
            // Disable segmented options since admin has global access bypass
            rowList.querySelectorAll('.segmented-btn').forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.cursor = 'not-allowed';
            });
            
            // Render all menu options visually at full access (Operar) for admin role
            rowList.querySelectorAll('.segmented-control').forEach(ctrl => {
                ctrl.querySelectorAll('.segmented-btn').forEach(btn => btn.classList.remove('active'));
                const operateBtn = ctrl.querySelector('.segmented-btn.write-access');
                if (operateBtn) operateBtn.classList.add('active');
            });
        } else {
            warning.style.display = 'none';
            rowList.querySelectorAll('.segmented-btn').forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '';
                btn.style.cursor = '';
            });
            
            // Query DB permissions for selected role
            loadMenuPermissions(rolId);
        }
    }

    // AJAX load menu permissions
    function loadMenuPermissions(rolId) {
        const overlay = document.getElementById('menu-matrix-overlay');
        overlay.classList.add('show');
        
        fetch((window.APM_BASE_URL || '') + '/control-acceso/permiso-menu?id_grupo_rol=' + rolId)
            .then(res => res.json())
            .then(data => {
                overlay.classList.remove('show');
                if (data.success && data.permisos) {
                    menuPermissionsState = data.permisos;
                    let activeCount = 0;
                    
                    document.querySelectorAll('[id^="control-menu-"]').forEach(ctrl => {
                        const menuId = ctrl.id.replace('control-menu-', '');
                        const val = data.permisos[menuId] !== undefined ? parseInt(data.permisos[menuId]) : 0;
                        if (val > 0) activeCount++;
                        
                        ctrl.querySelectorAll('.segmented-btn').forEach(btn => btn.classList.remove('active'));
                        const targetBtn = ctrl.querySelector(`.segmented-btn[data-val="${val}"]`);
                        if (targetBtn) targetBtn.classList.add('active');
                    });
                    
                    const badge = document.getElementById('menu-badge-' + rolId);
                    if (badge) badge.innerText = activeCount;
                }
            })
            .catch(err => {
                overlay.classList.remove('show');
                console.error('Error loading menu permissions:', err);
            });
    }

    // Save menu segment selection instantly via AJAX
    function saveMenuSegment(menuId, val) {
        if (!currentMenuRolId) return;
        
        const ctrl = document.getElementById('control-menu-' + menuId);
        if (!ctrl) return;
        
        // Optimistic UI updates
        ctrl.querySelectorAll('.segmented-btn').forEach(btn => btn.classList.remove('active'));
        const targetBtn = ctrl.querySelector(`.segmented-btn[data-val="${val}"]`);
        if (targetBtn) targetBtn.classList.add('active');
        
        // Save state locally
        menuPermissionsState[menuId] = val;
        
        // Instantly recalculate permission telemetry count badge
        let activeCount = 0;
        Object.keys(menuPermissionsState).forEach(k => {
            if (parseInt(menuPermissionsState[k]) > 0) activeCount++;
        });
        const badge = document.getElementById('menu-badge-' + currentMenuRolId);
        if (badge) badge.innerText = activeCount;
        
        const baseUrl = window.APM_BASE_URL || '';
        const formData = new FormData();
        formData.append('id_grupo_rol', currentMenuRolId);
        
        // Send full array state to prevent deletions in transactional SP
        Object.keys(menuPermissionsState).forEach(k => {
            formData.append(`permisos[${k}]`, menuPermissionsState[k]);
        });
        
        fetch(baseUrl + '/control-acceso/permiso-menu', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Glow telemetry success save indicator badge
                const indicator = document.getElementById('menu-saving-indicator');
                indicator.classList.add('show');
                setTimeout(() => indicator.classList.remove('show'), 1800);
            } else {
                console.error('Error saving menu permission:', data.error);
            }
        })
        .catch(err => console.error('Save menu permission request failed:', err));
    }

    // Select Form Role
    function selectFormRole(rolId, nombre, isAdmin) {
        currentFormRolId = rolId;
        
        // Highlight active role card in UI
        document.querySelectorAll('.form-role-card').forEach(card => card.classList.remove('selected'));
        const selectedCard = document.getElementById('form-role-card-' + rolId);
        if (selectedCard) selectedCard.classList.add('selected');
        
        // Toggle view container visibilities
        document.getElementById('form-matrix-placeholder').style.display = 'none';
        document.getElementById('form-matrix-form-wrap').style.display = 'block';
        
        // Update header info text
        document.getElementById('form-matrix-rol-name').innerText = nombre;
        
        const warning = document.getElementById('form-admin-warning');
        const rowList = document.getElementById('form-matrix-rows-list');
        
        if (isAdmin) {
            warning.style.display = 'block';
            // Disable segmented options since admin has global access bypass
            rowList.querySelectorAll('.segmented-btn').forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.cursor = 'not-allowed';
            });
            
            // Render all form options visually at full access (Borrar) for admin role
            rowList.querySelectorAll('.segmented-control').forEach(ctrl => {
                ctrl.querySelectorAll('.segmented-btn').forEach(btn => btn.classList.remove('active'));
                const deleteBtn = ctrl.querySelector('.segmented-btn.delete-access');
                if (deleteBtn) deleteBtn.classList.add('active');
            });
        } else {
            warning.style.display = 'none';
            rowList.querySelectorAll('.segmented-btn').forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '';
                btn.style.cursor = '';
            });
            
            // Query DB permissions for selected role
            loadFormPermissions(rolId);
        }
    }

    // AJAX load form permissions
    function loadFormPermissions(rolId) {
        const overlay = document.getElementById('form-matrix-overlay');
        overlay.classList.add('show');
        
        fetch((window.APM_BASE_URL || '') + '/control-acceso/permiso-formulario?id_grupo_rol=' + rolId)
            .then(res => res.json())
            .then(data => {
                overlay.classList.remove('show');
                if (data.success && data.permisos) {
                    formPermissionsState = data.permisos;
                    let activeCount = 0;
                    
                    document.querySelectorAll('[id^="control-form-"]').forEach(ctrl => {
                        const formId = ctrl.id.replace('control-form-', '');
                        const val = data.permisos[formId] !== undefined ? parseInt(data.permisos[formId]) : 0;
                        if (val > 0) activeCount++;
                        
                        ctrl.querySelectorAll('.segmented-btn').forEach(btn => btn.classList.remove('active'));
                        const targetBtn = ctrl.querySelector(`.segmented-btn[data-val="${val}"]`);
                        if (targetBtn) targetBtn.classList.add('active');
                    });
                    
                    const badge = document.getElementById('form-badge-' + rolId);
                    if (badge) badge.innerText = activeCount;
                }
            })
            .catch(err => {
                overlay.classList.remove('show');
                console.error('Error loading form permissions:', err);
            });
    }

    // Save form segment selection instantly via AJAX
    function saveFormSegment(formId, val) {
        if (!currentFormRolId) return;
        
        const ctrl = document.getElementById('control-form-' + formId);
        if (!ctrl) return;
        
        // Optimistic UI updates
        ctrl.querySelectorAll('.segmented-btn').forEach(btn => btn.classList.remove('active'));
        const targetBtn = ctrl.querySelector(`.segmented-btn[data-val="${val}"]`);
        if (targetBtn) targetBtn.classList.add('active');
        
        // Save state locally
        formPermissionsState[formId] = val;
        
        // Instantly recalculate permission telemetry count badge
        let activeCount = 0;
        Object.keys(formPermissionsState).forEach(k => {
            if (parseInt(formPermissionsState[k]) > 0) activeCount++;
        });
        const badge = document.getElementById('form-badge-' + currentFormRolId);
        if (badge) badge.innerText = activeCount;
        
        const baseUrl = window.APM_BASE_URL || '';
        const formData = new FormData();
        formData.append('id_grupo_rol', currentFormRolId);
        
        // Send full array state to prevent deletions in transactional SP
        Object.keys(formPermissionsState).forEach(k => {
            formData.append(`permisos[${k}]`, formPermissionsState[k]);
        });
        
        fetch(baseUrl + '/control-acceso/permiso-formulario', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Glow telemetry success save indicator badge
                const indicator = document.getElementById('form-saving-indicator');
                indicator.classList.add('show');
                setTimeout(() => indicator.classList.remove('show'), 1800);
            } else {
                console.error('Error saving form permission:', data.error);
            }
        })
        .catch(err => console.error('Save form permission request failed:', err));
    }

    // Global Initialization & Pre-fetching Badge Telemetry Counts
    document.addEventListener('DOMContentLoaded', () => {
        window.APM_BASE_URL = '<?= $baseUrl ?>';
        
        // Pre-fetch permission badge numbers for all non-admin roles
        const roleIds = [];
        document.querySelectorAll('.menu-role-card').forEach(card => {
            const id = parseInt(card.id.replace('menu-role-card-', ''));
            roleIds.push(id);
        });
        
        roleIds.forEach(id => {
            if (id === 1) {
                // Superuser indicators
                const mBadge = document.getElementById('menu-badge-1');
                if (mBadge) mBadge.innerText = '★';
                const fBadge = document.getElementById('form-badge-1');
                if (fBadge) fBadge.innerText = '★';
                return;
            }
            
            // Pre-load Menu badge counts
            fetch(window.APM_BASE_URL + '/control-acceso/permiso-menu?id_grupo_rol=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.permisos) {
                        let cnt = 0;
                        Object.keys(data.permisos).forEach(k => { if (data.permisos[k] > 0) cnt++; });
                        const badge = document.getElementById('menu-badge-' + id);
                        if (badge) badge.innerText = cnt;
                    }
                })
                .catch(err => console.warn('Could not pre-fetch menu badge for role ID ' + id));
                
            // Pre-load Form badge counts
            fetch(window.APM_BASE_URL + '/control-acceso/permiso-formulario?id_grupo_rol=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.permisos) {
                        let cnt = 0;
                        Object.keys(data.permisos).forEach(k => { if (data.permisos[k] > 0) cnt++; });
                        const badge = document.getElementById('form-badge-' + id);
                        if (badge) badge.innerText = cnt;
                    }
                })
                .catch(err => console.warn('Could not pre-fetch form badge for role ID ' + id));
        });
    });
</script>
