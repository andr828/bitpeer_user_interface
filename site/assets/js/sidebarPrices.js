// Push message number for market updates
var messageNumber = 0;
var debug = true;

$(document).ready(function() {

    var connection = new autobahn.Connection({
        url: "ws://localhost:8081/ws",
        realm: "realm1"
    });

    connection.onopen = function (session) {

        if(debug){console.log('Connected to push.bitPeer.com');}

        // Hide the websocket error div
        $("#noWebsocket").hide();

        // Handle voting subscriptions
        if(typeof voting !== 'undefined') {
            if(debug){console.log('Subscribed to com.bitPeer.voting');}
            session.subscribe('com.bitPeer.voting', updateVote);
        }   

        // Subscribe to the sidebar prices
        if(debug){console.log('Subscribed to om.bitPeer.sidebarPrices');}
        session.subscribe('com.bitPeer.sidebarPrices', sidebarPrices);

        // Subscribe to the sidebar stats
        if(debug){console.log('Subscribed to com.bitPeer.sidebarStats');}
        session.subscribe('com.bitPeer.sidebarStats', sidebarStats);

        // Subscribe to the market
        if(typeof marketID !== 'undefined') {
            if(debug){console.log('Subscribed to com.bitPeer.marketUpdates.' + marketID);}
            session.subscribe('com.bitPeer.marketUpdates.' + marketID, marketUpdates);
        }
    };


    connection.onclose = function (reason, details) {
        if(debug){console.log('Lost connection to push.bitPeer.com');}

        // Show the popup box
        if(reason == 'lost') {
            $("#noWebsocket").show();
        }
     }

    // Start the connection
    connection.open();


    // Handle the searching
    $('#searchBox').keyup(function() {

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

        if (str != '') {
            $('.total').hide();
        } else {
            $('.total').show();
        }

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
});

// Push an update to the sidebar
function sidebarPrices(args, data) {

    if(debug){console.log('sidebarPrices Update:', data);}

    // If the last price is the same as the current one. Don't do anything
    if($("#spanPrice-"+data.market).html() != data.price) {

        // Save the previous market price
        var previousMarketPrice = $("#spanPrice-"+data.market).html();

        // Set the new price
        $("#spanPrice-"+data.market).html(data.price);

        // Flash the sidebar color
        if($("#sidebarSpan-"+data.market).hasClass('active') == false) {

            // Work out what class to display
            if(previousMarketPrice < data.price) {
                var newMarketClass = 'changeup';
            } else {
                var newMarketClass = 'changedown';
            }

            // Add the class
            $("#sidebarSpan-"+data.market).toggleClass(newMarketClass);

            // Toggle the last price
            setTimeout(function(){
                $("#sidebarSpan-"+data.market).toggleClass(newMarketClass);
            }, 3000);
        }

        // Only change if yesterday price isn't 0
        if ($("#spanPrice-"+data.market).attr('yesterdayPrice') !== "0.00000000") {

            // Calculate the % change
            var percentChange = ((data.price - $("#spanPrice-"+data.market).attr('yesterdayPrice'))/$("#spanPrice-"+data.market).attr('yesterdayPrice')) * 100;

            // Update the price
            if(percentChange.toFixed(2) > 0) {
                if (percentChange > 1000) {
                    $("#spanChange-"+data.market).html('>+999.99%');
                } else {
                    $("#spanChange-"+data.market).html('+'+percentChange.toFixed(2) + '%');
                }
                $("#spanChange-"+data.market).removeClass('down').addClass('up');
            } else {
                $("#spanChange-"+data.market).html(percentChange.toFixed(2) + '%');
                $("#spanChange-"+data.market).removeClass('up').addClass('down');
            }

        }
    }
}

// Push an update to the sidebar stats
function sidebarStats(args, data) {
    if(debug){console.log('sidebarStats Update:', data);}

    // Update the statistic figures
    $(".sidebarBTC").html(data.btcVolume);
    $(".sidebarLTC").html(data.ltcVolume);
    $(".sidebarTrades").html(data.trades);

}

// Push an update to the market page
function marketUpdates(args, data) {

    if(debug){console.log('marketUpdates Update:', args, data);}

    // Don't do anything while order book refreshing, check again
    if (orderBookUpdating === true) {
        console.log('Update waiting for order book to finish updating. Type: ' + update.order + ', Price: ' + update.price);
        setTimeout(updateMarketData, 500, update);
        return;
    }

    if (typeof update.number != 'undefined') {
        
        if (messageNumber !== 0 && (messageNumber + 1) !== update.number) {
            // In case the messages have come out of order
            if(debug){console.warn("MISSED MESSAGE. Got " + update.number + " Expecting " + (messageNumber + 1));}
            
            // Let's refresh the order book
            updateOrderBook();
        }

        // Save the push message number
        messageNumber = update.number;

    }

    // Check if we are posting a trade
    if(data.type == 'trade') {

        // Change the . to - to stop jQuery breaking
        var priceUnformatted = update.price;
        var priceFormatted = priceUnformatted.replace(".", "-");

        // Update the page title
        var title = $(document).attr("title");
        if (title.substring(0,1) == "(") {
            var titleArray = title.split(') ');
            title = titleArray[1];
        }
        $(document).attr("title", "(" + update.price + ") " + title);

        // Add a new line to the trade history
        $('<tr class="newRow"><td class="bold">' + update.time + '</td><td class="bold"><span class="' + update.order + '">' + update.order.toUpperCase() + '</span></td><td class="bold">' + Number(update.price).toFixed(8) + '</td><td class="bold">' + Number(update.amount).toFixed(8) + '</td><td class="bold">' + Number(update.total).toFixed(8) + '</td> </tr>').insertAfter('#historyTable > tbody > tr:first');

        // Time it out
        setTimeout(function(){
            $('tr.newRow td').toggleClass("bold");
            $('tr.newRow').toggleClass("newRow");
        }, 3000);

        // Try and find a price
        if($('#' + update.oldOrder + 'Amount-' + priceFormatted).text() == update.amount) {

            // Delete the whole row
            $('#' + update.oldOrder + '-' + priceFormatted).remove();

            var rowCount = $('#' + update.oldOrder + 'Table tr').length;
            if(rowCount == 1) {
                // Add the no orders line
                $('<tr id="' + update.oldOrder + '-noOpen"><td class="none" colspan="3">There are no open orders.</td></tr>').insertAfter('#' + update.oldOrder + 'Table > tbody > tr:first');
            }

        } else {

            // Subtract the value
            var newAmount = $('#' + update.oldOrder + 'Amount-' + priceFormatted).text() - update.amount;

            // CHeck if the new amount is negative
            if(newAmount < 0) {
                
                // Delete the whole row
                $('#' + update.oldOrder + '-' + priceFormatted).remove();

                var rowCount = $('#' + update.oldOrder + 'Table tr').length;
                if(rowCount == 1) {
                    // Add the no orders line
                    $('<tr id="' + update.oldOrder + '-noOpen"><td class="none" colspan="3">There are no open orders.</td></tr>').insertAfter('#' + update.oldOrder + 'Table > tbody > tr:first');
                }
            
            } else {

                // Update the amount
                $('#' + update.oldOrder + 'Amount-' + priceFormatted).text(newAmount.toFixed(8));

                // Calculate the total
                var totalAmount = newAmount * update.price;

                // Update the total
                $('#' + update.oldOrder + 'Total-' + priceFormatted).text(totalAmount.toFixed(8));

                // Make the text box
                $('#' + update.oldOrder + 'Amount-' + priceFormatted).toggleClass("priceDown");
                $('#' + update.oldOrder + 'Total-' + priceFormatted).toggleClass("priceDown");
                $('#' + update.oldOrder + 'Price-' + priceFormatted).toggleClass("priceDown");

                // Time it out
                setTimeout(function(){
                    $('#' + update.oldOrder + 'Amount-' + priceFormatted).toggleClass("priceDown");
                    $('#' + update.oldOrder + 'Total-' + priceFormatted).toggleClass("priceDown");
                    $('#' + update.oldOrder + 'Price-' + priceFormatted).toggleClass("priceDown");
                }, 3000);
            }

        }

        // Work out what CSS class to apply
        var topPriceClass = false;
        if(update.price > $('#spanLastPrice').text()) {
            topPriceClass = 'priceUp';
        } else if(update.price < $('#spanLastPrice').text()) {
            topPriceClass = 'priceDown';
        }

        // Update the top price data
        $('#spanLastPrice').text(update.price);

        // Change the color of the last price
        if(topPriceClass != false) {

            // Set the class
            $('#spanLastPrice').toggleClass(topPriceClass);

            // Toggle the last price
            setTimeout(function(){
                $('#spanLastPrice').toggleClass(topPriceClass)
            }, 3000);
        }

        // Check the low price
        if(parseFloat($('#spanLowPrice').text()) > update.price) {
            $('#spanLowPrice').text(update.price);

            // Set the class
            $('#spanLowPrice').toggleClass('priceDown');

            // Toggle the last price
            setTimeout(function(){
                $('#spanLowPrice').toggleClass('priceDown')
            }, 3000);
        }

        // Check the high price
        if(parseFloat($('#spanHighPrice').text()) < update.price) {
            $('#spanHighPrice').text(update.price);

            // Set the class
            $('#spanHighPrice').toggleClass('priceUp');

            // Toggle the last price
            setTimeout(function(){
                $('#spanHighPrice').toggleClass('priceUp')
            }, 3000);
        }

    } else if(data.type == 'order') {

        // Change the . to - to stop jQuery breaking
        var priceUnformatted = update.price;
        var priceFormatted = priceUnformatted.replace(".", "-");

        if($('#' + update.order + '-' + priceFormatted).length == 0) {
            
            var orderInserted = 0;
            
            // Calculate the total
            var rowTotal = update.price * update.amount;

            // Order row does not exist - add it
            $('#' + update.order + 'Table tr').each(function() {
                
                if(update.order == 'buy') {

                    // Check if we have a BUY price match
                    if(parseFloat($(this).attr("price")) < update.price) {

                        // Log the row we found for now
                        //console.log(this);

                        // Insert the new row
                        $('<tr class="clickableOrder" id="' + update.order + '-' + priceFormatted + '" price="' + update.price + '">\n\
                            <td class="price priceUp" id="' + update.order + 'Price-' + priceFormatted + '">' + Number(update.price).toFixed(8) + '</td>\n\
                            <td class="amount priceUp" id="' + update.order + 'Amount-' + priceFormatted + '">' + Number(update.amount).toFixed(8) + '</td>\n\
                            <td class="priceUp" id="' + update.order + 'Total-' + priceFormatted + '">' + Number(rowTotal).toFixed(8) + '</td> \n\
                          </tr>').insertBefore(this);

                        // Time it out
                        setTimeout(function(){
                            $('#' + update.order + 'Amount-' + priceFormatted).toggleClass("priceUp");
                            $('#' + update.order + 'Total-' + priceFormatted).toggleClass("priceUp");
                            $('#' + update.order + 'Price-' + priceFormatted).toggleClass("priceUp");
                            $('#' + update.order + '' + priceFormatted).toggleClass("priceUp");
                        }, 3000);

                        orderInserted = 1;

                        // Break the loop
                        return false;

                    }

                } else {
                        
                    // Check if we have a SELL price match
                    if(parseFloat($(this).attr("price")) > update.price) {

                        // Log the row we found for now
                        //console.log(this);

                        // Inser the new row BEFORE existing one
                        $('<tr class="clickableOrder" id="' + update.order + '-' + priceFormatted + '" price="' + update.price + '">\n\
                            <td class="price priceUp" id="' + update.order + 'Price-' + priceFormatted + '">' + Number(update.price).toFixed(8) + '</td>\n\
                            <td class="amount priceUp" id="' + update.order + 'Amount-' + priceFormatted + '">' + Number(update.amount).toFixed(8) + '</td>\n\
                            <td class="priceUp" id="' + update.order + 'Total-' + priceFormatted + '">' + Number(rowTotal).toFixed(8) + '</td> \n\
                          </tr>').insertBefore(this);

                        // Time it out
                        setTimeout(function(){
                            $('#' + update.order + 'Amount-' + priceFormatted).toggleClass("priceUp");
                            $('#' + update.order + 'Total-' + priceFormatted).toggleClass("priceUp");
                            $('#' + update.order + 'Price-' + priceFormatted).toggleClass("priceUp");
                            $('#' + update.order + '' + priceFormatted).toggleClass("priceUp");
                        }, 3000);

                        orderInserted = 1;

                        // Break the loop
                        return false;

                    }
                }
            });
            
            // If the order wasn't inserted - add it at the bottom
            if(orderInserted === 0) {
       
                // Insert the new row
                $('#' + update.order + 'Table').append('<tr class="clickableOrder" id="' + update.order + '-' + priceFormatted + '" price="' + update.price + '">\n\
                    <td class="price priceUp" id="' + update.order + 'Price-' + priceFormatted + '">' + Number(update.price).toFixed(8) + '</td>\n\
                    <td class="amount priceUp" id="' + update.order + 'Amount-' + priceFormatted + '">' + Number(update.amount).toFixed(8) + '</td>\n\
                    <td class="priceUp" id="' + update.order + 'Total-' + priceFormatted + '">' + Number(rowTotal).toFixed(8) + '</td> \n\
                  </tr>');
                    
                // Delete the old row
                $('#' + update.order + '-noOpen').remove();

                // Time it out
                setTimeout(function(){
                    $('#' + update.order + 'Amount-' + priceFormatted).toggleClass("priceUp");
                    $('#' + update.order + 'Total-' + priceFormatted).toggleClass("priceUp");
                    $('#' + update.order + 'Price-' + priceFormatted).toggleClass("priceUp");
                    $('#' + update.order + '' + priceFormatted).toggleClass("priceUp");
                }, 3000);
            }
                

        } else {

            // Order row exists - update the amount & total

            // Subtract the value
            var newAmount = +$('#' + update.order + 'Amount-' + priceFormatted).text() + +update.amount;
            var existingAmount = $('#' + update.order + 'Amount-' + priceFormatted).text();
            
            // Work out if the price went up or down
            var priceClass = 'priceDown';
            if(newAmount > existingAmount) {
                priceClass = 'priceUp';
            }

            // Update the amount
            $('#' + update.order + 'Amount-' + priceFormatted).text(newAmount.toFixed(8));

            // Calculate the total
            var totalAmount = newAmount * update.price;

            // Update the total
            $('#' + update.order + 'Total-' + priceFormatted).text(totalAmount.toFixed(8));

            // Make the text box
            $('#' + update.order + 'Amount-' + priceFormatted).toggleClass(priceClass);
            $('#' + update.order + 'Total-' + priceFormatted).toggleClass(priceClass);
            $('#' + update.order + 'Price-' + priceFormatted).toggleClass(priceClass);
            $('#' + update.order + '' + priceFormatted).toggleClass("priceUp");

            // Time it out
            setTimeout(function(){
                $('#' + update.order + 'Amount-' + priceFormatted).toggleClass(priceClass);
                $('#' + update.order + 'Total-' + priceFormatted).toggleClass(priceClass);
                $('#' + update.order + 'Price-' + priceFormatted).toggleClass(priceClass);
                $('#' + update.order + '' + priceFormatted).toggleClass("priceUp");
            }, 3000);

        }

    } else if(data.type == 'delete') {

        // Change the . to - to stop jQuery breaking
        var priceUnformatted = update.price;
        var priceFormatted = priceUnformatted.replace(".", "-");

        // Try and find a price
        if($('#' + update.order + 'Amount-' + priceFormatted).text() == update.amount) {

            // Delete the whole row
            $('#' + update.order + '-' + priceFormatted).remove();
            
            var rowCount = $('#' + update.order + 'Table tr').length;
            if(rowCount == 1) {
                // Add the no orders line
                $('<tr id="' + update.order + '-noOpen"><td class="none" colspan="3">There are no open orders.</td></tr>').insertAfter('#' + update.order + 'Table > tbody > tr:first');
            }

        } else {

            // Subtract the value
            var newAmount = $('#' + update.order + 'Amount-' + priceFormatted).text() - update.amount;

            // Check if the new amount is negative
            if(newAmount < 0) {

                //Delete the whole row
                $('#' + update.order + '-' + priceFormatted).remove();

                var rowCount = $('#' + update.order + 'Table tr').length;
                if(rowCount == 1) {
                    // Add the no orders line
                    $('<tr id="' + update.order + '-noOpen"><td class="none" colspan="3">There are no open orders.</td></tr>').insertAfter('#' + update.order + 'Table > tbody > tr:first');
                }

            } else {

                // Update the amount
                $('#' + update.order + 'Amount-' + priceFormatted).text(newAmount.toFixed(8));

                // Calculate the total
                var totalAmount = newAmount * update.price;

                // Update the total
                $('#' + update.order + 'Total-' + priceFormatted).text(totalAmount.toFixed(8));

                // Make the text box
                $('#' + update.order + 'Amount-' + priceFormatted).toggleClass("priceDown");
                $('#' + update.order + 'Total-' + priceFormatted).toggleClass("priceDown");
                $('#' + update.order + 'Price-' + priceFormatted).toggleClass("priceDown");

                // Time it out
                setTimeout(function(){
                    $('#' + update.order + 'Amount-' + priceFormatted).toggleClass("priceDown");
                    $('#' + update.order + 'Total-' + priceFormatted).toggleClass("priceDown");
                    $('#' + update.order + 'Price-' + priceFormatted).toggleClass("priceDown");
                    $('#' + update.order + '' + priceFormatted).toggleClass("priceUp");
                }, 3000);
            }

        }

    } else if(data.type == 'chart') {

        // Only if the candlestick is visible
        if ($('#chartdiv_candlestick').is(':visible')) {

            // Remove last datapoint from chart (the hack)
            chartData.pop();

            // Add new datapoint
            chartData.push({
                date: update.date,
                open: update.open,
                close: update.close,
                high: update.high,
                low: update.low,
                exchange_volume: update.volume
            });

            // Hack: Adding extra row to make it show the latest point
            // Convert last date point and add 10 minutes
            var date = update.date;
            date = new Date(date.replace(" ","T") + 'Z');
            var localOffset = date.getTimezoneOffset() * 60000;
            date.setTime(date.getTime() + 600000 + localOffset);

            // Push to chart
            chartData.push({
                date: date,
                open: update.close,
                close: update.close,
                high: update.close,
                low: update.close,
                exchange_volume: 0
            });

            // Re-draw graph
            createStockChart();

        }

    } else if(data.type == 'stats') {
      
        // Update the market statistics figures
        $(".marketHigh").html(data.high);
        $(".marketLow").html(data.low);
        $(".marketVolume").html(data.volume);
    }
}

function updateBalance(update) {

    // Make sure we're on the market page and the market ID is defined
    if(typeof market !== 'undefined') {
        if (market == update.market) {

            // If it's a completed trade or cancelled order
            if (update.action == 0) {

                // If it's a buy or a sell
                if (update.type == 0) {
                    $('.coinBalance').text((+$('.coinBalance').text() + +update.balanceChange).toFixed(8));
                } else {
                    $('.exchangeBalance').text((+$('.exchangeBalance').text() + +update.balanceChange).toFixed(8));
                }

            } else {

                // If it's a buy or a sell
                if (update.type == 0) {
                    $('.exchangeBalance').text((+$('.exchangeBalance').text() + +update.balanceChange).toFixed(8));
                } else {
                    $('.coinBalance').text((+$('.coinBalance').text() + +update.balanceChange).toFixed(8));
                }
            }
        }
    }
}

function br2nl(str) {
    return str.replace(/<br\s*\/?>/mg,"\n");
}
