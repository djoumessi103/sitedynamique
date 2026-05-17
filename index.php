<?php 
session_start(); // Nécessaire pour détecter si l'admin est connecté
require_once 'includes/db.php';

// 1. Récupération des produits avec gestion du prix et du stock
$queryProducts = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$produits = $queryProducts->fetchAll();

// 2. Récupération des photos de la galerie dynamique
$queryGallery = $pdo->query("SELECT * FROM gallery ORDER BY created_at DESC LIMIT 8");
$photos = $queryGallery->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gala Agro | L'excellence onctueuse</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
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
                        galaDark: '#064e3b',
                        galaGold: '#f59e0b'
                    }
                }
            }
        }
    </script>

    <style>
        .glass-morphism { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
        #mobile-nav { transition: all 0.3s ease-in-out; transform: translateY(-10px); opacity: 0; pointer-events: none; }
        #mobile-nav.active { transform: translateY(0); opacity: 1; pointer-events: auto; }
        
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
    </style>
</head>
<body class="font-sans antialiased text-slate-900 overflow-x-hidden">

    <nav class="fixed w-full z-50 glass-morphism border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-5 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-galaGreen rounded-lg flex items-center justify-center text-white font-bold shadow-lg">G</div>
                <span class="text-xl font-extrabold tracking-tight text-galaDark italic uppercase">Mayo<span class="text-galaGold">Gala</span></span>
            </div>
            
            <div class="hidden md:flex gap-8 text-sm font-extrabold text-slate-600">
                <a id="link-nav-accueil" href="#accueil" class="hover:text-galaGreen transition">Accueil</a>
                <a id="link-nav-histoire" href="#a-propos" class="hover:text-galaGreen transition">Histoire</a>
                <a id="link-nav-gamme" href="#gamme" class="hover:text-galaGreen transition">Gamme</a>
                <a id="link-nav-qualite" href="#qualite" class="hover:text-galaGreen transition">Qualité</a>
                <a id="link-mobile-galerie" href="#galerie" class="mobile-link">Galerie</a>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-2 text-sm font-bold text-slate-700">
                 <a id="link-nav-contact" href="#contact" class="hover:text-galaGreen transition"class="hidden md:flex gap-8 text-sm font-extrabold text-slate-600">Contact</a>
                    <i class="fas fa-phone-alt mr-2"></i> 
                </a>
                <button id="menu-btn" class="md:hidden text-2xl text-galaDark focus:outline-none">
                    <i class="fas fa-bars-staggered"></i>
                </button>
            </div>
        </div>
        
        <div id="mobile-nav" class="absolute top-16 left-0 w-full bg-white shadow-2xl md:hidden border-t border-slate-50 z-40">
            <div class="flex flex-col p-6 gap-6 font-bold text-lg text-slate-700">
                <a id="link-mobile-accueil" href="#accueil" class="mobile-link">Accueil</a>
                <a id="link-mobile-histoire" href="#a-propos" class="mobile-link">Notre Histoire</a>
                <a id="link-mobile-produits" href="#gamme" class="mobile-link">Nos Produits</a>
                <a id="link-mobile-qualite" href="#qualite" class="mobile-link">Qualité</a>
                <a id="link-mobile-contact" href="#contact" class="mobile-link">Contact</a>
                <a id="link-mobile-galerie" href="#galerie" class="mobile-link">Galerie</a>
            </div>
        </div>
    </nav>

    <section id="accueil" class="relative pt-32 pb-20 bg-gradient-to-br from-green-50/50 via-white to-orange-50/30 overflow-hidden">
        <div class="max-w-7xl mx-auto px-5 flex flex-col md:flex-row items-center relative">
            <div class="md:w-3/5 text-center md:text-left z-10">
                <div class="inline-flex items-center gap-2 bg-white px-3 py-1 rounded-full border border-green-100 shadow-sm mb-6">
                    <span class="flex h-2 w-2 rounded-full bg-galaGreen animate-pulse"></span>
                    <span class="text-xs font-extrabold text-galaGreen tracking-[0.12em] uppercase">Production Locale & Qualité ISO</span>
                </div>
                <h1 class="text-5xl md:text-7xl font-extrabold text-slate-900 leading-[1.1] mb-6">
                    Le goût qui <span class="text-galaGreen underline decoration-galaGold/30 italic">réveille</span> vos plats
                </h1>
                <p class="text-lg text-slate-600 mb-10 max-w-xl mx-auto md:mx-0">
                    Découvrez la texture unique de la Mayonnaise Gala. Fabriquée avec passion au Cameroun pour offrir une sauce onctueuse, 100% au goût qui rassemble.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                    <a id="link-hero-gamme" href="#gamme" class="px-8 py-4 bg-galaGreen text-white rounded-2xl font-bold shadow-lg shadow-green-200 hover:scale-105 transition-transform text-center uppercase text-sm tracking-wider">Découvrir les formats</a>
                    <a id="link-hero-b2b" href="#b2b" class="px-8 py-4 bg-white text-galaDark border border-slate-200 rounded-2xl font-bold hover:bg-slate-50 transition text-center uppercase text-sm tracking-wider">Espace Distributeur</a>
                </div>
            </div>
       <div class="md:w-2/5 mt-16 md:mt-0 relative flex justify-center">
    <div class="absolute inset-0 bg-galaGreen/20 rounded-full blur-[80px] animate-pulse"></div>
    
    <div class="w-64 h-80 md:w-80 md:h-[450px] relative animate-float">
        <div class="absolute inset-0 bg-white rounded-[3rem] shadow-2xl border-[10px] border-white overflow-hidden flex items-center justify-center">
            <img src="WhatsApp-Image 2026-05-11 at 21.58.56 (1).jpeg" 
                 alt="Mayonnaise Gala Hero" 
                 class="w-full h-full object-contain p-4 product-shadow">
        </div>
        
        <div class="absolute -right-4 top-10 bg-galaGold text-white h-16 w-16 rounded-full flex items-center justify-center shadow-xl font-black text-xs text-center leading-tight rotate-12 z-10">
            100%<br>NATU
        </div>
    </div>
</div>
    </section>
    <section id="a-propos" class="py-24 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-5">
            <div class="grid md:grid-cols-2 gap-16 items-center">
                <div class="relative group">
                    <div class="absolute -inset-4 bg-slate-100 rounded-[4rem] -rotate-2 group-hover:rotate-0 transition-transform duration-500"></div>
                    <div class="relative w-full aspect-[4/5] bg-white rounded-[3rem] overflow-hidden border-[12px] border-white shadow-2xl">
                         <img src="WhatsApp-Image 2026-05-11 at 21.58.57 (1).jpeg" alt="Gala Agro Production" class="w-full h-full object-contain transition-transform duration-[2s] group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-galaDark/40 via-transparent to-transparent"></div>
                    </div>
                    <div class="absolute -bottom-8 -right-8 bg-galaGold text-white p-8 rounded-[2rem] shadow-2xl hidden lg:block animate-bounce">
                        <p class="text-xs uppercase font-black tracking-tighter mb-1">Expertise</p>
                        <i class="fas fa-award text-4xl"></i>
                    </div>
                </div>
                <div>
                    <h2 class="text-4xl font-extrabold mb-6 text-galaDark">Notre Histoire</h2>
                    <p class="text-slate-600 mb-8 leading-relaxed text-lg">
                        Créée pour répondre aux exigences des gourmets, <strong class="text-galaGreen">GALA AGRO</strong> produit la Mayonnaise Gala pour offrir une expérience culinaire unique. Nos valeurs reposent sur la qualité locale, une hygiène irréprochable et une proximité constante avec nos consommateurs.
                    </p>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="flex items-center gap-4 p-5 rounded-2xl bg-green-50 border-l-4 border-galaGreen hover:bg-green-100 transition-colors">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-galaGreen shadow-sm"><i class="fas fa-egg"></i></div>
                            <span class="font-bold text-slate-700">Huile raffinée & œufs frais du jour</span>
                        </div>
                        <div class="flex items-center gap-4 p-5 rounded-2xl bg-green-50 border-l-4 border-galaGreen hover:bg-green-100 transition-colors">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-galaGreen shadow-sm"><i class="fas fa-vial-circle-check"></i></div>
                            <span class="font-bold text-slate-700">Zéro colorants artificiels agressifs</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
     <section id="qualite" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-5 text-center">
            <h2 class="text-3xl font-extrabold mb-12 text-galaDark tracking-tight uppercase">Notre Engagement Qualité</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="p-10 bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-100/50 hover:border-galaGreen transition-colors group">
                    <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center text-galaGreen mb-8 mx-auto group-hover:bg-galaGreen group-hover:text-white transition-all">
                        <i class="fas fa-shield-alt text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Normes ISO 22000</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Une sécurité alimentaire garantie à chaque étape de la production industrielle.</p>
                </div>
                <div class="p-10 bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-100/50 hover:border-galaGreen transition-colors group">
                    <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center text-galaGreen mb-8 mx-auto group-hover:bg-galaGreen group-hover:text-white transition-all">
                        <i class="fas fa-leaf text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Ingrédients Frais</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Sélection rigoureuse des meilleurs œufs et huiles végétales de nos terroirs.</p>
                </div>
                <div class="p-10 bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-100/50 hover:border-galaGreen transition-colors group">
                    <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center text-galaGreen mb-8 mx-auto group-hover:bg-galaGreen group-hover:text-white transition-all">
                        <i class="fas fa-flask text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4">Laboratoire Interne</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Analyses quotidiennes pour assurer une onctuosité et une conservation parfaite.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="gamme" class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-galaDark tracking-tight">Notre Gamme Complète</h2>
                <p class="text-slate-600">Explorez nos différents conditionnements adaptés aux besoins des familles comme des professionnels de la restauration.</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($produits as $p): ?>
                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-xl transition duration-300 flex flex-col justify-between group relative">
                    
                    <?php if (isset($p['stock']) && $p['stock'] <= 0): ?>
                        <span class="absolute top-4 right-4 bg-red-500 text-white text-xs font-black px-3 py-1.5 rounded-full shadow-md uppercase tracking-wider z-10 animate-pulse">
                            Épuisé / Solde
                        </span>
                    <?php elseif (isset($p['en_solde']) && $p['en_solde'] == 1): ?>
                        <span class="absolute top-4 right-4 bg-galaGold text-white text-xs font-black px-3 py-1.5 rounded-full shadow-md uppercase tracking-wider z-10">
                            PROMO
                        </span>
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

                    <div class="mt-6 pt-4 border-t border-slate-50 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-xs text-slate-400 font-medium">Prix conseillé</span>
                            <span class="text-xl font-black text-galaDark">
                                <?= isset($p['prix']) && $p['prix'] > 0 ? number_format($p['prix'], 0, ',', ' ') . ' FCFA' : 'Prix sur demande'; ?>
                            </span>
                        </div>
                        
                        <?php if (isset($p['stock']) && $p['stock'] <= 0): ?>
                            <button disabled class="bg-slate-200 text-slate-400 text-sm font-bold px-4 py-2.5 rounded-xl cursor-not-allowed">
                                Indisponible
                            </button>
                        <?php else: ?>
                            <a href="#contact" class="bg-galaGreen/10 text-galaGreen hover:bg-galaGreen hover:text-white text-sm font-bold px-4 py-2.5 rounded-xl transition">
                                Commander
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true): ?>
                        <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition duration-300">
                            <a href="admin/edit_product.php?id=<?= $p['id'] ?>" class="bg-slate-900 text-white text-xs px-2 py-1 rounded border border-galaGreen/50 shadow-lg">
                                <i class="fas fa-edit"></i> Éditer
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="galerie" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-galaDark tracking-tight">Gala en Images</h2>
                <p class="text-slate-600">Immersion au cœur de notre univers de production et de nos événements de marque.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
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
     <section id="b2b" class="py-24 bg-white text-black overflow-hidden relative">

        <div class="absolute top-0 right-0 w-96 h-96 bg-black/20 rounded-full blur-[120px] -mr-48 -mt-48"></div>

        <div class="max-w-7xl mx-auto px-5 relative z-10">

            <div class="text-center mb-16">

                <h2 class="text-3xl md:text-5xl font-extrabold mb-6 text-white uppercase tracking-tighter">Espace <span class="text-galaGold italic">Distributeurs</span></h2>

                <p class="text-black max-w-2xl mx-auto opacity-80 text-lg italic">Rejoignez la famille GALA AGRO. Nous accompagnons nos partenaires B2B avec une logistique performante.</p>

            </div>

            

            <div class="grid md:grid-cols-2 gap-12 items-center bg-black/5 p-8 md:p-16 rounded-[4rem] border border-white/10 backdrop-blur-md">

                <div class="space-y-8">

                    <h3 class="text-2xl font-bold text-galaGold">Pourquoi devenir partenaire ?</h3>

                    <ul class="space-y-5">

                        <li class="flex items-center gap-4 bg-white/5 p-4 rounded-2xl border border-white/5 hover:bg-white/10 transition-colors">

                            <div class="w-10 h-10 bg-galaGold rounded-full flex items-center justify-center text-galaDark"><i class="fas fa-percentage font-bold"></i></div>

                            <span class="font-medium text-lg">Marges bénéficiaires attractives</span>

                        </li>

                        <li class="flex items-center gap-4 bg-white/5 p-4 rounded-2xl border border-white/5 hover:bg-white/10 transition-colors">

                            <div class="w-10 h-10 bg-galaGold rounded-full flex items-center justify-center text-galaDark"><i class="fas fa-truck font-bold"></i></div>

                            <span class="font-medium text-lg">Livraison prioritaire en 24/48h</span>

                        </li>

                        <li class="flex items-center gap-4 bg-white/5 p-4 rounded-2xl border border-white/5 hover:bg-white/10 transition-colors">

                            <div class="w-10 h-10 bg-galaGold rounded-full flex items-center justify-center text-galaDark"><i class="fas fa-bullhorn font-bold"></i></div>

                            <span class="font-medium text-lg">Supports marketing et PLV offerts</span>

                        </li>

                    </ul>

                    <div class="pt-6">

                        <a href="https://wa.me/237699105753" class="inline-flex items-center px-10 py-5 bg-galaGold text-galaDark rounded-2xl font-black shadow-2xl hover:scale-105 transition-transform uppercase text-sm tracking-[0.1em]">

                            <i class="fab fa-whatsapp mr-3 text-2xl"></i> Devenir Grossiste

                        </a>

                    </div>

                </div>

                <div class="hidden md:block">

                    <div class="grid grid-cols-2 gap-6">

                        <div class="aspect-square bg-white/5 rounded-[2.5rem] flex flex-col items-center justify-center border border-white/10 hover:bg-galaGreen/30 transition-all cursor-default">

                            <span class="text-4xl font-black text-galaGold mb-2">D1</span>

                            <p class="text-[10px] text-black uppercase font-black tracking-widest">Grossistes</p>

                        </div>

                        <div class="aspect-square bg-galaGreen/20 rounded-[2.5rem] flex flex-col items-center justify-center border border-galaGreen/30 shadow-2xl">

                            <span class="text-4xl font-black text-white mb-2">D2</span>

                            <p class="text-[10px] text-black-300 uppercase font-black tracking-widest text-center leading-tight">Grandes<br>Surfaces</p>

                        </div>

                        <div class="aspect-square bg-white/5 rounded-[2.5rem] flex flex-col items-center justify-center border border-white/10 hover:bg-galaGreen/30 transition-all cursor-default">

                            <span class="text-4xl font-black text-galaGold mb-2">D3</span>

                            <p class="text-[10px] text-black uppercase font-black tracking-widest">Horeca</p>

                        </div>

                        <div class="aspect-square bg-white/5 rounded-[2.5rem] flex flex-col items-center justify-center border border-white/10 hover:bg-galaGreen/30 transition-all cursor-default">

                            <span class="text-4xl font-black text-galaGold mb-2">D4</span>

                            <p class="text-[10px] text-black uppercase font-black tracking-widest">Export</p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>
<section id="contact" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-5">
            <div class="bg-slate-50 rounded-[4rem] overflow-hidden border border-slate-100 shadow-sm grid md:grid-cols-2">
                <div class="p-12 md:p-20">
                    <h2 class="text-4xl font-black mb-10 text-galaDark uppercase tracking-tighter">Parlons ensemble</h2>
                    <div class="space-y-8">
                        <div class="flex items-start gap-6">
                            <div class="w-12 h-12 bg-white rounded-2xl shadow-sm flex items-center justify-center text-galaGreen"><i class="fas fa-map-marker-alt text-xl"></i></div>
                            <div><p class="font-black text-lg">Siège Social</p><p class="text-slate-500">Douala, Cameroun</p></div>
                        </div>
                        <div class="flex items-start gap-6">
                            <div class="w-12 h-12 bg-white rounded-2xl shadow-sm flex items-center justify-center text-galaGreen"><i class="fas fa-phone text-xl"></i></div>
                            <div><p class="font-black text-lg">Téléphone</p><p class="text-slate-500">+237 676 588 240</p></div>
                        </div>
                        <div class="flex items-start gap-6">
                            <div class="w-12 h-12 bg-white rounded-2xl shadow-sm flex items-center justify-center text-galaGreen"><i class="fas fa-envelope text-xl"></i></div>
                            <div><p class="font-black text-lg">Email Professionnel</p><p class="text-slate-500">info@adisa-cm.com</p></div>
                        </div>
                    </div>
                </div>
                <div class="bg-galaGreen/5 p-12 md:p-20 flex flex-col justify-center">
                <form id="contactForm" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Votre Nom Complet</label>
                            <input type="text" name="nom" placeholder="Ex: Jean Dupont" class="w-full p-4 rounded-xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-galaGreen outline-none transition" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Téléphone</label>
                            <input type="tel" name="tel" placeholder="Ex: 699000000" class="w-full p-4 rounded-xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-galaGreen outline-none transition" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Votre Message</label>
                        <textarea name="message" placeholder="Détaillez votre demande ici..." class="w-full p-4 rounded-xl border border-slate-100 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-galaGreen outline-none h-36 transition resize-none"></textarea>
                    </div>
                    
                    <button type="submit" class="w-full bg-galaGreen text-white font-bold py-4 rounded-xl shadow-lg hover:bg-galaDark transition duration-300 tracking-wide">
                        <i class="fas fa-paper-plane mr-2 text-sm"></i> Envoyer le message
                    </button>
                </form>

                <div id="responseMsg" class="mt-4 hidden p-4 rounded-xl text-center font-bold text-sm shadow-inner transition duration-300"></div>
            </div>
        </div>
    </section>
     <div class="bg-galaDark py-12">
        <div class="max-w-7xl mx-auto px-5 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div><div class="text-3xl font-extrabold text-white mb-1">50+</div><div class="text-green-300 text-[10px] uppercase font-bold tracking-widest">Collaborateurs</div></div>
            <div><div class="text-3xl font-extrabold text-white mb-1">500t</div><div class="text-green-300 text-[10px] uppercase font-bold tracking-widest">Capacité / An</div></div>
            <div><div class="text-3xl font-extrabold text-white mb-1">100%</div><div class="text-green-300 text-[10px] uppercase font-bold tracking-widest">Hygiène locale</div></div>
            <div><div class="text-3xl font-extrabold text-white mb-1">ISO</div><div class="text-green-300 text-[10px] uppercase font-bold tracking-widest">Normes 22000</div></div>
        </div>
    </div>

    <footer class="bg-galaDark py-12 text-center text-white border-t border-white/10">
        <div class="flex justify-center gap-10 mb-6 text-[11px] font-black uppercase tracking-[0.2em]">
            <button id="btn-modal-legal" onclick="toggleModal('legal-modal')" class="hover:text-galaDark transition-colors">Mentions Légales</button>
            <button id="btn-modal-privacy" onclick="toggleModal('privacy-modal')" class="hover:text-galaDark transition-colors">Confidentialité</button>
        </div>
        
        <p class="text-[10px] font-bold text-white/80 italic tracking-[0.2em] uppercase mb-2">
            ©2026 GALA AGRO SARL — TOUS DROITS RÉSERVÉS
        </p>
        <p class="text-[9px] font-black text-white/60 uppercase tracking-widest">
            Développé par JAy group developper
        </p>
    </footer>

    <div id="legal-modal" class="fixed inset-0 z-[60] hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-5">
    <div class="bg-white rounded-[2rem] max-w-2xl w-full p-8 md:p-12 shadow-2xl relative overflow-y-auto max-h-[80vh]">
        <button id="btn-close-legal" onclick="toggleModal('legal-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-galaGold text-2xl transition-colors duration-200">
            <i class="fas fa-times"></i>
        </button>
            <h2 class="text-3xl font-black text-white mb-6 uppercase">Mentions Légales</h2>
            <div class="text-slate-600 space-y-4 text-sm leading-relaxed">
                <p><strong>Éditeur :</strong> GALA AGRO SARL, Douala, Cameroun.</p>
                <p><strong>Responsable :</strong> Direction de la communication GALA.</p>
                <p><strong>Hébergement :</strong> Serveurs sécurisés Winx Corp.</p>
                <p><strong>Propriété intellectuelle :</strong> Toute reproduction du contenu, des images ou du logo sans autorisation préalable est strictement interdite.</p>
            </div>
        </div>
    </div>

    <div id="privacy-modal" class="fixed inset-0 z-[60] hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-5">
        <div class="bg-white rounded-[2rem] max-w-2xl w-full p-8 md:p-12 shadow-2xl relative overflow-y-auto max-h-[80vh]">
            <button id="btn-close-privacy" onclick="toggleModal('privacy-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-galaGold  text-2xl">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="text-3xl font-black text-Galagreen mb-6 uppercase">Confidentialité</h2>
            <div class="text-slate-600 space-y-4 text-sm leading-relaxed">
                <p><strong>Collecte des données :</strong> Les informations collectées via nos formulaires sont destinées uniquement à la gestion de vos demandes commerciales.</p>
                <p><strong>Sécurité :</strong> Nous mettons en œuvre toutes les mesures nécessaires pour protéger vos informations personnelles contre tout accès non autorisé.</p>
                <p><strong>Vos droits :</strong> Conformément aux réglementations en vigueur, vous disposez d'un droit d'accès et de rectification de vos données en nous contactant par email.</p>
            </div>
        </div>
    </div>



    <script>
        // Menu Mobile Toggle
        const menuBtn = document.getElementById('menu-btn');
        const mobileNav = document.getElementById('mobile-nav');
        
        menuBtn.addEventListener('click', () => {
            mobileNav.classList.toggle('active');
            const icon = menuBtn.querySelector('i');
            icon.classList.toggle('fa-bars-staggered');
            icon.classList.toggle('fa-xmark');
        });

        document.querySelectorAll('.mobile-link').forEach(link => {
            link.addEventListener('click', () => {
                mobileNav.classList.remove('active');
                menuBtn.querySelector('i').className = 'fas fa-bars-staggered';
            });
        });

        // Envoi asynchrone du formulaire (Contact)
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const responseDiv = document.getElementById('responseMsg');

            // Animation d'envoi
            responseDiv.innerHTML = "<i class='fas fa-spinner animate-spin mr-2'></i> Envoi en cours...";
            responseDiv.className = "mt-4 p-4 rounded-xl bg-blue-50 text-blue-700 text-center font-bold text-sm block";

            fetch('traitement_contact.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                return response.text().then(text => {
                    return { status: response.status, text: text };
                });
            })
            .then(res => {
                responseDiv.innerHTML = res.text;
                responseDiv.classList.remove('hidden');
                
                if (res.status === 200) {
                    responseDiv.className = "mt-4 p-4 rounded-xl bg-green-100 text-green-700 text-center font-bold text-sm";
                    this.reset();
                } else {
                    responseDiv.className = "mt-4 p-4 rounded-xl bg-red-100 text-red-700 text-center font-bold text-sm";
                }
            })
            .catch(error => {
                responseDiv.innerHTML = "Une erreur réseau est survenue.";
                responseDiv.className = "mt-4 p-4 rounded-xl bg-red-100 text-red-700 text-center font-bold text-sm";
            });
          
        
        });

        function toggleModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.toggle('hidden');
                document.body.classList.toggle('overflow-hidden');
            }
        }

        window.addEventListener('click', (event) => {
            if (event.target.classList.contains('bg-slate-900/60')) {
                const openModal = document.querySelector('[id$="-modal"]:not(.hidden)');
                if(openModal) {
                    openModal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            }
        });
        
    </script>
</body>
</html>