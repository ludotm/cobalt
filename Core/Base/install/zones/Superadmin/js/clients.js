$(document).on('submit', '#filter-form', function(event){
    event.preventDefault();
    $('#filter-form .search-submit').trigger('click');
});

function get_search_filters() {

    var data = {};
    data.search = $('#search-filters .search-text').val();
    data.order = $('#search-filters .search-order').val();
    data.motif = $('#search-filters .search-motif').val();
    $current_folder = $('#folders .folder-pills.active');

    if ($current_folder.hasClass('cyan')) {data.folder = 'cyan';}
    else if ($current_folder.hasClass('green')) {data.folder = 'green';}
    else if ($current_folder.hasClass('red')) {data.folder = 'red';}
    else if ($current_folder.hasClass('pink')) {data.folder = 'pink';}
    else if ($current_folder.hasClass('orange')) {data.folder = 'orange';}
    else if ($current_folder.hasClass('blue')) {data.folder = 'blue';}
    else if ($current_folder.hasClass('yellow')) {data.folder = 'yellow';}
    else if ($current_folder.hasClass('purple')) {data.folder = 'purple';}
    else {data.folder = '';}
   
    return data;
}
function update_status (id_client) {

    var url = $('#folders').data('refresh-url') + '/'+id_client;

    $.ajax({
           url : url,
           type : 'GET',
           dataType : 'json',
           success : function(data) {
                $('#folders .folder-text').each(function(){

                    if ($(this).hasClass('cyan')) {
                        $(this).parent().find('.folder-count').empty().append(data.count_cyan);
                    } else if ($(this).hasClass('green')) {
                        $(this).parent().find('.folder-count').empty().append(data.count_green);
                    } else if ($(this).hasClass('red')) {
                        $(this).parent().find('.folder-count').empty().append(data.count_red);
                    } else if ($(this).hasClass('pink')) {
                        $(this).parent().find('.folder-count').empty().append(data.count_pink);
                    } else if ($(this).hasClass('orange')) {
                        $(this).parent().find('.folder-count').empty().append(data.count_orange);
                    } else if ($(this).hasClass('blue')) {
                        $(this).parent().find('.folder-count').empty().append(data.count_blue);
                    } else if ($(this).hasClass('yellow')) {
                        $(this).parent().find('.folder-count').empty().append(data.count_yellow);
                    } else if ($(this).hasClass('purple')) {
                        $(this).parent().find('.folder-count').empty().append(data.count_purple);
                    } 
                });

                update_timeline (data.process_step);
                update_popup_state (data.primary_status, 'test');
                $('#popup_status').empty().append(data.popup_status);

                // si on est dans un sous dossier, on vérifie si on doit cacher 
                // ou montrer un élément après update du status
                if ($('.folder-pills').hasClass('active')) { 
                    var current_status = $('#_big_users_table tr[data-id="'+id_client+'"] .status_td').find('.badge').attr('class');
                    current_status = current_status.replace(' ', '').replace('badge-', '').replace('badge', '').trim();

                    var current_folder = $('.folder-pills.active').attr('class');
                    current_folder = current_folder.replace(' ', '').replace('active', '').replace('folder-pills', '').trim();

                    $('#_big_users_table tr[data-id="'+id_client+'"] .status_td').empty().append(data.status_details);
                    
                    if (current_status == current_folder) {

                        if (data.status_details.indexOf(current_folder) <= 0) {
                            fade_item_from_list (id_client, 'hide');
                        }
                    } else  {
                        
                        if (data.status_details.indexOf(current_folder) >= 0) {
                            fade_item_from_list (id_client, 'show');
                        }
                    }

                // Sinon on update juste le statut dans la liste
                } else {
                    $('#_big_users_table tr[data-id="'+id_client+'"] .status_td').empty().append(data.status_details);
                }

                //data.secondary_status
            },
           error : function(data, state){
                console.log(data);
                flash_messenger('Erreur lors de la mise à jour des statuts, rechargement de la page...','error');
                //setTimeout(function(){location.reload();}, 1500);
           },
        });
}
function update_popup_state (color, text) {
    //$('.modal-header').css('background-color', color);
    //$('.modal-header').removeClass().addClass('modal-header '+color);
    //$('.modal-header h3').html('<i class="fa fa-folder-open-o"></i> &nbsp; '+text);
}

function update_timeline (step) {

    for (i=1; i<=3; i++) {
        if (step>=(i*2)) {
            $('#timeline .bullet:eq('+(i-1)+')').removeClass('current').addClass('active');
        } else if (step == (i*2-1)) {
            $('#timeline .bullet:eq('+(i-1)+')').removeClass('active').addClass('current');
        } else {
            $('#timeline .bullet:eq('+(i-1)+')').removeClass('active').removeClass('current');
        }
    }
}



function stop_contact_callback (data) {

    flash_messenger(data.msg,'success');

    $('#remote-modal .client-phone').addClass('strike');
    $('#remote-modal .client-mail').addClass('strike');

    $('#_big_users_table tr[data-id="'+data.id_client+'"] .client-phone').addClass('strike');
    $('#_big_users_table tr[data-id="'+data.id_client+'"] .client-mobile').addClass('strike');
    $('#_big_users_table tr[data-id="'+data.id_client+'"] .client-mail').addClass('strike');

    $('#ajax_container_account').refresh_ajax_container();
}

function cancel_stop_contact_callback (data) {

    flash_messenger(data.msg,'success');

    $('#remote-modal .client-phone').removeClass('strike');
    $('#remote-modal .client-mail').removeClass('strike');

    $('#_big_users_table tr[data-id="'+data.id_client+'"] .client-phone').removeClass('strike');
    $('#_big_users_table tr[data-id="'+data.id_client+'"] .client-mobile').removeClass('strike');
    $('#_big_users_table tr[data-id="'+data.id_client+'"] .client-mail').removeClass('strike');

    $('#ajax_container_account').refresh_ajax_container();
}

function abonnement_saved (data) {
    var id_client = $('#remote-modal section[data-client-id]').data('client-id');
    update_status (id_client);
}

// RELANCE 

function update_alert (id_client, state, url) {

    $popup_alert = $('#popup-alert');
    $bagde = $('#_big_users_table tr[data-id="'+id_client+'"] .status_td .badge-cyan');
    $alert_counter = $('#folders .folder-pills.cyan .folder-count');

    if (state == 'done') {
        var count = $alert_counter.html();
        count--;
        $alert_counter.empty().append((count));
        $popup_alert.addClass('done').data('ajax-json', url).data('ajax-callback', 'cancel_alert_done_callback');
        $bagde.data('ajax-json', url).addClass('done').data('ajax-callback', 'cancel_alert_done_callback');

    } else {
        var count = $alert_counter.html();
        count++;
        $alert_counter.empty().append((count));
        $popup_alert.removeClass('done').data('ajax-json', url).data('ajax-callback', 'alert_done_callback');
        $bagde.data('ajax-json', url).removeClass('done').data('ajax-callback', 'alert_done_callback');
    }
}

function cancel_alert_callback (data) {

    flash_messenger(data.msg,'success');
    $('#ajax_container_alert').refresh_ajax_container();
    update_alert (data.id_client, "cancel", data.url);
    update_status(data.id_client);
}

// HISTORIQUE
function refresh_historique(){
    $('#ajax_container_historique').refresh_ajax_container();
}

// FADE ITEM WHEN STATUS CHANGE
function fade_item_from_list (id_client, type) {
    //var id_client = $('#remote-modal section[data-client-id]').data('client-id'); 
    
    if (type == 'show') {
        $('tr[data-id="'+id_client+'"]').find('td').fadeIn('slow', 
            function(){ 
                $(this).css('display','static');                   
        });
    } else {
        $('tr[data-id="'+id_client+'"]').find('td').fadeOut('slow', 
            function(){ 
                $(this).css('display','none');                   
        });
    }
}


function seance_saved () {
    var id_client = $('#remote-modal section[data-client-id]').data('client-id');
    update_status(id_client);
}



















