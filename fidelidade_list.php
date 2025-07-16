<?php
$pageTitle = 'Fidelidade Cliente';
include 'header.php';

// fetch fidelity data
$sql = "SELECT f.ID_CLIENTE,c.NOME,c.CONTATO,f.PONTOS,DATE_FORMAT(f.ULTIMA_ATUALIZACAO,'%d/%m/%Y') AS ULTIMA_ATUALIZACAO
        FROM FIDELIDADE_CLIENTE f
        JOIN CLIENTE c ON c.ID=f.ID_CLIENTE";
$items = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// products for brinde category
$brindes = $pdo->query("SELECT p.ID,p.NOME FROM PRODUTO p JOIN CATEGORIA_PRODUTO c ON p.ID_CATEGORIA=c.ID WHERE c.NOME='BRINDE'")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mx-auto">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold">Fidelidade de Clientes</h2>
  </div>
  <table id="fidTable" class="display w-full">
    <thead>
      <tr><th>Cliente</th><th>Contato</th><th>Pontos</th><th>Última Atualização</th><th>Ações</th></tr>
    </thead>
    <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['NOME']) ?></td>
        <td><?= htmlspecialchars($it['CONTATO']) ?></td>
        <td><?= $it['PONTOS'] ?></td>
        <td><?= $it['ULTIMA_ATUALIZACAO'] ?></td>
        <td class="table-actions">
          <a href="#" class="troca edit" data-id="<?= $it['ID_CLIENTE'] ?>" title="Trocar Pontos"><i class="fas fa-exchange-alt"></i></a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<div id="modalTroca" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
  <div class="bg-white p-4 rounded w-96 shadow-lg">
    <h3 class="text-lg mb-2">Trocar Pontos</h3>
    <form id="trocaForm" class="space-y-2">
      <input type="hidden" name="id_cliente" id="trocaCliente">
      <div id="brindeRows"></div>
      <button type="button" id="addBrinde" class="mt-2 px-2 py-1 bg-gray-300 rounded">+</button>
      <div class="text-right mt-4">
        <button type="button" class="mr-2 px-3 py-1" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="bg-primary text-white px-3 py-1 rounded">Concluir</button>
      </div>
    </form>
  </div>
</div>
<script>
const brindes = <?= json_encode($brindes) ?>;
function rowHtml(){
  return `<div class="flex gap-2 brindeRow">
    <select name="produto_id[]" class="border p-1 rounded flex-1">
      <option value="">Selecione</option>
      ${brindes.map(b=>`<option value="${b.ID}">${b.NOME}</option>`).join('')}
    </select>
    <input type="number" name="pontos[]" class="border p-1 w-20" placeholder="Pontos">
    <button type="button" class="removeBrinde text-red-600">-</button>
  </div>`;
}
function openModal(id){
  document.getElementById('trocaCliente').value=id;
  document.getElementById('brindeRows').innerHTML=rowHtml();
  document.getElementById('modalTroca').classList.remove('hidden');
}
function closeModal(){ document.getElementById('modalTroca').classList.add('hidden'); }

$(function(){
  $(document).on('click','.troca',function(e){
    e.preventDefault();
    openModal($(this).data('id'));
  });
  $('#addBrinde').on('click',function(){
    $('#brindeRows').append(rowHtml());
  });
  $(document).on('click','.removeBrinde',function(){
    $(this).closest('.brindeRow').remove();
  });
  $('#trocaForm').on('submit',function(e){
    e.preventDefault();
    fetch('fidelidade_exchange.php',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams(new FormData(this))
    })
    .then(r=>r.json())
    .then(d=>{ if(d.success){ alert('Troca realizada'); location.reload(); } else alert(d.error||'Erro'); });
  });
});
</script>
<?php include 'footer.php'; ?>
