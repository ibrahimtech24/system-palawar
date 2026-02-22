    </main>
    
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- html2pdf for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo isset($basePath) ? $basePath : ''; ?>js/main.js"></script>
    
    <script>
        // Initialize DataTables with Kurdish language
        $(document).ready(function() {
            if ($.fn.DataTable) {
                $('.data-table').DataTable({
                    language: {
                        search: "گەڕان:",
                        lengthMenu: "پیشاندانی _MENU_ ڕیز",
                        info: "پیشاندانی _START_ تا _END_ لە _TOTAL_ ڕیز",
                        infoEmpty: "هیچ داتایەک نیە",
                        infoFiltered: "(فلتەرکراو لە _MAX_ ڕیز)",
                        zeroRecords: "هیچ ڕیزێک نەدۆزرایەوە",
                        paginate: {
                            first: "یەکەم",
                            last: "کۆتایی",
                            next: "دواتر",
                            previous: "پێشتر"
                        }
                    },
                    pageLength: 10,
                    responsive: true,
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
                });
            }
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
</body>
</html>
