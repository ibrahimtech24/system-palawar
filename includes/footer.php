    </main>
    
    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white py-2">
                    <h6 class="modal-title mb-0"><i class="fas fa-exclamation-triangle me-1"></i> <span id="confirmTitle">دڵنیایت؟</span></h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-3">
                    <i class="fas fa-trash-alt text-danger" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0" id="confirmMessage">ئایا دڵنیایت لە سڕینەوەی ئەم بابەتە؟</p>
                </div>
                <div class="modal-footer justify-content-center py-2 border-0">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> نەخێر
                    </button>
                    <a href="#" id="confirmAction" class="btn btn-danger btn-sm px-3">
                        <i class="fas fa-check"></i> بەڵێ
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast/Snackbar Container -->
    <div class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3" style="z-index: 9999;">
        <div id="toastNotification" class="toast align-items-center border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i id="toastIcon" class="fas fa-check-circle"></i>
                    <span id="toastMessage">سەرکەوتوو بوو</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    
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
            
            // Bootstrap form validation
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });
        
        // Global confirmation dialog function
        function confirmDelete(url, message = 'ئایا دڵنیایت لە سڕینەوەی ئەم بابەتە؟', title = 'دڵنیایت؟') {
            document.getElementById('confirmTitle').textContent = title;
            document.getElementById('confirmMessage').textContent = message;
            document.getElementById('confirmAction').href = url;
            var modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            modal.show();
            return false;
        }
        
        // Alias for general confirmation
        function confirmAction(url, message, title = 'دڵنیایت؟') {
            return confirmDelete(url, message, title);
        }
        
        // Toast/Snackbar notification function
        function showToast(message, type = 'success') {
            var toast = document.getElementById('toastNotification');
            var toastMessage = document.getElementById('toastMessage');
            var toastIcon = document.getElementById('toastIcon');
            
            toastMessage.textContent = message;
            
            // Remove old classes
            toast.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white');
            toastIcon.classList.remove('fa-check-circle', 'fa-times-circle', 'fa-exclamation-circle', 'fa-info-circle');
            
            // Set icon and color based on type
            switch(type) {
                case 'success':
                    toast.classList.add('bg-success', 'text-white');
                    toastIcon.classList.add('fa-check-circle');
                    break;
                case 'error':
                    toast.classList.add('bg-danger', 'text-white');
                    toastIcon.classList.add('fa-times-circle');
                    break;
                case 'warning':
                    toast.classList.add('bg-warning', 'text-white');
                    toastIcon.classList.add('fa-exclamation-circle');
                    break;
                case 'info':
                    toast.classList.add('bg-info', 'text-white');
                    toastIcon.classList.add('fa-info-circle');
                    break;
            }
            
            var bsToast = new bootstrap.Toast(toast, { delay: 1500 });
            bsToast.show();
        }
        
        // Check for success/error messages in URL
        $(document).ready(function() {
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                showToast('کردارەکە بە سەرکەوتوویی ئەنجام درا', 'success');
                // Remove parameter from URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            if (urlParams.has('deleted')) {
                showToast('سڕینەوە بە سەرکەوتوویی ئەنجام درا', 'success');
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            if (urlParams.has('error')) {
                showToast('هەڵەیەک ڕوویدا', 'error');
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>
</html>
