  <!-- DataTables, Select2 & Mask JS -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var sidebar = document.getElementById('sidebar');
      var toggleBtn = document.getElementById('toggleBtn');
      toggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        sidebar.classList.toggle('expanded');
      });
      document.querySelectorAll('.has-submenu > .menu-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
          e.stopPropagation();
          this.parentElement.classList.toggle('expanded');
        });
      });
      // Initialize Select2
      $('select').select2({ width: '100%' });
    });
  </script>
</body>
</html>
