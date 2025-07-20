  <!-- DataTables, Select2 & Mask JS -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
  <script>
      document.addEventListener('DOMContentLoaded', function() {
        var sidebar = document.getElementById('sidebar');
        var toggleBtn = document.getElementById('toggleBtn');

        toggleBtn.addEventListener('click', function(e) {
          e.stopPropagation();
          sidebar.classList.toggle('expanded');
        });

        // Open sidebar on first click when collapsed
        sidebar.querySelectorAll('.menu-item').forEach(function(mi) {
          mi.addEventListener('click', function(e) {
            if (!sidebar.classList.contains('expanded')) {
              e.preventDefault();
              sidebar.classList.add('expanded');
            }
          });
        });

        document.querySelectorAll('.has-submenu > .menu-item').forEach(function(item) {
          item.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!sidebar.classList.contains('expanded')) {
              sidebar.classList.add('expanded');
              return;
            }
            this.parentElement.classList.toggle('expanded');
          });
        });
        $('select').select2({ width: '100%' });
        $('input[name*=cpf],input[id*=cpf]').mask('000.000.000-00');
        $('input[name*=cnpj],input[id*=cnpj]').mask('00.000.000/0000-00');
        $('input[data-mask=telefone]').mask('(00)0.0000-0000');
        $('input.money,input[name*=valor]').mask('#.##0,00', {reverse:true});
        $('input[data-mask=percent],input[name*=juros]').mask('##0,00', {reverse:true});
        $('input[name*=cep],input[id*=cep]').mask('00.000-000').on('blur', function(){
          var cep=this.value.replace(/\D/g,'');
          if(cep.length!==8) return;
          var form=this.form;
          fetch('https://viacep.com.br/ws/'+cep+'/json/')
            .then(r=>r.json())
            .then(function(d){
              if(d.erro) return;
              if(form){
                $(form).find('input[name=rua],input[name=rua_entrega],input[name=endereco]').val(d.logradouro || '');
                $(form).find('input[name=bairro],input[name=bairro_entrega]').val(d.bairro || '');
                fetch('busca_cidade.php?cidade='+encodeURIComponent(d.localidade)+'&uf='+d.uf)
                  .then(r=>r.json())
                  .then(function(c){
                    if(c.id){
                      $(form).find('select[name=id_cidade]').val(c.id).trigger('change');
                    }
                  });
              }
            });
        });
        $('table.display').DataTable({
          responsive: true,
          language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
          }
        });
      });
  </script>
</body>
</html>
