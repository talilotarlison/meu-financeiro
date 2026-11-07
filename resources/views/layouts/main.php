<!doctype html>
<html lang="pt-BR" data-theme="<?= e($settings['theme'] ?? 'light') ?>">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <meta name="theme-color" content="#f5f7fb"><title><?= e($title ?? 'Meu Financeiro') ?> · Meu Financeiro</title>
  <meta name="description" content="Organize contas, movimentações, metas e investimentos em um só lugar."><link rel="manifest" href="/manifest.webmanifest"><link rel="icon" href="/assets/icons/icon-192.png" type="image/png"><link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
  <link rel="stylesheet" href="/assets/css/app.css?v=profile-20261107">
  <?php if (in_array((parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), ['/dashboard','/reports','/patrimony'], true)): ?><script defer src="https://cdn.jsdelivr.net/npm/chart.js@4"></script><?php endif; ?>
  <script defer src="/assets/js/app.js"></script>
</head>
<body class="<?= current_user() ? 'app-shell' : 'auth-shell' ?>">
<a class="skip-link" href="#main-content">Pular para o conteúdo</a>
<?php if (current_user()): $navUser = current_user(); ?>
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="/dashboard"><span class="brand-mark">m</span><span>meu<span class="brand-light">financeiro</span></span></a>
    <div class="workspace-label">ESPAÇO PESSOAL</div>
    <?php $routePath=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH); ?>
    <nav aria-label="Navegação principal"><a class="nav-link <?= $routePath==='/dashboard'?'active':'' ?>" href="/dashboard"><span class="nav-icon">◫</span>Visão geral</a>
      <span class="nav-heading">MOVIMENTAÇÕES</span><a class="nav-link <?= $routePath==='/transactions'?'active':'' ?>" href="/transactions"><span class="nav-icon">⇄</span>Receitas e despesas</a>
      <a class="nav-link <?= $routePath==='/transfers'?'active':'' ?>" href="/transfers"><span class="nav-icon">↗</span>Transferências</a><a class="nav-link <?= $routePath==='/commitments'?'active':'' ?>" href="/commitments"><span class="nav-icon">◷</span>Contas a pagar/receber</a>
      <span class="nav-heading">ORGANIZAÇÃO</span><a class="nav-link <?= $routePath==='/accounts'?'active':'' ?>" href="/accounts"><span class="nav-icon">▤</span>Contas</a>
      <a class="nav-link <?= in_array($routePath,['/cards','/invoices'],true)?'active':'' ?>" href="/cards"><span class="nav-icon">▦</span>Cartões</a><a class="nav-link <?= $routePath==='/categories'?'active':'' ?>" href="/categories"><span class="nav-icon">◈</span>Categorias</a>
      <span class="nav-heading">PLANEJAMENTO</span><a class="nav-link <?= $routePath==='/recurrences'?'active':'' ?>" href="/recurrences"><span class="nav-icon">↻</span>Recorrências</a><a class="nav-link <?= $routePath==='/budgets'?'active':'' ?>" href="/budgets"><span class="nav-icon">◎</span>Orçamentos</a><a class="nav-link <?= $routePath==='/goals'?'active':'' ?>" href="/goals"><span class="nav-icon">☆</span>Metas</a>
      <span class="nav-heading">ANÁLISES</span><a class="nav-link <?= str_starts_with($routePath,'/reports')?'active':'' ?>" href="/reports"><span class="nav-icon">▤</span>Relatórios</a>
      <span class="nav-heading">AVANÇADO</span><a class="nav-link <?= $routePath==='/investments'?'active':'' ?>" href="/investments"><span class="nav-icon">◇</span>Investimentos</a><a class="nav-link <?= $routePath==='/patrimony'?'active':'' ?>" href="/patrimony"><span class="nav-icon">◉</span>Patrimônio e dívidas</a><a class="nav-link <?= $routePath==='/imports'?'active':'' ?>" href="/imports"><span class="nav-icon">⇧</span>Importar extrato</a><a class="nav-link <?= $routePath==='/backup'?'active':'' ?>" href="/backup"><span class="nav-icon">▣</span>Backup
      </a>
    </nav>
    <div class="sidebar-bottom"><div class="security-note"><span>✦</span><div><strong>Suas finanças, com clareza.</strong><small>Seus dados ficam protegidos nesta conta.</small></div></div>
      <div class="user-row"><a class="avatar" href="/profile" aria-label="Meu perfil"><?= e(mb_strtoupper(mb_substr($navUser['name'],0,1))) ?></a><a class="user-copy" href="/profile"><strong><?= e($navUser['name']) ?></strong><small><?= e($navUser['email']) ?></small></a><form method="post" action="/logout"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><button class="icon-button" title="Sair" aria-label="Sair">↪</button></form></div>
    </div>
  </aside>
  <main class="main-area" id="main-content" tabindex="-1"><header class="topbar"><button class="menu-toggle" data-menu-toggle aria-label="Abrir menu">☰</button><div class="breadcrumb">Meu espaço <span>/</span> <strong><?= e($title ?? '') ?></strong></div><div class="top-actions"><span class="today"><?= e((new DateTimeImmutable())->format('d/m/Y')) ?></span><button class="theme-toggle" data-theme-toggle aria-label="Alternar tema" title="Alternar tema">◐</button><a class="top-profile" href="/profile" aria-label="Abrir perfil de <?= e($navUser['name']) ?>"><span class="avatar small"><?= e(mb_strtoupper(mb_substr($navUser['name'],0,1))) ?></span><span class="top-profile-name"><?= e((preg_split('/\s+/', trim($navUser['name'])) ?: [''])[0]) ?></span></a><form class="top-logout" method="post" action="/logout"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><button class="logout-button" type="submit" title="Sair" aria-label="Sair do sistema"><span aria-hidden="true">↪</span><span>Sair</span></button></form></div></header>
    <section class="page-content"><?php require $viewFile; ?></section>
  </main>
<?php else: ?>
  <main class="auth-layout" id="main-content" tabindex="-1"><div class="auth-brand"><a class="brand" href="/login"><span class="brand-mark">m</span><span>meu<span class="brand-light">financeiro</span></span></a><div class="auth-promise"><span class="eyebrow">ORGANIZE. PLANEJE. REALIZE.</span><h1>Seu dinheiro com mais <em>sentido.</em></h1><p>Uma visão clara das suas finanças para você tomar decisões melhores, todos os dias.</p><div class="promise-points"><span>✦ Controle no seu ritmo</span><span>✦ Seus dados são só seus</span></div></div><small class="auth-foot">SIMPLES PARA COMEÇAR. PRONTO PARA CRESCER.</small></div><div class="auth-panel"><div class="auth-mobile-brand"><a class="brand" href="/login"><span class="brand-mark">m</span><span>meu<span class="brand-light">financeiro</span></span></a></div><div class="auth-card"><?php require $viewFile; ?></div><div class="auth-legal">Ao continuar, seus dados são armazenados com segurança.</div></div></main>
<?php endif; ?>
</body></html>
