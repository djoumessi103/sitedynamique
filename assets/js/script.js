 document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault(); 
    
    const btn = this.querySelector('button[type="submit"]');
    const responseDiv = document.getElementById('contact-response');
    const formData = new FormData(this);

    btn.disabled = true;
    btn.innerText = "Envoi en cours...";
    responseDiv.classList.remove('hidden'); // Assurez-vous qu'il est visible

    fetch('traitement_contact.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // 1. Afficher le succès
        responseDiv.innerHTML = data;
        responseDiv.className = "mb-6 p-4 rounded-xl text-center font-bold bg-green-100 text-green-700 block";
        this.reset();
        localStorage.removeItem('message_commande');

        // 2. Faire disparaître après 5 secondes (5000 millisecondes)
        setTimeout(() => {
            responseDiv.classList.add('hidden');
        }, 5000); 
    })
    .catch(error => {
        responseDiv.innerHTML = "Une erreur est survenue, veuillez réessayer.";
        responseDiv.className = "mb-6 p-4 rounded-xl text-center font-bold bg-red-100 text-red-700 block";
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerText = "Envoyer le message";
    });
});
    let monPanier = [];
async function checkStockAndSubmit(id, nom, prix) {
    // 1. Récupération des données du formulaire produit
    const input = document.getElementById('quantity-' + id);
    const unitInput = document.getElementById('unit-' + id);
    const qtyRequested = parseInt(input.value) || 1;
    const unit = unitInput ? unitInput.value : 'carton(s)';
    
    // 2. Vérification du stock via l'API
    const response = await fetch(`api.php?action=get_stock&id=${id}`);
    const data = await response.json();
    
    // On compare avec ce qui est déjà dans le panier local pour éviter de dépasser
    const itemInCart = monPanier.find(item => item.id === id && item.unit === unit);
    const totalQtyInCart = itemInCart ? itemInCart.qty : 0;

    if ((totalQtyInCart + qtyRequested) > data.stock) {
        alert("⚠️ Stock insuffisant ! Il ne reste que " + data.stock + " en stock.");
        return;
    }

    // 3. Ajout au panier
    if (itemInCart) {
        itemInCart.qty += qtyRequested;
    } else {
        monPanier.push({ id, nom, qty: qtyRequested, unit, prix: parseFloat(prix) });
    }
    
    // 4. Mise à jour visuelle (Badge et apparition du bouton)
    updateBadge();
    const cartSummary = document.getElementById('cart-summary');
    if (cartSummary) cartSummary.classList.remove('hidden');
    
    // --- LIGNE DÉSACTIVÉE POUR NE PAS OUVRIR LA MODALE AUTOMATIQUEMENT ---
    // openCartModal(); 
    
    // Feedback optionnel
    alert("Produit ajouté au panier !");
}
  
    function openCartModal() {
        const list = document.getElementById('cart-items-list');
        const totalElement = document.getElementById('cart-total-price');
        let totalGlobal = 0;

        list.innerHTML = monPanier.map((item, index) => {
            const sousTotal = item.qty * item.prix;
            totalGlobal += sousTotal;
            return renderCartItem(item, index, sousTotal);
        }).join('');

        totalElement.textContent = totalGlobal.toLocaleString();
        document.getElementById('cartModal').classList.remove('hidden');
    }

    function renderCartItem(item, index, total) {
        return `
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl mb-3">
                <div>
                    <h4 class="font-bold text-galaDark">${item.nom}</h4>
                    <p class="text-xs text-slate-500">${item.qty} ${item.unit} x ${item.prix.toLocaleString()} FCFA</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="font-bold text-sm">${total.toLocaleString()} FCFA</span>
                    <button type="button" onclick="modifierQuantite(${index}, -1)" class="w-8 h-8 rounded-full bg-slate-200">-</button>
                    <button type="button" onclick="supprimerDuPanier(${index})" class="text-red-500"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>`;
    }

    function modifierQuantite(index, delta) {
        monPanier[index].qty += delta;
        if (monPanier[index].qty <= 0) monPanier.splice(index, 1);
        openCartModal();
        updateBadge();
    }

    function supprimerDuPanier(index) {
        monPanier.splice(index, 1);
        openCartModal();
        updateBadge();
    }

   function updateBadge() {
    const cartSummary = document.getElementById('cart-summary');
    const badge = document.getElementById('cart-count-badge');
    
    // 1. Calcul du total des quantités
    const totalQty = monPanier.reduce((acc, item) => acc + parseInt(item.qty), 0);
    
    // 2. Mise à jour du chiffre
    if (badge) {
        badge.textContent = totalQty;
    }
    
    // 3. Affichage du bouton flottant (si > 0 on affiche, sinon on cache)
    if (cartSummary) {
        if (totalQty > 0) {
            cartSummary.classList.remove('hidden'); // Affiche le panier
        } else {
            cartSummary.classList.add('hidden');    // Cache le panier
        }
    }
}
  /** --- 2. LOGIQUE DE FINALISATION --- **/
function nextToFinalization() {
    // 1. Calcul du total
    const total = monPanier.reduce((acc, item) => acc + (item.qty * item.prix), 0);
    
    // 2. Construction du message
    let message = "Détails de la commande :\n";
    monPanier.forEach(p => message += `- ${p.qty} ${p.unit} de ${p.nom} (${(p.qty * p.prix).toLocaleString()} FCFA)\n`);
    message += `\nTOTAL : ${total.toLocaleString()} FCFA`;

    // 3. Sauvegarde temporaire
    localStorage.setItem('message_commande', message);

    // 4. Fermer le panier et OUVRIR la modale de finalisation
    document.getElementById('cartModal').classList.add('hidden');
    document.getElementById('checkoutModal').classList.remove('hidden'); // ON L'AFFICHE ICI
}
document.getElementById('finalOrderForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // 1. Décrémentation du stock
    for (const item of monPanier) {
        let formData = new FormData();
        formData.append('product_id', item.id);
        formData.append('quantity', item.qty);

        try {
            const response = await fetch('process_order.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (!result.success) {
                alert("Erreur stock pour " + item.nom + " : " + result.error);
                return;
            }
        } catch (error) {
            console.error("Erreur réseau (Stock):", error);
            alert("Erreur de connexion au serveur.");
            return;
        }
    }

    // 2. Préparation du message (Calculé une seule fois)
    const total = monPanier.reduce((acc, item) => acc + (item.qty * item.prix), 0);
    let message = "Détails de la commande :\n";
    monPanier.forEach(p => message += `- ${p.qty} ${p.unit} de ${p.nom} (${(p.qty * p.prix).toLocaleString()} FCFA)\n`);
    message += `\nTOTAL : ${total.toLocaleString()} FCFA`;
    
    // Stockage local pour le formulaire
    localStorage.setItem('message_commande', message);

    // 3. Enregistrement global de la commande via save_order.php
    let finalData = new FormData(this);
    finalData.append('message', message); // On envoie le texte calculé ici

    try {
        const response = await fetch('save_order.php', {
            method: 'POST',
            body: finalData
        });

        const result = await response.json();

        if (result.success) {
            // Nettoyage final
            monPanier = [];
            updateBadge();
            document.getElementById('checkoutModal').classList.add('hidden');
            
            // Remplissage du champ contact et redirection
            const textareaContact = document.getElementById('message-commande');
            if (textareaContact) {
                textareaContact.value = message;
            }
            
            alert("Commande enregistrée avec succès !");
            window.location.href = '#contact';
        } else {
            alert("Erreur lors de l'enregistrement : " + result.error);
        }
    } catch (error) {
        console.error("Erreur save_order:", error);
        alert("Erreur lors de la sauvegarde finale.");
    }
});
    /** --- 3. UTILITAIRES --- **/
    function toggleOrderSelector(id) { document.getElementById('selector-container-' + id).classList.toggle('hidden'); }
    
    function incrementQuantity(id) {
        const input = document.getElementById(`quantity-${id}`);
        input.value = parseInt(input.value) + 1;
    }

    function decrementQuantity(id) {
        const input = document.getElementById(`quantity-${id}`);
        if (parseInt(input.value) > 1) input.value = parseInt(input.value) - 1;
    }
window.addEventListener('hashchange', () => {
    if (window.location.hash === '#contact') {
        const msg = localStorage.getItem('message_commande');
        const textarea = document.getElementById('message-commande');
        if (msg && textarea) textarea.value = msg;
    }
});
    // Initialisation
    document.addEventListener("DOMContentLoaded", () => {
        // Pré-remplissage sur la page contact
        const msg = localStorage.getItem('message_commande');
        const textarea = document.getElementById('message-commande');
        if (msg && textarea) textarea.value = msg;
    });
// Initialisation : Vérifier si l'utilisateur a déjà réussi le jeu
document.addEventListener("DOMContentLoaded", () => {
    if (sessionStorage.getItem('isHuman') === 'true') {
        document.getElementById('game-shield').style.display = 'none';
    }
});

// 1. Logique du Jeu et Cookies
document.addEventListener("DOMContentLoaded", () => {
    // Vérif jeu
    if (sessionStorage.getItem('isHuman') === 'true') {
        document.getElementById('game-shield').style.display = 'none';
    }
    // Vérif cookies
    if (!localStorage.getItem('cookiesAccepted')) {
        setTimeout(() => { document.getElementById('cookie-banner').classList.remove('hidden'); }, 2000);
    }
});

function winGame(btn) {
    const shield = document.getElementById('game-shield');
    btn.style.transition = "transform 0.6s ease-in-out";
    btn.style.transform = "rotate(360deg) scale(1.2)";
    sessionStorage.setItem('isHuman', 'true');
    setTimeout(() => { shield.style.opacity = '0'; setTimeout(() => { shield.style.display = 'none'; }, 700); }, 600);
}

function acceptCookies() {
    localStorage.setItem('cookiesAccepted', 'true');
    document.getElementById('cookie-banner').style.opacity = '0';
    setTimeout(() => { document.getElementById('cookie-banner').style.display = 'none'; }, 500);
}

function failGame() {
    const msg = document.getElementById('game-msg');
    msg.classList.remove('hidden');
    msg.style.transform = 'translateX(-10px)';
    setTimeout(() => { msg.style.transform = 'translateX(0)'; }, 100);
    setTimeout(() => { msg.classList.add('hidden'); }, 2000);
}
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.toggle('hidden');
        modal.classList.toggle('flex');
    } else {
        console.warn("La modale avec l'ID " + modalId + " n'a pas été trouvée.");
    }
}