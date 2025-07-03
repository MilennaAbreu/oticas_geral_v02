const API_BASE = 'http://localhost/otica_backend'; // ou em produção: '/backend'

// lista clientes
export async function listarClientes() {
  const res = await fetch(`${API_BASE}/clientes`);
  if (!res.ok) throw new Error(`Status ${res.status}`);
  return res.json();
}

// cadastra cliente
export async function criarCliente(dados) {
  const res = await fetch(`${API_BASE}/clientes`, {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(dados)
  });
  if (!res.ok) throw new Error(`Status ${res.status}`);
  return res.json();
}

// nao sendo utilizado por enquanto, apenas o app.js