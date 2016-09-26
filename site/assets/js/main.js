$(document).ready(function() {

    // Resize sidebar on full page load
    $(window).bind("load", function() {
        $("#sidebar").css("min-height", $(document).height()-$("#header").outerHeight()-$("#footer").outerHeight());
    });

    // If the page resizes, resize the sidebar
    $(window).resize(function () {
        $("#sidebar").css("min-height", 0);
        $("#sidebar").css("min-height", $(document).height()-$("#header").outerHeight()-$("#footer").outerHeight());
    });

    // Show the account dropdown on request
    $('.loggedin').on('click', function() {
        $('.accountmenu').slideToggle();
        $(this).toggleClass('active');
    });

    // Show the account dropdown on request
    $('.actions').on('click', function() {
        $(this).parent().find('.actionmenu').slideToggle();
        $(this).toggleClass('active');
    });

    // Close box
    $('.close').on('click', function() {
        $(this).parent().slideToggle();
    });

    // Search box on click and click out
    $('#searchBox').focus(function() {
        if ($(this).val() === 'Search...') {
            $(this).val('');
        }
    });
    $('#searchBox').focusout(function() {
        if ($(this).val() === '') {
            $(this).val('Search...');
        }
    });

    // Handle the searching
    $('#searchBox').keyup(function() {

        // Save search string
        var str = $(this).val();
        
        // Loop the sidebar
        $.each($('.name'), function(){
            var code = $(this).html();
            if(code.toLowerCase().indexOf(str.toLowerCase()) == -1) {
                $('#sidebarSpan-' + $(this).attr('market_id')).hide();
            } else {
                $('#sidebarSpan-' + $(this).attr('market_id')).show();
            }

        });

        // Hide estimated BTC row if searching
        if (str !== '') {
            $('.total').hide();
        } else {
            $('.total').show();
        }

        // Show the no results row if nothing found
        if ($('#btcMarkets li:visible').length == 1 || ($('#btcMarkets li:visible').length == 2 && $("#btcNoResults").is(":visible"))) {
            $("#btcNoResults").show();
        } else {
            $("#btcNoResults").hide();
        }
        if ($('#ltcMarkets li:visible').length == 1 || ($('#ltcMarkets li:visible').length == 2 && $("#ltcNoResults").is(":visible"))) {
            $("#ltcNoResults").show();
        } else {
            $("#ltcNoResults").hide();
        }
        if ($('#accountBalances li:visible').length == 1 || ($('#accountBalances li:visible').length == 2 && $("#balanceNoResults").is(":visible"))) {
            $("#balanceNoResults").show();
        } else {
            $("#balanceNoResults").hide();
        }

    });

    // Toggle sidebar sections
    $('#sidebar h2 a').on('click', function(event) {
        
        // Stop it jumping around
        event.preventDefault();

        // Get the section name
        var section = $(this).parent().next().attr('id');

        // Set cookie
        if ($('#' + section).is(':visible')) {
            $.cookie(section, '0');
        } else {
            $.cookie(section, '1');
        }

        // Toggle the section
        $('#' + section).slideToggle();

        // Swap the image
        if ($(this).find('img').attr('src').indexOf('up') > 0) {
            $(this).find('img').attr('src', '/assets/images/icons/toggledown.png');
        } else {
            $(this).find('img').attr('src', '/assets/images/icons/toggleup.png');
        }

        // Resize the sidebar
        $("#sidebar").css("min-height", 0);
        $("#sidebar").css("min-height", $(document).height()-$("#header").outerHeight()-$("#footer").outerHeight());

    });

    // Check cookies and hide the sidebar items as needed
    if ($.cookie('accountBalances') === undefined || $.cookie('accountBalances') === '1') {
        $('#accountBalances').show();
        $('#accountBalances').prev().find('img').attr('src', '/assets/images/icons/toggleup.png');
    }
    if ($.cookie('btcMarkets') === undefined || $.cookie('btcMarkets') === '1') {
        $('#btcMarkets').show();
        $('#btcMarkets').prev().find('img').attr('src', '/assets/images/icons/toggleup.png');
    }
    if ($.cookie('ltcMarkets') === undefined || $.cookie('ltcMarkets') === '1') {
        $('#ltcMarkets').show();
        $('#ltcMarkets').prev().find('img').attr('src', '/assets/images/icons/toggleup.png');
    }
    if ($.cookie('sidebarStats') === undefined || $.cookie('sidebarStats') === '1') {
        $('#sidebarStats').show();
        $('#sidebarStats').prev().find('img').attr('src', '/assets/images/icons/toggleup.png');
    }

});