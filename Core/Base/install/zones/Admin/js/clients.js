
$(document).on('submit', '#filter-form', function(event){
    event.preventDefault();
    $('#filter-form .search-submit').trigger('click');
});

function get_search_filters() {

    var data = {};
    data.search = $('#search-filters .search-text').val();
    data.order = $('#search-filters .search-order').val();
    data.age = $('#search-filters .search-age').val();
    data.gender = $('#search-filters .search-gender').val();

    return data;
}
