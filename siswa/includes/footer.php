                </div><!-- /.main-content -->
            </div><!-- /.app-wrapper -->
        </div><!-- /.container -->
    </div><!-- /.app-container -->

<script>
    // Add any additional JavaScript here
    document.addEventListener('DOMContentLoaded', function() {
        // Hide loading animation after page is loaded
        const pageLoading = document.getElementById('pageLoading');
        if (pageLoading) {
            pageLoading.classList.add('loaded');
            setTimeout(function() {
                pageLoading.style.display = 'none';
            }, 300);
        }
    });
</script>
</body>
</html> 