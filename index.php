<?php
ini_set('display_errors', 1);
error_reporting(E_ALL); 
session_start(); // Nécessaire pour détecter si l'admin est connecté

// 1. ON INCLUT D'ABORD LA BASE DE DONNÉES (Crée la variable $pdo)
require_once 'includes/db.php';

// 2. SCRIPT POUR L'ACTUALISATION AUTOMATIQUE EN TEMPS RÉEL (AJAX)
if (isset($_GET['api_live_update'])) {
    header('Content-Type: application/json');
    
    // Récupérer les produits
    $qProducts = $pdo->query("SELECT id, nom, format, prix, stock, image_url, en_solde FROM products ORDER BY id DESC");
    $allProducts = $qProducts->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer la galerie
    $qGallery = $pdo->query("SELECT id, titre, image_url FROM gallery ORDER BY created_at DESC LIMIT 8");
    $allGallery = $qGallery->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['products' => $allProducts, 'gallery' => $allGallery]);
    exit;
}

// 3. CHARGEMENT INITIAL DE LA PAGE (REQUÊTES TRADITIONNELLES)
$queryProducts = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$produits = $queryProducts->fetchAll();

$queryGallery = $pdo->query("SELECT * FROM gallery ORDER BY created_at DESC LIMIT 8");
$photos = $queryGallery->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gala Agro | L'excellence onctueuse</title>
    
    <script src="assets/tailwind.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    
     <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                    colors: {
                        galaGreen: '#059669',
                        galaDark: '#007A3D',
                        galaGold: '#f59e0b'
                    }
                }
            }
        }
    </script>

    <style>
        .glass-morphism { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
        /* ══════════════════════════════════════════
           HAMBURGER BUTTON — 2026 EDITION
        ══════════════════════════════════════════ */
        #menu-btn {
            position: relative;
            width: 42px; height: 42px;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 5px;
            background: rgba(0,122,61,0.06);
            border: 1.5px solid rgba(0,122,61,0.12);
            border-radius: 14px;
            cursor: pointer;
            transition: background 0.25s, border-color 0.25s, transform 0.15s;
            overflow: hidden;
        }
        @media (min-width: 1024px) {
            #menu-btn { display: none !important; }
            #mobile-nav, #nav-overlay { display: none !important; }
        }
        #menu-btn::after {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(circle at center, rgba(0,122,61,0.15) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        #menu-btn:active::after { opacity: 1; }
        #menu-btn:active { transform: scale(0.93); }
        #menu-btn.open {
            background: rgba(0,122,61,0.1);
            border-color: rgba(0,122,61,0.25);
        }
        .bar {
            display: block;
            height: 2px; border-radius: 99px;
            background: #007A3D;
            transform-origin: center;
            transition: transform 0.45s cubic-bezier(0.23,1,0.32,1),
                        opacity 0.3s ease, width 0.35s cubic-bezier(0.23,1,0.32,1);
        }
        .bar:nth-child(1) { width: 20px; }
        .bar:nth-child(2) { width: 14px; align-self: flex-start; margin-left: 9px; }
        .bar:nth-child(3) { width: 18px; }
        #menu-btn.open .bar:nth-child(1) { width: 20px; transform: translateY(7px) rotate(45deg); }
        #menu-btn.open .bar:nth-child(2) { opacity: 0; transform: scaleX(0); }
        #menu-btn.open .bar:nth-child(3) { width: 20px; transform: translateY(-7px) rotate(-45deg); }

        /* ══════════════════════════════════════════
           OVERLAY
        ══════════════════════════════════════════ */
        #nav-overlay {
            position: fixed; inset: 0; z-index: 8997;
            background: transparent;
            pointer-events: none;
            transition: background 0.45s ease;
        }
        #nav-overlay.active {
            background: rgba(2, 6, 23, 0.65);
            pointer-events: auto;
        }

        /* ══════════════════════════════════════════
           MOBILE NAV DRAWER — FULL PREMIUM 2026
        ══════════════════════════════════════════ */
        #mobile-nav {
            position: fixed;
            top: 0; right: 0; bottom: 0;
            width: min(88vw, 340px);
            z-index: 8999;
            display: flex; flex-direction: column;
            overflow: hidden;
            transform: translateX(105%);
            transition: transform 0.48s cubic-bezier(0.16, 1, 0.3, 1);

            background: #ffffff;
            border-left: 1px solid #f1f5f9;
        }
        #mobile-nav::before { display: none; }
        #mobile-nav > * { position: relative; z-index: 1; }
        #mobile-nav.active { transform: translateX(0); }

        /* ── Header ── */
        .mnh {
            display: flex; align-items: center; justify-content: space-between;
            padding: 52px 22px 18px;
        }
        .mnh-brand { display: flex; align-items: center; gap: 10px; }
        .mnh-logo {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, #007A3D, #059669);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 900; font-size: 16px;
            box-shadow: 0 4px 14px rgba(0,122,61,0.5);
        }
        .mnh-name { font-size: 1.05rem; font-weight: 900; color: #007A3D; letter-spacing: -0.01em; }
        .mnh-name span { color: #E30613; }
        .mnh-tag {
            font-size: 0.6rem; font-weight: 700; letter-spacing: 0.15em;
            color: #94a3b8; text-transform: uppercase;
            margin-top: 1px;
        }
        .mnh-close {
            width: 36px; height: 36px; border-radius: 10px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: center;
            color: #64748b; font-size: 14px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, transform 0.15s;
        }
        .mnh-close:active { background: #e2e8f0; color: #007A3D; transform: scale(0.92); }

        /* ── Divider ── */
        .mn-divider {
            height: 1px; margin: 0 22px 6px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
        }

        /* ── Nav Body ── */
        .mn-body { flex: 1; overflow-y: auto; padding: 8px 14px; scrollbar-width: none; }
        .mn-body::-webkit-scrollbar { display: none; }

        .mn-section-label {
            font-size: 0.6rem; font-weight: 800; letter-spacing: 0.2em;
            color: #94a3b8; text-transform: uppercase;
            padding: 10px 10px 6px;
        }

        .mn-link {
            display: flex; align-items: center; gap: 14px;
            padding: 13px 14px; border-radius: 16px;
            text-decoration: none; margin-bottom: 3px;
            color: #334155;
            font-weight: 600; font-size: 0.92rem;
            position: relative; overflow: hidden;
            opacity: 0; transform: translateX(28px);
            transition: color 0.2s, background 0.25s,
                        opacity 0.4s ease, transform 0.4s cubic-bezier(0.23,1,0.32,1);
        }
        .mn-link::before {
            content: '';
            position: absolute; left: 0; top: 0; bottom: 0;
            width: 3px; border-radius: 0 3px 3px 0;
            background: linear-gradient(180deg, #007A3D, #059669);
            opacity: 0;
            transition: opacity 0.25s;
        }
        .mn-link:hover, .mn-link:active { background: #f0fdf4; color: #007A3D; }
        .mn-link:hover::before { opacity: 1; }
        .mn-link:active { transform: scale(0.97) !important; }

        .mn-icon {
            width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: background 0.25s, border-color 0.25s;
        }
        .mn-link:hover .mn-icon {
            background: #dcfce7;
            border-color: #bbf7d0;
        }
        .mn-link-text { flex: 1; }
        .mn-link-sub {
            display: block; font-size: 0.68rem;
            color: #94a3b8; font-weight: 500; margin-top: 1px;
        }
        .mn-arrow {
            font-size: 11px; color: #cbd5e1;
            transition: transform 0.2s, color 0.2s;
        }
        .mn-link:hover .mn-arrow { transform: translateX(4px); color: #007A3D; }

        .mn-badge {
            font-size: 0.58rem; font-weight: 800; letter-spacing: 0.05em;
            padding: 2px 7px; border-radius: 99px;
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            color: #fff; text-transform: uppercase;
        }

        /* Stagger animation on open */
        #mobile-nav.active .mn-link { opacity: 1; transform: translateX(0); }
        #mobile-nav.active .mn-link:nth-child(1) { transition-delay: 0.06s; }
        #mobile-nav.active .mn-link:nth-child(2) { transition-delay: 0.11s; }
        #mobile-nav.active .mn-link:nth-child(3) { transition-delay: 0.16s; }
        #mobile-nav.active .mn-link:nth-child(4) { transition-delay: 0.21s; }
        #mobile-nav.active .mn-link:nth-child(5) { transition-delay: 0.26s; }

        /* ── Footer ── */
        .mn-footer { padding: 14px 14px 40px; }
        .mn-footer-divider {
            height: 1px; margin-bottom: 14px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
        }
        .mn-cta {
            display: flex; align-items: center; justify-content: center;
            gap: 10px; padding: 16px 20px; border-radius: 16px;
            background: linear-gradient(135deg, #007A3D 0%, #00a855 100%);
            color: #fff; font-weight: 800; font-size: 0.88rem;
            text-decoration: none; letter-spacing: 0.04em;
            box-shadow: 0 8px 28px rgba(0,122,61,0.45), 0 0 0 1px rgba(0,255,128,0.1) inset;
            opacity: 0; transform: translateY(10px);
            transition: opacity 0.4s ease 0.3s, transform 0.4s cubic-bezier(0.23,1,0.32,1) 0.3s,
                        box-shadow 0.25s, scale 0.15s;
        }
        #mobile-nav.active .mn-cta { opacity: 1; transform: translateY(0); }
        .mn-cta:active { scale: 0.97; box-shadow: 0 4px 14px rgba(0,122,61,0.3); }

        .mn-brand-strip {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 14px;
            opacity: 0;
            transition: opacity 0.4s ease 0.38s;
        }
        #mobile-nav.active .mn-brand-strip { opacity: 1; }
        .mn-brand-strip span {
            font-size: 0.6rem; font-weight: 700; letter-spacing: 0.15em;
            color: #94a3b8; text-transform: uppercase;
        }
        .mn-brand-dot { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; }
        
        .img-container {
            position: relative;
            overflow: hidden;
            background: radial-gradient(circle, #ffffff 0%, #f8fafc 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .fit-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
        }

        .product-shadow {
            filter: drop-shadow(0 15px 25px rgba(0,0,0,0.15));
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .group:hover .product-shadow {
            filter: drop-shadow(0 25px 35px rgba(5, 150, 105, 0.25));
            transform: scale(1.05) translateY(-10px);
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(2deg); }
            50% { transform: translateY(-15px) rotate(4deg); }
            100% { transform: translateY(0px) rotate(2deg); }
        }
        .animate-float { animation: float 5s ease-in-out infinite; }
        /* Animation de disparition en douceur */
.fade-out {
    opacity: 0;
    transition: opacity 0.5s ease-out;
}
    </style>
</head>
<body class="font-sans antialiased text-slate-900 overflow-x-hidden">
 <!-- Écran de Sécurité Ludique -->
<div id="game-shield" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/80 backdrop-blur-xl p-4 transition-all duration-700">
    <div class="bg-white p-8 rounded-[2.5rem] shadow-2xl text-center max-w-sm w-full border border-slate-100">
        <h2 class="text-2xl font-black text-slate-800 mb-2">Vérification humaine</h2>
        <p class="text-slate-500 text-sm mb-6">Cliquez sur le logo <span class="font-bold text-galaDark">Gala</span> pour entrer.</p>
        
        <div class="grid grid-cols-2 gap-4 mb-6">
            <button onclick="failGame()" class="p-6 bg-slate-100 rounded-3xl hover:bg-slate-200 transition"><i class="fas fa-box-open text-3xl text-slate-400"></i></button>
            <button onclick="winGame(this)" class="p-6 bg-red-50 rounded-3xl hover:bg-red-100 transition animate-pulse-gala flex items-center justify-center">
                <div class="w-12 h-12 bg-galaDark rounded-lg flex items-center justify-center text-white font-bold shadow-lg text-lg">G</div>
            </button>
            <button onclick="failGame()" class="p-6 bg-slate-100 rounded-3xl hover:bg-slate-200 transition"><i class="fas fa-truck text-3xl text-slate-400"></i></button>
            <button onclick="failGame()" class="p-6 bg-slate-100 rounded-3xl hover:bg-slate-200 transition"><i class="fas fa-utensils text-3xl text-slate-400"></i></button>
        </div>
        <p id="game-msg" class="text-red-500 font-bold text-sm hidden">Erreur ! Cliquez sur le logo Gala.</p>
    </div>
</div>

<!-- Bandeau Cookies Moderne -->
<div id="cookie-banner" class="fixed bottom-0 left-0 right-0 z-[50] p-4 hidden">
    <div class="max-w-4xl mx-auto bg-white/80 backdrop-blur-xl border border-white/20 shadow-2xl rounded-3xl p-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="text-sm text-slate-600">Nous utilisons des cookies pour améliorer votre expérience sur <strong>GalaMayo</strong>.</div>
        <button onclick="acceptCookies()" class="px-8 py-3 bg-galaDark text-white rounded-full font-bold hover:bg-black transition shadow-lg">Tout accepter</button>
    </div>
</div>

<style>
    @keyframes pulse-gala { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
    .animate-pulse-gala { animation: pulse-gala 2s infinite ease-in-out; }
</style>
    <nav class="fixed w-full z-50 glass-morphism border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-5 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-galaDark rounded-lg flex items-center justify-center text-white font-bold shadow-lg">G</div>
                <span class="text-xl font-extrabold tracking-tight text-galaDark sans-serif ">Gala<span class="text-[#E30613]">Mayo</span></span>
            </div>
            
            <div class="hidden md:flex gap-8 text-sm font-extrabold text-slate-600">
                <a id="link-nav-accueil" href="#accueil" class="hover:text-galaGreen transition">Accueil</a>
                <a id="link-nav-histoire" href="#a-propos" class="hover:text-galaGreen transition">Histoire</a>
                <a id="link-nav-gamme" href="#gamme" class="hover:text-galaGreen transition">Gamme</a>
                <a id="link-nav-qualite" href="#qualite" class="hover:text-galaGreen transition">Qualité</a>
                <a id="link-nav-galerie-top" href="#galerie" class="hover:text-galaGreen transition">Galerie</a>
            </div>


            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-2 text-sm font-bold text-slate-700">
                    <a id="link-nav-contact" href="#contact" class="hover:text-galaGreen transition flex items-center gap-2">
                        <i class="fas fa-phone-alt"></i> <span>Contact</span>
                    </a>
                </div>
          <button id="menu-btn" class="flex lg:hidden focus:outline-none" aria-label="Menu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
          </button>
            </div>
        </div>
    
    </nav>
    <!-- OVERLAY -->
    <div id="nav-overlay"></div>

    <!-- ══ MOBILE NAV — 2026 PREMIUM ══ -->
    <div id="mobile-nav" aria-hidden="true">

        <!-- Header -->
        <div class="mnh">
            <div class="mnh-brand">
                <div class="mnh-logo">G</div>
                <div>
                    <div class="mnh-name">Gala<span>Mayo</span></div>
                    <div class="mnh-tag">Cameroun · ISO 22000</div>
                </div>
            </div>
            <button class="mnh-close" id="nav-close-btn" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="mn-divider"></div>

        <!-- Links -->
        <div class="mn-body">
            <div class="mn-section-label">Navigation</div>

            <a href="#accueil" class="mn-link">
                <span class="mn-icon" style="color:#007A3D"><i class="fas fa-home"></i></span>
                <span class="mn-link-text">
                    Accueil
                    <span class="mn-link-sub">Page principale</span>
                </span>
                <i class="fas fa-chevron-right mn-arrow"></i>
            </a>

            <a href="#a-propos" class="mn-link">
                <span class="mn-icon" style="color:#059669"><i class="fas fa-leaf"></i></span>
                <span class="mn-link-text">
                    Notre Histoire
                    <span class="mn-link-sub">Depuis Douala, Cameroun</span>
                </span>
                <i class="fas fa-chevron-right mn-arrow"></i>
            </a>

            <a href="#gamme" class="mn-link">
                <span class="mn-icon" style="color:#d97706"><i class="fas fa-box-open"></i></span>
                <span class="mn-link-text">
                    Nos Produits
                    <span class="mn-link-sub">Toute la gamme Gala</span>
                </span>
                <span class="mn-badge">Nouveau</span>
            </a>

            <a href="#qualite" class="mn-link">
                <span class="mn-icon" style="color:#6366f1"><i class="fas fa-award"></i></span>
                <span class="mn-link-text">
                    Qualité
                    <span class="mn-link-sub">Normes ISO & hygiène</span>
                </span>
                <i class="fas fa-chevron-right mn-arrow"></i>
            </a>

            <a href="#galerie" class="mn-link">
                <span class="mn-icon" style="color:#db2777"><i class="fas fa-images"></i></span>
                <span class="mn-link-text">
                    Galerie
                    <span class="mn-link-sub">Nos photos & productions</span>
                </span>
                <i class="fas fa-chevron-right mn-arrow"></i>
            </a>
        </div>

        <!-- Footer -->
        <div class="mn-footer">
            <div class="mn-footer-divider"></div>
            <a href="#contact" class="mn-cta">
                <i class="fas fa-phone-alt"></i>
                Contactez-nous
            </a>
            <div class="mn-brand-strip">
                <span>Gala Agro SARL</span>
                <div class="mn-brand-dot"></div>
                <span>© 2026</span>
                <div class="mn-brand-dot"></div>
                <span>Cameroun</span>
            </div>
        </div>

    </div>

    <section id="accueil" class="relative pt-32 pb-20 bg-gradient-to-br from-green-50/50 via-white to-orange-50/30 overflow-hidden">
        <div class="max-w-7xl mx-auto px-5 flex flex-col md:flex-row items-center relative">
            <div class="w-full md:w-3/5 text-center md:text-left z-10">
                <div class="inline-flex items-center gap-2 bg-white px-3 py-1 rounded-full border border-green-100 shadow-sm mb-6">
                    <span class="flex h-2 w-2 rounded-full bg-[#007A3D] animate-pulse"></span>
                    <span class="text-xs font-extrabold text-[#007A3D] tracking-[0.12em] uppercase">Production Locale & Qualité ISO</span>
                </div>
                <h1 class="text-4xl sm:text-5xl md:text-7xl font-extrabold text-slate-900 leading-[1.1] mb-6">
                    Le goût qui <span class="text-[#007A3D] underline decoration-galaGold italic">réveille</span> vos plats
                </h1>
                <p class="text-base sm:text-lg text-slate-600 mb-10 max-w-xl mx-auto md:mx-0">
                    Découvrez la texture unique de la Mayonnaise Gala. Fabriquée avec passion au Cameroun pour offrir une sauce onctueuse, 100% au goût qui rassemble.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                    <a id="link-hero-gamme" href="#gamme" class="px-8 py-4 bg-[#007A3D] text-white rounded-2xl font-bold shadow-lg shadow-green-200 hover:scale-105 transition-transform text-center uppercase text-sm tracking-wider">Découvrir les formats</a>
                    <a id="link-hero-b2b" href="#b2b" class="px-8 py-4 bg-white text-[#007A3D] border border-slate-200 rounded-2xl font-bold hover:bg-slate-50 transition-transform text-center uppercase text-sm tracking-wider">Espace Distributeur</a>
                </div>
            </div>
            <div class="w-full md:w-2/5 mt-16 md:mt-0 relative flex justify-center">
                <div class="absolute inset-0 bg-[#007A3D]/20 rounded-full blur-[80px] animate-pulse"></div>
                
                <div class="w-64 h-80 md:w-80 md:h-[450px] relative animate-float">
                    <div class="absolute inset-0 bg-white rounded-[3rem] shadow-2xl border-[10px] border-white overflow-hidden flex items-center justify-center">
                        <img src="gala femme.png" 
                             alt="Mayonnaise Gala Hero" 
                             class="w-full h-full object-contain product-shadow">
                    </div>
                    
                    <div class="absolute -right-4 top-10 bg-galaGold text-white h-16 w-16 rounded-full flex items-center justify-center shadow-xl font-black text-xs text-center leading-tight rotate-12 z-10">
                        100%<br>NATU
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="a-propos" class="py-24 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
                <div class="relative group max-w-md mx-auto md:max-w-none w-full">
                    <div class="absolute -inset-4 bg-slate-100 rounded-[4rem] -rotate-2 group-hover:rotate-0 transition-transform duration-500"></div>
                    <div class="relative w-full aspect-[4/5] bg-white rounded-[3rem] overflow-hidden border-[12px] border-white shadow-2xl">
                         <img src="images1.jpg" alt="Gala Agro Production" class="w-full h-full object-contain transition-transform duration-[2s] group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-galaDark/40 via-transparent to-transparent"></div>
                    </div>
                    <div class="absolute -bottom-8 -right-8 bg-galaGold text-white p-8 rounded-[2rem] shadow-2xl hidden lg:block animate-bounce">
                        <p class="text-xs uppercase font-black tracking-tighter mb-1">Expertise</p>
                        <i class="fas fa-award text-4xl"></i>
                    </div>
                </div>
                <div>
                    <h2 class="text-3xl md:text-4xl font-extrabold mb-6 text-galaDark">Notre Histoire</h2>
                    <p class="text-slate-600 mb-8 leading-relaxed text-base sm:text-lg">
                        Créée pour répondre aux exigences des gourmets, <strong class="text-galaGreen">GALA AGRO</strong> produit la Mayonnaise Gala pour offrir une expérience culinaire unique. Nos valeurs reposent sur la qualité locale, une hygiène irréprochable et une proximité constante avec nos consommateurs.
                    </p>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="flex items-center gap-4 p-5 rounded-2xl bg-[#007A3D] border-l-4 border-galaGreen hover:bg-green-100 transition-colors">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-[#007A3D] shrink-0 shadow-sm"><i class="fas fa-egg"></i></div>
                            <span class="font-bold text-slate-700 text-sm sm:text-base">Huile raffinée & œufs frais du jour</span>
                        </div>
                        <div class="flex items-center gap-4 p-5 rounded-2xl bg-[#007A3D] border-l-4 border-galaGreen hover:bg-green-100 transition-colors">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-[#007A3D] shrink-0 shadow-sm"><i class="fas fa-vial-circle-check"></i></div>
                            <span class="font-bold text-slate-700 text-sm sm:text-base">Zéro colorants artificiels agressifs</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="qualite" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-5 text-center">
            <h2 class="text-2xl md:text-3xl font-extrabold mb-12 text-galaDark tracking-tight uppercase">Notre Engagement Qualité</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-8 md:p-10 bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-100/50 hover:border-galaGreen transition-colors group">
                    <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center text-[#007A3D] mb-8 mx-auto group-hover:bg-[#007A3D] group-hover:text-white transition-all">
                        <i class="fas fa-shield-alt text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Normes ISO 22000</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Une sécurité alimentaire garantie à chaque étape de la production industrielle.</p>
                </div>
                <div class="p-8 md:p-10 bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-100/50 hover:border-galaGreen transition-colors group">
                    <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center text-[#007A3D] mb-8 mx-auto group-hover:bg-[#007A3D] group-hover:text-white transition-all">
                        <i class="fas fa-leaf text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Ingrédients Frais</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Sélection rigoureuse des meilleurs œufs et huiles végétales de nos terroirs.</p>
                </div>
                <div class="p-8 md:p-10 bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-100/50 hover:border-galaGreen transition-colors group">
                    <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center text-[#007A3D] mb-8 mx-auto group-hover:bg-[#007A3D] group-hover:text-white transition-all">
                        <i class="fas fa-flask text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Laboratoire Interne</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Analyses quotidiennes pour assurer une onctuosité et une conservation parfaite.</p>
                </div>
            </div>
        </div>
    </section>
<section id="gamme" class="py-24 bg-slate-50">
    <div id="cart-summary" class="hidden fixed bottom-5 right-5 z-40 bg-galaDark text-white p-4 rounded-full shadow-2xl cursor-pointer hover:scale-110 transition-all duration-300 group" onclick="openCartModal()">
        <i class="fas fa-shopping-basket text-xl"></i>
        <span id="cart-count-badge" class="absolute -top-2 -right-2 bg-galaGreen text-white text-[10px] font-black w-6 h-6 flex items-center justify-center rounded-full border-2 border-white shadow-sm">0</span>
      </div>
    
    </div>

    <div class="max-w-7xl mx-auto px-5">
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-galaDark tracking-tight">Notre Gamme Complète</h2>
            <p class="text-slate-600">Explorez nos différents conditionnements adaptés aux besoins des familles comme des professionnels de la restauration.</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($produits as $p): ?>
            <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-xl transition duration-300 flex flex-col justify-between group relative" id="product-card-<?= $p['id']; ?>">
                
                <span id="badge-rupture-<?= $p['id']; ?>" class="absolute top-4 right-4 bg-red-500 text-white text-xs font-black px-3 py-1.5 rounded-full shadow-md uppercase tracking-wider z-10 animate-pulse <?= (isset($p['stock']) && $p['stock'] <= 0) ? '' : 'hidden'; ?>">Épuisé</span>
                <?php if (isset($p['en_solde']) && $p['en_solde'] == 1): ?>
                    <span id="badge-promo-<?= $p['id']; ?>" class="absolute top-4 right-4 bg-[#007A3D] text-white text-xs font-black px-3 py-1.5 rounded-full shadow-md uppercase tracking-wider z-10 <?= (isset($p['stock']) && $p['stock'] <= 0) ? 'hidden' : ''; ?>">PROMO</span>
                <?php endif; ?>

                <div>
                    <div class="aspect-square mb-6 overflow-hidden rounded-2xl bg-slate-50 flex items-center justify-center relative">
                        <img src="assets/img/<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>" class="w-full h-full object-contain p-6 group-hover:scale-110 transition duration-500">
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-xl font-bold text-galaDark"><?= htmlspecialchars($p['nom']) ?></h3>
                        <p class="text-galaGreen text-sm font-black tracking-wide uppercase"><?= htmlspecialchars($p['format']) ?></p>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-50 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-400 font-medium">Prix conseillé</span>
                            <span class="text-xl font-black text-galaDark"><?= isset($p['prix']) && $p['prix'] > 0 ? number_format($p['prix'], 0, ',', ' ') . ' FCFA' : 'Prix sur demande'; ?></span>
                        </div>
                        <button id="btn-order-<?= $p['id']; ?>" onclick="toggleOrderSelector(<?= $p['id'] ?>)" class="bg-[#007A3D]/10 text-[#007A3D] hover:bg-[#007A3D] hover:text-white text-sm font-bold px-4 py-2.5 rounded-xl transition <?= (isset($p['stock']) && $p['stock'] <= 0) ? 'bg-slate-200 text-slate-400 cursor-not-allowed opacity-50' : ''; ?>" <?= (isset($p['stock']) && $p['stock'] <= 0) ? 'disabled' : ''; ?>>
                            <?= (isset($p['stock']) && $p['stock'] <= 0) ? 'Indisponible' : 'Commander'; ?>
                        </button>
                    </div>

                    <div id="selector-container-<?= $p['id'] ?>" data-stock="<?= $p['stock'] ?>" class="hidden bg-slate-50 p-3 rounded-2xl border border-slate-200/60 flex flex-col gap-2 transition-all">
                        <div class="flex flex-col sm:flex-row gap-2 items-center justify-between w-full">
                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                <div class="flex items-center bg-white border border-slate-200 rounded-xl overflow-hidden h-9 w-full sm:w-28">
                                    <button type="button" onclick="decrementQuantity(<?= $p['id'] ?>)" class="px-3 text-slate-500 hover:bg-slate-100 h-full"><i class="fas fa-minus text-xs"></i></button>
                                    <input id="quantity-<?= $p['id'] ?>" type="number" value="1" min="1" max="<?= $p['stock'] ?>" class="w-full text-center font-bold text-sm text-slate-700 bg-transparent outline-none">
                                    <button type="button" onclick="incrementQuantity(<?= $p['id'] ?>)" class="px-3 text-slate-500 hover:bg-slate-100 h-full"><i class="fas fa-plus text-xs"></i></button>
                                </div>
                                <select id="unit-<?= $p['id'] ?>" class="w-1/2 sm:w-24 p-2 bg-white border border-slate-200 rounded-xl text-sm font-bold">
                                    <option value="carton(s)">Carton(s)</option>
                                    <option value="boite(s)">Boîte(s)</option>
                                </select>
                            </div>
                            <button onclick="checkStockAndSubmit(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nom'])) ?>', <?= $p['prix'] ?>)" class="w-full sm:w-auto bg-[#007A3D] text-white text-xs font-bold px-4 py-2.5 rounded-xl hover:bg-[#005c2e] transition">
                                Valider
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
     <div id="cartModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/80 p-4">
    <div class="bg-white max-w-lg w-full p-8 rounded-[2.5rem] shadow-2xl relative">
        <button type="button" onclick="document.getElementById('cartModal').classList.add('hidden')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-800 text-3xl font-bold">&times;</button>
        
        <h2 class="text-2xl font-black mb-4">Votre Panier</h2>
        <div id="cart-items-list" class="mb-4"></div>
        <p class="font-bold text-lg mb-6">Total : <span id="cart-total-price">0</span> FCFA</p>
        <button type="button" onclick="nextToFinalization()" class="w-full py-3 bg-galaGreen text-white rounded-xl font-black">Commander</button>
    </div>
</div>

<div id="checkoutModal" class="hidden fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/80 p-4">
    <div class="bg-white max-w-lg w-full p-8 rounded-[2.5rem] shadow-2xl overflow-y-auto max-h-[90vh] relative">
        <button type="button" onclick="document.getElementById('checkoutModal').classList.add('hidden')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-800 text-3xl font-bold">&times;</button>

        <h2 class="text-2xl font-black mb-6">Finaliser la commande</h2>
        <form id="finalOrderForm" action="traitement_commande.php" method="POST" enctype="multipart/form-data">
            <input type="text" name="hp_field" style="display:none !important;" tabindex="-1" autocomplete="off">
            <input type="text" name="nom" placeholder="Nom *" required class="w-full p-3 rounded-xl border border-slate-200">
            <input type="text" name="prenom" placeholder="Prénom *" required class="w-full p-3 rounded-xl border border-slate-200">
            <input type="text" name="cni" placeholder="Numéro CNI *" required class="w-full p-3 rounded-xl border border-slate-200">
            <input type="text" name="num_commercial" placeholder="Numéro Commercial" class="w-full p-3 rounded-xl border border-slate-200">
            <input type="text" name="nom_marche" placeholder="Nom du marché *" required class="w-full p-3 rounded-xl border border-slate-200">
            
            <select name="region" required class="w-full p-3 rounded-xl border border-slate-200">
                <option value="">-- Dans quelle région ? --</option>
                <option value="Adamaoua">Adamaoua</option>
                <option value="Centre">Centre</option>
                <option value="Est">Est</option>
                <option value="Extrême-Nord">Extrême-Nord</option>
                <option value="Littoral">Littoral</option>
                <option value="Nord">Nord</option>
                <option value="Nord-Ouest">Nord-Ouest</option>
                <option value="Ouest">Ouest</option>
                <option value="Sud">Sud</option>
                <option value="Sud-Ouest">Sud-Ouest</option>
            </select>

            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-500 uppercase">Copie CNI (Photo/PDF)</label>
                <label for="cni_file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <i class="fas fa-id-card text-2xl text-galaGreen mb-2"></i>
                        <p id="cni-preview" class="text-sm text-slate-500 font-medium">Cliquez pour importer la CNI</p>
                    </div>
                    <input id="cni_file" name="cni_file" type="file" class="hidden" onchange="previewFile(this, 'cni-preview')" accept="image/*,.pdf">
                </label>
            </div>

            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-500 uppercase">Bon de commande</label>
                <label for="bon_commande" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <i class="fas fa-file-upload text-2xl text-rose-600 mb-2"></i>
                        <p id="bon-preview" class="text-sm text-slate-500 font-medium">Cliquez pour importer le bon</p>
                    </div>
                    <input id="bon_commande" name="bon_commande" type="file" class="hidden" onchange="previewFile(this, 'bon-preview')" accept="image/*,.pdf">
                </label>
            </div>

            <button type="submit" class="w-full py-4 bg-galaGreen text-white rounded-xl font-black uppercase hover:bg-green-700 transition">Finaliser la commande</button>
            
            <button type="button" onclick="document.getElementById('checkoutModal').classList.add('hidden')" class="w-full py-2 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition">Annuler</button>
        </form>
    </div>
</div>
</select>
    <section id="galerie" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-5">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-[#007A3D] tracking-tight">Gala en Images</h2>
                <p class="text-slate-600">Immersion au cœur de notre univers de production et de nos événements de marque.</p>
            </div>

           <div id="live-gallery-container" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <?php if (empty($photos)): ?>
                    <p class="text-slate-400 italic col-span-full text-center py-6">Aucune image disponible dans la galerie.</p>
                <?php else: ?>
                    <?php foreach ($photos as $img): ?>
                    <div class="relative group h-48 bg-slate-100 rounded-2xl overflow-hidden shadow-sm border border-slate-100">
                        <img src="assets/img/gallery/<?= htmlspecialchars($img['image_url']) ?>" alt="<?= htmlspecialchars($img['titre']) ?>" class="w-full h-full object-contain transition duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent opacity-0 group-hover:opacity-100 transition duration-300 flex items-end p-4">
                            <p class="text-white text-xs font-bold truncate w-full"><?= htmlspecialchars($img['titre']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <section id="b2b" class="py-24 bg-white text-[#007A3D] overflow-hidden relative">
        <div class="absolute top-0 right-0 w-96 h-96 bg-[#007A3D]/10 rounded-full blur-[120px] -mr-48 -mt-48"></div>
        <div class="max-w-7xl mx-auto px-5 relative z-10">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-5xl font-extrabold mb-6 uppercase tracking-tighter">Espace <span class="text-galaGold italic">Distributeurs</span></h2>
                <p class="max-w-2xl mx-auto opacity-80 text-base sm:text-lg italic text-black-300">Rejoignez la famille GALA AGRO. Nous accompagnons nos partenaires B2B avec une logistique performante.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center bg-white/5 p-6 sm:p-8 md:p-16 rounded-[2.5rem] md:rounded-[4rem] border border-white/10 backdrop-blur-md">
                <div class="space-y-8">
                    <h3 class="text-2xl font-bold text-galaGold">Pourquoi devenir partenaire ?</h3>
                    <ul class="space-y-5">
                        <li class="flex items-center gap-4 bg-white/5 p-4 rounded-2xl border border-white/5 hover:bg-white/10 transition-colors">
                            <div class="w-10 h-10 bg-galaGold rounded-full flex items-center justify-center text-galaDark shrink-0"><i class="fas fa-percentage font-bold"></i></div>
                            <span class="font-medium text-base sm:text-lg">Marges bénéficiaires attractives</span>
                        </li>
                        <li class="flex items-center gap-4 bg-white/5 p-4 rounded-2xl border border-white/5 hover:bg-white/10 transition-colors">
                            <div class="w-10 h-10 bg-galaGold rounded-full flex items-center justify-center text-galaDark shrink-0"><i class="fas fa-truck font-bold"></i></div>
                            <span class="font-medium text-base sm:text-lg">Livraison prioritaire en 24h/24h</span>
                        </li>
                        <li class="flex items-center gap-4 bg-white/5 p-4 rounded-2xl border border-white/5 hover:bg-white/10 transition-colors">
                            <div class="w-10 h-10 bg-galaGold rounded-full flex items-center justify-center text-galaDark shrink-0"><i class="fas fa-bullhorn font-bold"></i></div>
                            <span class="font-medium text-base sm:text-lg">Supports marketing et PLV offerts</span>
                        </li>
                    </ul>
                    <div class="pt-6 text-center md:text-left">
                        <a href="https://wa.me/2376 676 588 240" class="inline-flex items-center px-10 py-5 bg-galaGold text-galaDark rounded-2xl font-black shadow-2xl hover:scale-105 transition-transform uppercase text-sm tracking-[0.1em]">
                            <i class="fab fa-whatsapp mr-3 text-2xl"></i> Devenir Grossiste
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:gap-6">
                    <div class="aspect-square bg-white/5 rounded-[1.5rem] sm:rounded-[2.5rem] flex flex-col items-center justify-center border border-white/10 hover:bg-galaGreen/30 transition-all cursor-default p-2">
                        <span class="text-2xl sm:text-4xl font-black text-[#007A3D] mb-2">D1</span>
                        <p class="text-[9px] sm:text-[10px] text-black-300 uppercase font-black tracking-widest">Grossistes</p>
                    </div>
                    <div class="aspect-square bg-[#007A3D]/20 rounded-[1.5rem] sm:rounded-[2.5rem] flex flex-col items-center justify-center border border-galaGreen/30 shadow-2xl p-2">
                        <span class="text-2xl sm:text-4xl font-black text-white mb-2">D2</span>
                        <p class="text-[9px] sm:text-[10px] text-black-300 uppercase font-black tracking-widest text-center leading-tight">Grandes<br>Surfaces</p>
                    </div>
                    <div class="aspect-square bg-white/5 rounded-[1.5rem] sm:rounded-[2.5rem] flex flex-col items-center justify-center border border-white/10 hover:bg-[#007A3D]/30 transition-all cursor-default p-2">
                        <span class="text-2xl sm:text-4xl font-black text-[#007A3D] mb-2">D3</span>
                        <p class="text-[9px] sm:text-[10px] text-black-300 uppercase font-black tracking-widest">Horeca</p>
                    </div>
                    <div class="aspect-square bg-white/5 rounded-[1.5rem] sm:rounded-[2.5rem] flex flex-col items-center justify-center border border-white/10 hover:bg-[#007A3D]/30 transition-all cursor-default p-2">
                        <span class="text-2xl sm:text-4xl font-black text-[#007A3D] mb-2">D4</span>
                        <p class="text-[9px] sm:text-[10px] text-black-300 uppercase font-black tracking-widest">Export</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="py-24 bg-slate-50">
    <div class="max-w-4xl mx-auto px-5">
           <div class="bg-slate-50 rounded-[2.5rem] md:rounded-[4rem] overflow-hidden border border-slate-100 shadow-sm grid grid-cols-1 md:grid-cols-2">
                <div class="p-8 sm:p-12 md:p-20">
                    <h2 class="text-3xl md:text-4xl font-black mb-10 text-[#007A3D] uppercase tracking-tighter">Parlons ensemble</h2>
                    <div class="space-y-8">
                        <div class="flex items-start gap-6">
                            <div class="w-12 h-12 bg-white rounded-2xl shadow-sm flex items-center justify-center text-[#007A3D] shrink-0"><i class="fas fa-map-marker-alt text-xl"></i></div>
                            <div><p class="font-black text-lg">Siège Social</p><p class="text-slate-500">Douala, Cameroun</p></div>
                        </div>
                        <div class="flex items-start gap-6">
                            <div class="w-12 h-12 bg-white rounded-2xl shadow-sm flex items-center justify-center text-[#007A3D] shrink-0"><i class="fas fa-phone text-xl"></i></div>
                            <div><p class="font-black text-lg">Téléphone</p><p class="text-slate-500">+237 676 588 240</p></div>
                        </div>
                        <div class="flex items-start gap-6">
                            <div class="w-12 h-12 bg-white rounded-2xl shadow-sm flex items-center justify-center text-[#007A3D] shrink-0"><i class="fas fa-envelope text-xl"></i></div>
                            <div><p class="font-black text-lg">Email Professionnel</p><p class="text-slate-500">info@adisa-cm.com</p></div>
                        </div>
                    </div>
                </div>
        <div class="bg-white p-10 md:p-14 rounded-[3rem] shadow-xl border border-slate-300">
            <h2 class="text-3xl font-extrabold text-galaDark mb-8 text-center">Finaliser votre Commande / Nous Contacter</h2>
            
            <div id="contact-response" class="hidden mb-6 p-4 rounded-xl text-center font-bold"></div>
             <form id="contactForm" method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nom complet *</label>
                        <input type="text" name="nom" required class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-galaGreen outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Téléphone *</label>
                        <input type="tel" name="tel" required class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-galaGreen outline-none transition" placeholder="Ex: 6xx xx xx xx">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Votre Message / Détails de la commande</label>
                    <textarea name="message" id="message-commande" rows="5" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-galaGreen outline-none transition" placeholder="Décrivez votre commande ou posez votre question..."></textarea>
                </div>
                <button type="submit" class="w-full py-4 bg-[#007A3D] text-white rounded-2xl font-bold shadow-lg hover:bg-[#005c2e] transition uppercase tracking-wider text-sm">
                    Envoyer le message
                </button>
            </form>
        </div>
    </div>
</section>

<button onclick="openRecrutementModal()" 
        class="inline-block px-10 py-5 bg-galaGold text-galaDark rounded-2xl font-black shadow-2xl hover:scale-105 transition-transform uppercase text-sm tracking-[0.1em]">
    <i class="fas fa-paper-plane mr-2"></i> Soumettre mon dossier
</button>

<div id="recrutementModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-sm hidden p-4">
    <div class="bg-white w-full max-w-xl rounded-[2.5rem] p-8 shadow-2xl relative">
        
        <button onclick="document.getElementById('recrutementModal').classList.add('hidden')" 
                class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition">
            <i class="fas fa-times text-2xl"></i>
        </button>

        <div id="successMessage" class="hidden text-center py-10">
            <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check text-4xl"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-800 mb-2">Dossier transmis !</h3>
            <p class="text-slate-500">Merci ! Votre candidature a bien été reçue par notre service RH.</p>
            <button onclick="document.getElementById('recrutementModal').classList.add('hidden')" 
                    class="mt-8 px-8 py-3 bg-galaDark text-white rounded-xl font-bold">Fermer</button>
        </div>

        <form id="recrutementForm" class="space-y-4">
            <input type="text" name="hp_field" style="display:none !important;" tabindex="-1" autocomplete="off">
            <h2 class="text-xl font-black text-galaDark uppercase mb-4">Candidature</h2>
            <input type="text" name="nom" placeholder="Nom complet *" required class="w-full p-4 bg-slate-100 rounded-xl outline-none focus:ring-2 ring-galaGreen">
            <div class="grid grid-cols-2 gap-4">
                <input type="email" name="email" placeholder="Email *" required class="w-full p-4 bg-slate-100 rounded-xl outline-none focus:ring-2 ring-galaGreen">
                <input type="tel" name="telephone" placeholder="Téléphone *" required class="w-full p-4 bg-slate-100 rounded-xl outline-none focus:ring-2 ring-galaGreen">
            </div>
            <input type="text" name="poste" placeholder="Poste visé *" required class="w-full p-4 bg-slate-100 rounded-xl outline-none focus:ring-2 ring-galaGreen">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label id="cvLabel" class="flex flex-col items-center justify-center h-32 border-2 border-dashed border-slate-200 rounded-2xl cursor-pointer hover:border-galaGreen hover:bg-slate-50 transition">
                    <i class="fas fa-file-pdf text-galaGreen text-2xl mb-2"></i>
                    <span class="text-xs text-slate-500 font-bold uppercase">CV (PDF)</span>
                    <input type="file" name="cv" accept=".pdf" required class="hidden" onchange="updateFileName('cvLabel', this)">
                </label>
                <label id="lettreLabel" class="flex flex-col items-center justify-center h-32 border-2 border-dashed border-slate-200 rounded-2xl cursor-pointer hover:border-galaGreen hover:bg-slate-50 transition">
                    <i class="fas fa-file-alt text-galaDark text-2xl mb-2"></i>
                    <span class="text-xs text-slate-500 font-bold uppercase">Lettre (PDF)</span>
                    <input type="file" name="lettre" accept=".pdf" required class="hidden" onchange="updateFileName('lettreLabel', this)">
                </label>
            </div>

            <button type="submit" id="submitBtn" class="w-full py-4 bg-galaGreen text-white rounded-xl font-black hover:bg-green-700 transition">
                Envoyer le dossier
            </button>
        </form>
    </div>
</div>
    <div class="bg-galaDark py-12">
        <div class="max-w-7xl mx-auto px-5 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div><div class="text-2xl sm:text-3xl font-extrabold text-white mb-1">50+</div><div class="text-green-300 text-[9px] sm:text-[10px] uppercase font-bold tracking-widest">Collaborateurs</div></div>
            <div><div class="text-2xl sm:text-3xl font-extrabold text-white mb-1">500t</div><div class="text-green-300 text-[9px] sm:text-[10px] uppercase font-bold tracking-widest">Capacité / An</div></div>
            <div><div class="text-2xl sm:text-3xl font-extrabold text-white mb-1">100%</div><div class="text-green-300 text-[9px] sm:text-[10px] uppercase font-bold tracking-widest">Hygiène locale</div></div>
            <div><div class="text-2xl sm:text-3xl font-extrabold text-white mb-1">ISO</div><div class="text-green-300 text-[9px] sm:text-[10px] uppercase font-bold tracking-widest">Normes 22000</div></div>
        </div>
    </div>

    <footer class="bg-galaDark py-12 text-center text-white border-t border-white/10">
        <div class="flex justify-center gap-10 mb-6 text-[11px] font-black uppercase tracking-[0.2em]">
            <button id="btn-modal-legal" onclick="toggleModal('legal-modal')" class="hover:text-galaGold transition-colors">Mentions Légales</button>
            <button id="btn-modal-privacy" onclick="toggleModal('privacy-modal')" class="hover:text-galaGold transition-colors">Confidentialité</button>
        </div>
        
        <p class="text-[10px] font-bold text-white/80 italic tracking-[0.2em] uppercase mb-2">
            ©2026 GALA AGRO SARL — TOUS DROITS RÉSERVÉS
        </p>
        <p class="text-[9px] font-black text-white/60 uppercase tracking-widest">
            Développé par JAY group developper
        </p>
    </footer>

    <div id="legal-modal" class="fixed inset-0 z-[60] hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-5">
        <div class="bg-white rounded-[2rem] max-w-2xl w-full p-8 md:p-12 shadow-2xl relative overflow-y-auto max-h-[80vh]">
            <button id="btn-close-legal" onclick="toggleModal('legal-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-galaGold text-2xl transition-colors duration-200">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="text-3xl font-black text-galaDark mb-6 uppercase">Mentions Légales</h2>
            <div class="text-slate-600 space-y-4 text-sm leading-relaxed text-left">
                <p><strong>Éditeur :</strong> GALA AGRO SARL, Douala, Cameroun.</p>
                <p><strong>Responsable :</strong> Direction de la communication GALA.</p>
                <p><strong>Hébergement :</strong> Serveurs sécurisés JAY group.</p>
                <p><strong>Propriété intellectuelle :</strong> Toute reproduction du contenu, des images ou du logo sans autorisation préalable est strictement interdite.</p>
            </div>
        </div>
    </div>

    <div id="privacy-modal" class="fixed inset-0 z-[60] hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-5">
        <div class="bg-white rounded-[2rem] max-w-2xl w-full p-8 md:p-12 shadow-2xl relative overflow-y-auto max-h-[80vh]">
            <button id="btn-close-privacy" onclick="toggleModal('privacy-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-galaGold text-2xl">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="text-3xl font-black text-galaGreen mb-6 uppercase">Confidentialité</h2>
            <div class="text-slate-600 space-y-4 text-sm leading-relaxed text-left">
                <p><strong>Collecte des données :</strong> Les informations collectées via nos formulaires sont destinées uniquement à la gestion de vos demandes commerciales.</p>
                <p><strong>Sécurité :</strong> Nous mettons en œuvre toutes les mesures nécessaires pour protéger vos informations personnelles contre tout accès non autorisé.</p>
                <p><strong>Vos droits :</strong> Conformément aux réglementations en vigueur, vous disposez d'un droit d'accès et de rectification de vos données en nous contactant par email.</p>
            </div>
        </div>
    </div>
    <script src="assets/js/script.js"></script>



    <div id="ratingModal" class="fixed inset-0 z-[9999] hidden bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-xl p-6 text-center animate-in fade-in zoom-in duration-300">
        <div class="text-4xl mb-4">💬</div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">Comment avez-vous trouvé notre service ?</h3>
        <p class="text-sm text-gray-500 mb-6">Votre avis nous aide à nous améliorer.</p>
        
        <div id="star-rating" class="flex justify-center gap-3 text-3xl mb-8">
            <span onclick="setRating(1)" class="cursor-pointer text-gray-300 hover:scale-110 transition">★</span>
            <span onclick="setRating(2)" class="cursor-pointer text-gray-300 hover:scale-110 transition">★</span>
            <span onclick="setRating(3)" class="cursor-pointer text-gray-300 hover:scale-110 transition">★</span>
            <span onclick="setRating(4)" class="cursor-pointer text-gray-300 hover:scale-110 transition">★</span>
            <span onclick="setRating(5)" class="cursor-pointer text-gray-300 hover:scale-110 transition">★</span>
        </div>
        
        <input type="hidden" id="order_id" value="">
        <input type="text" id="nom_client" placeholder="Votre nom" class="w-full mb-3 p-2 border rounded">

        <button onclick="envoyerAvisFinal()" class="w-full bg-[#25D366] text-white py-3 rounded-full font-bold hover:bg-[#128C7E] transition">
            Valider mon avis
        </button>
    </div>
</div>
</div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btn      = document.getElementById('menu-btn');
        const nav      = document.getElementById('mobile-nav');
        const overlay  = document.getElementById('nav-overlay');
        const closeBtn = document.getElementById('nav-close-btn');

        function openMenu() {
            nav.classList.add('active');
            overlay.classList.add('active');
            btn.classList.add('open');
            nav.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
        function closeMenu() {
            nav.classList.remove('active');
            overlay.classList.remove('active');
            btn.classList.remove('open');
            nav.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            nav.classList.contains('active') ? closeMenu() : openMenu();
        });

        closeBtn.addEventListener('click', closeMenu);
        overlay.addEventListener('click', closeMenu);

        nav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', closeMenu);
        });

        // ═══════════════════════════════════════════════
        // AFFICHER LE MODAL AVIS APRÈS ENVOI COMMANDE
        // ═══════════════════════════════════════════════
        const contactForm = document.getElementById('contactForm');
        const contactResponse = document.getElementById('contact-response');

        if (contactForm) {
            contactForm.addEventListener('submit', function() {
                // Pré-remplir le nom dans le modal avis
                const nomInput = contactForm.querySelector('input[name="nom"]');
                if (nomInput && nomInput.value.trim()) {
                    const nomClientInput = document.getElementById('nom_client');
                    if (nomClientInput) nomClientInput.value = nomInput.value.trim();
                }

                // Observer la div de réponse pour détecter le succès AJAX
                if (contactResponse) {
                    const observer = new MutationObserver(() => {
                        // Dès que la réponse apparaît (succès ou non), on affiche le modal
                        if (!contactResponse.classList.contains('hidden')) {
                            observer.disconnect();
                            setTimeout(() => {
                                document.getElementById('ratingModal').classList.remove('hidden');
                            }, 1200);
                        }
                    });
                    observer.observe(contactResponse, { attributes: true, attributeFilter: ['class'] });
                } else {
                    // Fallback si pas de div réponse
                    setTimeout(() => {
                        document.getElementById('ratingModal').classList.remove('hidden');
                    }, 1000);
                }
            });
        }
    });
</script>
</html>