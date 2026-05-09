// ============================================================
// ShopCM — assets/script.js
// TP Sécurité Web — Mai 2026
// ============================================================

// ── Enregistrement du Service Worker (PWA) ──────────────────
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker
            .register('/shopcm/sw.js', { scope: '/shopcm/' })
            .then(function (reg) {
                console.log('[ShopCM PWA] Service Worker enregistré — scope :', reg.scope);

                // Notifier si une mise à jour est disponible
                reg.addEventListener('updatefound', function () {
                    var newWorker = reg.installing;
                    newWorker.addEventListener('statechange', function () {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            console.log('[ShopCM PWA] Mise à jour disponible — rechargez la page.');
                        }
                    });
                });
            })
            .catch(function (err) {
                console.warn('[ShopCM PWA] Échec enregistrement SW :', err);
            });
    });
}

// ── Bouton d'installation PWA ────────────────────────────────
var deferredPrompt = null;

window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;

    // Afficher le bouton d'installation s'il existe dans le DOM
    var installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) {
        installBtn.style.display = 'inline-flex';
        installBtn.addEventListener('click', function () {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function (choice) {
                if (choice.outcome === 'accepted') {
                    console.log('[ShopCM PWA] Application installée.');
                }
                deferredPrompt = null;
                installBtn.style.display = 'none';
            });
        });
    }
});

window.addEventListener('appinstalled', function () {
    console.log('[ShopCM PWA] Application installée avec succès.');
    deferredPrompt = null;
});

document.addEventListener('DOMContentLoaded', function () {

    // ── Auto-expand encart SQL si ?show_sql=1 dans l'URL ──
    if (new URLSearchParams(window.location.search).get('show_sql') === '1') {
        var detail = document.querySelector('details.sql-encart');
        if (detail) {
            detail.open = true;
        }
    }

    // ── Fermer les alertes .alert[data-autohide] après 4 secondes ──
    document.querySelectorAll('.alert[data-autohide]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(function () {
                el.remove();
            }, 500);
        }, 4000);
    });

    // ── Gestion du panier (stockage cookie) ──
    var addButtons = document.querySelectorAll('.btn-add-cart');
    addButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var produitId = this.getAttribute('data-id');
            var panier = getPanier();

            var existing = panier.find(function (item) { return item.id == produitId; });
            if (existing) {
                existing.qty += 1;
            } else {
                panier.push({ id: produitId, qty: 1 });
            }

            setPanier(panier);

            // Feedback visuel
            var originalText = this.textContent;
            this.textContent = '✅ Ajouté !';
            this.disabled = true;
            var self = this;
            setTimeout(function () {
                self.textContent = originalText;
                self.disabled = false;
            }, 1500);
        });
    });

    // ── Fonctions cookie panier ──
    function getPanier() {
        var cookie = document.cookie.split('; ').find(function (row) {
            return row.startsWith('panier=');
        });
        if (!cookie) return [];
        try {
            return JSON.parse(decodeURIComponent(cookie.split('=').slice(1).join('=')));
        } catch (e) {
            return [];
        }
    }

    function setPanier(data) {
        var expires = new Date();
        expires.setDate(expires.getDate() + 7);
        document.cookie = 'panier=' + encodeURIComponent(JSON.stringify(data))
                        + '; expires=' + expires.toUTCString()
                        + '; path=/';
    }

});
