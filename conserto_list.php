<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'permissions.php';

$pageTitle = 'Consertos';

$sql = "SELECT c.ID,
               cl.NOME AS CLIENTE,
               u.NOME AS USUARIO,
               e.NOME AS EMPRESA,
               cl.CONTATO AS TELEFONE_CONTATO,
               DATE_FORMAT(c.DATA_ENTRADA,'%d/%m/%Y') AS DATA_ENTRADA,
               DATE_FORMAT(c.PREVISAO_ENTREGA,'%d/%m/%Y') AS PREVISAO_ENTRADA,
               c.PROBLEMA_DESCRITO,
               c.DURACAO_FINAL_SEGUNDOS,
               c.SITUACAO,
               UNIX_TIMESTAMP(t.INICIO) AS INICIO_TS
        FROM CONCERTO_OCULOS c
        LEFT JOIN CLIENTE cl ON cl.ID=c.ID_CLIENTE
        LEFT JOIN USUARIO u  ON u.ID=c.ID_USUARIO
        LEFT JOIN EMPRESA e  ON e.ID=c.ID_EMPRESA
        LEFT JOIN CONCERTO_OCULOS_TEMPO t ON t.ID_CONCERTO=c.ID AND t.FIM IS NULL";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$canDelete = hasRole('ADMINISTRADOR');
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4">Controle de Consertos</h2>
  <a href="conserto_form.php" class="bg-primary text-white px-4 py-2 rounded mb-4 inline-block">Novo Concerto</a>
  <table id="concertoTable" class="display w-full">
    <thead>
      <tr>
        <th>Ações</th><th>ID</th><th>Cliente</th><th>Usuário</th><th>Empresa</th>
        <th>Contato</th><th>Data Entrada</th><th>Previsão Entrega</th><th>Problema</th><th>Status</th>
        <th>Opções</th><th>Tempo</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr data-id="<?= $r['ID'] ?>" data-start="<?= $r['INICIO_TS'] ?>" data-total="<?= (int)$r['DURACAO_FINAL_SEGUNDOS'] ?>">
        <td class="table-actions">
          <a href="conserto_form.php?id=<?= $r['ID'] ?>" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
          <?php if($canDelete): ?>
          <a href="conserto_delete.php?id=<?= $r['ID'] ?>" onclick="return confirm('Excluir este conserto?');" class="delete" title="Deletar"><i class="fas fa-trash-alt"></i></a>
          <?php endif; ?>
        </td>
        <td><?= $r['ID'] ?></td>
        <td><?= htmlspecialchars($r['CLIENTE']) ?></td>
        <td><?= htmlspecialchars($r['USUARIO']) ?></td>
        <td><?= htmlspecialchars($r['EMPRESA']) ?></td>
        <td><?= htmlspecialchars($r['TELEFONE_CONTATO']) ?></td>
        <td><?= $r['DATA_ENTRADA'] ?></td>
        <td><?= $r['PREVISAO_ENTRADA'] ?></td>
        <td><?= htmlspecialchars($r['PROBLEMA_DESCRITO']) ?></td>
        <td>
          <div class="flex items-center gap-1">
            <select id="sit_<?= $r['ID'] ?>" class="border p-1 rounded status-select bg-opacity-20">
              <?php foreach(['PENDENTE','APROVADO','CANCELADO','ORÇAMENTO','ENTREGUE'] as $s): ?>
                <option value="<?= $s ?>" <?= $r['SITUACAO']==$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
            <button onclick="saveSit(<?= $r['ID'] ?>)" class="text-green-700 hover:text-green-900"><i class="fas fa-check"></i></button>
          </div>
        </td>
        <td class="acoes flex gap-2"></td>
        <td class="tempo">00:00:00</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<script>
function formatSeg(s){
  s=Math.floor(s);let h=Math.floor(s/3600);let m=Math.floor((s%3600)/60);let se=s%60;
  return String(h).padStart(2,'0')+':'+String(m).padStart(2,'0')+':'+String(se).padStart(2,'0');
}

document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('#concertoTable tbody tr').forEach(tr=>{
    const id=tr.dataset.id;
    const tempoEl=tr.querySelector('.tempo');
    const acaoEl=tr.querySelector('.acoes');
    let total=parseInt(tr.dataset.total)||0;
    let start=tr.dataset.start?parseInt(tr.dataset.start)*1000:null;
    let timer=null;

    function update(){
      const now=Date.now();
      const val=start?total+((now-start)/1000):total;
      tempoEl.textContent=formatSeg(val);
    }
    function render(){
      acaoEl.innerHTML='';
      if(start){
        acaoEl.innerHTML=`<button class="pause text-blue-600 px-2"><i class="fas fa-pause"></i></button><button class="stop text-red-600 px-2"><i class="fas fa-stop"></i></button>`;
        timer=setInterval(update,1000);
      }else{
        const disabled=tr.dataset.stopped==='1';
        acaoEl.innerHTML=`<button class="start text-green-600 px-2"${disabled?' disabled':''}><i class="fas fa-play"></i></button><button class="stop text-red-600 px-2"${disabled?' disabled':''}><i class="fas fa-stop"></i></button>`;
        clearInterval(timer); timer=null;
      }
      update();
    }
    function call(action){
      fetch('conserto_tempo.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`id=${id}&action=${action}`})
      .then(r=>r.json()).then(d=>{
        if(!d.success){alert(d.error||'Erro');return;}
        total=d.total;
        if(action==='start'){start=Date.now();}
        if(action==='pause'){start=null;}
        if(action==='stop'){start=null;tr.dataset.stopped='1';}
        render();
      });
    }
    acaoEl.addEventListener('click',e=>{
      if(e.target.closest('.start')) call('start');
      if(e.target.closest('.pause')) call('pause');
      if(e.target.closest('.stop')) call('stop');
  });
  render();
  });
});

function saveSit(id){
  const sel=document.getElementById('sit_'+id);
  fetch('conserto_update_status.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`id=${id}&status=${encodeURIComponent(sel.value)}`})
    .then(r=>r.json().catch(()=>{throw new Error('Resposta inválida do servidor');}))
    .then(d=>{ if(d.success){ setColor(sel); alert('Status atualizado'); } else { alert('Erro: '+(d.error||'')); } })
    .catch(err=>{ alert('Erro: '+err.message); });
}
function setColor(sel){
  sel.classList.remove('bg-green-100','bg-red-100','bg-blue-100','bg-yellow-100','text-green-800','text-red-800','text-blue-800','text-yellow-800');
  switch(sel.value){
    case 'APROVADO': sel.classList.add('bg-green-100','text-green-800'); break;
    case 'ENTREGUE': sel.classList.add('bg-green-200','text-green-800'); break;
    case 'CANCELADO': sel.classList.add('bg-red-100','text-red-800'); break;
    case 'ORÇAMENTO': sel.classList.add('bg-blue-100','text-blue-800'); break;
    default: sel.classList.add('bg-yellow-100','text-yellow-800');
  }
}
document.querySelectorAll('.status-select').forEach(setColor);
</script>
<?php include 'footer.php'; ?>
