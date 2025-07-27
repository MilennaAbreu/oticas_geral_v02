// js/app.js

document.addEventListener('DOMContentLoaded', () => {
    const API_BASE = 'http://localhost/otica_backend';

    // Exibe a página solicitada
    function showPage(pageId) {
        document.querySelectorAll('.page')
            .forEach(p => p.classList.toggle('active', p.id === pageId));

        switch (pageId) {
            case 'produtos':
                loadProdutos();
                loadTipos();
                loadCategorias();
                break;

            case 'produto-form':
                // quando abre form de produto, recarrega selects
                loadTipos();
                loadCategorias();
                break;

            case 'clientes':
                // oculta form, mostra lista
                document.getElementById('form-cliente').classList.add('hidden');
                document.getElementById('lista-clientes').classList.remove('hidden');
                document.getElementById('novo-cliente').classList.remove('hidden');
                loadCidades();
                loadClientes();
                break;
        }
    }

    // navegação do menu
    document.querySelectorAll('.menu a').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            showPage(link.dataset.page);
        });
    });

    // toggle sidebar
    document.querySelector('.toggle').addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('collapsed');
    });

    // --- CRUD DE PRODUTOS ---
    async function loadProdutos() {
        try {
            const res = await fetch(`${API_BASE}/produtos`);
            const produtos = await res.json();
            const tbody = document.querySelector('#lista-produtos tbody');
            tbody.innerHTML = '';
            produtos.forEach(p => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
          <td>${p.ID}</td>
          <td>${p.NOME}</td>
          <td>${p.ID_TIPO}</td>
          <td>${p.ID_CATEGORIA}</td>
          <td>${p.MARCA}</td>
          <td>${p.CODIGO}</td>
          <td>${p.UNIDADE_MEDIDA}</td>
          <td>${p.VALOR_UNITARIO}</td>
          <td>${p.ESTOQUE_ATUAL}</td>
          <td>${p.STATUS}</td>
          <td><img src="${p.IMAGEM || '/otica_backend/public/default.png'}" width="50"/></td>
         <!-- <td>${new Date(p.DATA_CRIACAO).toLocaleString()}</td> -->
         <!-- <td>${new Date(p.DATA_ATUALIZACAO).toLocaleString()}</td> -->
          <td>
            <button class="btn edit-prod" data-id="${p.ID}">✎</button>
            <button class="btn del-prod"  data-id="${p.ID}">🗑</button>
          </td>`;
                tbody.appendChild(tr);
            });
        } catch (err) {
            console.error('Erro ao carregar produtos:', err);
        }
    }

    // novo produto
    document.getElementById('novo-produto').addEventListener('click', () => {
        const f = document.getElementById('form-produto');
        f.reset();
        f.dataset.id = '';
        showPage('produto-form');
    });

    // cancelar produto
    document.getElementById('cancelar-produto').addEventListener('click', () => showPage('produtos'));

    // salvar produto
    document.getElementById('form-produto').addEventListener('submit', async e => {
        e.preventDefault();
        const f = e.target;
        let imgUrl = null;
        if (f.imagem && f.imagem.files.length) {
            const fd = new FormData();
            fd.append('imagem', f.imagem.files[0]);
            const r = await fetch(`${API_BASE}/upload`, { method: 'POST', body: fd });
            imgUrl = (await r.json()).url;
        }
        // normaliza valor
        let raw = f.valor_unitario.value.trim().replace(',', '.');
        let valor = parseFloat(raw) || 0;
        const payload = {
            nome: f.nome.value,
            id_tipo: +f.id_tipo.value || null,
            id_categoria: +f.id_categoria.value || null,
            marca: f.marca.value,
            codigo: f.codigo.value,
            unidade_medida: f.unidade_medida.value,
            valor_unitario: valor,
            estoque_atual: +f.estoque_atual.value || 0,
            status: f.status.value,
            imagem: imgUrl
        };
        const method = f.dataset.id ? 'PUT' : 'POST';
        const url = `${API_BASE}/produtos${f.dataset.id ? '/' + f.dataset.id : ''}`;
        await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        showPage('produtos');
    });

    // editar/excluir produto
    document.getElementById('lista-produtos').addEventListener('click', async e => {
        const id = e.target.dataset.id;
        if (e.target.classList.contains('edit-prod')) {
            const p = await (await fetch(`${API_BASE}/produtos/${id}`)).json();
            const f = document.getElementById('form-produto');
            f.dataset.id = p.ID;
            ['nome','id_tipo','id_categoria','marca','codigo','unidade_medida','valor_unitario','estoque_atual','status']
                .forEach(k => f[k].value = p[k.toUpperCase()] || '');
            showPage('produto-form');
        }
        if (e.target.classList.contains('del-prod') && confirm('Excluir este produto?')) {
            await fetch(`${API_BASE}/produtos/${id}`, { method: 'DELETE' });
            loadProdutos();
        }
    });

    // tipos
    async function loadTipos() {
        const sel = document.getElementById('id_tipo');
        sel.innerHTML = '<option value="">-- Selecione Tipo --</option>';
        (await (await fetch(`${API_BASE}/tipo_produto`)).json())
            .forEach(t => sel.appendChild(new Option(t.NOME, t.ID)));
    }
    document.getElementById('nova-tipo').addEventListener('click', async () => {
        const nome = prompt('Nome do Tipo:');
        if (!nome) return;
        const t = await (await fetch(`${API_BASE}/tipo_produto`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ nome }) })).json();
        await loadTipos();
        document.getElementById('id_tipo').value = t.ID;
    });

    // categorias
    async function loadCategorias() {
        const sel = document.getElementById('id_categoria');
        sel.innerHTML = '<option value="">-- Selecione Categoria --</option>';
        (await (await fetch(`${API_BASE}/categoria_produto`)).json())
            .forEach(c => sel.appendChild(new Option(c.NOME, c.ID)));
    }
    document.getElementById('nova-categoria').addEventListener('click', async () => {
        const nome = prompt('Nome da Categoria:');
        if (!nome) return;
        const c = await (await fetch(`${API_BASE}/categoria_produto`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ nome }) })).json();
        await loadCategorias();
        document.getElementById('id_categoria').value = c.ID;
    });

    // cidades
    async function loadCidades() {
        const sel = document.getElementById('cidade-select');
        sel.innerHTML = '<option value="">-- Selecione Cidade --</option>';
        (await (await fetch(`${API_BASE}/cidades`)).json())
            .forEach(c => sel.appendChild(new Option(c.NOME + ' — ' + c.UF, c.ID)));
    }
    document.getElementById('nova-cidade').addEventListener('click', async () => {
        const nome = prompt('Nome da Cidade:');
        const uf = prompt('UF (2 letras):');
        if (!nome || !uf) return;
        const n = await (await fetch(`${API_BASE}/cidades`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ nome, uf }) })).json();
        await loadCidades();
        document.getElementById('cidade-select').value = n.ID;
    });

    async function setCidadeByNomeUF(nome, uf, selectEl) {
        const cidades = await (await fetch(`${API_BASE}/cidades`)).json();
        const m = cidades.find(c => c.NOME.toLowerCase() === nome.toLowerCase() && c.UF === uf);
        if (m) selectEl.value = m.ID;
    }
    const cepInput = document.querySelector('#form-cliente input[name="cep"]');
    if (cepInput) {
        cepInput.addEventListener('blur', async () => {
            const cep = cepInput.value.replace(/\D/g, '');
            if (cep.length !== 8) return;
            const data = await (await fetch(`https://viacep.com.br/ws/${cep}/json/`)).json();
            if (data.erro) return;
            const f = cepInput.form;
            if (f.rua) f.rua.value = data.logradouro || '';
            if (f.bairro) f.bairro.value = data.bairro || '';
            if (f.id_cidade) await setCidadeByNomeUF(data.localidade, data.uf, f.id_cidade);
        });
    }

    // clientes
    async function loadClientes() {
        const data = await (await fetch(`${API_BASE}/clientes`)).json();
        const tbody = document.querySelector('#lista-clientes tbody');
        tbody.innerHTML = '';
        data.forEach(c => {
            tbody.insertAdjacentHTML('beforeend', `
                <tr>
                    <td>${c.ID}</td>
                    <td>${c.NOME}</td>
                    <td>${c.CPF}</td>
                    <td>${c.CIDADE || '-'}</td>
                    <td>${c.CONTATO || ''}</td>
                    <td>
                        <button class="btn edit-cli" data-id="${c.ID}">✎</button>
                        <button class="btn del-cli" data-id="${c.ID}">🗑</button>
                    </td>
                </tr>
            `);
        });
    }
    document.getElementById('novo-cliente').addEventListener('click', () => {
        const f = document.getElementById('form-cliente');
        f.reset();
        f.dataset.id = '';
        loadCidades();
        document.getElementById('lista-clientes').classList.add('hidden');
        document.getElementById('novo-cliente').classList.add('hidden');
        f.classList.remove('hidden');
    });
    document.getElementById('cancelar-cliente').addEventListener('click', () => {
        document.getElementById('form-cliente').classList.add('hidden');
        document.getElementById('lista-clientes').classList.remove('hidden');
        document.getElementById('novo-cliente').classList.remove('hidden');
    });
    document.getElementById('form-cliente').addEventListener('submit', async e => {
        e.preventDefault();
        const f = e.target;
        const id = f.dataset.id;
        const payload = {
            nome: f.nome.value,
            cpf: f.cpf.value,
            data_nascimento: f.data_nascimento.value || null,
            cep: f.cep.value || null,
            rua: f.rua.value || null,
            bairro: f.bairro.value || null,
            id_cidade: f.id_cidade.value || null,
            contato: f.contato.value || null,
            status: f.status.value
        };
        await fetch(`${API_BASE}/clientes${id ? '/' + id : ''}`, { method: id ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        loadClientes();
        document.getElementById('form-cliente').classList.add('hidden');
        document.getElementById('lista-clientes').classList.remove('hidden');
        document.getElementById('novo-cliente').classList.remove('hidden');
    });
    document.getElementById('lista-clientes').addEventListener('click', async e => {
        const id = e.target.dataset.id;
        if (e.target.classList.contains('edit-cli')) {
            const c = await (await fetch(`${API_BASE}/clientes/${id}`)).json();
            const f = document.getElementById('form-cliente');
            f.dataset.id = c.ID;
            ['nome','cpf','data_nascimento','cep','rua','bairro','contato','status']
                .forEach(k => f[k].value = c[k.toUpperCase()] || '');
            f.id_cidade.value = c.ID_CIDADE || '';
            loadCidades();
            document.getElementById('lista-clientes').classList.add('hidden');
            document.getElementById('novo-cliente').classList.add('hidden');
            f.classList.remove('hidden');
        }
        if (e.target.classList.contains('del-cli') && confirm('Excluir este cliente?')) {
            await fetch(`${API_BASE}/clientes/${id}`, { method: 'DELETE' });
            loadClientes();
        }
    });

    // inicia em home
    showPage('home');
});
