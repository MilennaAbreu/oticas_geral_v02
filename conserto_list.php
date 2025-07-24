<?php
require_once 'config.php';
require_once 'auth.php';

$pageTitle = 'Consertos';

$sql = "SELECT c.ID,
               cl.NOME AS CLIENTE,
               u.NOME AS USUARIO,
               e.NOME AS EMPRESA,
               c.TELEFONE_CONTATO,
               DATE_FORMAT(c.DATA_ENTRADA,'%d/%m/%Y') AS DATA_ENTRADA,
               DATE_FORMAT(c.PREVISAO_ENTREGA,'%d/%m/%Y') AS PREVISAO_ENTRADA,
               c.DURACAO_FINAL_SEGUNDOS,
               t.INICIO
        FROM CONCERTO_OCULOS c
        LEFT JOIN CLIENTE cl ON cl.ID=c.ID_CLIENTE
        LEFT JOIN USUARIO u  ON u.ID=c.ID_USUARIO
        LEFT JOIN EMPRESA e  ON e.ID=c.ID_EMPRESA
        LEFT JOIN CONCERTO_OCULOS_TEMPO t ON t.ID_CONCERTO=c.ID AND t.FIM IS NULL";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4">Controle de Consertos</h2>
  <table id="concertoTable" class="display w-full">
    <thead>
      <tr>
        <th>ID</th><th>Cliente</th><th>Usuário</th><th>Empresa</th>
        <th>Contato</th><th>Data Entrada</th><th>Previsão Entrega</th>
        <th>Ações</th><th>Tempo</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr data-id="<?= $r['ID'] ?>" data-start="<?= $r['INICIO'] ?>" data-total="<?= (int)$r['DURACAO_FINAL_SEGUNDOS'] ?>">
        <td><?= $r['ID'] ?></td>
        <td><?= htmlspecialchars($r['CLIENTE']) ?></td>
        <td><?= htmlspecialchars($r['USUARIO']) ?></td>
        <td><?= htmlspecialchars($r['EMPRESA']) ?></td>
        <td><?= htmlspecialchars($r['TELEFONE_CONTATO']) ?></td>
        <td><?= $r['DATA_ENTRADA'] ?></td>
        <td><?= $r['PREVISAO_ENTRADA'] ?></td>
        <td class="acoes"></td>
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
    let start=tr.dataset.start?new Date(tr.dataset.start).getTime():null;
    let timer=null;

    function update(){
      const now=Date.now();
      const val=start?total+((now-start)/1000):total;
      tempoEl.textContent=formatSeg(val);
    }
    function render(){
      acaoEl.innerHTML='';
      if(start){
        acaoEl.innerHTML=`<button class="pause text-blue-600"><i class="fas fa-pause"></i></button> <button class="stop text-red-600"><i class="fas fa-stop"></i></button>`;
        timer=setInterval(update,1000);
      }else{
        const disabled=tr.dataset.stopped==='1';
        acaoEl.innerHTML=`<button class="start text-green-600"${disabled?' disabled':''}><i class="fas fa-play"></i></button> <button class="stop text-red-600"${disabled?' disabled':''}><i class="fas fa-stop"></i></button>`;
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
</script>
<?php include 'footer.php'; ?>
