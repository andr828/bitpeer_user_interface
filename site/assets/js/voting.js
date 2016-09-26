var voting = true;

$(document).ready(function() {

  $("#voteForm").submit(function(event) {
    event.preventDefault();
    $.ajax({
        type:'POST',
        url: '/action/addVote',
        data: $('#voteForm').serialize(),
        success: function(data) {
          if (data.response == 'success') {
            $('#messi-modal').hide();
            $('#messi').hide();
            $('#spanSuccessBox').html(data.reason);
            $('#sucessBox').show();
            clearTimeout(timeout);
            timeout = setTimeout(function(){$('#sucessBox').hide()},10000);
          } else if (data.response == 'failure') {
            $('#messi-modal').hide();
            $('#messi').hide();
            $('#spanFailBox').html(data.reason);
            $('#sucessBox').hide();
            $('#failBox').show();
            clearTimeout(timeout);
            timeout = setTimeout(function(){$('#failBox').hide()},10000);
          }
        }
    });
  });

    var timeout;

    $('.btn').on('click', function(event) {

      if($(this).attr('id') == 'cancel') {
        event.preventDefault();
        // Hide the modal
        $('#messi-modal').hide();
        $('#messi').hide();

      } else if($(this).attr('id') != 'postbut') {
        event.preventDefault();
        // Show the modal box
        $('#clicked').val($(this).attr('coin'));
        $('#messi-modal').show();
        $('#messi').show();
      }
    });

    // Get the hash from the URL
    if (window.location.hash) {
        $('html,body').animate({scrollTop: $("a" + window.location.hash).offset().top - 95},'slow');
    }

    // Clicking on a link
    $('.coinlink').on('click', function(event) {
        var aid = $(this).attr('href');
        $('html,body').animate({scrollTop: $("a" + aid).offset().top - 95},'slow');
    });

});

// Push an update to the voting
function updateVote(args, data) {

    // Get the existing votes
    var existing = parseInt($("#vote-"+data.coin + " .votes").text()) + data.amount;
    $("#vote-"+data.coin + " .votes").html(existing);

    $("#vote-"+data.coin + " .votes").addClass("bold");

    setTimeout(function(){
        $("#vote-"+data.coin + " .votes").removeClass("bold");
    }, 3000);

    switchRow(data.coin, existing);
}

// Switches rows when the coin overtakes
function switchRow(coinId, total) {

    // Has it gone above the row above?
    if ($("#vote-"+coinId).prev().find('.votes').text() > 0 && $("#vote-"+coinId).prev().find('.votes').text() < total) {

        // Change the rank
        var rank = $("#vote-"+coinId).prev().find(".rank").text();
        $("#vote-"+coinId + " .rank").text(rank);
        $("#vote-"+coinId).prev().find(".rank").text(parseInt(rank) + 1);

        // Switch the row
        $("#vote-"+coinId).after($("#vote-"+coinId).prev());

        $("#vote-"+coinId).addClass("bold");

        setTimeout(function(){
            $("#vote-"+coinId).removeClass("bold");
        }, 3000);

        // Call again in case it should go higher
        switchRow(coinId, total);

    }

}