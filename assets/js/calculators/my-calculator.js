/**
 * PJM Calculator Core - Master JS (v19.2 - Auto Reset)
 * Lokalizacja: assets/js/calculators/my-calculator.js
 */
window.PJM_Core = (function ($) {
    "use strict";

    let cart = [];
    let isProcessing = false;

    // Pobierz konfigurację globalną (bezpiecznie)
    const getVars = () => (typeof pjm_calc_vars !== 'undefined') ? pjm_calc_vars : {};

    // --- 1. HELPERY ---
    const formatMoney = (v) => new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'PLN',
        minimumFractionDigits: 0
    }).format(v);

    // --- 2. SIDEBAR MANAGER ---
    function moveSummaryToSidebar(targetTabId) {
        try {
            const sidebarSlot = document.getElementById('pjm-dynamic-sidebar-area');
            if (!sidebarSlot) return;

            // Odłóż stary element na miejsce
            if (sidebarSlot.firstElementChild) {
                const currentItem = sidebarSlot.firstElementChild;
                const originalParentId = currentItem.dataset.originId;
                if (originalParentId) {
                    const originalParent = document.getElementById(originalParentId);
                    if (originalParent) originalParent.appendChild(currentItem);
                    else currentItem.remove();
                }
            }

            // Znajdź nowy element
            const tabContent = document.getElementById('tab-' + targetTabId);
            if (!tabContent) return;

            const summaryPanel = tabContent.querySelector('.pjm-summary-card, .summary-box');
            if (summaryPanel) {
                if (!summaryPanel.parentElement.id) {
                    summaryPanel.parentElement.id = 'origin-' + targetTabId + '-' + Math.random().toString(36).substr(2, 5);
                }
                summaryPanel.dataset.originId = summaryPanel.parentElement.id;
                sidebarSlot.appendChild(summaryPanel);
                summaryPanel.style.display = 'block';
            }
        } catch (e) {
            console.error("Sidebar move error:", e);
        }
    }

    // --- 3. OBSŁUGA ZAKŁADEK ---
    function switchTab(tabId, event) {
        if (event) event.preventDefault();

        // UI: Przyciski
        $('.pjm-tab-btn').removeClass('active');
        $(`.pjm-tab-btn[data-tab="${tabId}"]`).addClass('active');

        // UI: Kontent
        $('.pjm-tab-content').hide().removeClass('active');
        $(`#tab-${tabId}`).fadeIn(200).addClass('active');

        // Przenieś sidebar
        moveSummaryToSidebar(tabId);
    }

    // --- 4. RENDEROWANIE KOSZYKA ---
    function renderCart() {
        const container = $('#global-items-list');
        const totalEl = $('#global-total');
        const countEl = $('#cart-count');
        const btn = $('#btn-global-checkout');

        if (!container.length) return;
        container.empty();

        let total = 0;

        if (!cart || cart.length === 0) {
            container.html(`
                <div class="pjm-empty-state">
                    <span class="material-symbols-rounded">shopping_cart_off</span>
                    <p>Koszyk jest pusty</p>
                </div>
            `);
            if (btn.length) btn.addClass('disabled').prop('disabled', true);
        } else {
            cart.forEach((item, i) => {
                const finalPrice = item.pricing ? parseFloat(item.pricing.total) : 0;
                total += finalPrice;

                // Meta Info
                let metaHtml = '';
                if (item.meta.scope) metaHtml += `<div><strong>Zakres:</strong> ${item.meta.scope}</div>`;
                if (item.meta.dates) metaHtml += `<div><strong>Termin:</strong> ${item.meta.dates}</div>`;
                if (item.meta.location) metaHtml += `<div><strong>Lokalizacja:</strong> ${item.meta.location}</div>`;

                // Addons
                let addonsHtml = '';
                if (item._addons_data && item._addons_data.length > 0) {
                    addonsHtml = '<div class="item-addons-list" style="margin-top:8px; padding-top:8px; border-top:1px dashed #e2e8f0; font-size:11px; color:#64748b;">';
                    item._addons_data.forEach(addon => {
                        addonsHtml += `<div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                            <span>• ${addon.name}</span>
                            <span>+${formatMoney(addon.price)}</span>
                        </div>`;
                    });
                    addonsHtml += '</div>';
                }

                // Express Badge
                let expressHtml = '';
                if (item.delivery && item.delivery.mode !== 'standard') {
                    const label = item.delivery.mode === 'express_24h' ? '24h' : '48h';
                    expressHtml = `<div style="color:#d35400; font-weight:700; margin-top:4px; font-size:11px; display:flex; align-items:center; gap:4px;">
                        <span class="material-symbols-rounded" style="font-size:14px;">rocket_launch</span> Ekspres ${label} (+${item.delivery.surcharge_percent}%)
                    </div>`;
                }

                const itemHtml = `
                <div class="jm-cart-item">
                    <div class="item-top">
                        <span class="service-tag">
                            ${{
                        'text': 'Tekst',
                        'video': 'Wideo',
                        'loop': 'Pętla',
                        'event': 'Event'
                    }[item.service_type] || 'Usługa'}
                        </span>
                        <span class="btn-remove" onclick="PJM_Core.removeItem(${i})" title="Usuń">&times;</span>
                    </div>
                    <div class="item-title">${item.title}</div>
                    <div class="item-meta">
                        ${metaHtml}
                        ${expressHtml}
                        ${addonsHtml}
                    </div>
                    <div class="item-footer">
                        <span class="item-qty">${item.quantity} ${item.unit}</span>
                        <span class="item-price">${formatMoney(finalPrice)}</span>
                    </div>
                </div>`;

                container.append(itemHtml);
            });
            if (btn.length) btn.removeClass('disabled').prop('disabled', false);
        }

        if (totalEl.length) totalEl.text(formatMoney(total));
        if (countEl.length) countEl.text(cart.length);
    }

    // --- 5. KOMUNIKACJA AJAX ---

    async function refreshNonceAndRetry(callback) {
        try {
            const vars = getVars();
            const response = await fetch(vars.ajax_url + '?action=pjm_refresh_nonce');
            const data = await response.json();
            if (data.success) {
                pjm_calc_vars.nonce = data.data;
                callback();
            } else {
                alert("Sesja wygasła. Odśwież stronę.");
                isProcessing = false;
            }
        } catch (e) { isProcessing = false; }
    }

    function processAddToCart(formData, type, isRetry = false) {
        if (isProcessing && !isRetry) return;
        isProcessing = true;

        const btn = $(`.pjm-${type}-form .jm-btn-add-cart`);
        const originalText = btn.length ? (btn.data('originalText') || btn.html()) : '';

        if (btn.length) {
            if (!btn.data('originalText')) btn.data('originalText', originalText);
            btn.prop('disabled', true).html('<span class="material-symbols-rounded spin">sync</span> Dodawanie...');
        }

        const vars = getVars();
        formData.set('nonce', vars.nonce);

        $.ajax({
            url: vars.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (d) {
                if (d.success) {
                    // 1. UI: Sukces na przycisku
                    if (btn.length) btn.html('<span class="material-symbols-rounded">check</span> Dodano!');
                    setTimeout(() => {
                        if (btn.length) btn.prop('disabled', false).html(originalText);
                    }, 2000);

                    // 2. Aktualizacja koszyka
                    cart = d.data.cart;
                    renderCart();
                    isProcessing = false;

                    // 3. CZYSZCZENIE FORMULARZA (NOWOŚĆ)
                    const $container = $('.pjm-' + type + '-form');
                    if ($container.length) {
                        // Inputy tekstowe i pliki
                        $container.find('input[type="text"], input[type="number"], input[type="file"], textarea').val('');
                        // Checkboxy
                        $container.find('input[type="checkbox"]').prop('checked', false);
                        // Liczniki dodatków (jeśli są)
                        $container.find('.pjm-addon-qty-input').val(0);
                        $container.find('.minus').prop('disabled', true);
                        // Radio (reset do pierwszego)
                        $container.find('input[name="delivery_mode"]').first().prop('checked', true).trigger('change');

                        // Zdarzenie specjalne dla Loop (reset kalendarza) i Event (reset dni)
                        $(document).trigger('pjm_form_reset', [type]);
                    }

                    // 4. Scroll do koszyka
                    $('html, body').animate({
                        scrollTop: $("#pjm-global-cart-section").offset().top - 100
                    }, 500);

                } else {
                    // Obsługa błędów (wygasła sesja)
                    if ((d.data?.includes('Nonce') || d.data?.includes('wygasła')) && !isRetry) {
                        refreshNonceAndRetry(() => processAddToCart(formData, type, true));
                    } else {
                        alert("Błąd: " + (d.data || 'Nieznany błąd serwera'));
                        if (btn.length) btn.prop('disabled', false).html(originalText);
                        isProcessing = false;
                    }
                }
            },
            error: function (err) {
                console.error(err);
                alert("Błąd połączenia z serwerem.");
                if (btn.length) btn.prop('disabled', false).html(originalText);
                isProcessing = false;
            }
        });
    }

    function removeItem(idx) {
        if (isProcessing) return;
        const vars = getVars();

        $.post(vars.ajax_url, {
            action: 'pjm_remove_cart_item',
            nonce: vars.nonce,
            index: idx
        }, function (d) {
            if (d.success) {
                cart = d.data.cart;
                renderCart();
            }
        });
    }

    function checkout() {
        if (isProcessing || !cart.length) return;
        isProcessing = true;

        const btn = $('#btn-global-checkout');
        const originalText = btn.html();
        btn.html('Przetwarzanie...').prop('disabled', true);

        const vars = getVars();

        $.post(vars.ajax_url, {
            action: 'pjm_create_temp_order',
            nonce: vars.nonce
        }, function (d) {
            if (d.success) {
                window.location.href = d.data.checkout_url;
            } else {
                alert("Błąd: " + d.data);
                btn.html(originalText).prop('disabled', false);
                isProcessing = false;
            }
        }).fail(function () {
            alert("Błąd połączenia.");
            btn.html(originalText).prop('disabled', false);
            isProcessing = false;
        });
    }

    // --- INIT ---
    $(document).ready(function () {
        const firstTab = $('.pjm-tab-btn.active').data('tab') || 'video';
        switchTab(firstTab);

        const vars = getVars();
        if (vars.ajax_url) {
            $.post(vars.ajax_url, {
                action: 'pjm_get_cart',
                nonce: vars.nonce
            }, function (d) {
                if (d.success) {
                    cart = d.data.cart;
                    renderCart();
                }
            });
        }
    });

    // Public API
    return {
        switchTab,
        processAddToCart,
        removeItem,
        checkout,
        renderCart
    };

})(jQuery);