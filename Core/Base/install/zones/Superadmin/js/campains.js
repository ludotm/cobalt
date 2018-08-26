
function update_status () {

    var id_campain = $('#remote-modal section[data-campain-id]').data('campain-id');
    var campain_type = $('#remote-modal section[data-campain-id]').data('campain-type');

    var url = $('#update_status').data('update-url') + '/'+id_campain+'/'+campain_type;

    $.ajax({
        url : url,
        type : 'GET',
        dataType : 'json',
        success : function(data) {
            update_timeline (data);
            update_popup_status (data);
            update_list (data);
        }
    });
}

function update_timeline (data) {

    if (data.status.template != 0) {
        $('#timeline .bullet1').removeClass('current').addClass('active');
    } else {
        $('#timeline .bullet1').removeClass('active').removeClass('current');
    }

    if (data.status.target != '') {
        $('#timeline .bullet2').removeClass('current').addClass('active');
    } else {
        $('#timeline .bullet2').removeClass('active').removeClass('current');
    }

    if (data.status.content != '') {
        $('#timeline .bullet3').removeClass('current').addClass('active');
    } else {
        $('#timeline .bullet3').removeClass('active').removeClass('current');
    }

    if (data.status.sent == 0) {
        $('#timeline .bullet4').removeClass('active').removeClass('current');
    } else if (data.status.sent == 1) {
        $('#timeline .bullet4').removeClass('active').addClass('current');
    } else if (data.status.sent == 2 || data.status.sent == 3) {
        $('#timeline .bullet4').removeClass('current').addClass('active');
    }
}

function update_popup_status (data) {

    $('#popup_status').removeClass().addClass(data.status.color);
    $('#popup_status span').empty().append(data.status.label);
}

function update_list (data) {

    $item = $('tr[data-id="'+data.id+'"]');
    if (data.status.template) {
        $item.find('.badge-template').removeClass('badge-red').addClass('badge-green');
    } else {
        $item.find('.badge-template').removeClass('badge-green').addClass('badge-red');
    }

    if (data.status.target != '') {
        $item.find('.badge-target').removeClass('badge-red').addClass('badge-green');
    } else {
        $item.find('.badge-target').removeClass('badge-green').addClass('badge-red');
    }

    if (data.status.content != '') {
        $item.find('.badge-content').removeClass('badge-red').addClass('badge-green');
    } else {
        $item.find('.badge-content').removeClass('badge-green').addClass('badge-red');
    }

    if (data.status.sent == 0) {
        $item.find('.badge-action').removeClass('badge-green').removeClass('badge-orange').addClass('badge-red');
        $item.find('.badge-action').empty().append('Envoyer');
    } else if (data.status.sent == 1) {
        $item.find('.badge-action').removeClass('badge-red').removeClass('badge-green').addClass('badge-orange');
        $item.find('.badge-action').empty().append('Envoyer');
    } else if (data.status.sent == 2) {
        $item.find('.badge-action').removeClass('badge-red').removeClass('badge-orange').addClass('badge-green');
        $item.find('.badge-action').empty().append('Envoyé');
    } else if (data.status.sent == 3) {
        $item.find('.badge-action').removeClass('badge-red').removeClass('badge-green').addClass('badge-orange');
        $item.find('.badge-action').empty().append('Envoi en cours');
    }

    $item.find('.campain_status').removeClass('red').removeClass('orange').removeClass('green').addClass(data.status.color);
    $item.find('.campain_status span').empty().append(data.status.label);
    $item.find('.date_launch').empty().append(data.date_launch);
}

function send_campain_before() {
    if ($('#send_button').hasClass('inactive')) {
        $('#send_button').addClass('was_inactive');
    } else {
        $('#send_button').addClass('inactive');
    }
    if ($('#send_button2').hasClass('inactive')) {
        $('#send_button2').addClass('was_inactive');
    } else {
        $('#send_button2').addClass('inactive');
    }
    
    $('#processing').html("L'envoi est en cours, veuillez patienter...");
}
function send_campain_error_callback() {
    if ($('#send_button').hasClass('was_inactive')) {
        $('#send_button').removeClass('was_inactive');
    } else {
        $('#send_button').removeClass('inactive');
    }
    if ($('#send_button2').hasClass('was_inactive')) {
        $('#send_button2').removeClass('was_inactive');
    } else {
        $('#send_button2').removeClass('inactive');
    }
    $('#processing').html("Erreur inconnue lors de l'envoi");
}

function send_campain_callback(data) {

    $('#pocessing').html("Campagne envoyée avec succès");

    $('#ajax_container_template').refresh_ajax_container();
    $('#ajax_container_target_campain').refresh_ajax_container();
    $('#ajax_container_content').refresh_ajax_container();

    if (data.errors.length > 0) {
        $('#sending_errors').empty().append("<br><strong>Erreurs : </strong><br>");
        for (var i=0; i<data.errors.length; i++) {
            $('#sending_errors').append('<li>'+data.errors[i]+'</li>');
        }
        if (data.campain_sent) {
            $('[data-ajax-callback="send_campain_callback"]').addClass('inactive');
        }
    } else {
        //$('#ajax_container_send_campain').refresh_ajax_container();
        $('[data-ajax-callback="send_campain_callback"]').addClass('inactive');
        $('#send_button').addClass('inactive');
        $('#send_button2').addClass('inactive');
        $('#processing').html("");
    }

    update_status();
}

function unlock_campain_callback(data) {

    $('#ajax_container_template').refresh_ajax_container();
    $('#ajax_container_target_campain').refresh_ajax_container();
    $('#ajax_container_content').refresh_ajax_container();
    $('#ajax_container_send_campain').refresh_ajax_container();
    update_status();
}

function refresh_render_tab() {
    $('#ajax_container_render_mail').refresh_ajax_container();
}
function refresh_send_tab() {
    $('#ajax_container_send_campain').refresh_ajax_container();
}