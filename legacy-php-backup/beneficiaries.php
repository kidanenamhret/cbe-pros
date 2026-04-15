<?php
require_once 'includes/header.php';
?>
<div class="section active" style="display:block;">

            <div id="beneficiaries-section" class="section">
                <div class="transactions-section">
                    <h3 style="margin-bottom: 20px;">My Beneficiaries</h3>
                    <div id="beneficiariesList"
                        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            
</div>
<?php
require_once 'includes/footer.php';
?>