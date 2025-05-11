                </div> <!-- End of container-fluid -->
            </div> <!-- End of content wrapper -->
        </div> <!-- End of row -->
    </div> <!-- End of container-fluid -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS if needed -->
    <?php if (isset($use_datatables) && $use_datatables): ?>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <?php endif; ?>
    
    <!-- Custom JS -->
    <script src="../js/script.js"></script>
    
    <?php if (isset($page_specific_js)): ?>
    <?php echo $page_specific_js; ?>
    <?php else: ?>
    <script>
        $(document).ready(function() {
            // Initialize DataTables if present
            if ($.fn.DataTable && $('.datatable').length > 0) {
                $('.datatable').DataTable({
                    responsive: true,
                    order: [[0, 'desc']]
                });
            }
            
            // Add smooth fade-in animations to cards
            setTimeout(function() {
                $('.card').each(function(index) {
                    setTimeout(function(card) {
                        $(card).addClass('fade-in');
                    }, index * 100, this);
                });
            }, 300);
            
            // Animated counters for statistics
            $('.card .text-gray-800').each(function() {
                const $this = $(this);
                const countTo = parseInt($this.text());
                
                if (!isNaN(countTo) && countTo > 0) {
                    $({ countNum: 0 }).animate({
                        countNum: countTo
                    }, {
                        duration: 1000,
                        easing: 'swing',
                        step: function() {
                            $this.text(Math.floor(this.countNum));
                        },
                        complete: function() {
                            $this.text(this.countNum);
                        }
                    });
                }
            });
        });
    </script>
    <?php endif; ?>
</body>
</html> 