$(document).ready(function () {



/* loading spinner functions */
function showSpinner() { $('div.loading').show(); }
function hideSpinner() { $('div.loading').fadeOut('fast'); }

/**
 * Show / hide checkboxes
 */
$('.toggle-show-multiple').click(function() {
	$('.checkbox-hidden').toggleClass('visually-hidden');
	return false;
})

/**
 * Select all checkboxes
 */
$('input.select-all').change(function() {
    if(this.checked) { $('.select-current').prop( "checked", true ); }
    else             { $('.select-current').prop( "checked", false ); }
 })

/**
 * Load modal window
 */
$(document).on("click", '[data-bs-toggle=modal]', function() {
    // index
    var index = $(this).attr('data-bs-target')
    // default modeal1
    if(index===undefined) { index = "#modal1"; }
    // show loading
    showSpinner ();
    // clear old
    $(index+' .modal-content').html("");
    // load
    $(index+' .modal-content').load($(this).attr('href'), function() {
        // hide spinner
        hideSpinner ()
    });
    // show
    $(index).modal('show');
    // dont reload
    return false;
});

/**
 * Reload popup window
 */
$(document).on("click", '.reload-window', function() {
    location.reload();
    return false;
})

/**
 * Tooltips
 * @type {Array}
 */
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})

/**
 * Ignore open_popup for now
 */
$('.open_popup').click(function () {
	return false;
})


/**
 * Expired certs link
 * @method
 * @return void
 */
$('.circle-expire').click(function () {
    $('.bootstrap-table input[type=search]').val($(this).attr('data-dst-text')).focus();
})



// login
$('form#login').submit(function() {
    var logindata = $(this).serialize();

    $('div#loginCheck').hide();
    //post to check form
    $.post('/route/login/login_check.php', logindata, function(data) {
        $('div#loginCheck').html(data).fadeIn('fast');
        //reload after 2 seconds if succeeded!
        if(data.search("alert alert-success") != -1) {
            setTimeout(function (){window.location="/";}, 1000)
        }
        else {}
    });
    return false;
})


// expand
$('.expand_hosts, .shrink_hosts').click(function(){
    // show
    if($(this).hasClass('expand_hosts')) {
        $(this).removeClass('expand_hosts').addClass('shrink_hosts')
        $('td.td-hosts, th.td-hosts').removeClass('visually-hidden');
        $(this).find('i').removeClass('fa-expand').addClass('fa-compress');
        $(this).html($(this).html().replace("Expand", "Shrink"))
        createCookie("show_hosts","1",30)
    }
    // hide
    else {
        $(this).removeClass('shrink_hosts').addClass('expand_hosts')
        $('td.td-hosts, th.td-hosts').addClass('visually-hidden');
        $(this).find('i').removeClass('fa-compress').addClass('fa-expand');
        $(this).html($(this).html().replace("Shrink", "Expand"))
        createCookie("show_hosts","0",30)
    }
    // dont reload
    return false;
})



/* @cookies */
function createCookie(name,value,days) {
    var date;
    var expires;

    if (typeof days !== 'undefined') {
        date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        expires = "; expires="+date.toGMTString();
    }
    else {
        var expires = "";
    }

    document.cookie = name+"="+value+expires+"; path=/";
}


// end jQuery
})





