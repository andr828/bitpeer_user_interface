var timeout;
var orderWorkerRunning;
var orderBookUpdating = false;

// JS Floor functions
function floor(number) {
  var floored = number * 100000000;
  return Math.floor(floored) / 100000000;
}
/*- Convert Buy Orders to angularJS in market page.
- Convert Recent Market History to angularJS in market page.
- Convert Your Recent Trades to angularJS in market page.
- Convert Your Active Orders to angularJS in market page.
- Change dimenstion of logo. & Replace logo in site.
----------------------------------------------------------*/
// Function to handle polling users orders
function userOrderWorker() {
  
  var count;
  $.ajax({
    url: '/action/getUserOrders/' + market,
    type: 'GET',
    success: function(data) {
      count = data.count;
      if (count > 0) {
        $("#yourOrders").show();
        $("#yourOrders tbody tr").remove();
        $.each(data.data, function(key, value) {
          // Set the order type
          if (value.type == 0) {
            var type = '<strong>BUY</strong>';
          } else {
            var type = '<strong>SELL</strong>';
          }
          // Add the row
          $('#yourOrders tbody').append('<tr><td>' + value.time + '</td><td><span class="type">' + type + '</span></td><td>' + value.amount + '</td><td>' + value.price + '</td><td>' + value.total + '</td><td>' + value.fee + '</td><td>' + value.net_total + '</td><td align="right"><a class="btn inline cancelOrder" href="#" orderId="' + value.order_id + '"><img src="/assets/images/icons/delete.png" alt="Cancel" class="2x" width="16" />&nbsp; Cancel</a></td></tr>');
        });
      } else {
        $("#yourOrders").hide();
      }
    },
    complete: function() {
      // Schedule the next request only if we have orders
      if (count > 0) {
          orderWorkerRunning = setTimeout(userOrderWorker, 5000);
      } else {
          // Stop it running
          clearTimeout(orderWorkerRunning);
      }
    }
  });
}

// Function to pull order book when there has been a missed update
function updateOrderBook() {

  orderBookUpdating = true;

  console.log('Starting to updating order book...');
  
  $.ajax({
    url: '/action/getOrderBook/' + market,
    type: 'GET',
    success: function(data) {
      if (data.response == 'success') {

        // Remove existing buy orders
        $('#buyTable tbody tr').remove();

        if (data.buy.length > 0) {
          // Work through array
          $.each(data.buy, function(key, value) {
            // Replace dot in string
            var price = value.price.replace(".", "-");
            // Add row to table again
            $('#buyTable tbody').append('<tr class="clickableOrder" id="buy-' + price + '" price="' + value.price + '"><td class="price" id="buyPrice-' + price + '">' + value.price + '</td><td class="amount" id="buyAmount-' + price + '">' + value.amount + '</td><td id="buyTotal-' + price + '">' + value.total + '</td></tr>');
          });
        } else {
          // Add no orders row
          $('#buyTable tbody').append('<tr id="buy-noOpen"><td class="none" colspan="3">There are no open orders.</td></tr>');
        }

        // Remove existing sell orders
        $('#sellTable tbody tr').remove();

        if (data.buy.length > 0) {
          // Work through array
          $.each(data.sell, function(key, value) {
            // Replace dot in string
            var price = value.price.replace(".", "-");
            // Add row to table again
            $('#sellTable tbody').append('<tr class="clickableOrder" id="sell-' + price + '" price="' + value.price + '"><td class="price" id="sellPrice-' + price + '">' + value.price + '</td><td class="amount" id="sellAmount-' + price + '">' + value.amount + '</td><td id="sellTotal-' + price + '">' + value.total + '</td></tr>');
          });
        } else {
          // Add no orders row
          $('#sellTable tbody').append('<tr id="sell-noOpen"><td class="none" colspan="3">There are no open orders.</td></tr>');
        }

      }
    },
    complete: function() {
      console.log('Update complete.');

      // Allow the market data to update again
      orderBookUpdating = false;
    }
  });

}

// So validator checks hidden elements
$.validator.setDefaults({
    ignore: [],
});

$(document).ready(function() {

  // Format input fields to use 8 digits
  $('.buyForm input[name="amount"], .buyForm input[name="price"], .buyForm input[name="total"], .sellForm input[name="amount"], .sellForm input[name="price"], .sellForm input[name="total"]').change(function() {
    // Make sure using 8 digits
    $(this).parent().parent().find('input[name="price"]').val(Number($(this).parent().parent().find('input[name="price"]').val()).toFixed(8));
    $(this).parent().parent().find('input[name="amount"]').val(Number($(this).parent().parent().find('input[name="amount"]').val()).toFixed(8));
    $(this).parent().parent().find('input[name="total"]').val(Number($(this).parent().parent().find('input[name="total"]').val()).toFixed(8));
  });

  // Handle auto calculating the fees
  $('.buyForm input[name="amount"], .buyForm input[name="price"], .sellForm input[name="amount"], .sellForm input[name="price"]').keyup(function() {

    // Get the values
    var price = $(this).parent().parent().find('input[name="price"]').val();
    var amount = $(this).parent().parent().find('input[name="amount"]').val();
    var subtotal = floor(price * amount);

    $(this).parent().parent().find('.total').val(subtotal.toFixed(8));

    if ($(this).parent().parent().parent().find('input[name="type"]').val() == 0) {
      // BUY order
      var fee = floor(subtotal * buyerFee);
      $(this).parent().parent().find('.fee').attr('value', fee.toFixed(8));
      var total = +subtotal + +fee;
      $(this).parent().parent().find('.netTotal').attr('value', total.toFixed(8));
      $(this).parent().parent().find('input[name="buyNetTotal"]').val(total.toFixed(8));

    } else {
      // SELL order
      var fee = floor(subtotal * sellerFee);
      $(this).parent().parent().find('.fee').attr('value', fee.toFixed(8));
      var total = subtotal - fee;
      $(this).parent().parent().parent().find('.netTotal').attr('value', total.toFixed(8));
      $(this).parent().parent().find('input[name="sellNetTotal"]').val(total.toFixed(8));
    }

  });

  // Handle entering a set total
  $('.buyForm input[name="total"], .sellForm input[name="total"]').keyup(function(event) {

    // Get total and price
    var subtotal = $(this).val();
    var price = $(this).parent().parent().parent().find('input[name="price"]').val();
    
    // Work out new amount
    var amount = (subtotal / price).toFixed(8);

    // Set the amount value
    $(this).parent().parent().parent().find('input[name="amount"]').val(amount);

    if ($(this).parent().parent().parent().find('input[name="type"]').val() == 0) {
      // BUY order
      var fee = floor(subtotal * buyerFee);
      $(this).parent().parent().find('.fee').attr('value', fee.toFixed(8));
      var total = +subtotal + +fee;
      $(this).parent().parent().find('.netTotal').attr('value', total.toFixed(8));
      $(this).parent().parent().find('input[name="buyNetTotal"]').val(total.toFixed(8));

    } else {
      // SELL order
      var fee = floor(subtotal * sellerFee);
      $(this).parent().parent().find('.fee').attr('value', fee.toFixed(8));
      var total = subtotal - fee;
      $(this).parent().parent().parent().find('.netTotal').attr('value', total.toFixed(8));
      $(this).parent().parent().find('input[name="sellNetTotal"]').val(total.toFixed(8));
    }

  });

  // Function for handle clicking a balance & auto populating totals
  $('.exchangeBalance').on('click', function(event) {

    event.preventDefault();

    var price = $('.buyForm input[name="price"]').val();

    // Hack to get the right total when using amount and floor
    var total = +$(this).text() + +0.000000009;
    var amount = ((total / price) / (1 + buyerFee)).toFixed(8);

    // Set the values & trigger a re-calc
    $('.buyForm input[name="amount"]').val(amount);
    $('.buyForm input[name="amount"]').trigger('change');
    $('.buyForm input[name="amount"]').keyup();

  });

  // Function to handle clicking a coin balance & auto populating totals
  $('.coinBalance').on('click', function(event) {

    event.preventDefault();

    // Set the vlaue and trigger a change
    $('.sellForm input[name="amount"]').val($(this).text());
    $('.sellForm input[name="amount"]').trigger('change');
    $('.sellForm input[name="amount"]').keyup();
  });


  // FUnction to handle clickng a sell / buy order
  $('#buyTable, #sellTable').on('click', '.clickableOrder', function(event) {

    event.preventDefault();

    // Save the amount in this row
    var amount = parseFloat($(this).find('.amount').text());

    // Need to check the previous rows and add any amounts there too
    var row = $(this);
    while (row.prev().hasClass('clickableOrder')) {
      amount += parseFloat(row.prev().find('.amount').text());
      row = row.prev();
    }

    // Set the values
    $('.buyForm input[name="amount"], .sellForm input[name="amount"]').val(amount);
    $('.buyForm input[name="price"], .sellForm input[name="price"]').val($(this).find('.price').text());

    // Trigger a change
    $('.buyForm input[name="amount"], .sellForm input[name="amount"]').trigger('change');
    $('.buyForm input[name="amount"], .sellForm input[name="amount"]').keyup();

    // In case we have less funds available for a BUY
    if ($('.exchangeBalance').length && $('.buyForm input[name="buyNetTotal"]').val() > parseFloat($('.exchangeBalance').text())) {
      // Hack to get the right total when using amount and floor
      var total = +$('.exchangeBalance').text() + +0.000000009;
      var buyAmount = ((total / $(this).find('.price').text()) / (1 + buyerFee)).toFixed(8);
      // Set the value
      $('.buyForm input[name="amount"]').val(buyAmount);
      // Trigger a change
      $('.buyForm input[name="amount"]').trigger('change');
      $('.buyForm input[name="amount"]').keyup();
    }

    // In case we have less funds available for a SELL
    if ($('.coinBalance').length && amount > parseFloat($('.coinBalance').text())) {
      // Set the value
      $('.sellForm input[name="amount"]').val($('.coinBalance').text());
      // Trigger a change
      $('.sellForm input[name="amount"]').trigger('change');
      $('.sellForm input[name="amount"]').keyup();
    }

  });

  // Setup the form validation defaults
  $.validator.setDefaults({
    errorPlacement: function(error, element) {
        error.prependTo(element.parent());
    }
  });

  // Handle the buy form validation
  $(".buyForm").validate({
    ignore: [],
    rules: {
        amount: {
          min: 0,
          required: true
        },
        price: {
          required: true,
          min: 0.00000001
        },
        buyNetTotal: {
          required: true,
          min: 0.00010000,
          max: function () { return parseFloat($('.exchangeBalance').text()); }
        }
    },
    messages: {
        amount: {
          min: "Amount must be greater than zero.",
          required: "Please enter an amount."
        },
        price: {
          min: "Price must be greater than zero.",
          required: "Please enter a price."
        },
        buyNetTotal: {
          min: "Total must be equal to or greater than 0.00010000.",
          max: "Net total must be equal to or less than your available balance."
        }
    }
  });

  // Setup SELL form validation
  $(".sellForm").validate({
    rules: {
        amount: {
          required: true,
          min: 0,
          max: function () { return parseFloat($('.coinBalance').text()); }
        },
        price: {
          required: true,
          min: 0.00000001
        },
        sellNetTotal: {
          required: true,
          min: 0.00010000
        }
    },
    messages: {
        amount: {
          min: "Amount must be greater than zero.",
          max: "Amount must be equal to or less than your available balance.",
          required: "Please enter an amount."
        },
        price: {
          min: "Price must be greater than zero.",
          required: "Please enter a price."
        },
        sellNetTotal: {
          min: "Total must be equal to or greater than 0.00010000."
        }
    }
  });

  // Hadle submitting a BUY / SELL order
  $(".buyForm1, .sellForm1").submit(function(event) {

    event.preventDefault();
    var myClass = $(this).attr("class");

    // Check if the form is valid
    if ($(this).valid()) {

      // Get a confiramtion
      new Messi('Are you sure you would like to place this order?<br /><br /><strong>Market:</strong> ' + coin + '/' + exchange + '<br /><strong>Amount:</strong> ' + $('.' + myClass).find('input[name="amount"]').val() + ' ' + coin + '<br /><strong>Price:</strong> ' + $('.' + myClass).find('input[name="price"]').val() + ' ' + exchange + '<br /><strong>Net Total:</strong> ' + $('.' + myClass).find('.netTotal').val() + ' ' + exchange + '<br /><br />Please ensure the order numbers are correct before confirming. We will be unable to refund/cancel any live order that has been matched.', {title: 'Please confirm', titleClass: 'info',  buttons: [{id: 0, label: 'Yes', val: 'Y', btnClass: 'btn-success'}, {id: 1, label: 'No', val: 'N', btnClass: 'btn-danger'}], modal: true,
          callback: function(val) {

            // Yes we are a go
            if(val == 'Y') {

              // Do the AJAX order subission
              $.ajax({
                 type: 'POST',
                 url: '/action/addOrder',
                 data: $('.' + myClass).serialize(),
                 success: function(data) {

                   // Check if we got a successful add order
                   if(data.response == 'success') {

                      // Handle the success box
                      $('#spanSuccessBox').html(data.reason);
                      $('#sucessBox').show();
                      $('#failBox').hide();
                      clearTimeout(timeout);
                      timeout = setTimeout(function(){$('#sucessBox').hide()},10000);

                      // Update the balance
                      if (myClass == "buyForm") {
                         $('.exchangeBalance').text(($('.exchangeBalance').text() - data.removeFromBalance).toFixed(8));
                      } else {
                         $('.coinBalance').text(($('.coinBalance').text() - data.removeFromBalance).toFixed(8));
                      }

                      // Start worker that checks for user trades
                      clearTimeout(orderWorkerRunning);
                      userOrderWorker();
                      
                   } else {

                      // Show a fail box!
                      $('#spanFailBox').html(data.reason);
                      $('#failBox').show();
                      $('#sucessBox').hide();
                      // Scroll to it
                      $('html, body').animate({
                          scrollTop: $("#failBox").offset().top - 95
                      }, 1000);
                      clearTimeout(timeout);
                      timeout = setTimeout(function(){$('#failBox').hide()},10000);
                   }
                 }
              });
            }
          }
       });
     }
  });

  // Handle cancelling an order
  $('#yourOrders').on('click', '.cancelOrder', function(event) {

     event.preventDefault();
     var orderId = $(this).attr('orderId');
     var type = $(this).parent().parent().find('span').text();

     // Display a popup
     new Messi('Are you sure you would like to cancel this order?<br /><br />WARNING: Your order is currently live so there is a possibility that it may be matched before it can be cancelled.', {title: 'Please confirm', titleClass: 'info',  buttons: [{id: 0, label: 'Yes', val: 'Y', btnClass: 'btn-success'}, {id: 1, label: 'No', val: 'N', btnClass: 'btn-danger'}], modal: true,
        callback: function(val) {

          // User cllicked yes
          if(val == 'Y') {

            // Do the AJAX request
            $.ajax({
              type:'POST',
              url: '/action/cancelOrder',
              data: 'orderId=' + orderId + '&market=' + market + '&csrf_token_market' + market + '=' + token,
              success: function(data) {
                // Check if it was successful
                if(data.response == 'success') {
                   // Handle the success box
                   $('#spanSuccessBox').html(data.reason);
                   $('#sucessBox').show();
                   $('#failBox').hide();
                   clearTimeout(timeout);
                   timeout = setTimeout(function(){$('#sucessBox').hide()},10000);
                } else {
                  // Show a fail box
                   $('#spanFailBox').html(data.reason);
                   $('#failBox').show();
                   $('#sucessBox').hide();
                   $('html, body').animate({
                      scrollTop: $("#failBox").offset().top - 95
                   }, 1000);
                   clearTimeout(timeout);
                   timeout = setTimeout(function(){$('#failBox').hide()},10000);
                }
              }
            });
          }
        }
     });
  });

  $('a.candlestick').on('click', function() {

    if (!$(this).hasClass('active')) {

      // Switch the charts
      $('#chartdiv_candlestick').show();
      $('#chartdiv_orderdepth').hide();

      // Don't want to update order depth chart any more
      orderChartShowing = false;
      clearTimeout(orderChartUpdate);

      // load the data
      AmCharts.loadJSON('6hh', false);
      // Create the chart
      createStockChart();

      // Make this link active
      $(this).addClass('active');
      $('.orderdepth').removeClass('active');

    }

  });

  $('a.orderdepth').on('click', function() {
    if (!$(this).hasClass('active')) {

      // Switch the charts
      $('#chartdiv_candlestick').hide();
      $('#chartdiv_orderdepth').show();

      orderChartShowing = true;

      // Create the chart
      drawChart();

      // Make this link active
      $(this).addClass('active');
      $('.candlestick').removeClass('active');
    }
  });
})