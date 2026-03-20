<?php
require_once 'php/auth.php';
checkLogin();
require_once 'php/db.php';

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBE-Pros | Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body style="display: block; overflow: auto; height: auto;">
    <div class="dashboard-layout">
        <aside class="sidebar">
            <h2
                style="margin-bottom: 30px; background: linear-gradient(to right, #FFF, #FFD700); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                CBE-PROS</h2>
            <nav>
                <div style="margin-bottom: 15px;"><a href="#"
                        style="color: #FFF; text-decoration: none; font-weight: 500;">Overview</a></div>
                <div style="margin-bottom: 15px;"><a href="#transfer"
                        style="color: var(--text-dim); text-decoration: none;">Transfer</a></div>
                <div style="margin-top: 50px;"><a href="php/logout.php"
                        style="color: var(--error); text-decoration: none;">Logout</a></div>
            </nav>
        </aside>

        <main class="main-content">
            <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
                <div class="user-info">
                    <span style="color: var(--secondary);">Gold Member</span>
                </div>
            </header>

            <section class="overview-cards"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div class="card">
                    <h3 style="color: var(--text-dim); font-size: 0.9rem; margin-bottom: 10px;">Account Number</h3>
                    <p id="acc-number" style="font-size: 1.5rem; font-weight: 700;">Loading...</p>
                </div>
                <div class="card">
                    <h3 style="color: var(--text-dim); font-size: 0.9rem; margin-bottom: 10px;">Current Balance</h3>
                    <p id="balance" style="font-size: 1.8rem; font-weight: 700;">ETB 0.00</p>
                </div>
            </section>

            <section id="transfer" style="margin-top: 40px;">
                <h2 style="font-size: 1.3rem; margin-bottom: 20px; color: #FFF;">Direct Transfer</h2>
                <div class="card">
                    <form id="transfer-form">
                        <div class="input-group">
                            <label>Recipient Username or Email</label>
                            <input type="text" name="receiver_username" placeholder="Enter username/email" required>
                        </div>
                        <div class="input-group">
                            <label>Amount (ETB)</label>
                            <input type="number" name="amount" placeholder="0.00" required step="0.01">
                        </div>
                        <button type="submit" class="btn">Send Money</button>
                    </form>
                    <div id="transfer-msg" style="margin-top: 15px; font-size: 0.9rem;"></div>
                </div>
            </section>

            <section style="margin-top: 40px;">
                <h2 style="font-size: 1.3rem; margin-bottom: 20px; color: #FFF;">Recent Activity</h2>
                <div class="card" style="padding: 0;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead style="background: rgba(255,255,255,0.05);">
                            <tr>
                                <th style="padding: 15px; color: var(--text-dim);">Date</th>
                                <th style="padding: 15px; color: var(--text-dim);">Type</th>
                                <th style="padding: 15px; color: var(--text-dim);">Party</th>
                                <th style="padding: 15px; color: var(--text-dim);">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="transaction-list">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        async function loadData() {
            try {
                const response = await fetch('php/get_data.php');
                const result = await response.json();
                if (result.status === 'success') {
                    document.getElementById('acc-number').innerText = result.data.account_number;
                    document.getElementById('balance').innerText = 'ETB ' + parseFloat(result.data.balance).toLocaleString();

                    const list = document.getElementById('transaction-list');
                    list.innerHTML = '';
                    result.data.transactions.forEach(t => {
                        const amountColor = t.entry_type === 'Debit' ? 'var(--error)' : 'var(--success)';
                        const prefix = t.entry_type === 'Debit' ? '-' : '+';
                        list.innerHTML += `
                            <tr style="border-bottom: 1px solid var(--glass-border);">
                                <td style="padding: 15px;">${t.created_at.split(' ')[0]}</td>
                                <td style="padding: 15px;">${t.type}</td>
                                <td style="padding: 15px;">${t.other_party || 'N/A'}</td>
                                <td style="padding: 15px; color: ${amountColor}">${prefix} ETB ${Math.abs(t.amount)}</td>
                            </tr>
                        `;
                    });
                }
            } catch (error) {
                console.error('Error loading data:', error);
            }
        }

        document.getElementById('transfer-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const msgDiv = document.getElementById('transfer-msg');
            msgDiv.innerText = 'Processing...';
            msgDiv.style.color = 'var(--text-dim)';

            try {
                const response = await fetch('php/transfer.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                msgDiv.innerText = result.message;
                msgDiv.style.color = result.status === 'success' ? 'var(--success)' : 'var(--error)';
                if (result.status === 'success') {
                    e.target.reset();
                    loadData();
                }
            } catch (error) {
                msgDiv.innerText = 'An error occurred.';
                msgDiv.style.color = 'var(--error)';
            }
        });

        loadData();
    </script>
</body>

</html>