<?php
// index.php - Página principal RUGAL - Versión mejorada
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RUGAL — El ecosistema inteligente para tu mascota | Cali, Colombia</title>
    <link rel="stylesheet" href="styles.css">
    <?php include 'pwa-head.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        /* ====================================================
           ESTILOS NUEVOS — 100% compatibles con styles.css
           Solo extienden lo que ya existe, no pisan nada
        ==================================================== */

        /* Navbar */
        .mobile-overlay { position:fixed; inset:0; background:rgba(0,0,0,.35); opacity:0; pointer-events:none; transition:opacity 200ms; z-index:10000; }
        .mobile-overlay.active { opacity:1; pointer-events:auto; }
        .navbar { z-index:10005; position:relative; }
        #navbarMenu { z-index:10010; }
        @media(max-width:900px){
            #navbarMenu { position:fixed; top:70px; right:16px; width:280px; height:calc(100% - 90px); background:rgba(255,255,255,.98); border-radius:14px; padding:1rem; box-shadow:0 25px 50px rgba(0,0,0,.15); transform:translateX(110%); transition:transform 260ms cubic-bezier(.2,.9,.3,1); overflow-y:auto; }
            #navbarMenu.active { transform:translateX(0); }
            .nav-menu-inner { display:flex; flex-direction:column; gap:6px; }
            .navbar-link { display:block; padding:12px 14px; color:#111827; border-radius:10px; text-decoration:none; font-weight:600; }
            .navbar-link:hover { background:rgba(124,58,237,.06); color:#7c3aed; }
            .nav-auth-buttons { margin-top:12px; display:flex; flex-direction:column; gap:8px; }
            .btn { display:inline-flex; align-items:center; gap:8px; justify-content:center; }
            .navbar-mobile-toggle { background:transparent; border:none; color:#111827; font-size:1.15rem; }
        }
        body.menu-open * { -webkit-backdrop-filter:none!important; backdrop-filter:none!important; filter:none!important; }

        .btn-outline {
            background: transparent;
            border: 2px solid rgba(255,255,255,.25);
            color: white;
            display: inline-flex; align-items: center; gap: .5rem;
            padding: 1.125rem 2.25rem; border-radius: 12px;
            font-weight: 600; font-size: 1.125rem;
            text-decoration: none; cursor: pointer;
            transition: all .3s ease;
        }
        .btn-outline:hover { border-color: #a78bfa; color: #a78bfa; }
        .btn-glow:hover { box-shadow: 0 8px 24px rgba(124,58,237,.45); }
        .text-accent { color: #a78bfa; }

        @media(max-width:768px){
            .gamification-card { grid-template-columns:1fr!important; padding:2.5rem 1.5rem!important; text-align:center!important; gap:2rem!important; display:grid!important; }
            .gamification-card ul li { justify-content:center!important; text-align:left; }
            .gamification-card h2 { font-size:2rem!important; }
        }

        /* ── ALIADOS — mismo fondo oscuro #0f172a del hero ── */
        .aliados-section {
            background: #0f172a;
            padding: 7rem 0;
            position: relative; overflow: hidden;
        }
        .aliados-section::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background:
                radial-gradient(ellipse 50% 55% at 5% 50%, rgba(124,58,237,.18) 0%, transparent 65%),
                radial-gradient(ellipse 40% 45% at 95% 50%, rgba(139,92,246,.12) 0%, transparent 65%);
        }
        .aliados-section .container { position: relative; z-index: 1; }

        .section-tag-light {
            display: inline-block;
            background: rgba(167,139,250,.15);
            color: #a78bfa;
            border: 1px solid rgba(167,139,250,.3);
            padding: .4rem 1.2rem; border-radius: 50px;
            font-size: .78rem; font-weight: 600;
            letter-spacing: .1em; text-transform: uppercase;
            margin-bottom: 1.2rem;
        }
        .aliados-title {
            font-size: clamp(2.2rem, 5vw, 3.6rem);
            font-weight: 900; line-height: 1.08;
            letter-spacing: -1.5px; color: white; margin-bottom: 1rem;
        }
        .aliados-title span { color: #a78bfa; }
        .aliados-subtitle { color: rgba(255,255,255,.5); font-size: 1.05rem; max-width: 500px; margin-bottom: 3.5rem; line-height: 1.7; }

        .aliados-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media(max-width:768px){ .aliados-grid { grid-template-columns: 1fr; } }

        .aliado-card { border-radius: 24px; padding: 40px 36px; transition: transform .3s ease; }
        .aliado-card:hover { transform: translateY(-6px); }
        .aliado-card.vet    { background: rgba(16,185,129,.07);  border: 1px solid rgba(16,185,129,.2); }
        .aliado-card.tienda { background: rgba(167,139,250,.07); border: 1px solid rgba(167,139,250,.2); }
        .aliado-emoji { font-size: 2.2rem; margin-bottom: 14px; display: block; }
        .aliado-type  { font-size: 1.5rem; font-weight: 800; margin-bottom: 8px; }
        .vet .aliado-type    { color: #34d399; }
        .tienda .aliado-type { color: #a78bfa; }
        .aliado-desc { color: rgba(255,255,255,.5); font-size: .9rem; margin-bottom: 24px; line-height: 1.7; }

        .benefit-list { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 11px; }
        .benefit-list li { display: flex; align-items: flex-start; gap: 11px; font-size: .88rem; color: rgba(255,255,255,.75); line-height: 1.5; }
        .bcheck { width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .62rem; font-weight: 800; flex-shrink: 0; margin-top: 1px; }
        .vet .bcheck    { background: rgba(52,211,153,.18);  color: #34d399; }
        .tienda .bcheck { background: rgba(167,139,250,.18); color: #a78bfa; }

        /* ── PRECIOS — fondo blanco como el resto de secciones claras ── */
        .pricing-section { background: white; padding: 7rem 0; }

        .pricing-inner-tag {
            display: inline-block;
            background: rgba(124,58,237,.08); color: #7c3aed;
            padding: .4rem 1.2rem; border-radius: 50px;
            font-size: .78rem; font-weight: 600;
            letter-spacing: .1em; text-transform: uppercase; margin-bottom: 1rem;
        }
        .pricing-tabs {
            display: flex; gap: 6px; background: #f1f5f9;
            border-radius: 50px; padding: 5px;
            width: fit-content; margin: 2.5rem auto 3rem;
        }
        .p-tab {
            background: transparent; border: none;
            padding: 10px 28px; border-radius: 50px;
            font-family: 'Inter', sans-serif; font-weight: 600; font-size: .88rem;
            color: #64748b; cursor: pointer; transition: all .25s;
        }
        .p-tab.active { background: white; color: #1e1b4b; box-shadow: 0 2px 8px rgba(0,0,0,.08); }

        .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); gap: 24px; max-width: 820px; margin: 0 auto; }
        .p-panel { display: none; }
        .p-panel.active { display: grid; }

        .p-card { background: white; border: 1.5px solid #e2e8f0; border-radius: 24px; padding: 36px 30px; transition: transform .25s, box-shadow .25s; position: relative; }
        .p-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(124,58,237,.1); }
        .p-card.featured { border-color: #7c3aed; background: linear-gradient(160deg, #fdfbff 0%, white 100%); }

        .featured-badge {
            position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            color: white; font-weight: 700; font-size: .72rem;
            padding: 5px 18px; border-radius: 50px;
            white-space: nowrap; letter-spacing: .05em; text-transform: uppercase;
        }
        .p-name   { font-weight: 700; color: #64748b; font-size: .9rem; margin-bottom: 16px; text-transform: uppercase; letter-spacing: .06em; }
        .p-price  { font-size: 2.6rem; font-weight: 900; color: #1e1b4b; line-height: 1; margin-bottom: 4px; }
        .p-price sup   { font-size: 1.1rem; vertical-align: super; margin-right: 2px; }
        .p-price small { font-size: .9rem; font-weight: 400; color: #94a3b8; }
        .p-period  { font-size: .82rem; color: #94a3b8; margin-bottom: 26px; }
        .p-divider { height: 1px; background: #f1f5f9; margin-bottom: 22px; }
        .p-features { list-style: none; padding: 0; margin-bottom: 28px; display: flex; flex-direction: column; gap: 10px; }
        .p-features li { display: flex; align-items: flex-start; gap: 10px; font-size: .88rem; color: #475569; line-height: 1.5; }
        .p-yes { color: #10b981; font-weight: 700; flex-shrink: 0; }
        .p-no  { color: #cbd5e1; flex-shrink: 0; }

        .btn-plan { width: 100%; padding: 14px; border-radius: 12px; border: 1.5px solid #e2e8f0; background: transparent; color: #1e1b4b; font-weight: 700; font-size: .9rem; cursor: pointer; transition: all .25s; text-align: center; text-decoration: none; display: block; }
        .btn-plan:hover { border-color: #7c3aed; color: #7c3aed; }
        .btn-plan.primary { background: linear-gradient(135deg, #7c3aed, #a855f7); color: white; border-color: transparent; }
        .btn-plan.primary:hover { box-shadow: 0 8px 24px rgba(124,58,237,.4); transform: translateY(-2px); }

        /* ── STEPS — mismo fondo #f8faff que la sección de gamificación ── */
        .steps-section { background: #f8faff; padding: 7rem 0; border-top: 1px solid #f3e8ff; }
        .steps-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 24px; margin-top: 3.5rem; }
        .step-card { background: white; border: 1px solid #ede9fe; border-radius: 20px; padding: 2rem 1.5rem; text-align: center; transition: transform .25s, box-shadow .25s; }
        .step-card:hover { transform: translateY(-5px); box-shadow: 0 12px 28px rgba(124,58,237,.1); }
        .step-num { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #a855f7); color: white; font-weight: 800; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.1rem; }
        .step-card h3 { color: #1e1b4b; font-weight: 700; font-size: .98rem; margin-bottom: 8px; }
        .step-card p  { color: #64748b; font-size: .86rem; line-height: 1.65; }

        /* ── CTA WS — mismo fondo #0f172a del hero y aliados ── */
        .cta-ws-section { background: #0f172a; padding: 7rem 0; text-align: center; position: relative; overflow: hidden; }
        .cta-ws-section::before { content: ''; position: absolute; inset: 0; pointer-events: none; background: radial-gradient(ellipse 65% 55% at 50% 50%, rgba(124,58,237,.2) 0%, transparent 70%); }
        .cta-ws-box { position: relative; background: rgba(124,58,237,.08); border: 1px solid rgba(167,139,250,.25); border-radius: 32px; padding: 72px 48px; max-width: 700px; margin: 0 auto; }
        @media(max-width:600px){ .cta-ws-box { padding: 48px 24px; } }
        .cta-ws-title { font-size: clamp(1.9rem, 4vw, 2.8rem); font-weight: 900; color: white; letter-spacing: -1px; margin-bottom: 14px; line-height: 1.1; }
        .cta-ws-title span { color: #a78bfa; }
        .cta-ws-sub { color: rgba(255,255,255,.5); font-size: 1rem; margin-bottom: 2.5rem; max-width: 420px; margin-left: auto; margin-right: auto; line-height: 1.7; }
        .btn-ws { display: inline-flex; align-items: center; gap: 12px; background: #25D366; color: white; border: none; border-radius: 50px; padding: 17px 38px; font-weight: 700; font-size: 1rem; cursor: pointer; text-decoration: none; transition: transform .2s, box-shadow .2s; }
        .btn-ws:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(37,211,102,.4); }
        .cta-ws-note { color: rgba(255,255,255,.25); font-size: .8rem; margin-top: 16px; }
        .cta-ws-note a { color: rgba(167,139,250,.6); text-decoration: none; }
        .cta-ws-note a:hover { color: #a78bfa; }

        /* Reveal */
        .rv { opacity:0; transform:translateY(26px); transition:opacity .65s ease, transform .65s ease; }
        .rv.show { opacity:1; transform:translateY(0); }
    </style>
</head>
<body>
    <div class="cursor-dot"></div>
    <div class="cursor-outline"></div>
    <div class="bg-animation">
        <div class="gradient-circle"></div><div class="gradient-circle"></div><div class="gradient-circle"></div>
        <div class="floating-paws">
            <i class="fas fa-paw"></i><i class="fas fa-paw"></i><i class="fas fa-paw"></i><i class="fas fa-paw"></i><i class="fas fa-paw"></i>
        </div>
    </div>

    <!-- NAVBAR -->
    <nav id="mainNav" class="navbar">
        <div class="container navbar-content">
            <a href="index.php" class="logo"><i class="fas fa-paw"></i><span>RUGAL</span></a>
            <button class="navbar-mobile-toggle" id="mobileToggle" style="position:relative;z-index:9999;"><i class="fas fa-bars"></i></button>
            <div class="navbar-links" id="navbarMenu">
                <div class="nav-menu-inner">
                    <a href="#inicio"       class="navbar-link active">Inicio</a>
                    <a href="#temas"        class="navbar-link">Plan de Salud</a>
                    <a href="#gamificacion" class="navbar-link">Beneficios</a>
                    <a href="#aliados"      class="navbar-link">Para Aliados</a>
                    <a href="#precios"      class="navbar-link">Precios</a>
                    <a href="#comunidad"    class="navbar-link">Comunidad</a>
                </div>
                <div class="nav-auth-buttons">
                    <button id="pwa-install-index-btn" class="btn btn-primary" style="display: none; align-items: center; gap: 8px; font-weight: 600; font-size: 0.95rem; border: none; cursor: pointer;"><i class="fas fa-download"></i> Instalar App</button>
                    <?php if(isset($_SESSION['logged_in'])): ?>
                        <a href="dashboard.php" class="btn btn-glass"><i class="fas fa-tachometer-alt"></i> Panel</a>
                        <a href="logout.php"    class="btn btn-primary btn-glow"><i class="fas fa-sign-out-alt"></i> Salir</a>
                    <?php else: ?>
                        <a href="login.php"    class="btn btn-glass"><i class="fas fa-sign-in-alt"></i> Entrar</a>
                        <a href="registro.html" class="btn btn-primary btn-glow"><i class="fas fa-user-plus"></i> Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- HERO (idéntico al original) -->
    <section id="inicio" class="hero-split">
        <div class="hero-left">
            <div class="container">
                <div class="hero-static-content animate-slide-up visible">
                    <div class="tag">🐕 Cuidado Preventivo y Bienestar Organizado</div>
                    <h1 class="hero-title">El Aliado Confiable en el <span class="text-accent">Cuidado</span> Diario</h1>
                    <p class="hero-subtitle">RUGAL conecta dueños de mascotas con veterinarias y tiendas en Cali. IA, recordatorios, recompensas y todo organizado en una sola app.</p>
                    <div class="hero-cta">
                        <?php if(!isset($_SESSION['logged_in'])): ?>
                            <a href="registro.html" class="btn btn-primary btn-xl btn-glow"><i class="fas fa-paw"></i> Crear Perfil Gratis</a>
                        <?php else: ?>
                            <a href="dashboard.php" class="btn btn-primary btn-xl btn-glow"><i class="fas fa-tachometer-alt"></i> Mi Panel</a>
                        <?php endif; ?>
                        <a href="#aliados" class="btn-outline btn-xl"><i class="fas fa-handshake"></i> Soy veterinaria / tienda</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-right">
            <div class="carousel-slides">
                <div class="carousel-slide active slide-1"></div>
                <div class="carousel-slide slide-2"></div>
            </div>
            <div class="carousel-overlay"></div>
            <div class="carousel-indicators">
                <div class="indicator active" onclick="gotoSlide(0)"></div>
                <div class="indicator"        onclick="gotoSlide(1)"></div>
            </div>
        </div>
    </section>

    <!-- AVISO -->
    <div class="container">
        <div style="background:rgba(124,58,237,.05);border-left:5px solid #7c3aed;padding:1.5rem 2rem;border-radius:12px;margin:2rem 0;font-size:1rem;color:#4b5563;">
            <i class="fas fa-check-circle" style="color:#10b981;margin-right:8px;"></i>
            <strong>Aviso de Salud:</strong> En RUGAL promovemos la prevención responsable. Nuestra plataforma es una guía de apoyo complementaria que fortalece, no reemplaza, la relación con tu veterinario de confianza.
        </div>
    </div>

    <!-- PLAN DE SALUD -->
    <section id="temas" class="section" style="background:white;">
        <div class="container">
            <div class="section-header">
                <div class="tag" style="background:rgba(16,185,129,.1);color:#10b981;display:inline-block;margin-bottom:1rem;">🛡️ Seguimiento Responsable</div>
                <h2 class="section-title" style="color:#1e1b4b;">Gestión de Salud <span class="text-gradient">Organizada</span></h2>
                <p class="section-subtitle">Herramientas claras para un acompañamiento constante</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div class="animate-slide-left">
                    <div style="padding:2.5rem;background:#fdfbff;border-radius:30px;border:1px solid #f3e8ff;box-shadow:0 10px 30px rgba(124,58,237,.05);">
                        <p style="font-size:1.1rem;line-height:1.8;color:#4b5563;">
                            Creemos que la organización es la base de la longevidad. RUGAL te ayuda a llevar un registro ordenado de vacunas, peso y síntomas — listo para compartir con tu veterinario en cualquier momento.
                        </p>
                        <div style="background:rgba(124,58,237,.05);border-left:5px solid #7c3aed;padding:1.5rem;border-radius:15px;margin-top:2rem;">
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:.5rem;">
                                <i class="fas fa-robot" style="color:#7c3aed;font-size:1.2rem;"></i>
                                <strong style="color:#1e1b4b;">Plan mensual de salud con IA <span style="background:#7c3aed;color:white;font-size:.7rem;padding:2px 8px;border-radius:20px;margin-left:6px;">Premium</span></strong>
                            </div>
                            <p style="font-size:.92rem;color:#6b7280;font-style:italic;margin-top:8px;">
                                Cada mes RUGAL genera una rutina diaria personalizada para tu mascota con consejos, recomendaciones y seguimiento — basada en su historial, edad y raza.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-6">
                    <div style="display:flex;gap:20px;align-items:flex-start;padding:1.5rem;background:white;border-radius:20px;box-shadow:var(--shadow-md);">
                        <div style="background:#ddd6fe;color:#7c3aed;padding:15px;border-radius:12px;"><i class="fas fa-calendar-alt fa-lg"></i></div>
                        <div><h4 style="color:#1e1b4b;margin-bottom:5px;">Bitácora de Cuidado</h4><p style="color:#64748b;font-size:.9rem;">Registra síntomas y novedades de forma sencilla y clara.</p></div>
                    </div>
                    <div style="display:flex;gap:20px;align-items:flex-start;padding:1.5rem;background:white;border-radius:20px;box-shadow:var(--shadow-md);">
                        <div style="background:#dcfce7;color:#10b981;padding:15px;border-radius:12px;"><i class="fas fa-syringe fa-lg"></i></div>
                        <div><h4 style="color:#1e1b4b;margin-bottom:5px;">Control de Prevención</h4><p style="color:#64748b;font-size:.9rem;">Recordatorios automáticos de vacunas y visitas periódicas.</p></div>
                    </div>
                    <div style="display:flex;gap:20px;align-items:flex-start;padding:1.5rem;background:white;border-radius:20px;box-shadow:var(--shadow-md);">
                        <div style="background:#fef3c7;color:#f59e0b;padding:15px;border-radius:12px;"><i class="fas fa-robot fa-lg"></i></div>
                        <div><h4 style="color:#1e1b4b;margin-bottom:5px;">Chat con IA de síntomas</h4><p style="color:#64748b;font-size:.9rem;">Describe los síntomas de tu mascota y recibe orientación antes de ir al veterinario.</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- GAMIFICACIÓN -->
    <section id="gamificacion" class="section" style="background:#f8faff;position:relative;overflow:hidden;">
        <div class="container">
            <div class="cta-card gamification-card" style="background:white;border-radius:40px;padding:4rem;box-shadow:0 40px 80px rgba(124,58,237,.1);display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center;border:2px solid #f3e8ff;">
                <div>
                    <div class="tag" style="background:#FDB931;color:#000;display:inline-block;margin-bottom:1.5rem;font-weight:800;">🌱 RECOMPENSAS POR EL BUEN CUIDADO</div>
                    <h2 style="font-size:3rem;color:#1e1b4b;line-height:1.1;margin-bottom:1.5rem;">Cuidar Bien tiene sus <span style="color:#7c3aed;">Beneficios</span></h2>
                    <p style="font-size:1.1rem;color:#475569;margin-bottom:2rem;">Valoramos tu compromiso. Cumple metas de prevención y educación para acceder a beneficios exclusivos en la red aliada de RUGAL.</p>
                    <ul style="list-style:none;padding:0;margin-bottom:2.5rem;">
                        <li style="display:flex;align-items:center;gap:15px;margin-bottom:15px;color:#1e293b;">
                            <i class="fas fa-check-circle" style="color:#10b981;font-size:1.3rem;"></i>
                            <span><strong>Free:</strong> Recompensas con hasta <strong>10% de descuento</strong> en aliados</span>
                        </li>
                        <li style="display:flex;align-items:center;gap:15px;margin-bottom:15px;color:#1e293b;">
                            <i class="fas fa-crown" style="color:#FDB931;font-size:1.3rem;"></i>
                            <span><strong>Premium:</strong> Recompensas exclusivas con hasta <strong>50% de descuento</strong></span>
                        </li>
                        <li style="display:flex;align-items:center;gap:15px;color:#1e293b;">
                            <i class="fas fa-check-circle" style="color:#10b981;font-size:1.3rem;"></i>
                            <strong>Ranking y reconocimientos de comunidad</strong>
                        </li>
                    </ul>
                    <a href="registro.html" class="btn btn-primary btn-xl btn-glow"><i class="fas fa-trophy"></i> Unirse al Cuidado Activo</a>
                </div>
                <div style="text-align:center;">
                    <div style="background:#fdfbff;border-radius:50%;width:300px;height:300px;display:flex;flex-direction:column;align-items:center;justify-content:center;margin:0 auto;border:15px solid #f3e8ff;box-shadow:inset 0 0 50px rgba(124,58,237,.05);">
                        <div style="font-size:3.5rem;line-height:1;">🏆</div>
                        <div style="font-size:1.1rem;font-weight:800;color:#1e1b4b;margin-top:10px;">PUNTOS</div>
                        <div style="margin-top:12px;background:#dcfce7;color:#10b981;padding:7px 14px;border-radius:30px;font-weight:700;font-size:.78rem;">Y RECOMPENSAS</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PARA ALIADOS -->
    <section id="aliados" class="aliados-section">
        <div class="container">
            <div class="section-tag-light rv">🤝 Para veterinarias y tiendas</div>
            <h2 class="aliados-title rv">Más clientes.<br><span>Menos papeleo.</span></h2>
            <p class="aliados-subtitle rv">RUGAL le da a tu negocio una presencia digital completa en Cali. Panel de administración, agenda, estadísticas y clientes que ya usan la app a diario.</p>
            <div class="aliados-grid">
                <div class="aliado-card vet rv">
                    <span class="aliado-emoji">🏥</span>
                    <div class="aliado-type">Veterinarias</div>
                    <p class="aliado-desc">Gestiona tu agenda, historial de pacientes y estadísticas desde un panel profesional. Tus clientes te encuentran en el directorio.</p>
                    <ul class="benefit-list">
                        <li><span class="bcheck">✓</span> Agenda digital con horarios personalizados</li>
                        <li><span class="bcheck">✓</span> Historial clínico completo de cada paciente</li>
                        <li><span class="bcheck">✓</span> Preconsultas automáticas generadas con IA</li>
                        <li><span class="bcheck">✓</span> Visibilidad en el directorio de RUGAL</li>
                        <li><span class="bcheck">✓</span> Estadísticas de citas, ingresos y clientes</li>
                        <li><span class="bcheck">✓</span> Panel de promociones para atraer clientes nuevos</li>
                        <li><span class="bcheck">✓</span> Gestión de servicios y precios</li>
                    </ul>
                </div>
                <div class="aliado-card tienda rv">
                    <span class="aliado-emoji">🛍️</span>
                    <div class="aliado-type">Tiendas</div>
                    <p class="aliado-desc">Pon tu catálogo frente a los dueños de mascotas que ya usan RUGAL en tu ciudad. Visibilidad y ventas sin complicaciones.</p>
                    <ul class="benefit-list">
                        <li><span class="bcheck">✓</span> Catálogo de productos con fotos y precios</li>
                        <li><span class="bcheck">✓</span> Visibilidad en el mapa y directorio de RUGAL</li>
                        <li><span class="bcheck">✓</span> Gestión de inventario en tiempo real</li>
                        <li><span class="bcheck">✓</span> Panel de ventas y estadísticas</li>
                        <li><span class="bcheck">✓</span> Crea promociones visibles para todos los usuarios</li>
                        <li><span class="bcheck">✓</span> Los usuarios Premium canjean puntos contigo</li>
                        <li><span class="bcheck">✓</span> Acceso a clientes del perfil exacto de tu negocio</li>
                    </ul>
                </div>
            </div>

            <!-- ===== REGISTRO INTERACTIVO ALIADO ===== -->
            <div class="ally-reg-wrap rv" id="allyRegWrap" style="margin-top: 56px;">

                <!-- Selector de tipo -->
                <div style="text-align:center; margin-bottom:32px;">
                    <p style="color:rgba(255,255,255,.4);font-size:.85rem;text-transform:uppercase;letter-spacing:.12em;margin-bottom:16px;font-weight:700;">¿Con qué tipo de negocio te unirías?</p>
                    <div class="ally-type-tabs">
                        <button class="att-btn att-vet active" id="tab-vet" onclick="switchAllyType('veterinaria')">
                            <span class="att-icon">🏥</span>
                            <span class="att-label">Veterinaria</span>
                            <span class="att-sub">Clínica / Consultorio</span>
                        </button>
                        <button class="att-btn att-store" id="tab-store" onclick="switchAllyType('tienda')">
                            <span class="att-icon">🛍️</span>
                            <span class="att-label">Tienda</span>
                            <span class="att-sub">Pet Shop / Productos</span>
                        </button>
                    </div>
                </div>

                <!-- Formulario -->
                <div class="ally-reg-card" id="allyRegCard">
                    <!-- Header dinámico -->
                    <div class="arc-header">
                        <div class="arc-icon" id="arcIcon">🏥</div>
                        <div>
                            <div class="arc-title" id="arcTitle">Unirse como Veterinaria</div>
                            <div class="arc-sub">Completa tu solicitud — el admin verificará tu negocio</div>
                        </div>
                        <div class="arc-badge" id="arcBadge" style="background:rgba(52,211,153,.15);color:#34d399;border-color:rgba(52,211,153,.3);">
                            <i class="fas fa-hospital"></i> Veterinaria
                        </div>
                    </div>

                    <form id="inlineAllyForm" enctype="multipart/form-data">
                        <input type="hidden" name="rol" id="inlineRol" value="veterinaria">

                        <!-- Fila 1 -->
                        <div class="arc-grid">
                            <div class="arc-field">
                                <label>Nombre del local <span class="arc-req">*</span></label>
                                <input type="text" name="nombre_local" class="arc-input" required placeholder="Ej: Clínica VetCare">
                            </div>
                            <div class="arc-field">
                                <label>Tu nombre (responsable) <span class="arc-req">*</span></label>
                                <input type="text" name="nombre" class="arc-input" required placeholder="Tu nombre completo">
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="arc-field">
                            <label>Descripción del negocio <span class="arc-req">*</span></label>
                            <textarea name="descripcion" class="arc-input" rows="3" required placeholder="Cuéntanos sobre tu negocio, servicios, especialidades..."></textarea>
                        </div>

                        <!-- Campos específicos VET -->
                        <div class="arc-specific arc-vet-fields" id="inlineVetFields">
                            <div class="arc-grid">
                                <div class="arc-field">
                                    <label><i class="fas fa-stethoscope arc-field-icon vet"></i> Servicios principales</label>
                                    <input type="text" name="servicios" class="arc-input" placeholder="Ej: Consulta general, vacunación, cirugía">
                                </div>
                                <div class="arc-field">
                                    <label><i class="fas fa-tag arc-field-icon vet"></i> Precio consulta (COP)</label>
                                    <input type="number" name="precio_consulta" class="arc-input" placeholder="50000">
                                </div>
                            </div>
                        </div>

                        <!-- Campos específicos TIENDA -->
                        <div class="arc-specific arc-store-fields" id="inlineStoreFields" style="display:none;opacity:0;">
                            <div class="arc-grid">
                                <div class="arc-field">
                                    <label><i class="fas fa-box-open arc-field-icon store"></i> Tipo de productos</label>
                                    <input type="text" name="tipo_alimento" class="arc-input" placeholder="Ej: Alimento premium, juguetes, accesorios">
                                </div>
                                <div class="arc-field">
                                    <label><i class="fas fa-dog arc-field-icon store"></i> Razas especializadas (opcional)</label>
                                    <input type="text" name="razas_recomendadas" class="arc-input" placeholder="Ej: Bulldog, Labrador, Poodle">
                                </div>
                            </div>
                        </div>

                        <!-- Dirección + Maps -->
                        <div class="arc-grid">
                            <div class="arc-field" style="grid-column: 1 / -1;">
                                <label><i class="fas fa-map-marker-alt" style="color:#a78bfa;margin-right:5px;"></i> Dirección <span class="arc-req">*</span></label>
                                <input type="text" name="direccion" class="arc-input" required placeholder="Ej: Cra 5 #12-34, Cali">
                            </div>
                        </div>

                        <!-- Email + Tel -->
                        <div class="arc-grid">
                            <div class="arc-field">
                                <label>Email <span class="arc-req">*</span></label>
                                <input type="email" name="email" class="arc-input" required placeholder="correo@negocio.com">
                            </div>
                            <div class="arc-field">
                                <label>Teléfono <span class="arc-req">*</span></label>
                                <input type="tel" name="telefono" class="arc-input" required placeholder="3001234567">
                            </div>
                        </div>

                        <!-- Contraseña -->
                        <div class="arc-grid">
                            <div class="arc-field">
                                <label>Contraseña <span class="arc-req">*</span> <small style="color:rgba(255,255,255,.35);">(mín. 8 caracteres)</small></label>
                                <input type="password" name="password" class="arc-input" id="inlinePwd" required minlength="8" placeholder="••••••••">
                            </div>
                            <div class="arc-field">
                                <label>Confirmar contraseña <span class="arc-req">*</span></label>
                                <input type="password" name="confirmPassword" class="arc-input" id="inlinePwdC" required placeholder="••••••••">
                            </div>
                        </div>

                        <!-- Fotos -->
                        <div class="arc-field">
                            <label><i class="fas fa-camera" style="color:#a78bfa;margin-right:5px;"></i> Fotos del local <span class="arc-req">*</span> <small style="color:rgba(255,255,255,.35);">(1–3 fotos reales)</small></label>
                            <div class="arc-upload" onclick="document.getElementById('inlinePhotos').click()">
                                <input type="file" id="inlinePhotos" name="fotos_local[]" accept="image/*" multiple style="display:none;" onchange="previewInlinePhotos(this)">
                                <i class="fas fa-cloud-upload-alt" style="font-size:26px;color:#a78bfa;margin-bottom:6px;display:block;"></i>
                                <span style="font-size:13px;color:rgba(255,255,255,.5);">Haz clic para subir fotos de tu local</span>
                                <span style="font-size:11px;color:rgba(255,255,255,.3);margin-top:3px;display:block;">JPG, PNG o WebP · Máx. 5MB por foto</span>
                            </div>
                            <div class="arc-photo-previews" id="inlinePhotoPreviews"></div>
                        </div>

                        <!-- Terms -->
                        <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:20px;">
                            <input type="checkbox" name="terms" id="inlineTerms" required style="margin-top:3px;width:16px;height:16px;accent-color:#a78bfa;flex-shrink:0;">
                            <label for="inlineTerms" style="font-size:13px;color:rgba(255,255,255,.5);cursor:pointer;">
                                Acepto los <a href="#" style="color:#a78bfa;">términos y condiciones</a> para ser aliado RUGAL. Entiendo que mi cuenta será verificada antes de activarse.
                            </label>
                        </div>

                        <!-- Error global -->
                        <div id="inlineError" style="display:none;background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:12px 16px;font-size:13px;color:#fca5a5;margin-bottom:16px;"></div>

                        <!-- Submit -->
                        <button type="submit" class="arc-submit" id="arcSubmit">
                            <span id="arcSubmitText"><i class="fas fa-paper-plane"></i> Enviar Solicitud de Registro</span>
                        </button>
                    </form>

                    <!-- Success panel -->
                    <div class="arc-success" id="arcSuccess" style="display:none;">
                        <h3 style="color:white;font-size:22px;font-weight:800;margin-bottom:10px;">✅ Solicitud enviada correctamente.</h3>
                        <p style="color:rgba(255,255,255,.6);line-height:1.7;max-width:380px;margin:0 auto;">El equipo RUGAL revisará tu información y te contactará al número registrado en máximo 24 horas.</p>
                        <a href="login.php" style="display:inline-flex;align-items:center;gap:8px;margin-top:20px;padding:12px 28px;background:linear-gradient(135deg,#7c3aed,#a855f7);color:white;border-radius:12px;text-decoration:none;font-weight:700;font-size:14px;">
                            <i class="fas fa-sign-in-alt"></i> Ir al Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
    /* ===== ALLY REGISTRATION INLINE ===== */
    .ally-type-tabs {
        display: inline-flex; gap: 14px;
        background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1);
        border-radius: 20px; padding: 8px;
    }
    .att-btn {
        display: flex; flex-direction: column; align-items: center; gap: 2px;
        padding: 14px 28px; border-radius: 14px; border: none; cursor: pointer;
        background: transparent; transition: all .3s; font-family: 'Inter', sans-serif;
        min-width: 130px;
    }
    .att-btn.active.att-vet { background: rgba(52,211,153,.15); border: 1.5px solid rgba(52,211,153,.35); }
    .att-btn.active.att-store { background: rgba(167,139,250,.15); border: 1.5px solid rgba(167,139,250,.35); }
    .att-btn:not(.active) { border: 1.5px solid transparent; }
    .att-btn:not(.active):hover { background: rgba(255,255,255,.05); }
    .att-icon { font-size: 1.9rem; line-height: 1; display: block; }
    .att-label { font-size: .95rem; font-weight: 800; color: white; }
    .att-sub { font-size: .72rem; color: rgba(255,255,255,.35); font-weight: 500; }
    .att-btn.active .att-sub { color: rgba(255,255,255,.55); }

    .ally-reg-card {
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 24px; padding: 36px 40px;
        max-width: 780px; margin: 0 auto;
        backdrop-filter: blur(6px);
        transition: border-color .4s;
    }
    .ally-reg-card.mode-vet   { border-color: rgba(52,211,153,.25); }
    .ally-reg-card.mode-store { border-color: rgba(167,139,250,.25); }

    .arc-header {
        display: flex; align-items: center; gap: 16px; margin-bottom: 28px;
        padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,.08);
    }
    .arc-icon { font-size: 2.2rem; }
    .arc-title { font-size: 1.25rem; font-weight: 800; color: white; }
    .arc-sub { font-size: .82rem; color: rgba(255,255,255,.4); margin-top: 2px; }
    .arc-badge {
        margin-left: auto; padding: 5px 14px; border-radius: 20px;
        font-size: 12px; font-weight: 700; border: 1px solid;
        white-space: nowrap; display: flex; align-items: center; gap: 6px;
    }

    .arc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    @media(max-width:600px){ .arc-grid { grid-template-columns: 1fr; } }
    .arc-field { margin-bottom: 16px; }
    .arc-field label { display: block; font-size: 13px; font-weight: 600; color: rgba(255,255,255,.6); margin-bottom: 7px; }
    .arc-req { color: #f87171; }
    .arc-field-icon { margin-right: 5px; }
    .arc-field-icon.vet   { color: #34d399; }
    .arc-field-icon.store { color: #a78bfa; }

    .arc-input {
        width: 100%; padding: 12px 16px;
        background: rgba(255,255,255,.07); border: 1.5px solid rgba(255,255,255,.12);
        border-radius: 10px; color: white; font-size: 14px; font-family: 'Inter', sans-serif;
        transition: all .25s; outline: none;
    }
    .arc-input::placeholder { color: rgba(255,255,255,.25); }
    .arc-input:focus { border-color: #a78bfa; background: rgba(167,139,250,.08); box-shadow: 0 0 0 3px rgba(167,139,250,.1); }
    textarea.arc-input { resize: vertical; min-height: 80px; }

    /* Animated field group */
    .arc-specific {
        overflow: hidden; transition: max-height .4s ease, opacity .35s ease;
        max-height: 200px; opacity: 1;
    }
    .arc-specific.collapsed { max-height: 0; opacity: 0; pointer-events: none; }

    .arc-upload {
        border: 2px dashed rgba(167,139,250,.3); border-radius: 12px;
        padding: 22px; text-align: center; cursor: pointer;
        background: rgba(167,139,250,.04); transition: all .3s;
    }
    .arc-upload:hover { border-color: rgba(167,139,250,.6); background: rgba(167,139,250,.08); }

    .arc-photo-previews {
        display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;
    }
    .arc-photo-item {
        width: 72px; height: 72px; border-radius: 8px; overflow: hidden;
        border: 1.5px solid rgba(255,255,255,.15); position: relative;
    }
    .arc-photo-item img { width: 100%; height: 100%; object-fit: cover; }

    .arc-submit {
        width: 100%; padding: 15px; border: none; border-radius: 12px; cursor: pointer;
        font-family: 'Inter', sans-serif; font-size: 15px; font-weight: 700;
        background: linear-gradient(135deg, #7c3aed, #a855f7); color: white;
        transition: all .3s; position: relative;
    }
    .arc-submit:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(124,58,237,.4); }
    .arc-submit:disabled { opacity: .65; cursor: not-allowed; transform: none !important; }
    .arc-submit.mode-vet   { background: linear-gradient(135deg, #10b981, #34d399); box-shadow: none; }
    .arc-submit.mode-vet:hover { box-shadow: 0 12px 28px rgba(16,185,129,.4); }

    .arc-success { text-align: center; padding: 20px 0; animation: arcFadeIn .5s ease; }
    .arc-success-icon { font-size: 52px; margin-bottom: 14px; animation: arcPop .5s ease; }
    @keyframes arcFadeIn { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform: translateY(0); } }
    @keyframes arcPop { 0%{transform:scale(0)} 70%{transform:scale(1.15)} 100%{transform:scale(1)} }

    @media(max-width:600px){
        .ally-reg-card { padding: 24px 18px; }
        .ally-type-tabs { flex-direction: column; width: 100%; }
        .att-btn { flex-direction: row; justify-content: center; gap: 10px; min-width: auto; }
        .arc-header { flex-wrap: wrap; }
        .arc-badge { display: none; }
    }
    </style>



    <!-- PRECIOS -->
    <section id="precios" class="pricing-section">
        <div class="container">
            <div style="text-align:center;">
                <div class="pricing-inner-tag rv">Planes y precios</div>
                <h2 class="section-title rv" style="color:#1e1b4b;">Transparente, sin <span class="text-gradient">sorpresas</span></h2>
                <p class="section-subtitle rv" style="color:#64748b;">El primer mes de aliado es completamente gratis. Sin contratos.</p>
            </div>
            <div class="pricing-tabs rv">
                <button class="p-tab active" onclick="switchPricing('usuarios', this)">Usuarios</button>
                <button class="p-tab"        onclick="switchPricing('aliados', this)">Aliados</button>
            </div>

            <!-- USUARIOS -->
            <div class="pricing-grid p-panel active rv" id="pp-usuarios">
                <div class="p-card rv">
                    <p class="p-name">Free</p>
                    <div class="p-price"><sup>$</sup>0 <small>COP</small></div>
                    <p class="p-period">Para siempre gratis · sin tarjeta</p>
                    <div class="p-divider"></div>
                    <ul class="p-features">
                        <li><span class="p-yes">✓</span> 1 mascota registrada</li>
                        <li><span class="p-yes">✓</span> Vacunas y recordatorios automáticos</li>
                        <li><span class="p-yes">✓</span> Agenda de citas básica</li>
                        <li><span class="p-yes">✓</span> Seguimiento diario de salud</li>
                        <li><span class="p-yes">✓</span> Comunidad y educación</li>
                        <li><span class="p-yes">✓</span> Recompensas básicas <small style="color:#94a3b8;">(hasta 10% desc.)</small></li>
                        <li><span class="p-no">✗</span> Plan mensual de salud con IA</li>
                        <li><span class="p-no">✗</span> Chat con IA de síntomas</li>
                        <li><span class="p-no">✗</span> Historial clínico completo</li>
                    </ul>
                    <a href="registro.html" class="btn-plan">Registrarme gratis</a>
                </div>
                <div class="p-card featured rv">
                    <div class="featured-badge">⭐ Más popular</div>
                    <p class="p-name">Premium</p>
                    <div class="p-price"><sup>$</sup>12.500 <small>COP</small></div>
                    <p class="p-period">por mes · cancela cuando quieras</p>
                    <div class="p-divider"></div>
                    <ul class="p-features">
                        <li><span class="p-yes">✓</span> Mascotas ilimitadas</li>
                        <li><span class="p-yes">✓</span> Todo lo del plan Free</li>
                        <li><span class="p-yes">✓</span> <strong>Plan mensual de salud con IA</strong><br><small style="color:#7c3aed;margin-left:20px;">Rutina diaria con consejos personalizados</small></li>
                        <li><span class="p-yes">✓</span> Chat con IA de síntomas</li>
                        <li><span class="p-yes">✓</span> Historial clínico completo</li>
                        <li><span class="p-yes">✓</span> <strong>Recompensas Premium</strong><br><small style="color:#7c3aed;margin-left:20px;">Hasta 50% de descuento en aliados</small></li>
                        <li><span class="p-yes">✓</span> Acceso prioritario a promociones</li>
                        <li><span class="p-yes">✓</span> Soporte prioritario</li>
                    </ul>
                    <a href="upgrade-premium.php" class="btn-plan primary">Activar Premium — $12.500/mes</a>
                </div>
            </div>

            <!-- ALIADOS -->
            <div class="pricing-grid p-panel" id="pp-aliados" style="display:none; max-width:460px;">
                <div class="p-card featured rv">
                    <div class="featured-badge">🤝 Plan único para todos</div>
                    <p class="p-name">Plan Aliado</p>
                    <div class="p-price"><sup>$</sup>55.000 <small>COP</small></div>
                    <p class="p-period">por mes · <strong style="color:#10b981;">1er mes COMPLETAMENTE GRATIS</strong></p>
                    <div class="p-divider"></div>
                    <ul class="p-features">
                        <li><span class="p-yes">✓</span> Perfil verificado en el directorio RUGAL</li>
                        <li><span class="p-yes">✓</span> Agenda digital con horarios personalizados</li>
                        <li><span class="p-yes">✓</span> Catálogo de productos y servicios</li>
                        <li><span class="p-yes">✓</span> Historial clínico de pacientes</li>
                        <li><span class="p-yes">✓</span> Preconsultas automáticas con IA</li>
                        <li><span class="p-yes">✓</span> Panel de ventas y estadísticas completas</li>
                        <li><span class="p-yes">✓</span> Promociones destacadas para tus clientes</li>
                        <li><span class="p-yes">✓</span> Usuarios Premium canjean puntos contigo</li>
                        <li><span class="p-yes">✓</span> Soporte directo prioritario</li>
                    </ul>
                    <a href="#contacto" class="btn-plan primary">Empezar gratis el 1er mes</a>
                </div>
            </div>
        </div>
    </section>

    <!-- COMUNIDAD -->
    <section id="comunidad" class="section" style="background:white;">
        <div class="container">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="animate-slide-left">
                    <div class="tag" style="background:rgba(124,58,237,.1);color:#7c3aed;display:inline-block;margin-bottom:1rem;">🤝 COMUNIDAD REAL</div>
                    <h2 class="section-title" style="color:#1e1b4b;text-align:left;">Construyendo una <span class="text-gradient">Cercanía Sana</span></h2>
                    <p class="section-subtitle" style="text-align:left;font-size:1.1rem;">Un espacio seguro para crecer juntos en el conocimiento animal.</p>
                    <p style="color:#475569;font-size:1rem;line-height:1.8;margin-top:1.5rem;">RUGAL conecta a propietarios responsables para compartir experiencias y consejos útiles, creando una red de apoyo mutuo centrada en el bienestar real de nuestras mascotas en Cali.</p>
                </div>
                <div class="animate-slide-right" style="position:relative;">
                    <img src="comunidad.jpeg" alt="Sociedad Sana RUGAL" style="border-radius:40px;box-shadow:0 20px 50px rgba(0,0,0,.1);width:100%;height:auto;object-fit:cover;">
                    <div style="position:absolute;bottom:30px;left:30px;background:var(--gradient-primary);padding:1.5rem 2rem;border-radius:25px;color:white;box-shadow:0 15px 30px rgba(124,58,237,.3);">
                        <h4 style="margin:0;font-size:1.3rem;">Comunidad Local · Cali 🇨🇴</h4>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CÓMO FUNCIONA -->
    <section class="steps-section">
        <div class="container">
            <div style="text-align:center;">
                <div class="tag" style="background:rgba(124,58,237,.1);color:#7c3aed;display:inline-block;margin-bottom:1rem;">¿Cómo funciona?</div>
                <h2 class="section-title rv" style="color:#1e1b4b;">Empieza en <span class="text-gradient">minutos</span></h2>
                <p class="section-subtitle rv" style="color:#64748b;">Registrarte es gratis y no requiere tarjeta de crédito</p>
            </div>
            <div class="steps-grid">
                <div class="step-card rv"><div class="step-num">1</div><h3>Regístrate</h3><p>Crea tu cuenta como dueño, veterinaria o tienda. Gratis en menos de 2 minutos.</p></div>
                <div class="step-card rv"><div class="step-num">2</div><h3>Configura tu perfil</h3><p>Agrega tus mascotas o configura tu negocio con horarios, servicios y productos.</p></div>
                <div class="step-card rv"><div class="step-num">3</div><h3>Conéctate</h3><p>Agenda citas, gestiona pacientes, vende productos o canjea recompensas.</p></div>
                <div class="step-card rv"><div class="step-num">4</div><h3>Crece</h3><p>Más clientes, mejor salud para las mascotas y un negocio más organizado.</p></div>
            </div>
        </div>
    </section>

    <!-- CTA WHATSAPP -->
    <section id="contacto" class="cta-ws-section">
        <div class="container">
            <div class="cta-ws-box rv">
                <div class="tag" style="background:rgba(167,139,250,.15);color:#a78bfa;display:inline-block;margin-bottom:1.2rem;">¿Listo para empezar?</div>
                <h2 class="cta-ws-title">1er mes de aliado <span>completamente gratis</span></h2>
                <p class="cta-ws-sub">Sin tarjeta de crédito. Sin contratos. Escríbenos por WhatsApp y te activamos la cuenta hoy mismo.</p>
                <a href="https://wa.me/573167197604?text=Hola%2C%20quiero%20unirme%20a%20RUGAL%20%F0%9F%90%BE" class="btn-ws">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Escribir por WhatsApp
                </a>
                <p class="cta-ws-note">También: <a href="mailto:a4ntiag0@gmail.com">a4ntiag0@gmail.com</a></p>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer" style="background:#1e1b4b;color:white;padding:80px 0 50px;">
        <div class="container">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                <div>
                    <a href="index.php" style="display:flex;align-items:center;gap:12px;margin-bottom:1.5rem;text-decoration:none;">
                        <div style="background:white;color:#7c3aed;width:45px;height:45px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;"><i class="fas fa-paw"></i></div>
                        <span style="color:white;font-weight:800;font-size:1.8rem;letter-spacing:-1px;">RUGAL</span>
                    </a>
                    <p style="color:rgba(255,255,255,.6);line-height:1.8;font-size:.95rem;">Tu aliado local para un cuidado organizado y consciente. Hecho en Cali para el bienestar de cada mascota.</p>
                    <div style="display:flex;gap:12px;margin-top:1.8rem;">
                        <a href="#" style="color:white;background:rgba(255,255,255,.1);width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color:white;background:rgba(255,255,255,.1);width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;"><i class="fab fa-tiktok"></i></a>
                        <a href="https://wa.me/573000000000" style="color:white;background:rgba(37,211,102,.2);width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;text-decoration:none;"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div>
                    <h4 style="color:white;font-weight:700;margin-bottom:1.4rem;">Plataforma</h4>
                    <ul style="list-style:none;padding:0;">
                        <li style="margin-bottom:.85rem;"><a href="#temas"        style="color:rgba(255,255,255,.55);text-decoration:none;">Plan de Salud</a></li>
                        <li style="margin-bottom:.85rem;"><a href="#gamificacion" style="color:rgba(255,255,255,.55);text-decoration:none;">Recompensas</a></li>
                        <li style="margin-bottom:.85rem;"><a href="#comunidad"    style="color:rgba(255,255,255,.55);text-decoration:none;">Comunidad</a></li>
                        <li style="margin-bottom:.85rem;"><a href="#precios"      style="color:rgba(255,255,255,.55);text-decoration:none;">Precios</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="color:white;font-weight:700;margin-bottom:1.4rem;">Aliados</h4>
                    <ul style="list-style:none;padding:0;">
                        <li style="margin-bottom:.85rem;"><a href="#precios" style="color:rgba(255,255,255,.55);text-decoration:none;">Ser veterinaria aliada</a></li>
                        <li style="margin-bottom:.85rem;"><a href="#precios" style="color:rgba(255,255,255,.55);text-decoration:none;">Ser tienda aliada</a></li>
                        <li style="margin-bottom:.85rem;"><a href="#precios"             style="color:rgba(255,255,255,.55);text-decoration:none;">Ver plan aliado</a></li>
                        <li style="margin-bottom:.85rem;"><a href="#contacto"            style="color:rgba(255,255,255,.55);text-decoration:none;">Primer mes gratis</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="color:white;font-weight:700;margin-bottom:1.4rem;">Contacto</h4>
                    <p style="color:rgba(255,255,255,.55);font-size:.9rem;margin-bottom:1rem;"><i class="fas fa-envelope" style="color:#7c3aed;margin-right:8px;"></i> a4ntiag0@gmail.com</p>
                    <p style="color:rgba(255,255,255,.55);font-size:.9rem;margin-bottom:1rem;"><i class="fab fa-whatsapp" style="color:#25D366;margin-right:8px;"></i> WhatsApp directo</p>
                    <p style="color:rgba(255,255,255,.55);font-size:.9rem;"><i class="fas fa-map-marker-alt" style="color:#7c3aed;margin-right:8px;"></i> Cali, Valle del Cauca</p>
                </div>
            </div>
            <div style="border-top:1px solid rgba(255,255,255,.05);margin-top:56px;padding-top:26px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:14px;">
                <p style="color:rgba(255,255,255,.35);font-size:.85rem;margin:0;">© 2025 RUGAL · Hecho en Colombia 🇨🇴</p>
                <p style="color:rgba(255,255,255,.22);font-size:.75rem;margin:0;font-style:italic;">RUGAL es una plataforma de apoyo preventivo. No reemplaza la atención de un médico veterinario habilitado.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
    <script>
        window.addEventListener('scroll', () => {
            document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 50);
        });

        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById('mobileToggle');
            const menu   = document.getElementById('navbarMenu');
            const overlay = document.getElementById('mobileOverlay');
            if (!toggle || !menu) return;

            const open  = () => { menu.classList.add('active'); overlay?.classList.add('active'); document.body.style.overflow='hidden'; document.body.classList.add('menu-open'); toggle.innerHTML='<i class="fas fa-times"></i>'; };
            const close = () => { menu.classList.remove('active'); overlay?.classList.remove('active'); document.body.style.overflow=''; document.body.classList.remove('menu-open'); toggle.innerHTML='<i class="fas fa-bars"></i>'; };

            toggle.addEventListener('click', () => menu.classList.contains('active') ? close() : open());
            overlay?.addEventListener('click', close);
            document.querySelectorAll('.navbar-link').forEach(l => l.addEventListener('click', close));
            window.closeMobileMenu = close;

            // Cursor
            const dot = document.querySelector('.cursor-dot');
            const outline = document.querySelector('.cursor-outline');
            if (dot && outline) {
                window.addEventListener('mousemove', e => {
                    dot.style.left = e.clientX+'px'; dot.style.top = e.clientY+'px';
                    outline.animate({ left: e.clientX+'px', top: e.clientY+'px' }, { duration:500, fill:'forwards' });
                });
                document.querySelectorAll('button, a, .aliado-card, .p-card, .step-card').forEach(el => {
                    el.addEventListener('mouseenter', () => { dot.style.transform='scale(1.5)'; outline.style.transform='scale(1.2)'; });
                    el.addEventListener('mouseleave', () => { dot.style.transform='scale(1)'; outline.style.transform='scale(1)'; });
                });
            }

            // Animaciones existentes
            const animated = document.querySelectorAll('.animate-slide-left, .animate-slide-right, .feature-card');
            const obs = new IntersectionObserver(entries => {
                entries.forEach(e => { if(e.isIntersecting){ e.target.classList.add('appear'); obs.unobserve(e.target); } });
            }, { threshold: 0.1 });
            animated.forEach(el => obs.observe(el));
        });

        // Carousel
        let cur = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const inds   = document.querySelectorAll('.indicator');
        function gotoSlide(n) {
            slides[cur].classList.remove('active'); inds[cur].classList.remove('active');
            cur = n;
            slides[cur].classList.add('active'); inds[cur].classList.add('active');
        }
        let si = setInterval(() => gotoSlide((cur+1) % slides.length), 3000);
        inds.forEach(i => i.addEventListener('click', () => { clearInterval(si); si = setInterval(() => gotoSlide((cur+1)%slides.length), 3000); }));

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                const t = document.querySelector(a.getAttribute('href'));
                if (!t) return; e.preventDefault();
                if (typeof window.closeMobileMenu === 'function') window.closeMobileMenu();
                setTimeout(() => window.scrollTo({ top: t.offsetTop - 80, behavior:'smooth' }), 140);
            });
        });

        // Pricing tabs
        function switchPricing(tab, btn) {
            document.querySelectorAll('.p-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.p-panel').forEach(p => { p.style.display='none'; p.classList.remove('active'); });
            btn.classList.add('active');
            const panel = document.getElementById('pp-'+tab);
            panel.style.display = 'grid'; panel.classList.add('active');
        }

        // Reveal scroll
        const rvEls = document.querySelectorAll('.rv');
        const rvObs = new IntersectionObserver(entries => {
            entries.forEach((e, i) => {
                if (e.isIntersecting) { setTimeout(() => e.target.classList.add('show'), i * 80); rvObs.unobserve(e.target); }
            });
        }, { threshold: 0.1 });
        rvEls.forEach(el => rvObs.observe(el));

        // ===== INLINE ALLY REGISTRATION =====
        let currentAllyType = 'veterinaria';

        function switchAllyType(type) {
            currentAllyType = type;
            const card   = document.getElementById('allyRegCard');
            const vetTab = document.getElementById('tab-vet');
            const storeTab = document.getElementById('tab-store');
            const vetFields   = document.getElementById('inlineVetFields');
            const storeFields = document.getElementById('inlineStoreFields');
            const icon   = document.getElementById('arcIcon');
            const title  = document.getElementById('arcTitle');
            const badge  = document.getElementById('arcBadge');
            const submit = document.getElementById('arcSubmit');

            // Reset tabs
            vetTab.classList.remove('active');
            storeTab.classList.remove('active');
            card.classList.remove('mode-vet', 'mode-store');
            submit.classList.remove('mode-vet');

            document.getElementById('inlineRol').value = type;

            if (type === 'veterinaria') {
                vetTab.classList.add('active');
                card.classList.add('mode-vet');
                icon.textContent = '🏥';
                title.textContent = 'Unirse como Veterinaria';
                badge.innerHTML = '<i class="fas fa-hospital"></i> Veterinaria';
                badge.style.background = 'rgba(52,211,153,.15)';
                badge.style.color = '#34d399';
                badge.style.borderColor = 'rgba(52,211,153,.3)';
                submit.classList.add('mode-vet');

                // Animate: collapse store, expand vet
                storeFields.classList.add('collapsed');
                setTimeout(() => {
                    storeFields.style.display = 'none';
                    vetFields.style.display = 'block';
                    vetFields.style.opacity = '0';
                    requestAnimationFrame(() => {
                        vetFields.classList.remove('collapsed');
                        setTimeout(() => { vetFields.style.opacity = '1'; }, 20);
                    });
                }, 300);
            } else {
                storeTab.classList.add('active');
                card.classList.add('mode-store');
                icon.textContent = '🛍️';
                title.textContent = 'Unirse como Tienda';
                badge.innerHTML = '<i class="fas fa-store"></i> Tienda';
                badge.style.background = 'rgba(167,139,250,.15)';
                badge.style.color = '#a78bfa';
                badge.style.borderColor = 'rgba(167,139,250,.3)';

                // Animate: collapse vet, expand store
                vetFields.classList.add('collapsed');
                setTimeout(() => {
                    vetFields.style.display = 'none';
                    storeFields.style.display = 'block';
                    storeFields.style.opacity = '0';
                    requestAnimationFrame(() => {
                        storeFields.classList.remove('collapsed');
                        setTimeout(() => { storeFields.style.opacity = '1'; }, 20);
                    });
                }, 300);
            }
        }

        function previewInlinePhotos(input) {
            const container = document.getElementById('inlinePhotoPreviews');
            container.innerHTML = '';
            Array.from(input.files).slice(0, 3).forEach(file => {
                const reader = new FileReader();
                reader.onload = e => {
                    const div = document.createElement('div');
                    div.className = 'arc-photo-item';
                    div.innerHTML = `<img src="${e.target.result}" alt="Foto local">`;
                    container.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('inlineAllyForm');
            if (!form) return;

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const errDiv = document.getElementById('inlineError');
                const submit = document.getElementById('arcSubmit');
                const submitText = document.getElementById('arcSubmitText');
                errDiv.style.display = 'none';

                // Basic client validation
                const pwd   = document.getElementById('inlinePwd').value;
                const pwdC  = document.getElementById('inlinePwdC').value;
                if (pwd !== pwdC) {
                    errDiv.textContent = 'Las contraseñas no coinciden.';
                    errDiv.style.display = 'block';
                    return;
                }
                const fotos = document.getElementById('inlinePhotos').files;
                if (fotos.length === 0) {
                    errDiv.textContent = 'Por favor sube al menos 1 foto de tu local para verificación.';
                    errDiv.style.display = 'block';
                    return;
                }

                submit.disabled = true;
                submitText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando solicitud...';

                try {
                    const fd = new FormData(form);
                    const res = await fetch('proceso-registro.php', { method: 'POST', body: fd });
                    const data = await res.json();

                    if (data.success) {
                        form.style.display = 'none';
                        document.getElementById('arcSuccess').style.display = 'block';
                        // Scroll to success
                        document.getElementById('arcSuccess').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        errDiv.textContent = data.message || 'Error en el registro. Verifica los campos e intenta nuevamente.';
                        errDiv.style.display = 'block';
                        submit.disabled = false;
                        submitText.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Solicitud de Registro';
                    }
                } catch {
                    errDiv.textContent = 'Error de conexión. Intenta nuevamente.';
                    errDiv.style.display = 'block';
                    submit.disabled = false;
                    submitText.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Solicitud de Registro';
                }
            });
        });
    </script>

</body>
</html>