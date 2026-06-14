<?php
// =============================================================
//  VetSys – Painel Principal
//  Arquivo : painel.php
// =============================================================
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/sessao.php';

exigeLogin(); // redireciona para login se não estiver autenticado

$perfil = perfilAtual();
$nome   = nomeAtual();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel – VetSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --verde: #1a6b3c; --verde-claro: #2ecc71; }

        body { background: #f4f6f9; }

        /* ── Sidebar ── */
        .sidebar {
            width: 240px;
            min-height: 100vh;
            background: var(--verde);
            position: fixed;
            top: 0; left: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }
        .sidebar .logo {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.15);
            text-align: center;
            color: #fff;
        }
        .sidebar .logo i { font-size: 2rem; }
        .sidebar .logo span { display: block; font-size: 1.3rem; font-weight: 700; }
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem 1.25rem;
            color: rgba(255,255,255,.85);
            text-decoration: none;
            transition: background .15s;
            font-size: .95rem;
        }
        .sidebar nav a:hover,
        .sidebar nav a.active { background: rgba(255,255,255,.15); color: #fff; }
        .sidebar nav a i { font-size: 1.15rem; width: 20px; text-align: center; }
        .sidebar .logout-area {
            margin-top: auto;
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,.15);
        }

        /* ── Conteúdo principal ── */
        .main-content {
            margin-left: 240px;
            padding: 2rem;
        }

        /* ── Navbar topo ── */
        .topbar {
            background: #fff;
            border-radius: 12px;
            padding: .75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
        }
        .badge-perfil {
            background: var(--verde);
            color: #fff;
            border-radius: 50px;
            padding: .3rem .8rem;
            font-size: .8rem;
        }

        /* ── Cards de atalho ── */
        .card-atalho {
            border: none;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            transition: transform .15s, box-shadow .15s;
            text-decoration: none;
            color: inherit;
        }
        .card-atalho:hover { transform: translateY(-4px); box-shadow: 0 6px 20px rgba(0,0,0,.13); }
        .card-atalho .icon-box {
            width: 56px; height: 56px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: #fff;
            margin-bottom: 1rem;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .sidebar { width: 60px; }
            .sidebar .logo span, .sidebar nav a span, .sidebar .logout-area span { display: none; }
            .main-content { margin-left: 60px; padding: 1rem; }
        }
    </style>
</head>
<body>

<!-- ── Sidebar ─────────────────────────────────────────────── -->
<aside class="sidebar">
    <div class="logo">
        <i class="bi bi-heart-pulse-fill"></i>
        <span>VetSys</span>
    </div>

    <nav class="mt-2">
        <a href="painel.php" class="active">
            <i class="bi bi-grid-fill"></i><span>Painel</span>
        </a>

        <!-- Visível para Gerente e Recepcionista -->
        <?php if (in_array($perfil, ['Gerente', 'Recepcionista'])): ?>
        <a href="clientes/index.php">
            <i class="bi bi-people-fill"></i><span>Clientes</span>
        </a>
        <?php endif; ?>

        <!-- Visível para todos -->
        <a href="pets/index.php">
            <i class="bi bi-bug-fill"></i><span>Pets</span>
        </a>

        <?php if (in_array($perfil, ['Gerente', 'Recepcionista'])): ?>
        <a href="consultas/index.php">
            <i class="bi bi-calendar2-check-fill"></i><span>Consultas</span>
        </a>
        <?php endif; ?>

        <?php if ($perfil === 'Veterinario'): ?>
        <a href="consultas/minhas.php">
            <i class="bi bi-calendar2-check-fill"></i><span>Minhas Consultas</span>
        </a>
        <?php endif; ?>

        <?php if (in_array($perfil, ['Gerente', 'Recepcionista'])): ?>
        <a href="veterinarios/index.php">
            <i class="bi bi-person-badge-fill"></i><span>Veterinários</span>
        </a>
        <a href="servicos/index.php">
            <i class="bi bi-clipboard2-pulse-fill"></i><span>Serviços</span>
        </a>
        <?php endif; ?>

        <?php if ($perfil === 'Gerente'): ?>
        <a href="relatorios/index.php">
            <i class="bi bi-file-earmark-bar-graph-fill"></i><span>Relatórios</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="logout-area">
        <a href="auth/logout.php" class="btn btn-outline-light btn-sm w-100 d-flex align-items-center gap-2">
            <i class="bi bi-box-arrow-left"></i><span>Sair</span>
        </a>
    </div>
</aside>

<!-- ── Conteúdo Principal ─────────────────────────────────── -->
<main class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div>
            <h5 class="mb-0 fw-bold">Bem-vindo, <?= htmlspecialchars($nome) ?>!</h5>
            <small class="text-muted"><?= date('l, d \d\e F \d\e Y') ?></small>
        </div>
        <span class="badge-perfil">
            <i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($perfil) ?>
        </span>
    </div>

    <!-- Mensagens flash -->
    <?php $erro = getFlash('erro'); if ($erro): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Cards de atalho (variam por perfil) -->
    <div class="row g-3">

        <?php if (in_array($perfil, ['Gerente', 'Recepcionista'])): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="clientes/index.php" class="card card-atalho p-3 d-block">
                <div class="icon-box" style="background:#3498db;"><i class="bi bi-people-fill"></i></div>
                <div class="fw-semibold">Clientes</div>
                <small class="text-muted">Gerir tutores</small>
            </a>
        </div>
        <?php endif; ?>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="pets/index.php" class="card card-atalho p-3 d-block">
                <div class="icon-box" style="background:#e67e22;"><i class="bi bi-bug-fill"></i></div>
                <div class="fw-semibold">Pets</div>
                <small class="text-muted">Gerir pacientes</small>
            </a>
        </div>

        <?php if (in_array($perfil, ['Gerente', 'Recepcionista'])): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="consultas/index.php" class="card card-atalho p-3 d-block">
                <div class="icon-box" style="background:#9b59b6;"><i class="bi bi-calendar2-check-fill"></i></div>
                <div class="fw-semibold">Consultas</div>
                <small class="text-muted">Agendamentos</small>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="veterinarios/index.php" class="card card-atalho p-3 d-block">
                <div class="icon-box" style="background:#1abc9c;"><i class="bi bi-person-badge-fill"></i></div>
                <div class="fw-semibold">Veterinários</div>
                <small class="text-muted">Equipe médica</small>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="servicos/index.php" class="card card-atalho p-3 d-block">
                <div class="icon-box" style="background:#e74c3c;"><i class="bi bi-clipboard2-pulse-fill"></i></div>
                <div class="fw-semibold">Serviços</div>
                <small class="text-muted">Catálogo de preços</small>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($perfil === 'Gerente'): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="relatorios/index.php" class="card card-atalho p-3 d-block">
                <div class="icon-box" style="background:#1a6b3c;"><i class="bi bi-file-earmark-bar-graph-fill"></i></div>
                <div class="fw-semibold">Relatórios</div>
                <small class="text-muted">Faturamento em PDF</small>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($perfil === 'Veterinario'): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="consultas/minhas.php" class="card card-atalho p-3 d-block">
                <div class="icon-box" style="background:#9b59b6;"><i class="bi bi-calendar2-check-fill"></i></div>
                <div class="fw-semibold">Minhas Consultas</div>
                <small class="text-muted">Ver agenda</small>
            </a>
        </div>
        <?php endif; ?>

    </div><!-- /row -->
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
