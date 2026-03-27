<?php
require_once 'includes/header.php';

// Fetch users goals
try {
    $stmt = $conn->prepare("SELECT * FROM savings_goals WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch goals: " . $e->getMessage());
    $goals = [];
}
?>

<div class="section active" style="display:block;">
    <div id="goals-section" class="section">
        <div class="welcome-header">
            <div>
                <h2>Savings Goals</h2>
                <p>Visualize your financial dreams and track your progress.</p>
            </div>
            <button class="btn" style="width: auto;" onclick="openAddGoalModal()">
                <i class="fas fa-plus"></i> New Goal
            </button>
        </div>

        <div class="goals-grid" id="goalsGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 25px;">
            <?php if (empty($goals)): ?>
                <div class="account-card" style="grid-column: 1/-1; text-align: center; padding: 40px;">
                    <i class="fas fa-bullseye" style="font-size: 50px; color: var(--text-dim); margin-bottom: 20px;"></i>
                    <h3>No goals set yet</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 20px;">Setting goals is the first step toward financial freedom.</p>
                    <button class="btn" style="width: auto; margin: 0 auto;" onclick="openAddGoalModal()">Create Your First Goal</button>
                </div>
            <?php else: ?>
                <?php foreach ($goals as $goal): 
                    $progress = ($goal['target_amount'] > 0) ? min(100, ($goal['current_amount'] / $goal['target_amount']) * 100) : 0;
                    $remaining = max(0, $goal['target_amount'] - $goal['current_amount']);
                ?>
                    <div class="account-card goal-card" id="goal-<?php echo $goal['id']; ?>">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <span class="badge-success"><?php echo ucfirst($goal['status']); ?></span>
                            <div class="dropdown">
                                <button class="btn-icon" style="background: none; border: none; color: white;"><i class="fas fa-ellipsis-v"></i></button>
                            </div>
                        </div>
                        <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($goal['title']); ?></h3>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="font-size: 14px; opacity: 0.8;">Target: ETB <?php echo number_format($goal['target_amount'], 2); ?></span>
                            <span style="color: var(--success); font-weight: 600;"><?php echo round($progress); ?>%</span>
                        </div>
                        <div class="progress-container" style="height: 10px; background: rgba(255,255,255,0.1); border-radius: 5px; overflow: hidden; margin-bottom: 15px;">
                            <div class="progress-bar" style="width: <?php echo $progress; ?>%; height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); transition: width 1s ease;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 20px; font-weight: 700;">ETB <?php echo number_format($goal['current_amount'], 2); ?></div>
                                <div style="font-size: 12px; opacity: 0.6;">Saved so far</div>
                            </div>
                            <button class="btn" style="width: auto; padding: 10px 15px; background: rgba(255,255,255,0.1);" onclick="openAddSavingsModal(<?php echo $goal['id']; ?>, '<?php echo htmlspecialchars($goal['title']); ?>')">
                                <i class="fas fa-coins"></i> Add Savings
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Goal Modal -->
<div id="addGoalModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1001; justify-content: center; align-items: center;">
    <div class="glass-container" style="max-width: 450px; padding: 30px; position: relative;">
        <button onclick="closeAddGoalModal()" style="position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.1); border: none; color: white; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fas fa-times"></i></button>
        <h3 style="margin-bottom: 20px;">Create Brand New Goal</h3>
        <form id="addGoalForm" onsubmit="handleNewGoal(event)">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group">
                <label>Goal Title (e.g. New House, Dream Wedding)</label>
                <input type="text" name="title" required placeholder="What are you saving for?">
            </div>
            <div class="form-group">
                <label>Target Amount (ETB)</label>
                <input type="number" name="target" step="100" min="100" required placeholder="How much do you need?">
            </div>
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn">Start The Journey</button>
                <button type="button" class="btn" style="background: rgba(255,255,255,0.1);" onclick="closeAddGoalModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Savings Modal -->
<div id="addSavingsModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1001; justify-content: center; align-items: center;">
    <div class="glass-container" style="max-width: 450px; padding: 30px; position: relative;">
        <button onclick="closeAddSavingsModal()" style="position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.1); border: none; color: white; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fas fa-times"></i></button>
        <h3 style="margin-bottom: 10px;" id="savingGoalTitle">Add Savings</h3>
        <p style="color: var(--text-secondary); margin-bottom: 20px;">Allocate funds from your primary account to this goal.</p>
        <form id="addSavingsForm" onsubmit="handleContribution(event)">
            <input type="hidden" name="goal_id" id="contributionGoalId">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label>Source Account</label>
                <select name="source_account" required>
                    <?php foreach ($user_accounts as $account): ?>
                        <?php if($account['currency'] == 'ETB'): ?>
                        <option value="<?php echo $account['account_number']; ?>">
                            <?php echo ucfirst($account['account_type']); ?> - 
                            <?php echo substr($account['account_number'], -4); ?> 
                            (ETB <?php echo number_format($account['balance'], 2); ?>)
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Amount to Contribute (ETB)</label>
                <input type="number" name="amount" step="1" min="1" required placeholder="Enter amount">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <button type="submit" class="btn">Push Savings</button>
                <button type="button" class="btn" style="background: rgba(255,255,255,0.1);" onclick="closeAddSavingsModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddGoalModal() {
    document.getElementById('addGoalModal').style.display = 'flex';
}

function closeAddGoalModal() {
    document.getElementById('addGoalModal').style.display = 'none';
}

function openAddSavingsModal(id, title) {
    document.getElementById('contributionGoalId').value = id;
    document.getElementById('savingGoalTitle').innerText = "Add Savings to " + title;
    document.getElementById('addSavingsModal').style.display = 'flex';
}

function closeAddSavingsModal() {
    document.getElementById('addSavingsModal').style.display = 'none';
}

async function handleNewGoal(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('php/add_goal.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert(result.message);
        }
    } catch (e) {
        alert("Operation failed, check server connection.");
    }
}

async function handleContribution(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('php/contribute_goal.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert(result.message);
        }
    } catch (e) {
        alert("Operation failed.");
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
