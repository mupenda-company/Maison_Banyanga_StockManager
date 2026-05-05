/**
 * Application JavaScript principale
 * Utilise Alpine.js pour la réactivité
 */

// Configuration Alpine.js
document.addEventListener('alpine:init', () => {
    // Store pour le thème (DÉJÀ INITIALISÉ DANS LE LAYOUT)
    
    // Store pour les notifications
    Alpine.store('notifications', {
        items: [],
        
        add(message, type = 'success', duration = 5000) {
            const id = Date.now();
            this.items.push({ id, message, type });
            
            if (duration > 0) {
                setTimeout(() => this.remove(id), duration);
            }
        },
        
        remove(id) {
            this.items = this.items.filter(item => item.id !== id);
        },
        
        success(message) {
            this.add(message, 'success');
        },
        
        error(message) {
            this.add(message, 'error');
        },
        
        warning(message) {
            this.add(message, 'warning');
        },
        
        info(message) {
            this.add(message, 'info');
        }
    });
    
    // Store pour les modals
    Alpine.store('modals', {
        openModals: [],
        
        open(id) {
            if (!this.openModals.includes(id)) {
                this.openModals.push(id);
                document.body.style.overflow = 'hidden';
            }
        },
        
        close(id) {
            this.openModals = this.openModals.filter(m => m !== id);
            if (this.openModals.length === 0) {
                document.body.style.overflow = '';
            }
        },
        
        isOpen(id) {
            return this.openModals.includes(id);
        }
    });

    Alpine.store('confirm', {
        open: false,
        title: 'Confirmation',
        message: '',
        confirmText: 'Confirmer',
        cancelText: 'Annuler',
        type: 'danger',
        _resolver: null,

        ask(options = {}) {
            this.title = options.title || 'Confirmation';
            this.message = options.message || '';
            this.confirmText = options.confirmText || 'Confirmer';
            this.cancelText = options.cancelText || 'Annuler';
            this.type = options.type || 'danger';
            this.open = true;

            return new Promise(resolve => {
                this._resolver = resolve;
            });
        },

        confirm() {
            const r = this._resolver;
            this._resolver = null;
            this.open = false;
            if (typeof r === 'function') r(true);
        },

        cancel() {
            const r = this._resolver;
            this._resolver = null;
            this.open = false;
            if (typeof r === 'function') r(false);
        }
    });
});

// Fonctions utilitaires
const App = {
    // Effectuer une requête API
    async api(url, method = 'GET', data = null) {
        // Ajouter BASE_URL si l'URL est relative
        const fullUrl = url.startsWith('http') ? url : (window.BASE_URL || '') + url;
        
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(fullUrl, options);
            
            // Vérifier si la réponse est du JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error('Erreur serveur - réponse non JSON');
            }
            
            const result = await response.json();
            
        if (!response.ok) {
            let errorMsg = 'Une erreur est survenue';
            let errors = {};
            
            if (result) {
                errorMsg = result.message || errorMsg;
                errors = result.errors || {};
            }
            
            const error = new Error(errorMsg);
            error.errors = errors;
            throw error;
        }
            
            if (result && typeof result === 'object' && Object.prototype.hasOwnProperty.call(result, 'data')) {
                const data = result.data;

                // Cas DELETE/retour sans data: on renvoie l'enveloppe (message disponible)
                if (data === null || data === undefined) {
                    return result;
                }

                // Si data est un objet/array, on injecte message/success (non-énumérable)
                // pour permettre les deux usages:
                // - result.code (data)
                // - result.message (message)
                // - result.data.password (compat)
                if (typeof data === 'object') {
                    try {
                        if (!Object.prototype.hasOwnProperty.call(data, 'message')) {
                            Object.defineProperty(data, 'message', {
                                value: result.message,
                                enumerable: false,
                                configurable: true
                            });
                        }
                        if (!Object.prototype.hasOwnProperty.call(data, 'success')) {
                            Object.defineProperty(data, 'success', {
                                value: result.success,
                                enumerable: false,
                                configurable: true
                            });
                        }
                        if (!Object.prototype.hasOwnProperty.call(data, 'data')) {
                            Object.defineProperty(data, 'data', {
                                value: data,
                                enumerable: false,
                                configurable: true
                            });
                        }
                    } catch (_) {}
                    return data;
                }

                // Data primitive (rare): on renvoie une enveloppe simple
                return { data, message: result.message, success: result.success };
            }

            return result;
        } catch (error) {
            throw error;
        }
    },
    
    // Notification Toast
    notify(message, type = 'success') {
        try {
            const store = typeof Alpine !== 'undefined' ? Alpine.store('notifications') : null;
            if (store && typeof store.add === 'function') {
                store.add(message, type);
            } else {
                const container = document.querySelector('.notifications-container');
                if (!container) return;

                const id = Date.now();
                const el = document.createElement('div');
                el.dataset.id = String(id);
                el.className = 'pointer-events-auto alert-animate rounded-md border px-4 py-3 shadow-lg bg-white text-gray-900';

                if (type === 'success') el.className += ' border-green-200';
                if (type === 'error') el.className += ' border-red-200';
                if (type === 'warning') el.className += ' border-yellow-200';
                if (type === 'info') el.className += ' border-blue-200';

                el.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="flex-1 text-sm">${String(message || '')}</div>
                        <button type="button" class="text-gray-400 hover:text-gray-600" aria-label="Fermer">
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                `;
                el.querySelector('button')?.addEventListener('click', () => el.remove());
                container.appendChild(el);
                setTimeout(() => el.remove(), 5000);
            }
        } catch (e) {
            const container = document.querySelector('.notifications-container');
            if (!container) return;

            const el = document.createElement('div');
            el.className = 'pointer-events-auto alert-animate rounded-md border border-red-200 px-4 py-3 shadow-lg bg-white text-gray-900';
            el.textContent = String(message || '');
            container.appendChild(el);
            setTimeout(() => el.remove(), 5000);
        }
    },

    applyPrimaryColor(hexColor) {
        const hex = String(hexColor || '').trim();
        if (!hex) return;

        const rgb = this.hexToRgb(hex);
        if (!rgb) return;

        const palette = this.generatePrimaryPalette(rgb);
        const root = document.documentElement;
        Object.entries(palette).forEach(([key, value]) => {
            root.style.setProperty(`--color-primary-${key}`, value);
        });
    },

    hexToRgb(hex) {
        const clean = hex.replace('#', '').trim();
        if (!/^[0-9a-fA-F]{3}$|^[0-9a-fA-F]{6}$/.test(clean)) return null;
        const full = clean.length === 3 ? clean.split('').map(c => c + c).join('') : clean;
        const int = parseInt(full, 16);
        return {
            r: (int >> 16) & 255,
            g: (int >> 8) & 255,
            b: int & 255
        };
    },

    rgbToHsl(r, g, b) {
        r /= 255;
        g /= 255;
        b /= 255;

        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        let h = 0;
        let s = 0;
        const l = (max + min) / 2;

        if (max !== min) {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case r:
                    h = (g - b) / d + (g < b ? 6 : 0);
                    break;
                case g:
                    h = (b - r) / d + 2;
                    break;
                default:
                    h = (r - g) / d + 4;
            }
            h /= 6;
        }
        return { h, s, l };
    },

    hslToRgb(h, s, l) {
        let r, g, b;
        if (s === 0) {
            r = g = b = l;
        } else {
            const hue2rgb = (p, q, t) => {
                if (t < 0) t += 1;
                if (t > 1) t -= 1;
                if (t < 1 / 6) return p + (q - p) * 6 * t;
                if (t < 1 / 2) return q;
                if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
                return p;
            };
            const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
            const p = 2 * l - q;
            r = hue2rgb(p, q, h + 1 / 3);
            g = hue2rgb(p, q, h);
            b = hue2rgb(p, q, h - 1 / 3);
        }
        return {
            r: Math.round(r * 255),
            g: Math.round(g * 255),
            b: Math.round(b * 255)
        };
    },

    generatePrimaryPalette(rgb) {
        const { h, s, l } = this.rgbToHsl(rgb.r, rgb.g, rgb.b);
        const steps = {
            50: 0.96,
            100: 0.92,
            200: 0.84,
            300: 0.74,
            400: 0.64,
            500: Math.min(0.60, Math.max(0.25, l)),
            600: 0.48,
            700: 0.38,
            800: 0.28,
            900: 0.20,
        };

        const palette = {};
        Object.entries(steps).forEach(([key, lightness]) => {
            const c = this.hslToRgb(h, s, lightness);
            palette[key] = `${c.r} ${c.g} ${c.b}`;
        });

        return palette;
    },

    // Formater un nombre en devise
    formatMoney(amount, currency = (window.DEVISE || 'CDF')) {
        if (currency === 'USD') {
            return new Intl.NumberFormat('fr-CD', {
                style: 'decimal',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount) + ' USD';
        }
        return new Intl.NumberFormat('fr-CD', {
            style: 'decimal',
            minimumFractionDigits: 0
        }).format(amount) + ' CDF';
    },
    
    // Convertir entre USD et CDF
    convertMoney(amount, from = (window.BASE_DEVISE || 'CDF'), to = (window.DEVISE || 'CDF'), taux = null) {
        // Si pas de taux fourni, utiliser le taux par défaut
        const rate = taux || window.TAUX_CHANGE || 2800;
        
        if (from === to) return amount;
        
        if (from === 'USD' && to === 'CDF') {
            return amount * rate;
        }
        
        if (from === 'CDF' && to === 'USD') {
            return amount / rate;
        }
        
        return amount;
    },
    
    // Formater avec conversion automatique
    formatMoneyConverted(amount, fromDevise = null, toDevise = null) {
        const baseDevise = window.BASE_DEVISE || 'CDF';
        const targetDevise = toDevise || window.DEVISE || 'CDF';
        const sourceDevise = fromDevise || baseDevise;
        
        if (sourceDevise === targetDevise) {
            return this.formatMoney(amount, targetDevise);
        }
        
        const converted = this.convertMoney(amount, sourceDevise, targetDevise);
        return this.formatMoney(converted, targetDevise);
    },
    
    // Mettre à jour le logo globalement
    updateLogoGlobally(logoUrl) {
        window.LOGO_URL = logoUrl;
        
        // Mettre à jour tous les éléments logo
        const logoElements = document.querySelectorAll('#app-logo, .app-logo');
        logoElements.forEach(el => {
            if (el.tagName === 'IMG') {
                el.src = logoUrl;
            }
        });
        
        // Dispatch un événement personnalisé
        window.dispatchEvent(new CustomEvent('logoUpdated', { detail: { url: logoUrl } }));
    },
    
    // Mettre à jour le nom de l'entreprise globalement
    updateCompanyName(name) {
        window.NOM_ENTREPRISE = name;
        window.dispatchEvent(new CustomEvent('companyNameUpdated', { detail: { name: name } }));
    },
    
    // Formater une date
    formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            ...options
        };
        return new Date(date).toLocaleDateString('fr-CD', defaultOptions);
    },
    
    // Formater une date courte
    formatDateShort(date) {
        return new Date(date).toLocaleDateString('fr-CD', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    },
    
    // Formater l'heure
    formatTime(date) {
        return new Date(date).toLocaleTimeString('fr-CD', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    // Formater date et heure
    formatDateTime(date) {
        return this.formatDate(date) + ' ' + this.formatTime(date);
    },
    
    // Ouvrir une modal
    openModal(id) {
        Alpine.store('modals').open(id);
    },
    
    // Fermer une modal
    closeModal(id) {
        Alpine.store('modals').close(id);
    },
    
    // Confirmer une action
    async confirm(messageOrOptions, callback) {
        const options = typeof messageOrOptions === 'string'
            ? { message: messageOrOptions }
            : (messageOrOptions || {});

        const store = (typeof Alpine !== 'undefined' && Alpine.store) ? Alpine.store('confirm') : null;
        const ok = store && typeof store.ask === 'function'
            ? await store.ask(options)
            : window.confirm(options.message || 'Confirmer ?');

        if (typeof callback === 'function') {
            if (ok) callback();
            return;
        }

        return ok;
    },
    
    // Imprimer un élément
    print(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Impression</title>
                    <link href="${window.location.origin}/public/css/app.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; }
                        @media print {
                            body { padding: 0; }
                        }
                    </style>
                </head>
                <body>
                    ${element.innerHTML}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.onload = () => {
                printWindow.print();
                printWindow.close();
            };
        }
    },
    
    // Télécharger en PDF (nécessite une bibliothèque PDF)
    downloadPDF(elementId, filename = 'document.pdf') {
        // Cette fonction nécessite une bibliothèque comme html2pdf.js
        this.notify('Téléchargement PDF non disponible pour le moment', 'info');
    },
    
    // Debounce
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Composant Modal Alpine
document.addEventListener('alpine:init', () => {
    Alpine.data('modal', (id) => ({
        id,
        show: false,
        
        init() {
            this.show = Alpine.store('modals').isOpen(this.id);
            this.$watch('show', (value) => {
                if (value) {
                    Alpine.store('modals').open(this.id);
                } else {
                    Alpine.store('modals').close(this.id);
                }
            });
        },
        
        open() {
            this.show = true;
        },
        
        close() {
            this.show = false;
        },
        
        isOpen() {
            return Alpine.store('modals').isOpen(this.id);
        }
    }));
    
    // Composant DataTable
    Alpine.data('dataTable', (options = {}) => ({
        data: [],
        loading: false,
        page: 1,
        perPage: options.perPage || 20,
        total: 0,
        lastPage: 1,
        search: '',
        filters: {},
        sortField: options.sortField || 'id',
        sortDirection: options.sortDirection || 'desc',
        
        init() {
            this.loadData();
        },
        
        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.page,
                    search: this.search,
                    sortField: this.sortField,
                    sortDirection: this.sortDirection,
                    ...this.filters
                });
                
                const result = await App.api(`${options.url}?${params}`);
                this.data = result.data || result;
                this.total = result.total || this.data.length;
                this.lastPage = result.last_page || 1;
            } catch (error) {
                App.notify('Erreur lors du chargement des données', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        nextPage() {
            if (this.page < this.lastPage) {
                this.page++;
                this.loadData();
            }
        },
        
        prevPage() {
            if (this.page > 1) {
                this.page--;
                this.loadData();
            }
        },
        
        goToPage(page) {
            this.page = page;
            this.loadData();
        },
        
        setFilter(key, value) {
            this.filters[key] = value;
            this.page = 1;
            this.loadData();
        },
        
        clearFilters() {
            this.filters = {};
            this.search = '';
            this.page = 1;
            this.loadData();
        },
        
        sort(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'asc';
            }
            this.loadData();
        },
        
        searchItems: App.debounce(function() {
            this.page = 1;
            this.loadData();
        }, 300)
    }));
    
    // Composant Form
    Alpine.data('form', (options = {}) => ({
        data: {},
        errors: {},
        loading: false,
        
        init() {
            this.data = options.defaultData || {};
        },
        
        async submit() {
            this.loading = true;
            this.errors = {};
            
            try {
                const result = await App.api(options.url, options.method || 'POST', this.data);
                App.notify(result.message || 'Enregistrement réussi', 'success');
                
                if (options.onSuccess) {
                    options.onSuccess(result);
                }
                
                return result;
            } catch (error) {
                if (error.errors) {
                    this.errors = error.errors;
                }
                App.notify(error.message || 'Une erreur est survenue', 'error');
                throw error;
            } finally {
                this.loading = false;
            }
        },
        
        hasError(field) {
            return this.errors[field] !== undefined;
        },
        
        getError(field) {
            return this.errors[field]?.[0] || '';
        },
        
        clearError(field) {
            delete this.errors[field];
        }
    }));
});

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', () => {
    // Initialiser le thème si Alpine est prêt
    if (typeof Alpine !== 'undefined' && Alpine.store('theme')) {
        Alpine.store('theme').init();
    } else {
        // Fallback si Alpine n'est pas encore là
        const isDark = localStorage.getItem('theme') === 'dark' || 
                      (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', isDark);
    }
    
    // Gestion des tooltips
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', (e) => {
            const tooltip = document.createElement('div');
            tooltip.className = 'absolute z-50 px-2 py-1 text-xs text-white bg-gray-900 rounded shadow-lg';
            tooltip.textContent = e.target.dataset.tooltip;
            tooltip.style.top = e.target.offsetTop - 30 + 'px';
            tooltip.style.left = e.target.offsetLeft + 'px';
            tooltip.id = 'tooltip-' + Date.now();
            document.body.appendChild(tooltip);
        });
        
        el.addEventListener('mouseleave', () => {
            document.querySelectorAll('[id^="tooltip-"]').forEach(t => t.remove());
        });
    });

    if (window.COULEUR_PRIMAIRE) {
        App.applyPrimaryColor(window.COULEUR_PRIMAIRE);
    }
});

// Exposer globalement
window.App = App;
