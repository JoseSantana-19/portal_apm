<?php
/**
 * Global Footer Component. Closes layout containers, initialises icons and JS.
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = dirname($scriptName);
$baseUrl = str_replace('\\', '/', $baseUrl);
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}
?>
        </div> <!-- Closes .content-body -->
    </div> <!-- Closes .content-area -->
    </div> <!-- Closes .main-workspace -->
    </div> <!-- Closes .app-shell -->

    <!-- Main JavaScript files -->
    <script>
        // Set base path globally so Javascript is 100% aware of subfolders under XAMPP
        window.APM_BASE_URL = '<?= $baseUrl ?>';
    </script>
    <script src="<?= $baseUrl ?>/js/main.js"></script>
    <script>
        // Sidebar accordion toggle
        function toggleSidebarModule(modId) {
            const header = document.getElementById('smhdr-' + modId);
            const tree = document.getElementById('smtree-' + modId);
            if (!header || !tree) return;
            const isOpen = tree.classList.contains('open');
            
            // Accordion: collapse all other trees
            document.querySelectorAll('.sm-header').forEach(h => {
                if (h.id !== 'smhdr-' + modId) h.classList.remove('open');
            });
            document.querySelectorAll('.sm-tree').forEach(t => {
                if (t.id !== 'smtree-' + modId) t.classList.remove('open');
            });
            
            if (isOpen) {
                header.classList.remove('open');
                tree.classList.remove('open');
            } else {
                header.classList.add('open');
                tree.classList.add('open');
            }
            
            // Refresh lucide icons in newly visible elements
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // Trigger lucide on initial load
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>
</html>
