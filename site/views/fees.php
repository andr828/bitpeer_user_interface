<div id="content">

    <sidebar coin-description-array="coinDescriptionArray" translation="translation" product-name="{{ prodName }}"
             number-of-trades="numberOfTrades"
             set-current-coin-function="setSelectedCurrency(coin)"></sidebar>
    <div id="contentinner">


        <?php

        // Get the market pairings
        $marketPairs = $engine->library['marketsStats']->getMarketPairs($market);

        $engine->loadLibrary('wallet/withdraw');

        $withdrawFees = $engine->library['walletWithdraw']->getWithdrawFees();

        ?>

        <div ng-controller="translationController">
            <h1>{{prodName}} {{translation.FEES_FEES}}</h1>

            <h2 style="text-align: center;">{{translation.FEES_TRADING_FEES}}</h2>

            <p style="text-align: center;">{{translation.FEES_CON1}}<br/><br/></p>

            <table class="table fees">
                <tr>
                    <th style="width:180px;">MARKET</th>
                    <th>BUY FEE</th>
                    <th>SELL FEE</th>
                </tr>
                <?php
                foreach ($marketPairs AS $key => $value) {
                    $fees = $engine->library['marketsStats']->getMarketFees($key);
                    echo '<tr>';
                    echo '<td><strong>' . $value . '</strong></td>';
                    echo '<td>';
                    echo ($fees['buyer_fee'] == 0) ? 'FREE!' : number_format($fees['buyer_fee'] * 100, 2, '.', '') . '%</td>';
                    echo '<td>';
                    echo ($fees['seller_fee'] == 0) ? 'FREE!' : number_format($fees['seller_fee'] * 100, 2, '.', '') . '%</td>';
                    echo '</tr>';
                }
                ?>
            </table>

            <h2 style="text-align: center;">{{translation.FEES_WITHDRAW_FEES}}</h2>

            <p style="text-align: center;">{{translation.FEES_WITHDRAW_CON1}}<br/><br/></p>

            <table class="table fees">
                <tr>
                    <th style="width:180px;">COIN CODE</th>
                    <th style="width:180px;">COIN NAME</th>
                    <th>WITHDRAW FEE</th>
                </tr>
                <?php
                foreach ($withdrawFees AS $key => $value) {
                    echo '<tr>';
                    echo '<td><strong>' . $value['code'] . '</strong></td>';
                    echo '<td>' . $value['name'] . '</td>';
                    echo '<td>' . $value['withdraw_fee'] . ' ' . $value['code'] . '</td>';
                    echo '</tr>';
                }
                ?>
            </table>
        </div>

    </div>