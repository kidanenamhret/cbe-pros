// Global variables
        let currentPage = 1;
        let loadingMore = false;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function () {
            loadDashboardData();
            loadNotifications();
            loadScheduledTransfers();
            loadBeneficiaries();
            loadUserProfile();
            
            if (document.getElementById('allTransactionsList')) {
                loadAllTransactions();
            }

            // Refresh data every 30 seconds
            setInterval(loadDashboardData, 30000);
            setInterval(loadNotifications, 30000);
        });

        // Show selected section
        function showSection(section, ev) {
            const e = ev || window.event;
            if (e && e.preventDefault) e.preventDefault();

            // Update active state in sidebar
            document.querySelectorAll('.sidebar nav a').forEach(link => {
                link.classList.remove('active');
            });
            
            // Find the correct sidebar link and activate it
            const sidebarLink = document.querySelector(`.sidebar nav a[onclick*="'${section}'"]`);
            if (sidebarLink) {
                sidebarLink.classList.add('active');
            }

            // Hide all sections
            document.querySelectorAll('.section').forEach(s => {
                s.classList.remove('active');
            });
            
            // Show the target section
            const targetSection = document.getElementById(section + '-section');
            if (targetSection) {
                targetSection.classList.add('active');
            }

            // Load section-specific data
            if (section === 'transactions') {
                loadAllTransactions();
            } else if (section === 'beneficiaries') {
                loadBeneficiaries();
            }
        }

        // Show transfer tab
        function showTransferTab(tab, ev) {
            const e = ev || window.event;
            if (e && e.preventDefault) e.preventDefault();
            
            document.querySelectorAll('.transfer-tab').forEach(t => {
                t.classList.remove('active');
            });
            
            const targetTab = document.querySelector(`.transfer-tab[onclick*="'${tab}'"]`);
            if (targetTab) {
                targetTab.classList.add('active');
            }

            document.querySelectorAll('.transfer-content').forEach(c => {
                c.classList.remove('active');
            });
            
            const targetContent = document.getElementById('transfer-' + tab);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        }

        // Load dashboard data
        async function loadDashboardData() {
            try {
                const response = await fetch('php/get_data.php');
                const result = await response.json();

                if (result.status === 'success') {
                    updateAccountsGrid(result.data.accounts);
                    updateRecentTransactions(result.data.recent_transactions);
                    updateSummary(result.data.summary);
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        // Update accounts grid
        function updateAccountsGrid(accounts) {
            const grid = document.getElementById('accountsGrid');
            const gridFull = document.getElementById('accountsGridFull');

            if (grid) {
                let html = '';
                accounts.forEach((account, index) => {
                    const cardClass = index === 0 ? 'account-card primary' : 'account-card';
                    html += `
                        <div class="${cardClass}" onclick="viewAccountDetails('${account.account_number}')">
                            <div class="account-header">
                                <span class="account-type">${account.account_type}</span>
                                <span class="account-status status-${account.status}">${account.status}</span>
                            </div>
                            <div class="account-number">${maskAccountNumber(account.account_number)}</div>
                            <div class="account-balance">${formatCurrency(account.balance)} <span class="account-currency">${account.currency}</span></div>
                        </div>
                    `;
                });
                grid.innerHTML = html;
            }

            if (gridFull) {
                let htmlFull = '';
                accounts.forEach((account, index) => {
                    const cardClass = index === 0 ? 'account-card primary' : 'account-card';
                    htmlFull += `
                        <div class="${cardClass}" onclick="viewAccountDetails('${account.account_number}')">
                            <div class="account-header">
                                <span class="account-type">${account.account_type}</span>
                                <span class="account-status status-${account.status}">${account.status}</span>
                            </div>
                            <div class="account-number" style="letter-spacing: 2px;">${account.account_number}</div>
                            <div class="account-balance">${formatCurrency(account.balance)} <span class="account-currency">${account.currency}</span></div>
                        </div>
                    `;
                });
                gridFull.innerHTML = htmlFull;
            }
        }

        // Update recent transactions
        function updateRecentTransactions(transactions) {
            const list = document.getElementById('recentTransactionsList');

            if (!transactions || transactions.length === 0) {
                list.innerHTML = '<tr><td colspan="5" style="text-align: center;">No recent transactions</td></tr>';
                return;
            }

            list.innerHTML = '';
            transactions.slice(0, 5).forEach(t => {
                const amountClass = t.entry_type === 'Credit' ? 'transaction-credit' : 'transaction-debit';
                const amountPrefix = t.entry_type === 'Credit' ? '+' : '-';
                
                let descText = t.description || 'Transfer';
                if (t.other_party && t.other_party !== 'Unknown' && t.other_party !== 'System' && t.type === 'transfer') {
                    descText = (t.entry_type === 'Credit' ? 'From: ' : 'To: ') + t.other_party;
                }

                list.innerHTML += `
                    <tr>
                        <td>${t.date}</td>
                        <td>${descText}</td>
                        <td><small>${t.reference || 'N/A'}</small></td>
                        <td class="${amountClass}">${amountPrefix} ${formatCurrency(t.amount)}</td>
                        <td><span class="badge-${t.status_color}">${t.status}</span></td>
                    </tr>
                `;
            });
        }

        // Update summary
        function updateSummary(summary) {
            document.getElementById('totalBalance').textContent = formatCurrency(summary.total_balance);
            document.getElementById('activeAccounts').textContent = summary.active_accounts;
        }

        // Load all transactions with filters
        async function loadAllTransactions(reset = true) {
            if (reset) {
                currentPage = 1;
                const _el_allTransactionsList = document.getElementById('allTransactionsList');
                if (_el_allTransactionsList) _el_allTransactionsList.innerHTML = '<tr><td colspan="8" style="text-align: center;">Loading...</td></tr>';
            }

            const typeEl = document.getElementById('transactionTypeFilter');
            const type = typeEl ? typeEl.value : 'all';
            const searchEl = document.getElementById('transactionSearch');
            const search = searchEl ? searchEl.value : '';
            const dateEl = document.getElementById('transactionDate');
            const date = dateEl ? dateEl.value : '';

            try {
                const response = await fetch(`php/get_transactions.php?page=${currentPage}&type=${type}&search=${encodeURIComponent(search)}&date=${date}`);
                const result = await response.json();

                if (result.status === 'success') {
                    displayTransactions(result.data.transactions, reset);

                    if (result.data.has_more) {
                        document.getElementById('loadMoreBtn').style.display = 'inline-block';
                    } else {
                        document.getElementById('loadMoreBtn').style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
            }
        }

        // Display transactions
        function displayTransactions(transactions, reset) {
            const list = document.getElementById('allTransactionsList');

            if (reset) {
                list.innerHTML = '';
            }

            if (!transactions || transactions.length === 0) {
                if (reset) {
                    list.innerHTML = '<tr><td colspan="8" style="text-align: center;">No transactions found</td></tr>';
                }
                return;
            }

            transactions.forEach(t => {
                const amountClass = t.entry_type === 'Credit' ? 'transaction-credit' : 'transaction-debit';
                const amountPrefix = t.entry_type === 'Credit' ? '+' : '-';
                
                let descText = t.description || '-';
                if (t.other_party && t.other_party !== 'System' && t.type === 'transfer') {
                    descText = (t.entry_type === 'Credit' ? 'From: ' : 'To: ') + t.other_party;
                }

                list.innerHTML += `
                    <tr>
                        <td>${t.date} ${t.time}</td>
                        <td><small>${t.reference || 'N/A'}</small></td>
                        <td>${descText}</td>
                        <td>${t.type}</td>
                        <td class="${amountClass}">${amountPrefix} ${formatCurrency(t.amount)}</td>
                        <td>${t.fee ? formatCurrency(t.fee) : '-'}</td>
                        <td>${t.balance_after ? formatCurrency(t.balance_after) : '-'}</td>
                        <td><span class="badge-${t.status_color}">${t.status}</span></td>
                    </tr>
                `;
            });
        }

        // Load more transactions
        function loadMoreTransactions() {
            if (!loadingMore) {
                loadingMore = true;
                currentPage++;
                loadAllTransactions(false).then(() => {
                    loadingMore = false;
                });
            }
        }

        // Load scheduled transfers
        async function loadScheduledTransfers() {
            try {
                const response = await fetch('php/get_scheduled_transfers.php');
                const result = await response.json();

                const list = document.getElementById('scheduledTransfersList');

                if (!result.data || result.data.length === 0) {
                    list.innerHTML = '<tr><td colspan="5" style="text-align: center;">No scheduled transfers</td></tr>';
                    return;
                }

                list.innerHTML = '';
                result.data.forEach(t => {
                    list.innerHTML += `
                        <tr>
                            <td>${t.scheduled_date}</td>
                            <td>${t.to_name || t.to_account}</td>
                            <td>${formatCurrency(t.amount)}</td>
                            <td><span class="badge-${t.status === 'pending' ? 'warning' : 'success'}">${t.status}</span></td>
                            <td>
                                ${t.status === 'pending' ?
                            `<button onclick="cancelScheduledTransfer(${t.id})" style="background: none; border: none; color: var(--error); cursor: pointer;">
                                        <i class="fas fa-times"></i>
                                    </button>` : ''}
                            </td>
                        </tr>
                    `;
                });
            } catch (error) {
                console.error('Error loading scheduled transfers:', error);
            }
        }

        // Load beneficiaries
        async function loadBeneficiaries() {
            try {
                const response = await fetch('php/get_beneficiaries.php');
                const result = await response.json();

                const list = document.getElementById('beneficiariesList');

                if (!result.data || result.data.length === 0) {
                    list.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">No saved beneficiaries</p>';
                    return;
                }

                list.innerHTML = '';
                result.data.forEach(b => {
                    list.innerHTML += `
                        <div class="account-card" style="cursor: default;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                <i class="fas fa-user-circle" style="font-size: 40px; color: var(--primary);"></i>
                                <button onclick="removeBeneficiary(${b.id})" style="background: none; border: none; color: var(--error); cursor: pointer;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div style="font-weight: 600; margin-bottom: 5px;">${b.name || 'Unknown'}</div>
                            <div style="font-size: 14px; color: var(--text-secondary);">${maskAccountNumber(b.account)}</div>
                            <button onclick="useBeneficiary('${b.account}')" class="btn" style="margin-top: 15px; padding: 8px;">Send Money</button>
                        </div>
                    `;
                });
            } catch (error) {
                console.error('Error loading beneficiaries:', error);
            }
        }

        // Load user profile
        async function loadUserProfile() {
            try {
                const response = await fetch('php/get_profile.php');
                const result = await response.json();

                if (result.status === 'success') {
                    document.getElementById('profileFullname').value = result.data.fullname;
                    document.getElementById('profileEmail').value = result.data.email;
                    document.getElementById('profilePhone').value = result.data.phone || 'Not set';
                    document.getElementById('profileMemberSince').value = result.data.member_since;
                }
            } catch (error) {
                console.error('Error loading profile:', error);
            }
        }

        // Load notifications
        async function loadNotifications() {
            try {
                const response = await fetch('php/get_notifications.php');
                const result = await response.json();

                // Update badge
                document.getElementById('notificationCount').textContent = result.data.unread_count;

                // Update panel
                const list = document.getElementById('notificationsList');

                if (!result.data.notifications || result.data.notifications.length === 0) {
                    list.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 20px;">No notifications</p>';
                    return;
                }

                list.innerHTML = '';
                result.data.notifications.forEach(n => {
                    const unreadClass = !n.is_read ? 'unread' : '';
                    list.innerHTML += `
                        <div class="notification-item ${unreadClass}" onclick="markNotificationRead(${n.id})">
                            <div class="notification-title">${n.title}</div>
                            <div style="font-size: 13px; margin-bottom: 5px;">${n.message}</div>
                            <div class="notification-meta">${n.created_at}</div>
                        </div>
                    `;
                });
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        }

        // Handle transfer submission
        async function handleTransfer(event, type) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);
            formData.append('transfer_type', type);

            const btn = form.querySelector('button[type="submit"]');
            const messageDiv = document.getElementById('transferMessage');

            btn.disabled = true;
            btn.innerHTML = '<span class="loader"></span> Processing...';
            messageDiv.innerHTML = '';

            try {
                const response = await fetch('php/transfer.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showMessage(messageDiv, result.message, 'success');
                    form.reset();
                    loadDashboardData();
                    loadBeneficiaries();
                    loadAllTransactions(true);

                    // Show success notification
                    alert('Transfer completed successfully!\nReference: ' + result.data.reference);
                } else {
                    showMessage(messageDiv, result.message, 'error');
                }
            } catch (error) {
                showMessage(messageDiv, 'An error occurred. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Send Money';
            }
        }

        // Handle scheduled transfer
        async function handleScheduledTransfer(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);
            formData.append('is_scheduled', 'true');

            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="loader"></span> Scheduling...';

            try {
                const response = await fetch('php/transfer.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    alert('Transfer scheduled successfully!\nReference: ' + result.data.reference);
                    form.reset();
                    loadScheduledTransfers();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Schedule Transfer';
            }
        }

        // Calculate fees
        function calculateFees() {
            const amount = parseFloat(document.querySelector('input[name="amount"]').value) || 0;
            const urgent = document.getElementById('urgentTransfer').checked;

            if (amount <= 0) {
                document.getElementById('feeCalculator').style.display = 'none';
                return;
            }

            document.getElementById('feeCalculator').style.display = 'block';

            // Base fee
            const baseFee = 5;

            // Percentage fee (0.5% for amounts over 1000, max 100)
            let percentageFee = 0;
            if (amount > 1000) {
                percentageFee = Math.min(amount * 0.005, 100);
            }

            // Urgent fee (1% for urgent, max 200)
            let urgentFee = 0;
            if (urgent) {
                urgentFee = Math.min(amount * 0.01, 200);
            }

            const totalFee = baseFee + percentageFee + urgentFee;
            const totalDeduction = amount + totalFee;

            document.getElementById('baseFee').textContent = formatCurrency(baseFee);
            document.getElementById('percentageFee').textContent = formatCurrency(percentageFee);
            document.getElementById('urgentFee').textContent = formatCurrency(urgentFee);
            document.getElementById('totalFee').textContent = formatCurrency(totalFee);
            document.getElementById('totalDeduction').textContent = formatCurrency(totalDeduction);
        }

        // Toggle notifications panel
        function toggleNotifications() {
            const panel = document.getElementById('notificationsPanel');
            panel.classList.toggle('open');
        }

        // Mark notification as read
        async function markNotificationRead(id) {
            try {
                await fetch('php/mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });
                loadNotifications();
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }

        // Cancel scheduled transfer
        async function cancelScheduledTransfer(id) {
            if (!confirm('Are you sure you want to cancel this scheduled transfer?')) {
                return;
            }

            try {
                const response = await fetch('php/cancel_scheduled.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    alert('Scheduled transfer cancelled');
                    loadScheduledTransfers();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('An error occurred');
            }
        }

        // Remove beneficiary
        async function removeBeneficiary(id) {
            if (!confirm('Are you sure you want to remove this beneficiary?')) {
                return;
            }

            try {
                const response = await fetch('php/remove_beneficiary.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    loadBeneficiaries();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('An error occurred');
            }
        }

        // Use beneficiary
        function useBeneficiary(account) {
            showSection('transfer');
            showTransferTab('account');
            document.querySelector('input[name="receiver_account"]').value = account;
        }

        // View account details
        function viewAccountDetails(accountNumber) {
            // Implement account details view
            alert('Viewing account: ' + maskAccountNumber(accountNumber));
        }

        // Update profile
        async function updateProfile(event) {
            event.preventDefault();
            // Implement profile update
        }

        // Change password
        function changePassword() {
            // Implement password change
            alert('Password change functionality will be implemented here');
        }

        // Enable 2FA
        function enable2FA() {
            // Implement 2FA setup
            alert('2FA setup will be implemented here');
        }

        // Save preferences
        function savePreferences() {
            // Implement preferences save
            alert('Preferences saved');
        }

        // Upload profile image
        async function uploadProfileImage(input) {
            if (!input.files || !input.files[0]) return;
            
            const messageDiv = document.getElementById('uploadMessage');
            messageDiv.innerHTML = '<span class="loader" style="width: 12px; height: 12px; border-width: 2px;"></span> Uploading...';
            messageDiv.style.color = 'var(--text-secondary)';
            
            const formData = new FormData();
            formData.append('profile_image', input.files[0]);
            
            try {
                const response = await fetch('php/upload_profile.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    messageDiv.innerHTML = result.message;
                    messageDiv.style.color = 'var(--success)';
                    
                    // Update the settings page preview
                    const avatarContainer = document.querySelector('.avatar-large');
                    avatarContainer.innerHTML = `<img src="${result.image_url}?t=${new Date().getTime()}" style="width: 100%; height: 100%; object-fit: cover;" id="settingsAvatarPreview">`;
                    
                    // Update the top header avatar
                    const headerAvatar = document.querySelector('.header-right .avatar');
                    if (headerAvatar) {
                        headerAvatar.innerHTML = `<img src="${result.image_url}?t=${new Date().getTime()}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">`;
                    }
                } else {
                    messageDiv.innerHTML = result.message;
                    messageDiv.style.color = 'var(--error)';
                }
            } catch (error) {
                console.error('Error uploading image:', error);
                messageDiv.innerHTML = 'An unexpected error occurred.';
                messageDiv.style.color = 'var(--error)';
            }
            
            // Clear input
            input.value = '';
        }

        // Show message helper
        function showMessage(element, message, type) {
            element.innerHTML = message;
            element.style.color = type === 'success' ? 'var(--success)' : 'var(--error)';
            setTimeout(() => {
                element.innerHTML = '';
            }, 5000);
        }

        // Format currency helper
        function formatCurrency(amount) {
            return 'ETB ' + parseFloat(amount).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Mask account number helper
        function maskAccountNumber(number) {
            return '****' + number.slice(-4);
        }