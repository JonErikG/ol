jQuery(document).ready(function($) {
    'use strict';

    function getAjaxUrl() {
        if (typeof olTippingAjax !== 'undefined' && olTippingAjax.ajax_url) {
            return olTippingAjax.ajax_url;
        }
        if (typeof olTippingData !== 'undefined' && olTippingData.ajaxUrl) {
            return olTippingData.ajaxUrl;
        }
        return '';
    }

    function getNonce() {
        const formNonce = $('input[name="nonce"]').val();
        if (formNonce) {
            return formNonce;
        }
        if (typeof olTippingAjax !== 'undefined' && olTippingAjax.nonce) {
            return olTippingAjax.nonce;
        }
        if (typeof olTippingData !== 'undefined' && olTippingData.nonce) {
            return olTippingData.nonce;
        }
        return '';
    }

    // Sjekk om det er en lagøvelse
    function isTeamEvent() {
        return $('#ol-tipping-form').data('event-type') === 'team';
    }

    // Håndter søk av utøvere
    $(document).on('keyup', '.ol-athlete-search', function() {
        const $input = $(this);
        const position = $input.data('position');
        const $resultsDiv = $input.siblings('.ol-search-results');
        const search = $input.val().trim();

        if (search.length < 2) {
            $resultsDiv.hide();
            return;
        }

        const ajaxUrl = getAjaxUrl();
        if (!ajaxUrl) {
            return;
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'search_athletes',
                nonce: getNonce(),
                search: search
            },
            success: function(response) {
                if (response.success) {
                    let html = '<ul class="ol-search-list">';
                    response.data.forEach(function(athlete) {
                        html += `<li class="ol-search-result" data-id="${athlete.id}" data-name="${athlete.name}" data-country="${athlete.country}">
                            <strong>${athlete.name}</strong><br/>
                            <small>${athlete.country}</small>
                        </li>`;
                    });
                    html += '</ul>';
                    $resultsDiv.html(html).show();
                } else {
                    $resultsDiv.html('<p class="ol-no-results">Ingen resultater</p>').show();
                }
            }
        });
    });

    // Håndter søk av land (for lagøvelser)
    $(document).on('keyup focus', '.ol-country-search', function() {
        const $input = $(this);
        const position = $input.data('position');
        const $resultsDiv = $input.siblings('.ol-search-results');
        const search = $input.val().trim();

        const ajaxUrl = getAjaxUrl();
        if (!ajaxUrl) {
            return;
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'search_countries',
                nonce: getNonce(),
                search: search
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '<ul class="ol-search-list">';
                    response.data.forEach(function(country) {
                        html += `<li class="ol-country-result" data-id="${country.id}" data-name="${country.name}" data-flag="${country.flag}">
                            <span class="ol-flag">${country.flag}</span>
                            <strong>${country.name}</strong>
                        </li>`;
                    });
                    html += '</ul>';
                    $resultsDiv.html(html).show();
                } else {
                    $resultsDiv.html('<p class="ol-no-results">Ingen land funnet</p>').show();
                }
            }
        });
    });

    // Velg land fra søkeresultater (for lagøvelser)
    $(document).on('click', '.ol-country-result', function() {
        const $item = $(this);
        const countryId = $item.data('id');
        const countryName = $item.data('name');
        const countryFlag = $item.data('flag');
        const $input = $item.closest('.ol-search-container').find('.ol-country-search');
        const position = $input.data('position');
        const $idInput = $input.siblings('.ol-country-id');
        const $resultsDiv = $item.closest('.ol-search-results');

        // Sett ID
        $idInput.val(countryId);

        // Fjern tidligere valg hvis det finnes
        const $prevSelected = $input.siblings('.ol-selected-country');
        if ($prevSelected.length) {
            $prevSelected.remove();
        }

        // Opprett og vis valgt land
        const html = `
            <div class="ol-selected-country">
                <div class="ol-country-info">
                    <span class="ol-country-flag">${countryFlag}</span>
                    <span class="ol-country-name">${countryName}</span>
                </div>
                <button type="button" class="ol-remove-country" data-position="${position}">✕</button>
            </div>
        `;
        
        $input.hide();
        $resultsDiv.hide();
        $input.after(html);
    });

    // Fjern valgt land
    $(document).on('click', '.ol-remove-country', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const position = $btn.data('position');
        const $card = $btn.closest('.ol-tipping-position-card');
        const $input = $card.find('.ol-country-search');
        const $idInput = $card.find('.ol-country-id');

        $idInput.val('');
        $btn.closest('.ol-selected-country').remove();
        $input.show().val('');
    });

    // Velg utøver fra søkeresultater
    $(document).on('click', '.ol-search-result', function() {
        const $item = $(this);
        const athleteId = $item.data('id');
        const athleteName = $item.data('name');
        const athleteCountry = $item.data('country');
        const $input = $item.closest('.ol-search-container').find('.ol-athlete-search');
        const position = $input.data('position');
        const $idInput = $input.siblings('.ol-athlete-id');
        const $resultsDiv = $item.closest('.ol-search-results');

        // Sett ID
        $idInput.val(athleteId);

        // Lagre tidligere valg hvis det finnes
        const $prevSelected = $input.siblings('.ol-selected-athlete');
        
        if ($prevSelected.length) {
            $prevSelected.remove();
        }

        // Opprett og vis valgt utøver
        const html = `
            <div class="ol-selected-athlete">
                <div class="ol-athlete-info">
                    <div class="ol-athlete-name">${athleteName}</div>
                    <div class="ol-athlete-country">${athleteCountry}</div>
                </div>
                <button type="button" class="ol-remove-athlete" data-position="${position}">✕</button>
            </div>
        `;
        
        $input.hide();
        $resultsDiv.hide();
        $input.after(html);
    });

    // Fjern valgt utøver
    $(document).on('click', '.ol-remove-athlete', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const position = $btn.data('position');
        const $card = $btn.closest('.ol-tipping-position-card');
        const $input = $card.find('.ol-athlete-search');
        const $idInput = $card.find('.ol-athlete-id');

        $idInput.val('');
        $btn.closest('.ol-selected-athlete').remove();
        $input.show().val('');
    });

    // Lukk søkeresultater når man klikker utenfor
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.ol-search-container').length) {
            $('.ol-search-results').hide();
        }
    });

    // Submit form
    $('#ol-tipping-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        if ($form.data('has-tipped') === 1 || $form.data('has-tipped') === '1') {
            showMessage('Du har allerede tippet på denne øvelsen. Tipping kan kun gjøres én gang.', 'error');
            return;
        }
        const eventId = $form.data('event-id');
        const eventType = $form.data('event-type');
        let submitData = {
            action: 'submit_tip',
            nonce: $form.find('input[name="nonce"]').val(),
            event_id: eventId
        };

        if (eventType === 'team') {
            // Lagøvelse - samle country IDs
            const countryIds = {};
            $form.find('.ol-country-id').each(function() {
                const $input = $(this);
                const position = $input.data('position');
                const countryId = $input.val();
                countryIds['country_id[' + position + ']'] = countryId;
            });

            // Valider at minst 1 plass er fylt
            const filledCount = Object.values(countryIds).filter(id => id > 0).length;
            if (filledCount === 0) {
                showMessage('Du må velge minst ett land', 'error');
                return;
            }

            submitData = { ...submitData, ...countryIds };
        } else {
            // Individuell øvelse - samle athlete IDs
            const athleteIds = {};
            $form.find('.ol-athlete-id').each(function() {
                const $input = $(this);
                const position = $input.data('position');
                const athleteId = $input.val();
                athleteIds['athlete_id[' + position + ']'] = athleteId;
            });

            // Valider at minst 1 plass er fylt
            const filledCount = Object.values(athleteIds).filter(id => id > 0).length;
            if (filledCount === 0) {
                showMessage('Du må velge minst en utøver', 'error');
                return;
            }

            submitData = { ...submitData, ...athleteIds };
        }

        const ajaxUrl = getAjaxUrl();
        if (!ajaxUrl) {
            showMessage('AJAX-URL mangler. Last siden på nytt.', 'error');
            return;
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: submitData,
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message || 'Tips lagret!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(response.data || 'Det oppstod en feil', 'error');
                }
            },
            error: function() {
                showMessage('Nettverksfeil. Prøv igjen.', 'error');
            }
        });
    });

    // Vis meldinger
    function showMessage(message, type) {
        const $msg = $('#ol-form-message');
        $msg.removeClass('ol-success ol-error')
            .addClass('ol-' + type)
            .text(message)
            .show();
    }
});
