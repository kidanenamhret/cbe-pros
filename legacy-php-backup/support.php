<?php
require_once 'includes/header.php';

// Fetch users tickets
try {
    $stmt = $conn->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch tickets: " . $e->getMessage());
    $tickets = [];
}
?>

<div class="section active" style="display:block;">
    <div id="support-section" class="section">
        <div class="welcome-header">
            <div>
                <h2>Help & Support Hub</h2>
                <p>Submit tickets and track our support responses.</p>
            </div>
            <button class="btn" style="width: auto;" onclick="openNewTicketModal()">
                <i class="fas fa-plus"></i> Create Ticket
            </button>
        </div>

        <div class="transactions-section" style="margin-top: 25px;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="ticketList">
                        <?php if (empty($tickets)): ?>
                        <tr><td colspan="7" style="text-align: center;">No support tickets found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo $ticket['id']; ?></td>
                                <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                <td><?php echo $ticket['category']; ?></td>
                                <td><span class="badge-<?php echo ($ticket['priority'] == 'high') ? 'danger' : 'info'; ?>"><?php echo $ticket['priority']; ?></span></td>
                                <td><span class="badge-<?php echo ($ticket['status'] == 'open') ? 'success' : 'secondary'; ?>"><?php echo $ticket['status']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></td>
                                <td>
                                    <button class="btn" style="padding: 5px 10px; font-size: 12px;" onclick="viewTicketThread(<?php echo $ticket['id']; ?>, '<?php echo htmlspecialchars($ticket['subject']); ?>')">
                                        <i class="fas fa-comments"></i> Chat
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- New Ticket Modal -->
<div id="newTicketModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1001; justify-content: center; align-items: center;">
    <div class="glass-container" style="max-width: 500px; padding: 30px;">
        <h3 style="margin-bottom: 20px;">Open Support Ticket</h3>
        <form id="newTicketForm" onsubmit="handleNewTicket(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group">
                <label>Ticket Subject (Summarize the issue)</label>
                <input type="text" name="subject" required placeholder="What's bothering you?">
            </div>
            <div class="form-group">
                <label>Issue Category</label>
                <select name="category" required>
                    <option value="Account Issue">Account Issue</option>
                    <option value="Transaction Problem">Transaction Problem</option>
                    <option value="Loan Query">Loan Query</option>
                    <option value="Forex Help">Forex Help</option>
                    <option value="General Feedback">General Feedback</option>
                </select>
            </div>
            <div class="form-group">
                <label>Urgency Level</label>
                <select name="priority">
                    <option value="low">Low - Routine inquiry</option>
                    <option value="medium">Medium - Important</option>
                    <option value="high">High - Immediate attention!</option>
                </select>
            </div>
            <div class="form-group">
                <label>Detailed Message</label>
                <textarea name="message" rows="4" required placeholder="Explain clearly..."></textarea>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn">Submit Ticket</button>
                <button type="button" class="btn" style="background: rgba(255,255,255,0.1);" onclick="closeNewTicketModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Ticket Chat Modal -->
<div id="chatModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1001; justify-content: center; align-items: center;">
    <div class="glass-container" style="max-width: 600px; height: 80vh; display: flex; flex-direction: column; padding: 0; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.3);">
            <h3 id="chatModalTitle" style="color: white; margin: 0;">Ticket Chat</h3>
            <button onclick="closeChatModal()" style="background: var(--primary); border: none; color: white; cursor: pointer; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="chatThread" style="flex: 1; padding: 20px; overflow-y: auto; background: rgba(0,0,0,0.2);">
            <!-- Messages go here -->
        </div>
        <div style="padding: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <form id="replyForm" style="display: flex; gap: 10px;" onsubmit="handleSendReply(event)">
                <input type="hidden" name="ticket_id" id="chatTicketId">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="text" name="message" id="replyMessage" required placeholder="Type your reply..." style="flex: 1; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 8px; padding: 10px;">
                <button type="submit" class="btn" style="width: auto;"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </div>
</div>

<script>
function openNewTicketModal() { document.getElementById('newTicketModal').style.display = 'flex'; }
function closeNewTicketModal() { document.getElementById('newTicketModal').style.display = 'none'; }
function closeChatModal() { document.getElementById('chatModal').style.display = 'none'; }

async function handleNewTicket(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const response = await fetch('php/create_ticket.php', { method: 'POST', body: formData });
    const result = await response.json();
    if(result.success) location.reload(); else alert(result.message);
}

async function viewTicketThread(id, subject) {
    document.getElementById('chatTicketId').value = id;
    document.getElementById('chatModalTitle').innerText = "Chatting about: " + subject;
    document.getElementById('chatModal').style.display = 'flex';
    fetchMessages(id);
}

async function fetchMessages(ticketId) {
    const response = await fetch('php/get_ticket_messages.php?ticket_id=' + ticketId);
    const messages = await response.json();
    const container = document.getElementById('chatThread');
    container.innerHTML = '';
    
    messages.forEach(m => {
        const isMe = (m.sender_role === 'user');
        const html = `
            <div style="margin-bottom: 20px; display: flex; flex-direction: column; align-items: ${isMe ? 'flex-end' : 'flex-start'}">
                <div style="max-width: 80%; padding: 12px 16px; border-radius: 12px; background: ${isMe ? 'var(--primary)' : 'rgba(255,255,255,0.1)'}; color: white;">
                    ${m.message}
                </div>
                <small style="margin-top: 5px; opacity: 0.6; font-size: 10px;">${m.sender_role.toUpperCase()} • ${m.created_at}</small>
            </div>
        `;
        container.innerHTML += html;
    });
    container.scrollTop = container.scrollHeight;
}

async function handleSendReply(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const response = await fetch('php/send_ticket_reply.php', { method: 'POST', body: formData });
    const result = await response.json();
    if(result.success) {
        document.getElementById('replyMessage').value = '';
        fetchMessages(document.getElementById('chatTicketId').value);
    } else {
        alert(result.message);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
