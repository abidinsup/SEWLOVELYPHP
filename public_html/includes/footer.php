    </div> <!-- End Main Flex Wrapper -->

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
    
    <!-- Custom Scripts -->
    <?php if (file_exists(__DIR__ . '/../assets/js/main.js')): ?>
        <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <?php endif; ?>
</body>
</html>
