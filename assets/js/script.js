document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault(); 
    
    const btn = this.querySelector('button[type="submit"]');
    const responseDiv = document.getElementById('contact-response');
    const formData = new FormData(this);

    btn.disabled = true;
    btn.innerText = "Envoi en cours...";
    responseDiv.classList.remove('hidden');

    fetch('traitement_contact.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text().then(text => ({ ok: response.ok, text })))
    .then(result => {
        btn.disabled = false;
        btn.innerText = "Envoyer le message";

        // Dans votre bloc contactForm (result.ok) :
        if (result) {
            responseDiv.innerHTML = result.text;
            responseDiv.className = "mb-6 p-4 rounded-xl text-center font-bold bg-green-100 text-green-700 block";
            this.reset();
            unlockMessageField(false); // Message envoyé : le champ redevient libre pour un futur message

            // Vérifie si l'avis a déjà été demandé durant cette session
            if (!sessionStorage.getItem('avisDejaDemande')) {
                setTimeout(() => {
                    const modal = document.getElementById('ratingModal');
                    if (modal) {
                        modal.classList.remove('hidden');
                        // On marque l'avis comme "déjà demandé" pour cette session
                        
                    }
                }, 1000);
            }
            // Disparition du message de succès
            setTimeout(() => {
                responseDiv.classList.add('hidden');
                responseDiv.innerHTML = "";
            }, 5000);
        } else {
            responseDiv.innerHTML = result.text;
            responseDiv.className = "mb-6 p-4 rounded-xl text-center font-bold bg-red-100 text-red-700 block";
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerText = "Envoyer le message";
        responseDiv.innerHTML = "❌ Erreur de connexion au serveur.";
        responseDiv.className = "mb-6 p-4 rounded-xl text-center font-bold bg-red-100 text-red-700 block";
    });
});

let monPanier = [];
let currentNote = 5; // Initialisation sécurisée

// ══════════════════════════════════════════════════════
// Verrouillage du champ "Détails de la commande"
// (rempli automatiquement pendant la finalisation de commande)
// ══════════════════════════════════════════════════════
function lockMessageField(message) {
    const textarea = document.getElementById('message-commande');
    const hint = document.getElementById('message-commande-lock-hint');
    if (!textarea) return;
    textarea.value = message;
    textarea.setAttribute('readonly', 'readonly');
    textarea.classList.add('bg-slate-100', 'text-slate-500', 'cursor-not-allowed');
    textarea.classList.remove('bg-slate-50');
    if (hint) hint.classList.remove('hidden');
}

function unlockMessageField(clear = true) {
    const textarea = document.getElementById('message-commande');
    const hint = document.getElementById('message-commande-lock-hint');
    if (!textarea) return;
    textarea.removeAttribute('readonly');
    textarea.classList.remove('bg-slate-100', 'text-slate-500', 'cursor-not-allowed');
    textarea.classList.add('bg-slate-50');
    if (clear) textarea.value = '';
    if (hint) hint.classList.add('hidden');
}

async function checkStockAndSubmit(id, nom, prix) {
    const input = document.getElementById('quantity-' + id);
    const unitInput = document.getElementById('unit-' + id);
    const qtyRequested = parseInt(input.value) || 1;
    const unit = unitInput ? unitInput.value : 'carton(s)';
    
    const response = await fetch(`api.php?action=get_stock&id=${id}`);
    const data = await response.json();
    
    const itemInCart = monPanier.find(item => item.id === id && item.unit === unit);
    const totalQtyInCart = itemInCart ? itemInCart.qty : 0;

    if ((totalQtyInCart + qtyRequested) > data.stock) {
        alert("⚠️ Stock insuffisant ! Il ne reste que " + data.stock + " en stock.");
        return;
    }

    if (itemInCart) {
        itemInCart.qty += qtyRequested;
    } else {
        monPanier.push({ id, nom, qty: qtyRequested, unit, prix: parseFloat(prix) });
    }
    
    updateBadge();
    const cartSummary = document.getElementById('cart-summary');
    if (cartSummary) cartSummary.classList.remove('hidden');
    
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
    const totalQty = monPanier.reduce((acc, item) => acc + parseInt(item.qty), 0);
    
    if (badge) badge.textContent = totalQty;
    
    if (cartSummary) {
        if (totalQty > 0) cartSummary.classList.remove('hidden');
        else cartSummary.classList.add('hidden');
    }
}

function nextToFinalization() {
    const total = monPanier.reduce((acc, item) => acc + (item.qty * item.prix), 0);
    let message = "Détails de la commande :\n";
    monPanier.forEach(p => message += `- ${p.qty} ${p.unit} de ${p.nom} (${(p.qty * p.prix).toLocaleString()} FCFA)\n`);
    message += `\nTOTAL : ${total.toLocaleString()} FCFA`;

    localStorage.setItem('message_commande', message);
    lockMessageField(message); // Pré-remplit ET verrouille le champ dès l'ouverture du formulaire
    document.getElementById('cartModal').classList.add('hidden');
    document.getElementById('checkoutModal').classList.remove('hidden');
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').classList.add('hidden');
    localStorage.removeItem('message_commande');
    unlockMessageField(); // Commande annulée : le champ redevient libre
}

document.getElementById('finalOrderForm').addEventListener('submit', async function(e) {
    e.preventDefault();

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

    const total = monPanier.reduce((acc, item) => acc + (item.qty * item.prix), 0);
    let message = "Détails de la commande :\n";
    monPanier.forEach(p => message += `- ${p.qty} ${p.unit} de ${p.nom} (${(p.qty * p.prix).toLocaleString()} FCFA)\n`);
    message += `\nTOTAL : ${total.toLocaleString()} FCFA`;
    
    localStorage.setItem('message_commande', message);

    let finalData = new FormData(this);
    finalData.append('message', message);

    const numComInput = document.getElementById('id_de_votre_input_commercial');
    if (numComInput) {
        finalData.append('num_commercial', numComInput.value);
    }

    try {
        const response = await fetch('save_order.php', { method: 'POST', body: finalData });
        const result = await response.json();

        if (result.success) {
            // 1. Réinitialisation du panier et de l'interface
            monPanier = [];
            updateBadge();
            document.getElementById('checkoutModal').classList.add('hidden');
            
            // 2. Mise à jour de l'ID de commande pour la modale avis
            const orderIdInput = document.getElementById('order_id');
            if (orderIdInput) orderIdInput.value = result.order_id;
            
            // 3. On vide uniquement Nom/Téléphone du formulaire de contact
            //    (le champ message reste verrouillé avec le récapitulatif de commande)
            const contactForm = document.getElementById('contactForm');
            if (contactForm) {
                const nomField = contactForm.querySelector('input[name="nom"]');
                const telField = contactForm.querySelector('input[name="tel"]');
                if (nomField) nomField.value = '';
                if (telField) telField.value = '';
            }

            // 4. Remplissage + verrouillage de la zone "Détails de la commande"
            lockMessageField(message);
            localStorage.removeItem('message_commande');

            const responseDiv = document.getElementById('contact-response');
            if (responseDiv) {
                responseDiv.innerHTML = ""; 
                responseDiv.classList.add('hidden');
            }
            
            alert("Commande enregistrée avec succès !");
            window.location.href = '#contact';
        }else {
            alert("Erreur lors de l'enregistrement : " + result.error);
        }
    } catch (error) {
        console.error("Erreur save_order:", error);
        alert("Erreur lors de la sauvegarde finale : " + error.message);
    }
});

function toggleOrderSelector(id) { document.getElementById('selector-container-' + id).classList.toggle('hidden'); }
function incrementQuantity(id) { document.getElementById(`quantity-${id}`).value = parseInt(document.getElementById(`quantity-${id}`).value) + 1; }
function decrementQuantity(id) {
    const input = document.getElementById(`quantity-${id}`);
    if (parseInt(input.value) > 1) input.value = parseInt(input.value) - 1;
}

window.addEventListener('hashchange', () => {
    if (window.location.hash === '#contact') {
        const msg = localStorage.getItem('message_commande');

        if (msg) {
            lockMessageField(msg);
            localStorage.removeItem('message_commande'); // On vide ici aussi
        }
    }
});
document.addEventListener("DOMContentLoaded", () => {
    // 1. Gestion du message de commande
    const msg = localStorage.getItem('message_commande');

    if (msg) {
        lockMessageField(msg);
        // On supprime la clé après l'avoir utilisée pour éviter le remplissage au rechargement
        localStorage.removeItem('message_commande'); 
    }

    // 2. Vérification du jeu (Human check)
    if (sessionStorage.getItem('isHuman') === 'true') {
        const shield = document.getElementById('game-shield');
        if(shield) shield.style.display = 'none';
    }

    // 3. Gestion des cookies
    if (!localStorage.getItem('cookiesAccepted')) {
        setTimeout(() => { 
            const cb = document.getElementById('cookie-banner'); 
            if(cb) cb.classList.remove('hidden'); 
        }, 2000);
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
    }
}

function setRating(note) {
    currentNote = note;
    const stars = document.querySelectorAll('#star-rating span');
    stars.forEach((star, index) => {
        if (star) {
            star.style.color = index < note ? '#F8D64E' : '#D1D5DB';
        }
    });
}

// Fermeture manuelle du modal avis (clic sur la croix) : on ne le réaffichera plus durant cette session
function closeRatingModal() {
    const modal = document.getElementById('ratingModal');
    if (modal) {
        modal.classList.add('fade-out');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('fade-out');
        }, 500);
    }
    sessionStorage.setItem('avisDejaDemande', 'true');
}
// 1. Fonction pour ouvrir la modale
function openRecrutementModal() {
    document.getElementById('recrutementModal').classList.remove('hidden');
}

// 2. Fonction pour mettre à jour le nom du fichier sélectionné (votre besoin UI)
function updateFileName(labelId, input) {
    const label = document.getElementById(labelId);
    if (input.files.length > 0) {
        label.querySelector('span').innerText = input.files[0].name;
        label.classList.add('border-green-500', 'bg-green-50');
    }
}

// 3. Gestion de l'envoi du formulaire (Ajax)
document.getElementById('recrutementForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = document.getElementById('submitBtn');
    
    submitBtn.innerText = "Envoi en cours...";
    submitBtn.disabled = true;

    fetch('traitement_recrutement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('recrutementForm').classList.add('hidden');
            document.getElementById('successMessage').classList.remove('hidden');
        } else {
            alert("Erreur : " + data.message);
            submitBtn.innerText = "Envoyer le dossier";
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert("Une erreur est survenue lors de l'envoi.");
        submitBtn.innerText = "Envoyer le dossier";
        submitBtn.disabled = false;
    });
});
async function envoyerAvisFinal() {
    const orderIdInput = document.getElementById('order_id');
    
    if (!orderIdInput || !orderIdInput.value) {
        alert("Erreur : ID de commande manquant.");
        return;
    }

    const formData = new FormData();
    formData.append('note', currentNote);
    formData.append('order_id', orderIdInput.value);

    try {
        const response = await fetch('traitement_avis.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        if(result.success) {
            alert("Merci pour votre retour !");
            sessionStorage.setItem('avisDejaDemande', 'true');
            
            // --- NOUVEAU : Animation de fermeture ---
            const modal = document.getElementById('ratingModal');
            if(modal) {
                modal.classList.add('fade-out'); // Déclenche l'animation
                
                setTimeout(() => {
                    modal.classList.add('hidden');    // Masque définitivement
                    modal.classList.remove('fade-out'); // Nettoie la classe pour la prochaine fois
                }, 500); // Doit correspondre à la durée du CSS (0.5s)
            }
        } else {
            alert("Erreur : " + result.error);
        }
    } catch(err) {
        console.error("Erreur réseau :", err);
    }
}