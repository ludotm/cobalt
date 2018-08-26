
        // SCRIPTS DOM READY
        $(document).ready(function(){
            
            $('body').tooltip({
                selector: '[data-toggle="tooltip"]'
            });
        });
/*
        $(document).on('show.bs.modal', '.modal', function () {
            var zIndex = 1040 + (10 * $('.modal:visible').length);
            $(this).css('z-index', zIndex);
            setTimeout(function() {
                $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
            }, 0);
        });
*/      


        /* --------------------------- FLASH MESSENGER-------------------------- */

        function flash_messenger (msg, type) {
            var icon ='';

            type = !type ? 'warning' : type;

            switch(type) {
                case 'error': icon = 'times-circle'; type = 'danger'; break;
                case 'warning': icon = 'warning'; break;
                case 'success': icon = 'check'; break;
            }
            $('#flash-messenger').html('<div class="alert alert-'+type+'" style="display:none;"><p><i class="fa fa-close pull-right margin5"></i><i class="fa fa-'+icon+' fa-lg"></i> '+msg+'</p></div>');

            $('#flash-messenger .alert').slideDown(600, function (){
                
                var closed = false;
                var that = this;
                
                $(this).click(function(){
                    closed = true;
                    $(this).fadeOut(600, function(){$(this).remove();});
                });
                
                setTimeout(function(){
                    if (!closed) {
                        $(that).fadeOut(600, function(){$(that).remove();});
                    }
                },4000);
            });
        }

        function ajax_success (data) {
            flash_messenger(data.msg, data.type_msg || 'success');
        }

        function ajax_error (data) {
            flash_messenger(data.responseText, 'error');
        }

        /* --------------------------- TOGGLE SHOW HIDE -------------------------- */

        $(document).on('click', '[data-toggle-show]', function(event){

            $elem = $($(this).data('toggle-show'));

            if ($elem.css('display') == 'none') {
                $elem.css('display','block');
            } else {
                $elem.css('display','none');
            }
        });

        /* --------------------------- MODAL CONFIRM -------------------------- */

        $(document).on('click', '[data-confirm]', function(event){

            event.stopImmediatePropagation();

                if (!$('#modal-confirm').length) {
                    $('body').append('<div id="modal-confirm" class="modal" role="dialog" aria-labelledby="dataConfirmLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><h3 id="dataConfirmLabel">Merci de confirmer</h3></div><div class="modal-body"></div><div class="modal-footer"><button class="btn" id="dataCancel" aria-hidden="true">Non</button><a class="btn btn-confirm" id="dataConfirmOK">Oui</a></div></div></div></div>');
                }
                $('#modal-confirm').find('.modal-body').text($(this).attr('data-confirm'));

                $('#modal-confirm').modal({show:true});

                var $that = $(this);

                $('#modal-confirm').on('hidden.bs.modal', function () {
                    $(this).find('#dataCancel').unbind('click');
                    $(this).find('#dataConfirmOK').unbind('click');
                });

                $('#dataCancel').click(function(){
                    $('#modal-confirm').modal('hide');
                });

                $('#dataConfirmOK').click(function(){
                    $('#modal-confirm').modal('hide');
                    var confirm_message = $that.data('confirm');

                    $that.removeAttr('data-confirm');
                    if ($that.attr('href') != '#' && $that.attr('href') != '') { // SI NON AJAX
                        window.location.href = $that.attr('href');
                    } else { // SI AJAX
                        $that.trigger('click');
                        $that.attr('data-confirm', confirm_message);
                    }
                });
            return false;
        });
        
        /* --------------------------- MODAL MESSAGE -------------------------- */

        $(document).on('click', '[data-modal]', function(event){
            event.preventDefault();

            var content = $(this).data('modal');
            var title = !$(this).data('modal-title') ? 'Information' : $(this).data('modal-title') ;
            var width = !$(this).data('modal-width') ? '600px' : $(this).data('modal-width') ;

            if ($('#remote-modal').length) {
                $('#remote-modal').remove();
            }
            if ($('#modal').length) {
                $('#modal').remove();
            }
            $('body').append('<div id="modal" class="modal" role="dialog" aria-labelledby="dataConfirmLabel" aria-hidden="true"><div class="modal-dialog" style="width:'+width+';"><div class="modal-content" ><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><h3 id="dataConfirmLabel">'+title+'</h3></div><div class="modal-body"></div></div></div></div>');
            
            $('#modal .modal-body').html(content);
            $('#modal').modal({show:true});
        });

        /* --------------------------- SMOOTH SCROLL WITH ANCHORS-------------------------- */

        $(function() {
          $('a[href*=#]:not([href=#])').click(function() {
            if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
              var target = $(this.hash);
              target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
              if (target.length) {
                $('html,body').animate({
                  scrollTop: target.offset().top
                }, 600);
                return false;
              }
            }
          });
        });

        function scroll_to_top() {
            $('html, body').animate({ scrollTop: 0 }, 'slow');
        }

         /* --------------------------- IN PLACE EDIT (à travailler) -------------------------- */

        $(document).on('dblclick.data-api', '[data-toggle="inplace-edit"]', function(event){
            event.stopPropagation();
            event.preventDefault();

            var $this = $(this);
            $text = $this.find('span');
            $input = $this.find('input');
            $select = $this.find('select');

            if ($input.length == 1) {

                $input.val($text.html()).show();
                $text.hide();

                $input.one('change', function(){
                    $text.html($input.val()).show();
                    $input.hide();
                });

            } else if ($select.length == 1) {

                //$select.val($text.html()).show();
                $select.show();
                $text.hide();

                $select.one('select', function(){
                    $option = $this.find('select option:selected');
                    $text.html($option.text()).show();
                    $select.hide();
                });
            }
        });

        /* --------------------------- RESET COMPLET D'UN FORMULAIRE-------------------------- */

        $.fn.clear_form = function() {
          return this.each(function() {
            var type = this.type, tag = this.tagName.toLowerCase();
            if (tag == 'form')
              return $(':input',this).clear_form();
            if (type == 'text' || type == 'password' || type == 'date' || type == 'datetime' || type == 'email' || type == 'url' || type == 'search' || type == 'number' || tag == 'textarea')
              this.value = '';
            else if (type == 'checkbox' || type == 'radio')
              this.checked = false;
            else if (tag == 'select')
              this.selectedIndex = 0;
          });
        };
        
         /* --------------------------- SLIDE & FADE-------------------------- */

        $.fn.slideFadeToggle  = function(speed, callback, easing) {
            return this.animate({opacity: 'toggle', height: 'toggle'}, speed, easing, callback);
        };

        /* ---------------------------  NL2BR, BR2NL -------------------------- */

        jQuery.nl2br = function(text){
            return text.replace(/(\r\n|\n\r|\r|\n)/g, "<br>");
        };
        jQuery.br2nl = function(text){
            return text.replace(/<br>/g, "\r");
        };
        /* $.nl2br("textmultiline"); */

         /* -------- CUMULE EVENEMENT SIMPLE CLICK ET DOUBLE CLICK (SINON ) -------- */

        $.fn.one_two_click = function(single_click_callback, double_click_callback, timeout) {
          return this.each(function(){
            var clicks = 0, self = this;
            //$(this).on('click touchstart',function(event){
            $(this).on('mouseup touchend',function(event){
              clicks++;
              if (clicks == 1) {
                setTimeout(function(){
                  if(clicks == 1) {
                    single_click_callback.call(self, event);
                  } else {
                    double_click_callback.call(self, event);
                  }
                  clicks = 0;
                }, timeout || 250);
              }
            });
          });
        };

    /* -------------- NOMBRE D'OCCURENCE DANS UNE CHAINE ------------------*/
    
    function count_char_string (substring, string) {
        var n=0;
        var pos=0;

        while(true){
            pos=string.indexOf(substring,pos);
            if(pos!=-1){ n++; pos+=substring.length;}
            else{break;}
        }
        return(n);
    }

    /* --------------------------- MINI MANAGER -------------------------- */

    $.fn.MiniManager = function() {
        
        var manager = this;

                var add_item = function (event) {
                    event.preventDefault();
                    var url = $(manager).data('url') + '/add/0';
                    var data = $(this).serialize();
                    var that = this;

                    $.ajax({
                              method: "POST",
                              url: url,
                              data: data,
                            })
                            .done(function( response ) {

                                // masque la ligne "Aucun élément"
                                $(manager).find('table.list_items tr:first').css('display','none');
                                $(that)[0].reset();

                                $line = $(manager).find('table.list_items tr:last');
                                $line.after('<tr data-id="'+response.id+'">'+$line.html()+'</tr>');

                                $new_line = $(manager).find('table.list_items tr:last').hide().show('slow');

                                $.each( response, function( key, value ) {

                                    var $field_container = $new_line.find('[data-field="'+key+'"]');
                                    if ($field_container) {
                                        
                                        if ($field_container.data('type') == 'hidden') {
                                            $new_line.find('[data-field="'+key+'"]').html('<input type="hidden" name="'+key+'" value="'+value+'">');

                                        } else if ($field_container.data('type')=='select') {
                                            $new_line.find('[data-field="'+key+'"]').html($(that).find('select[name="'+key+'"] option[value="'+value+'"]').html());
                                        
                                        } else {
                                            $new_line.find('[data-field="'+key+'"]').html(value);
                                        }
                                    }
                                });

                                $new_line.find('.edit-action').click(edit_item);
                                $new_line.find('.delete-action').click(delete_item);
                            });
                };

                var delete_item = function (event) {
                    
                    if (!$(this).attr('data-confirm')) {

                        event.preventDefault();

                        var $line = $(this).closest('[data-id]');
                        console.log($line);
                        var url = $(manager).data('url') + '/delete/' + $line.data('id');

                        $.ajax({
                                  method: "GET",
                                  url: url,
                                })
                                .done(function( response ) {

                                    $line.find('.edit-action').unbind('click');
                                    $line.find('.delete-action').unbind('click');
                                    $line.find('.select-action').unbind('click');
                                    $line.hide('slow', function(){

                                        // affiche la ligne "Aucun élément" si il ne reste aucun élément
                                        var $table = $(manager).find('table.list_items');
                                        if ($table.find('tr').length <=3) {
                                            $table.find('tr:first').css('display','block');
                                        }
                                        $(this).remove();
                                    });
                                });
                    }
                };

                var edit_item = function (event) {
                    event.preventDefault();

                        var $line = $(this).closest('[data-id]');

                        $line.find('td[data-field]').each(function(){

                            var field = $(this).data('field');
                            var type = $(this).data('type');
                            var value = $.trim($(this).html());

                            switch (type) {
                                case 'text': 
                                    $(this).html('<input type="text" name="'+field+'" value="'+value+'" />');
                                    break;
                                case 'select':
                                    var $select = $(this).html('<select name="'+field+'">'+$(manager).find('form.add-action select[name="'+field+'"]').html()+'</select>');
                                    $(this).find('option').each(function(){
                                        if($(this).html() == value) {
                                            $(this).prop('selected', true);
                                        }
                                    });
                                    break;
                            }
                        });

                        // cache tous les boutons edit
                        $line.find('.edit-action').hide();

                        $line.find('td[data-type="submit"]').html('<button>Modifier</button>');

                        $line.find('button').click(function(){
                            
                            var url = $(manager).data('url') + '/edit/' + $line.data('id');
                            var data = $line.find('input,select,textarea').serialize();

                            $.ajax({
                              method: "POST",
                              url: url,
                              data: data,
                            })
                            .done(function( response ) {

                                $line.find('td[data-field]').each(function(){

                                    var field = $(this).data('field');
                                    var type = $(this).data('type');

                                    switch (type) {
                                        case 'text': 
                                            var value = $(this).find('input').val();
                                            $(this).html(value);
                                            break;
                                        case 'select':
                                            var value_label = $(this).find('select option:selected').html();
                                            $(this).html(value_label);
                                            break;
                                    }
                                });
                                $line.find('button').unbind('click').remove();
                                $line.find('.edit-action').show();
                            });

                        });
                }

                var select_item = function (event) {
                
                    event.preventDefault();

                    var $line = $(this).closest('[data-id]');
                    
                    var data = {};
                    data.id = $line.data('id');

                    $line.find('td[data-field]').each(function(){

                            var field = $(this).data('field');
                            var type = $(this).data('type');
                            var value = $.trim($(this).html());

                            switch (type) {
                                
                                case 'hidden':
                                    data[field] = $(this).find('input').val();
                                    break;

                                case 'text': 
                                    data[field] = value;
                                    break;

                                case 'select':
                                    var $select = $(manager).find('form.add-action select[name="'+field+'"]');
                                    $select.find('option').each(function(){
                                        if($.trim($(this).html()) == value) {
                                            data[field] = $(this).val();
                                        }
                                    });
                                    break;
                            }
                    });
                    $(manager).trigger('selectItem', data);
                };

        $(this).find('.add-action').submit(add_item);
        $(this).find('.delete-action').click(delete_item);
        $(this).find('.edit-action').click(edit_item);
        $(this).find('.select-action').click(select_item);

        /*
            SYNTAXE POUR RECUPERER UN ITEM SELECTIONNE DEPUIS L EXTERIEUR
            
            $('#sections_manager').on('selectItem', function(event, data) {
                console.log(data);
            });

        */
    };

    /* --------------------------- CALLBACK ON EVENT -------------------------- */

        $(document).on('click', '[data-click-callback]', function(event){
            event.preventDefault();
            var callback = $(this).data('click-callback');
            window[callback.replace(/[^a-zA-Z 0-9 _]+/g, '')](event);
        });

        $(document).on('dblclick', '[data-dblclick-callback]', function(event){
            event.preventDefault();
            var callback = $(this).data('dblclick-callback');
            window[callback.replace(/[^a-zA-Z 0-9 _]+/g, '')](event);
        });

    /* --------------------------- AJAX-------------------------- */

        /* --------------------------- AJAX EVENTS -------------------------- */
        
        $( document ).ajaxStart(function() {
            $('#loading').fadeTo( 300 , 1); 
            $('.alert').remove();
        });
        $( document ).ajaxComplete(function() {
            $('#loading').fadeTo( 300 , 0, function(){
                $(this).css('display','none');
            });
        });

        /* --------------------------- AJAX LINKS -------------------------- */

        $(document).on('click', '[data-ajax]', function(event){
            event.preventDefault();

            if ($(this).hasClass('inactive')) {
                return false;
            }
            
            var url = $(this).data('ajax');

            if (url == 'return') {
                url = $(this).closest('[data-ajax-container]').data('ajax-container');
            }

            var scroll = !$(this).data('scroll') ? 'none' : $(this).data('scroll');
            var target = !$(this).data('ajax-target') ? $(this).closest('[data-ajax-container]') : $(this).data('ajax-target');
            var transition = $(this).data('transition') ? $(this).data('transition') : 'none';

            var data = !$(this).data('data') ? {} : window[ $(this).data('data').replace(/[^a-zA-Z 0-9 _]+/g, '')]();

            var before_send = !$(this).data('before-send') ? function(){} : window[ $(this).data('before-send').replace(/[^a-zA-Z 0-9 _]+/g, '')]();
            var callback = $(this).data('ajax-callback');
            var error_callback = $(this).data('error-callback');

            $.ajax({
               url : url,
               type : 'GET',
               dataType : 'html',
               data: data,
               beforeSend: before_send,
               success : function(html, state, data) {
                    if (callback) {

                        window[callback.replace(/[^a-zA-Z 0-9 _]+/g, '')](data);
                    }
                    ajax_load_transition (target, html, transition);
                    if (scroll == 'top') {
                        scroll_to_top();
                    } 
               },
               error : function(data, state){
                    if (error_callback) {
                        window[error_callback.replace(/[^a-zA-Z 0-9 _]+/g, '')](data);
                    } else {
                        ajax_load_transition (target, data.responseText, 'none');    
                    }
               },
            });
        });

         /* --------------------------- AJAX LINKS JSON -------------------------- */

        $(document).on('click', '[data-ajax-json]', function(event){
            event.preventDefault();

            if ($(this).hasClass('inactive')) {
                return false;
            }

            var url = $(this).data('ajax-json');
            
            var before_send = !$(this).data('before-send') ? function(){} : window[ $(this).data('before-send').replace(/[^a-zA-Z 0-9 _]+/g, '')]();
            var error_callback = $(this).data('error-callback');
            var callback = $(this).data('ajax-callback');

            $.ajax({
               url : url,
               type : 'GET',
               dataType : 'json',
               beforeSend: before_send,
               success : function (data) {

                    if (callback) {
                        window[callback.replace(/[^a-zA-Z 0-9 _]+/g, '')](data);
                    }
                    if (data.msg) {
                        flash_messenger(data.msg, data.type_msg || 'success');
                    }
                },
               error : function(data, state){
                    if (error_callback) {
                        window[error_callback.replace(/[^a-zA-Z 0-9 _]+/g, '')](data);
                    } else {
                        flash_messenger('Une erreur s\'est produite','error');    
                    }
               },
            });
        });

        /* --------------------------- REFERSH AJAX CONTAINER -------------------------- */

        $.fn.refresh_ajax_container = function() {

            var url = $(this).data('ajax-container');

            if (!url) {
                return;
            }

            var target = $(this);
            var transition = $(this).data('transition') ? $(this).data('transition') : 'none';

            $.ajax({
               url : url,
               type : 'GET',
               dataType : 'html',

               success : function(html, state, data) {
                    ajax_load_transition (target, html, transition);
               },
               error : function(data, state){
                    ajax_load_transition (target, data.responseText, 'none');
               },
            });
        };

       /* --------------------------- AJAX FORMS -------------------------- */

        $(document).on('submit', '[data-ajax-form]', function(event){
            event.preventDefault();

            var $form  = $(this);
            var target = !$(this).data('ajax-target') ? $(this).closest('[data-ajax-container]') : $(this).data('ajax-target');
            var scroll = !$(this).data('scroll') ? 'none' : $(this).data('scroll');
            var transition = $(this).data('ajax-form');
            var callback = $(this).data('ajax-callback');
            var error_callback = $(this).data('error-callback');
            var before_send = !$(this).data('before-send') ? function(){} : window[ $(this).data('before-send').replace(/[^a-zA-Z 0-9 _]+/g, '')]();
            
            if ($form.attr('method') == 'POST') {
                var formData = new FormData($(this)[0]);
            } else if ($form.attr('method') == 'GET') {
                var formData = $form.serialize();
            }
            
            $.ajax({
                url:  $form.attr('action'),
                type: $form.attr('method'),
                dataType: 'html',
                data: formData,
                beforeSend: before_send,
                success: function(html){

                    if (callback) {
                        window[callback.replace(/[^a-zA-Z 0-9 _]+/g, '')]();
                    }
                    ajax_load_transition (target, html, transition);
                    if (scroll == 'top') {
                        scroll_to_top();
                    } 
                },
                error: function(data, state){
                    if (callback) {
                        window[callback.replace(/[^a-zA-Z 0-9 _]+/g, '')]();
                    } else {
                        ajax_load_transition (target, data.responseText, 'none');    
                    }
                },

                cache: false,
                contentType: false, // type de donnée envoyée
                processData: false,
                async: true, // rends la requete asynchrone (si false, bloque l'utilisation de javascript le temps de l'envoi)
            });

            return false;
        });
        

        /* --------------------------- IFRAME MODAL REMOTE -------------------------- */

        $(document).on('click', '[data-modal-iframe]', function(event){
            event.preventDefault();

            var url = $(this).data('modal-iframe');
            var title = !$(this).data('modal-title') ? '' : $(this).data('modal-title') ;
            var width = !$(this).data('modal-width') ? '600px' : $(this).data('modal-width') ;
            var height = !$(this).data('modal-height') ? '400px' : $(this).data('modal-height') ;

            if ($('#remote-modal').length) {
                $('#remote-modal').remove();
            }
            $('body').append('<div id="remote-modal" class="modal" role="dialog" aria-labelledby="dataConfirmLabel" aria-hidden="true"><div class="modal-dialog" style="width:'+width+';"><div class="modal-content" ><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><h3 id="dataConfirmLabel">'+title+'</h3></div><div class="modal-body" style="height:'+height+'; padding:0;"><iframe src="'+url+'" style="height:'+height+';"></iframe></div></div></div></div>');
            $('#remote-modal').modal({show:true});
            $('#remote-modal').on('hidden',function(e){
                $(this).remove();
            });
        });

        /* --------------------------- AJAX MODAL REMOTE -------------------------- */

        $(document).on('click', '[data-ajax-modal]', function(event){
            event.preventDefault();

            var url = $(this).data('ajax-modal');
            var title = !$(this).data('modal-title') ? '' : $(this).data('modal-title') ;
            var width = !$(this).data('modal-width') ? '600px' : $(this).data('modal-width') ;

            if ($('#remote-modal').length) {
                $('#remote-modal').remove();
            }
            $('body').append('<div id="remote-modal" class="modal" role="dialog" aria-labelledby="dataConfirmLabel" aria-hidden="true"><div class="modal-dialog" style="width:93%; max-width:'+width+';"><div class="modal-content" ><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><h3 id="dataConfirmLabel">'+title+'</h3></div><div class="modal-body" data-ajax-container></div></div></div></div>');
            

            $.ajax({
               url : url,
               type : 'GET',
               dataType : 'html',

               success : function(html, state, data) {
                    $('#remote-modal').modal({show:true});
                    $('#remote-modal [data-ajax-container]:first').html(html);
               },
               error : function(data, state){
                    $('#remote-modal').modal({show:true});
                    $('#remote-modal [data-ajax-container]:first').html(data.responseText);
               },
            });
        });
            
       /* --------------------------- AJAX TRANSITIONS -------------------------- */

        var ajax_load_transition = function (target, html, transition) {

            switch (transition) {
                
                        case 'slide-left':
                        case 'slide-right': 

                            var transition_duration = 700;

                            var container_width = $(target).width()+'px';
                            var container_height = $(target).height()+'px';
                            
                            var direction = transition == 'slide-left' ? 'left' : 'right';
                            $(target).html('<div class="transition-container" id="old-ajax-slide"><div class="transition-container2">'+$(target).html()+'</div></div><div class="transition-container" id="new-ajax-slide"><div class="transition-container2"></div></div>');

                            $(target).css({
                                'height': container_height,
                            });
                            $(target).find('.transition-container').css({
                                'width' : container_width,
                                'height': container_height,
                                'position': 'absolute',
                                'top': 0,
                                'left':0,
                            }); 
                            $(target).find('.transition-container2').css({
                                'position': 'relative',
                            }); 
                            $('#new-ajax-slide').css({
                                'opacity':'0', 
                                'left': (direction == 'left' ? container_width : '-'+container_width),
                                'height': 'auto',
                            });

                            $("#new-ajax-slide .transition-container2").html(html);

                            var new_height = $("#new-ajax-slide").height()+'px';
                           
                            $(target).animate({ 
                                height: new_height,
                            }, transition_duration, function() {
                                $(target).css('height','auto');
                            });

                            $( "#old-ajax-slide" ).animate({
                                opacity: 0,
                                left: (direction == 'left' ? '-' : '+')+"="+container_width,
                              }, transition_duration-100, function() {
                                    $(this).remove();
                            });

                            $( "#new-ajax-slide" ).animate({
                                opacity: 1,
                                left: 0,
                              }, transition_duration, function() {
                                $('#old-ajax-slide').remove();
                                $('#new-ajax-slide').contents().unwrap();
                                $('#new-ajax-slide .transition-container2').contents().unwrap();
                                
                            });
                            break;

                        case 'quick-slide': 

                            var container_width = $(target).width()+'px';
                            var container_height = $(target).height()+'px';
                            var transition_duration = 500;
                            $(target).html('<div class="transition-container" id="old-ajax-slide"><div class="transition-container2">'+$(target).html()+'</div></div><div class="transition-container" id="new-ajax-slide"><div class="transition-container2"></div></div>');

                            $(target).css({
                                'height': container_height,
                                'position': 'relative',
                            });
                            $(target).find('.transition-container2').css({
                                'position': 'relative',
                            }); 
                            $(target).find('.transition-container').css({
                                'width' : container_width,
                                'height': container_height,
                                'position': 'absolute',
                                'top': 0,
                                'left':0,
                            }); 
                            $('#new-ajax-slide').css({
                                'opacity':'0', 
                                'left': '100px',
                                'height': 'auto',
                            });

                            $("#new-ajax-slide .transition-container2").html(html);
                           var new_height = $("#new-ajax-slide").height()+'px';
                           
                           $(target).animate({ 
                                height: new_height,
                            }, transition_duration*1.6, function() {
                                $(target).css('height','auto');
                            });

                           $( "#old-ajax-slide" ).animate({
                                opacity: 0,
                              }, transition_duration/2, function() {
                                    $(this).remove();
                            });

                           $( "#new-ajax-slide" ).delay(transition_duration/2).animate({
                                opacity: 1,
                                left: 0,
                              }, transition_duration, function() {
                                $('#old-ajax-slide').remove();
                                $('#new-ajax-slide').contents().unwrap();
                                $('#new-ajax-slide .transition-container2').contents().unwrap();
                                
                            });
                            break;

                        case 'opacity':
                        case 'fade': 
                            $(target).fadeTo( 300 , 0, function() {
                                $(target).html(html);
                                $(target).fadeTo( 300 , 1, function() {
                                    // Animation complete.
                                });
                            });
                            break;

                        case 'none': 
                        default:
                            $(target).html(html);
                            break;
                    }
        }

        /* --------------------------- EMAIL & SMS MODAL -------------------------- */

        $(document).on('click', '[data-email-modal]', function(event){
            event.preventDefault();

            var url = '/form_email';
            var email = $(this).data('email-modal');
            var title = 'Envoyer un email' ;
            var width = '600px';

            if (!$('#remote-modal').length) {
                $('body').append('<div id="remote-modal" class="modal" role="dialog" aria-labelledby="dataConfirmLabel" aria-hidden="true"><div class="modal-dialog" style="width:93%; max-width:'+width+';"><div class="modal-content" ><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><h3 id="dataConfirmLabel">'+title+'</h3></div><div class="modal-body" data-ajax-container></div></div></div></div>');
            }

            $.ajax({
               url : url+'?email='+email,
               type : 'GET',
               dataType : 'html',

               success : function(html, state, data) {
                    $('#remote-modal').modal({show:true});
                    $('#remote-modal [data-ajax-container]:first').html(html);
               },
               error : function(data, state){
                    $('#remote-modal').modal({show:true});
                    $('#remote-modal [data-ajax-container]:first').html(data.responseText);
               },
            });
        });
        
        $(document).on('click', '[data-sms-modal]', function(event){
            event.preventDefault();

            var url = '/form_sms';
            var num = $(this).data('sms-modal');
            var title = 'Envoyer un SMS' ;
            var width = '600px';

            if (!$('#remote-modal').length) {
                $('body').append('<div id="remote-modal" class="modal" role="dialog" aria-labelledby="dataConfirmLabel" aria-hidden="true"><div class="modal-dialog" style="width:93%; max-width:'+width+';"><div class="modal-content" ><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><h3 id="dataConfirmLabel">'+title+'</h3></div><div class="modal-body" data-ajax-container></div></div></div></div>');
            }

            $.ajax({
               url : url+'?num='+num,
               type : 'GET',
               dataType : 'html',

               success : function(html, state, data) {
                    $('#remote-modal').modal({show:true});
                    $('#remote-modal [data-ajax-container]:first').html(html);
               },
               error : function(data, state){
                    $('#remote-modal').modal({show:true});
                    $('#remote-modal [data-ajax-container]:first').html(data.responseText);
               },
            });
        });

         /* --------------------------- FORM FIELD SMS -------------------------- */

         function sms_field(name) {

            $textarea = $('textarea[name="'+name+'"]');
            $counter = $('#counter_'+name);

            var count_chars = function() {
                
                var val = $textarea.val();
                var count = val.length;

                count += count_char_string('|', val);
                count += count_char_string('^', val);
                count += count_char_string('€', val);
                count += count_char_string('}', val);
                count += count_char_string('{', val);
                count += count_char_string('[', val);
                count += count_char_string(']', val);
                count += count_char_string('~', val);
                count += count_char_string('\\', val);

                if (count <= 160) {
                    var count_sms = 1;
                } else {
                    count_sms = Math.ceil(count/157);
                }

                $counter.html(count+' caractère'+(count>1?'s':'')+' - équivalent à '+count_sms+' SMS' +(count>=785 ? '<br><span style="color:red;">Nombre de caractère maximum atteint</span>':'') );

                return count;
            };

            count_chars();

            $textarea.keyup(function(e) {
                
                var max = 785;
                var count = count_chars();
                
                if (e.which < 0x20) {
                    // e.which < 0x20, then it's not a printable character
                    // e.which === 0 - Not a character
                    return;     // Do nothing
                }
                if (count == max) {
                    e.preventDefault();

                } else if (count > max) {
                    // Maximum exceeded
                    this.value = this.value.substring(0, (max) );
                    count_chars();
                }

            });
         }


        /* --------------------------- CREER UN SET DE COULEUR A PARTIR D'UNE SEULE POUR HIGHCHARTS  -------------------------- */

        function highcharts_color_set (base) {
            var colors = [];
            colors.push(Highcharts.Color(base).get());
            for (var i = 0; i < 9; i ++) {
                colors.push(Highcharts.Color(base).brighten((i - 3) / 14).get());
            }
            return colors;
        }