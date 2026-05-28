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
    <div class="max-w-7xl mx-auto px-5">
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-galaDark tracking-tight">Notre Gamme Complète</h2>
            <p class="text-slate-600">Explorez nos différents conditionnements adaptés aux besoins des familles comme des professionnels de la restauration.</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($produits as $p): ?>
            <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-xl transition duration-300 flex flex-col justify-between group relative">
                
                <?php if (isset($p['stock']) && $p['stock'] <= 0): ?>
                    <span class="absolute top-4 right-4 bg-red-500 text-white text-xs font-black px-3 py-1.5 rounded-full shadow-md uppercase tracking-wider z-10 animate-pulse">
                        Épuisé
                    </span>
                <?php elseif (isset($p['en_solde']) && $p['en_solde'] == 1): ?>
                    <span class="absolute top-4 right-4 bg-[#007A3D] text-white text-xs font-black px-3 py-1.5 rounded-full shadow-md uppercase tracking-wider z-10">
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

                <div class="mt-6 pt-4 border-t border-slate-50 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
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
                            <button onclick="toggleOrderSelector(<?= $p['id'] ?>)" class="bg-[#007A3D]/10 text-[#007A3D] hover:bg-[#007A3D] hover:text-white text-sm font-bold px-4 py-2.5 rounded-xl transition">
                                Commander
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (!(isset($p['stock']) && $p['stock'] <= 0)): ?>
                    <div id="selector-container-<?= $p['id'] ?>" data-stock="<?= $p['stock'] ?>" class="hidden bg-slate-50 p-3 rounded-2xl border border-slate-200/60 flex flex-col gap-2 transition-all">
                        <div class="flex flex-col sm:flex-row gap-2 items-center justify-between w-full">
                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                <div class="flex items-center bg-white border border-slate-200 rounded-xl focus-within:ring-2 focus-within:ring-galaGreen transition overflow-hidden h-9 w-full sm:w-28">
                                    <button type="button" onclick="decrementQuantity(<?= $p['id'] ?>)" class="px-3 text-slate-500 hover:bg-slate-100 h-full transition select-none">
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    
                                    <input 
                                        id="quantity-<?= $p['id'] ?>" 
                                        type="number" 
                                        value="1" 
                                        min="1" 
                                        max="<?= $p['stock'] ?>"
                                        oninput="validateInputStock(<?= $p['id'] ?>)"
                                        class="w-full text-center font-bold text-sm text-slate-700 bg-transparent outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                    >
                                    
                                    <button type="button" onclick="incrementQuantity(<?= $p['id'] ?>)" class="px-3 text-slate-500 hover:bg-slate-100 h-full transition select-none">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                                <select id="unit-<?= $p['id'] ?>" class="w-1/2 sm:w-24 p-2 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-galaGreen outline-none">
                                    <option value="carton(s)">Carton(s)</option>
                                    <option value="boite(s)">Boîte(s)</option>
                                </select>
                            </div>
                            
                            <button onclick="checkStockAndSubmit(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nom'])) ?>')" class="w-full sm:w-auto bg-[#007A3D] text-white text-xs font-bold px-4 py-2.5 rounded-xl hover:bg-[#005c2e] transition whitespace-nowrap">
                                Valider
                            </button>
                        </div>
                        <p id="error-stock-<?= $p['id'] ?>" class="hidden text-[11px] text-red-600 font-bold mt-1 text-center sm:text-left">
                            <i class="fas fa-exclamation-triangle"></i> Quantité max disponible : <?= $p['stock'] ?> unités.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

    <section id="galerie" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-5">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-[#007A3D] tracking-tight">Gala en Images</h2>
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
                        <a href="https://wa.me/237699105753" class="inline-flex items-center px-10 py-5 bg-galaGold text-galaDark rounded-2xl font-black shadow-2xl hover:scale-105 transition-transform uppercase text-sm tracking-[0.1em]">
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

            <form id="contactForm" class="space-y-6">
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
                    <textarea name="message" rows="5" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-galaGreen outline-none transition" placeholder="Votre texte ou message de commande automatique apparaîtra ici..."></textarea>
                </div>
                <button type="submit" class="w-full py-4 bg-[#007A3D] text-white rounded-2xl font-bold shadow-lg hover:bg-[#005c2e] transition uppercase tracking-wider text-sm">
                    Envoyer le message
                </button>
            </form>
        </div>
    </div>
</section>

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

  

  <script>
    // Menu mobile toggle
    const menuBtn = document.getElementById('menu-btn');
    const mobileNav = document.getElementById('mobile-nav');
    if(menuBtn && mobileNav) {
        menuBtn.addEventListener('click', () => {
            mobileNav.classList.toggle('active');
        });
    }

    // Gestion du sélecteur de commande directe
    function toggleOrderSelector(id) {
        const container = document.getElementById('selector-container-' + id);
        if (container) {
            container.classList.toggle('hidden');
        }
    }

    // Fonctions de contrôle des quantités pour le sélecteur (Boutons + et -)
    function incrementQuantity(id) {
        const input = document.getElementById('quantity-' + id);
        const container = document.getElementById('selector-container-' + id);
        const maxStock = parseInt(container.getAttribute('data-stock')) || 999;
        let currentVal = parseInt(input.value) || 0;
        if (currentVal < maxStock) {
            input.value = currentVal + 1;
            validateInputStock(id);
        }
    }

    function decrementQuantity(id) {
        const input = document.getElementById('quantity-' + id);
        let currentVal = parseInt(input.value) || 1;
        if (currentVal > 1) {
            input.value = currentVal - 1;
            validateInputStock(id);
        }
    }

    // Permet la saisie manuelle fluide sans bloquer l'écriture
    function validateInputStock(id) {
        const input = document.getElementById('quantity-' + id);
        const container = document.getElementById('selector-container-' + id);
        const errorMsg = document.getElementById('error-stock-' + id);
        const maxStock = parseInt(container.getAttribute('data-stock')) || 0;
        
        if (input.value === "") {
            if(errorMsg) errorMsg.classList.add('hidden');
            return;
        }

        let val = parseInt(input.value);

        if (val > maxStock) {
            if(errorMsg) errorMsg.classList.remove('hidden');
            input.value = maxStock;
        } else {
            if(errorMsg) errorMsg.classList.add('hidden');
        }
    }

    // Sécurité au cas où l'utilisateur quitte le champ en le laissant vide
    document.querySelectorAll("input[id^='quantity-']").forEach(input => {
        input.addEventListener('blur', function() {
            let val = parseInt(this.value);
            if (isNaN(val) || val < 1) {
                this.value = 1;
            }
        });
    });

    // ÉTAPE 1 : Le client clique sur "Valider" sous un produit de la gamme
    function checkStockAndSubmit(id, productName) {
        const quantityInput = document.getElementById('quantity-' + id);
        let quantity = parseInt(quantityInput.value) || 1;
        const unit = document.getElementById('unit-' + id).value;
        const container = document.getElementById('selector-container-' + id);
        const maxStock = parseInt(container.getAttribute('data-stock')) || 0;

        if (quantity > maxStock) {
            alert("Désolé, la quantité demandée dépasse le stock disponible.");
            return false;
        }
        
        // 1. Écriture automatique dans la zone de message
        const messageField = document.querySelector('textarea[name="message"]');
        if (messageField) {
            messageField.value = "Bonjour, je souhaite commander directement " + quantity + " " + unit + " de " + productName + ". Merci de me recontacter au plus vite pour valider les modalités.";
        }
        
        // 2. Défilement fluide vers le formulaire de contact
        const contactSection = document.getElementById('contact');
        if (contactSection) {
            contactSection.scrollIntoView({ behavior: 'smooth' });
            setTimeout(() => {
                const nameInput = document.querySelector('input[name="nom"]');
                if(nameInput) nameInput.focus();
            }, 800);
        }

        // 3. Soustraction automatique immédiate du stock en BDD (Requête asynchrone vers l'administration)
        // Note: l'URL pointe vers 'admin/products_manager.php' car votre fichier s'y trouve.
        fetch('admin/products_manager.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                'ajax_decrement_id': id,
                'qty_to_remove': quantity
            })
        })
        .then(response => {
            if (response.ok) {
                const newStock = maxStock - quantity;
                container.setAttribute('data-stock', newStock);
                // Si le stock tombe à zéro, on actualisera l'affichage de la carte après validation du formulaire
            }
        })
        .catch(error => console.error('Erreur lors de la mise à jour du stock :', error));
    }

    // ÉTAPE 2 : Traitement et Envoi réel du formulaire de contact en BDD via AJAX
    const contactForm = document.getElementById('contactForm');
    const responseDiv = document.getElementById('contact-response');

    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Empêche le rechargement brutal de la page

            const formData = new FormData(this);

            fetch('traitement_contact.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                responseDiv.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');
                
                if (response.ok) {
                    // Message de succès vert
                    responseDiv.classList.add('bg-green-100', 'text-green-700');
                    responseDiv.innerHTML = `<i class="fas fa-check-circle mr-2"></i> ${text}`;
                    contactForm.reset(); // Vide le formulaire
                    
                    // Optionnel : Rafraîchir après 2 secondes pour voir le changement des badges RUPTURE de stock si applicable
                    setTimeout(() => { location.reload(); }, 2500);
                } else {
                    // Message d'erreur rouge
                    responseDiv.classList.add('bg-red-100', 'text-red-700');
                    responseDiv.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i> ${text}`;
                }
            })
            .catch(error => {
                responseDiv.classList.remove('hidden', 'bg-green-100', 'text-green-700');
                responseDiv.classList.add('bg-red-100', 'text-red-700');
                responseDiv.innerHTML = `<i class="fas fa-wifi mr-2"></i> Erreur réseau impossible de joindre le serveur.`;
                console.error(error);
            });
        });
    }
</script>
</body>
</html>