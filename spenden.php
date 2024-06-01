<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ETH und ERC-20 Spenden mit MetaMask</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header text-center">
                        <h4 class="mb-0">Spende Ethereum oder ERC-20 Token</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 my-auto">
                                <canvas id="ethQrCode" class="img-fluid mb-3"></canvas>
                            </div>
                            <div class="col-md-8">
                                <div class="text-center mb-4">
                                    Kurs: <span class="h4 text-success fw-bold" id="ethPrice"></span> EUR/ETH
                                </div>
                                <div class="mb-3">
                                    <label for="currencySelect" class="form-label">Währung auswählen</label>
                                    <select id="currencySelect" class="form-select">
                                        <option value="eth">Ethereum (ETH)</option>
                                        <option value="erc20">ERC-20 Token</option>
                                    </select>
                                </div>
                                <div id="erc20Fields" style="display:none;">
                                    <div class="mb-3">
                                        <label for="tokenAddress" class="form-label">Token Contract Adresse</label>
                                        <input type="text" id="tokenAddress" placeholder="0x..." class="form-control">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <input type="number" id="euroAmount" class="form-control">
                                            <span class="input-group-text">EUR</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <input type="number" step="0.000001" id="cryptoAmount" class="form-control">
                                            <span class="input-group-text" id="cryptoSymbol">ETH</span>
                                        </div>
                                    </div>
                                </div>
                                <div id="messages" class="text-center"></div>
                                <div class="text-center mb-3">
                                    <button id="metaMaskButton" class="btn btn-primary" disabled>Mit MetaMask spenden</button>
                                </div>
                                <p class="text-center">Oder sende Ethereum oder Tokens direkt an die Adresse:</p>
                                <p class="text-center"><strong id="ethAddress" class="text-monospace"><a href="ethereum:0x92a3A3C1B45383465e4d65B68c7a4A10e9b1BDD8">0x92a3A3C1B45383465e4d65B68c7a4A10e9b1BDD8</a></strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', async function() {
    const ethAddress = "0x92a3A3C1B45383465e4d65B68c7a4A10e9b1BDD8";
    let ethPrice = 0;

    async function fetchEthPrice() {
        try {
            const response = await fetch('https://api.coingecko.com/api/v3/simple/price?ids=ethereum&vs_currencies=eur');
            const data = await response.json();
            ethPrice = data.ethereum.eur;
            console.log(`ETH Preis: ${ethPrice} EUR`);
            document.getElementById('ethPrice').textContent = ethPrice;
        } catch (error) {
            console.error('Fehler beim Abrufen des ETH-Preises:', error);
        }
    }

    await fetchEthPrice();

    function checkAmount() {
        const euroAmount = document.getElementById('euroAmount').value;
        const cryptoAmount = document.getElementById('cryptoAmount').value;
        const metaMaskButton = document.getElementById('metaMaskButton');

        if (euroAmount && cryptoAmount) {
            metaMaskButton.removeAttribute('disabled');
        } else {
            metaMaskButton.setAttribute('disabled', 'true');
        }
    }

    document.getElementById('euroAmount').addEventListener('input', function() {
        const euroAmount = parseFloat(this.value);
        if (!isNaN(euroAmount) && document.getElementById('currencySelect').value === 'eth') {
            const cryptoAmount = (euroAmount / ethPrice).toFixed(6);
            document.getElementById('cryptoAmount').value = cryptoAmount;
            checkAmount();
        }
    });

    document.getElementById('cryptoAmount').addEventListener('input', function() {
        const cryptoAmount = parseFloat(this.value);
        if (!isNaN(cryptoAmount) && document.getElementById('currencySelect').value === 'eth') {
            const euroAmount = (cryptoAmount * ethPrice).toFixed(2);
            document.getElementById('euroAmount').value = euroAmount;
            checkAmount();
        }
    });

    document.getElementById('currencySelect').addEventListener('change', function() {
        const currency = document.getElementById('currencySelect').value;
        if (currency === 'eth') {
            document.getElementById('cryptoSymbol').textContent = 'ETH';
            document.getElementById('erc20Fields').style.display = 'none';
        } else {
            document.getElementById('cryptoSymbol').textContent = 'Token';
            document.getElementById('erc20Fields').style.display = 'block';
        }
        checkAmount();
    });

    const qr = new QRious({
        element: document.getElementById('ethQrCode'),
        value: `ethereum:${ethAddress}`,
        size: 200
    });

    document.getElementById('metaMaskButton').addEventListener('click', async function() {
        const currency = document.getElementById('currencySelect').value;
        const cryptoAmount = document.getElementById('cryptoAmount').value;

        if (typeof window.ethereum !== 'undefined') {
            try {
                await window.ethereum.request({ method: 'eth_requestAccounts' });
                const accounts = await ethereum.request({ method: 'eth_accounts' });
                const account = accounts[0];

                if (currency === 'eth') {
                    const valueInWei = (cryptoAmount * 1e18).toString(16);
                    const transactionParameters = {
                        to: ethAddress,
                        from: account,
                        value: `0x${valueInWei}`,
                    };

                    const txHash = await ethereum.request({
                        method: 'eth_sendTransaction',
                        params: [transactionParameters],
                    });

                    console.log('Transaction sent with hash: ', txHash);
                    showMessage('Transaktion erfolgreich gesendet', 'success');
                } else if (currency === 'erc20') {
                    const tokenAddress = document.getElementById('tokenAddress').value;
                    if (!tokenAddress) {
                        showMessage('Bitte geben Sie die Token-Contract-Adresse ein.', 'danger');
                        return;
                    }

                    const transactionParameters = {
                        to: tokenAddress,
                        from: account,
                        data: getERC20TransferData(ethAddress, cryptoAmount),
                    };

                    const txHash = await ethereum.request({
                        method: 'eth_sendTransaction',
                        params: [transactionParameters],
                    });

                    console.log('Transaction sent with hash: ', txHash);
                    showMessage('Transaktion erfolgreich gesendet', 'success');
                }
            } catch (error) {
                console.error('User rejected the request or an error occurred:', error);
                showMessage('Fehler beim Senden der Transaktion', 'danger');
            }
        } else {
            showMessage('MetaMask ist nicht installiert. Bitte installiere MetaMask und versuche es erneut.', 'danger');
        }
    });

    function getERC20TransferData(to, amount) {
        const erc20TransferMethodId = '0xa9059cbb';
        const valueInWei = (amount * 1e18).toString(16).padStart(64, '0');
        const recipient = to.toLowerCase().replace(/^0x/, '').padStart(64, '0');
        return `${erc20TransferMethodId}${recipient}${valueInWei}`;
    }

    function showMessage(message, type) {
        const messagesContainer = document.getElementById('messages');
        messagesContainer.innerHTML = '';
        const messageElement = document.createElement('div');
        messageElement.className = `alert alert-${type} py-0 mb-3`;
        messageElement.textContent = message;
        messagesContainer.appendChild(messageElement);
    }
});

    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
