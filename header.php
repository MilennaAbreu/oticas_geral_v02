<?php
require 'config.php';
require 'auth.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($pageTitle ?? 'Sistema de Óticas') ?></title>
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#8E070D',
            secondary: '#1F2937',
            sidebarBg: '#f8f9fa',
            itemHover: '#e2e6ea'
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    .sidebar { background-color: #f8f9fa; width: 4rem; transition: width .3s; }
    .sidebar.expanded { width: 12rem; }
    .sidebar:not(.expanded) .label { display: none; }
    .menu-item { display: flex; align-items: center; padding: .75rem 1rem; color: #1F2937; cursor: pointer; transition: background .2s; }
    .menu-item:hover { background-color: #e2e6ea; }
    .has-submenu > .submenu { display: none; }
    .has-submenu.expanded > .submenu { display: block; }
    input:focus, select:focus { border: none !important; border-bottom: 2px solid #8E070D !important; outline: none !important; box-shadow: none !important; }
    table thead { background-color: #8E070D !important; }
    table thead th { color: #ffffff !important; }
    /* spacing and actions */
    table.dataTable tbody td, table.dataTable thead th {
      padding: 0.75rem 1rem !important;
    }
    .table-actions a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0.25rem 0.5rem;
      border-radius: 0.25rem;
      transition: background-color .2s, color .2s, transform .2s;
      margin-right: 0.25rem;
    }
    .table-actions a.edit { color: #2563eb; }
    .table-actions a.delete { color: #dc2626; }
    .table-actions a:hover {
      color: #fff;
      transform: scale(1.05);
    }
    .table-actions a.edit:hover { background-color: #2563eb; }
    .table-actions a.delete:hover { background-color: #dc2626; }
    form .form-group { margin-bottom: 1rem; }
    form .form-control {
      border: 1px solid #d1d5db; /* gray-300 */
      padding: 0.5rem 0.75rem;
      border-radius: 0.25rem;
      width: 100%;
    }
  </style>
</head>
<body class="flex">
  <div id="sidebar" class="sidebar flex flex-col">
    <button id="toggleBtn" class="menu-item focus:outline-none" aria-label="Toggle sidebar">
      <i class="fas fa-bars w-6 h-6" style="color:#8E070D;"></i>
      <span class="label ml-3">Menu</span>
    </button>
    <ul class="flex-1 mt-4 space-y-1">
      <!-- Home -->
      <li>
        <a href="dashboard.php" class="menu-item">
          <i class="fas fa-home w-6 h-6" style="color:#8E070D;"></i>
          <span class="label ml-3">Home</span>
        </a>
      </li>
      <!-- Cadastros -->
      <li class="has-submenu">
        <div class="menu-item">
          <i class="fas fa-user-cog w-6 h-6" style="color:#8E070D;"></i>
          <span class="label ml-3">Cadastros</span>
        </div>
        <ul class="submenu pl-6 space-y-1">
          <li><a href="empresa_list.php" class="menu-item">Empresa</a></li>
          <li><a href="user_list.php" class="menu-item">Usuários</a></li>
          <li><a href="cliente_list.php" class="menu-item">Clientes</a></li>
          <li><a href="fornecedor_list.php" class="menu-item">Fornecedores</a></li>
          <li class="has-submenu">
            <div class="menu-item">Produtos</div>
            <ul class="submenu pl-6 space-y-1">
              <li><a href="pecas_list.php" class="menu-item">Peças</a></li>
              <li><a href="produtos_list.php" class="menu-item">Produtos</a></li>
              <li><a href="categorias_list.php" class="menu-item">Categorias</a></li>
              <li><a href="tipo_produtos_list.php" class="menu-item">Tipo de Produtos</a></li>
            </ul>
          </li>
          <li><a href="condicoes_pagamento_list.php" class="menu-item">Condições de Pagamento</a></li>
          <li><a href="frete_list.php" class="menu-item">Frete</a></li>
        </ul>
      </li>
      <!-- Processos -->
      <li class="has-submenu">
        <div class="menu-item">
          <i class="fas fa-tools w-6 h-6" style="color:#8E070D;"></i>
          <span class="label ml-3">Processos</span>
        </div>
        <ul class="submenu pl-6 space-y-1">
          <li><a href="conserto_list.php" class="menu-item">Consertos</a></li>
          <li><a href="vendas_list.php" class="menu-item">Vendas</a></li>
          <li><a href="encaminhamento_list.php" class="menu-item">Encaminhamentos</a></li>
        </ul>
      </li>
      <!-- Financeiro -->
      <li class="has-submenu">
        <div class="menu-item">
          <i class="fas fa-coins w-6 h-6" style="color:#8E070D;"></i>
          <span class="label ml-3">Financeiro</span>
        </div>
        <ul class="submenu pl-6 space-y-1">
          <li><a href="contas_pagar_list.php" class="menu-item">Contas a Pagar</a></li>
          <li><a href="contas_receber_list.php" class="menu-item">Contas a Receber</a></li>
        </ul>
      </li>
      <!-- Relatórios -->
      <li class="has-submenu">
        <div class="menu-item">
          <i class="fas fa-chart-pie w-6 h-6" style="color:#8E070D;"></i>
          <span class="label ml-3">Relatórios</span>
        </div>
        <ul class="submenu pl-6 space-y-1">
          <li><a href="rel_pagar.php" class="menu-item">Contas a Pagar</a></li>
          <li><a href="rel_receber.php" class="menu-item">Contas a Receber</a></li>
          <li><a href="rel_vendas_geral.php" class="menu-item">Vendas Gerais</a></li>
          <li><a href="rel_vendas_vendedor.php" class="menu-item">Por Vendedor</a></li>
          <li><a href="rel_vendas_empresa.php" class="menu-item">Por Empresa</a></li>
        </ul>
      </li>
    </ul>
  </div>
  <div class="main ml-16 transition-all duration-300 p-6">
