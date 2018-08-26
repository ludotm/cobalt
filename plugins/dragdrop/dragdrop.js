/* --------------------------- DRAG & DROP & SORT-------------------------- */

        /*
        attributs HTML :
        - draggable="true" pour rendre un objet draggable
        - sortable="true" ET draggable="true" pour rendre une liste d'objet triables par drag & drop
        - droppable="xxxx" pour rendre un objet droppable, valeurs possibles :
            - "drop_in_single" : drop à l'intérieur, un seul objet possible à la fois 
            - "drop_in" : drop à l'intérieur, plusieurs objets possibles à la fois 
            - "drop_replace" : remplace la ciblé droppée par le nouvel objet
            - "drop_remove" : détruit l'objet droppé 
            - "drop_callback" : Aucun déplacement, juste un callback
        attributs supplémentaires :
        - data-drag-duplicate="true" : sur un objet draggable, duplique cet objet quand draggé au lieu de simplement le déplacer
        - data-drag-parent="#container" : sur un objet draggable, drag un objet parent cible entier au lieu de prendre seulement l'objet cliqué
        - data-drop-callback="nom_de_funtion" : pour n'importe quel objet, function callback à appeler une fois un objet draggé ou droppé, 
        - data-drop-allowed=".cube #container" : sur un objet droppable, liste les classes ou ID, séparé d'un espace, qui sont autorisé en drop
        */

        // DRAG
        $(document).on({

            dragstart: function(ev) {
                if ($(ev.target).data('drag-parent')) {
                    $container = $(ev.target).closest($(ev.target).data('drag-parent'));
                    if ($(ev.target).data('drag-duplicate')) {
                        $container.data('drag-duplicate','true');
                    }
                    ev.originalEvent.dataTransfer.setDragImage($container[0], 0, 0);
                } else {
                    $container = $(ev.target);
                }

                if (!$container.data('drag-duplicate')) {
                    $container.css('opacity','0.5');
                }

                $container.addClass('current-drag');
                ev.originalEvent.dataTransfer.setData("draggable_element", $container.prop('outerHTML'));
            },
            dragend: function(ev) {
                $('.current-drag').css('opacity','1').removeClass('current-drag');
                $('.drop-highlight').removeClass('drop-highlight');
            },
        }, '[draggable="true"]');

        // DROP
        $(document).on({

            dragover: function(ev) {
                ev.preventDefault();
                $(this).addClass('drop-highlight');

                if ($(this).attr('droppable') == "drop_in_single") {
                    $(this).children().css('display','none');
                }
            },
            dragleave: function(ev) {
                ev.preventDefault();
                $(this).removeClass('drop-highlight');

                if ($(this).attr('droppable') == "drop_in_single") {
                    $(this).children().css('display','block');
                }
            },
            drop: function(ev) {
                ev.preventDefault();
                var element = ev.originalEvent.dataTransfer.getData("draggable_element");
 
                if ($(this).data('drop-allowed')) {

                    var classes = $(this).data('drop-allowed').split(" ");
                    var allowed = false;
                    $.each(classes, function( index, value ) {
                      if ($(element).is(value)) {
                        allowed = true;
                      }
                    });
                    if (!allowed) {
                        $('.current-drag').css('opacity',1).removeClass('current-drag');
                        return false;
                    }
                }

                var drop_type = $(this).attr('droppable');
                $(this).removeClass('drop-highlight');  

                switch (drop_type) {
                    case "drop_replace": $(this).before($(element).removeClass('current-drag').css('opacity',1)); break;
                    case "drop_remove": break; // rien
                    case "drop_callback": $('.current-drag').css('opacity',1); break; 
                    case "drop_in_single": $(this).empty().append($(element).removeClass('current-drag').css('opacity',1)); break;
                    case "drop_in": default: $(this).append($(element).removeClass('current-drag').css('opacity',1)); break; //
                }

                var callback = $(this).data('drop-callback');

                if (callback) {
                    window[callback.replace(/[^a-zA-Z 0-9 _]+/g, '')](ev);
                }

                if (drop_type == 'drop_callback') {
                    $('.current-drag').removeClass('current-drag');
                } else {
                    if (!$('.current-drag').data('drag-duplicate')) {
                        $('.current-drag').remove();
                    }
                    if (drop_type == "drop_replace") {
                        $(this).remove();
                    }
                }
                
            },
        }, '[droppable]');
        
        // SORT
        $(document).on({
            drop: function(ev) {
                return false;
            },
            dragover: function(ev) { // Ecarte 2 éléments pour laisser la place au drop
                ev.preventDefault(); 

                if($(ev.target).parent().is('[droppable]')) {
                    return false;
                }

                if (!$(ev.target).hasClass('droppable-cell') && $('.current-drag').index() != $(ev.target).index() && $('.current-drag').index()+1 != $(ev.target).index()) {

                    $droppable_cell = $('.current-drag').clone().removeClass('current-drag').removeAttr('sortable').addClass('droppable-cell').attr('droppable','drop_replace').css('color','transparent').css('background-color','transparent').css('border','2px dashed #AAA');
                    $(ev.target).before($droppable_cell);
                }
            },

        }, '[sortable]');

        $(document).on({
            dragleave: function(ev) {
                ev.preventDefault();
                $(ev.target).parent().find('.droppable-cell').remove();
            },
        }, '.droppable-cell');