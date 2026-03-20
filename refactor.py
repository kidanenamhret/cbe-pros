import os
import re

with open('dashboard.php', 'r', encoding='utf-8') as f:
    text = f.read()

# Make directories
os.makedirs('css', exist_ok=True)
os.makedirs('js', exist_ok=True)
os.makedirs('includes', exist_ok=True)

# 1. Extract CSS
css_match = re.search(r'<style>(.*?)</style>', text, re.DOTALL)
css_content = css_match.group(1).strip()
with open('css/dashboard.css', 'w', encoding='utf-8') as f:
    f.write(css_content)

# 2. Extract JS
js_match = re.search(r'<script>(.*?)</script>(.*?</body>)', text, re.DOTALL)
js_content = js_match.group(1).strip()
tail_html = js_match.group(2).strip()

# Replace innerHTML with safe checks in JS (quick and dirty safe assignment for grid elements)
js_content = re.sub(
    r'(document\.getElementById\(([\'"])(.*?)\2\)\.innerHTML\s*=)',
    r'const _el_\3 = document.getElementById(\2\3\2);\n                if (_el_\3) _el_\3.innerHTML =',
    js_content
)
# Safely do event listeners (like form submit)
js_content = re.sub(
    r'document\.getElementById\(([\'"])(.*?)\1\)\.addEventListener',
    r'const _form_\2 = document.getElementById(\1\2\1);\n        if (_form_\2) _form_\2.addEventListener',
    js_content
)
# Make sure we don't declare the same constants twice
js_content = js_content.replace('const _form_transferUsernameForm =', 'let _form_transferUsernameForm =', 1).replace('const _form_transferUsernameForm =', '_form_transferUsernameForm =')
js_content = js_content.replace('const _form_transferAccountForm =', 'let _form_transferAccountForm =', 1).replace('const _form_transferAccountForm =', '_form_transferAccountForm =')
js_content = js_content.replace('const _form_transferPhoneForm =', 'let _form_transferPhoneForm =', 1).replace('const _form_transferPhoneForm =', '_form_transferPhoneForm =')
js_content = js_content.replace('const _form_scheduledTransferForm =', 'let _form_scheduledTransferForm =', 1).replace('const _form_scheduledTransferForm =', '_form_scheduledTransferForm =')
js_content = js_content.replace('const _form_editProfileForm =', 'let _form_editProfileForm =', 1).replace('const _form_editProfileForm =', '_form_editProfileForm =')
js_content = js_content.replace('const _form_changePasswordForm =', 'let _form_changePasswordForm =', 1).replace('const _form_changePasswordForm =', '_form_changePasswordForm =')

# Replace PHP tags in JS with global variables
js_content = js_content.replace("const csrfToken = '<?php echo $csrf_token; ?>';", "const csrfToken = window.CSRF_TOKEN;")
js_content = js_content.replace("const userId = <?php echo $user_id; ?>;", "const userId = window.USER_ID;")

with open('js/dashboard.js', 'w', encoding='utf-8') as f:
    f.write(js_content)

# 3. Parse Sections
# The top PHP part before HTML
head_php = re.search(r'(<\?php.*?\?>).*?<!DOCTYPE', text, re.DOTALL).group(1)

html_head = re.search(r'(<!DOCTYPE.*?</head>)', text, re.DOTALL).group(1)
# Remove the <style> tag from html_head and link the CSS
html_head = re.sub(r'<style>.*?</style>', '<link rel="stylesheet" href="css/dashboard.css">', html_head, flags=re.DOTALL)

sidebar = re.search(r'(<aside class="sidebar">.*?</aside>)', text, re.DOTALL).group(1)
# Fix sidebar links
sidebar = sidebar.replace("href=\"javascript:void(0)\" class=\"active\" onclick=\"showSection('overview', event)\"", "href=\"dashboard.php\" class=\"<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>\"")
sidebar = sidebar.replace("href=\"javascript:void(0)\" onclick=\"showSection('transfer', event)\"", "href=\"transfer.php\" class=\"<?php echo $current_page == 'transfer.php' ? 'active' : ''; ?>\"")
sidebar = sidebar.replace("href=\"javascript:void(0)\" onclick=\"showSection('accounts', event)\"", "href=\"accounts.php\" class=\"<?php echo $current_page == 'accounts.php' ? 'active' : ''; ?>\"")
sidebar = sidebar.replace("href=\"javascript:void(0)\" onclick=\"showSection('transactions', event)\"", "href=\"transactions.php\" class=\"<?php echo $current_page == 'transactions.php' ? 'active' : ''; ?>\"")
sidebar = sidebar.replace("href=\"javascript:void(0)\" onclick=\"showSection('beneficiaries', event)\"", "href=\"beneficiaries.php\" class=\"<?php echo $current_page == 'beneficiaries.php' ? 'active' : ''; ?>\"")
sidebar = sidebar.replace("href=\"javascript:void(0)\" onclick=\"showSection('settings', event)\"", "href=\"settings.php\" class=\"<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>\"")

top_header = re.search(r'(<header class="dashboard-header">.*?</header>)', text, re.DOTALL).group(1)

# Extract individual sections
def extract_section(section_id):
    pattern = r'<div id="' + section_id + r'" class="section( active)?">(.*?)<!-- [A-Z].*? Section -->'
    match = re.search(pattern, text, re.DOTALL)
    if not match:
        pattern = r'<div id="' + section_id + r'" class="section( active)?">(.*?)<!-- Notifications Panel -->'
        match = re.search(pattern, text, re.DOTALL)
    
    if match:
        content = match.group(2).strip()
        # Ensure it is well-formed inside
        # Actually it's safer to just split by `<!-- ... Section -->`
        return content
    return ""

def split_by_comments():
    parts = text.split("<!-- Overview Section -->")
    before_overview = parts[0]
    rest = parts[1]
    
    # split rest into chunks based on <!-- X Section -->
    sections_raw = re.split(r'<!-- (Transfer|Accounts|Transactions|Beneficiaries|Settings) Section -->', rest)
    
    overview_raw = sections_raw[0]
    
    sections_map = {'overview': overview_raw}
    for i in range(1, len(sections_raw), 2):
        name = sections_raw[i].lower()
        content = sections_raw[i+1]
        sections_map[name] = content
        
    # The last section (settings) has the Notifications panel attached to its end.
    if 'settings' in sections_map:
        settings_split = sections_map['settings'].split("<!-- Notifications Panel -->")
        sections_map['settings'] = settings_split[0]
        sections_map['footer_html'] = "<!-- Notifications Panel -->\n" + settings_split[1].split("<script>")[0]
        
    return sections_map

sections = split_by_comments()

# Extract PHP logic for dashboard that fetches accounts
php_accounts_fetch = """
// Get user accounts for the account selector
try {
    $stmt = $conn->prepare("
        SELECT id, account_number, balance, currency, account_type, status
        FROM accounts 
        WHERE user_id = ? AND status = 'active'
        ORDER BY 
            CASE account_type 
                WHEN 'checking' THEN 1 
                WHEN 'savings' THEN 2 
                ELSE 3 
            END
    ");
    $stmt->execute([$user_id]);
    $user_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching accounts: " . $e->getMessage());
    $user_accounts = [];
}
"""

# Create header.php
header_php_content = f"""{head_php}
<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
{html_head}
<body>
    <div class="dashboard-layout">
{sidebar}
        <!-- Main Content -->
        <main class="main-content">
{top_header}
"""
with open('includes/header.php', 'w', encoding='utf-8') as f:
    f.write(header_php_content)

# Create footer.php
footer_php_content = f"""
        </main>
    </div>
{sections.get('footer_html', '')}
    <script>
        window.CSRF_TOKEN = '<?php echo $csrf_token; ?>';
        window.USER_ID = <?php echo $user_id; ?>;
    </script>
    <script src="js/dashboard.js"></script>
{tail_html}
"""
with open('includes/footer.php', 'w', encoding='utf-8') as f:
    f.write(footer_php_content)

# Page wrapper generator
def make_page(name, content, add_accounts_php=False):
    php_part = "<?php\nrequire_once 'includes/header.php';\n"
    if add_accounts_php:
        php_part += php_accounts_fetch + "\n"
    php_part += "?>\n"
    
    # Strip the <div id="x-section" class="section"> wrapping because we render it directly
    # Wait, the CSS uses `.section.active` to show them. If we remove `.section`, CSS might break?
    # Actually, dashboard.css says: `.section { display: none; } .section.active { display: block; }`
    # We should wrap it in `<div class="section active" style="display:block;">` or modify CSS.
    # Let's just wrap it:
    html_content = f'<div class="section active" style="display:block;">\n{content}\n</div>'
    
    # Make quick actions real links
    if name == 'dashboard':
        html_content = html_content.replace("onclick=\"showSection('transfer', event)\"", "onclick=\"window.location.href='transfer.php'\"")
        html_content = html_content.replace("onclick=\"showSection('transactions', event)\"", "onclick=\"window.location.href='transactions.php'\"")
        html_content = html_content.replace("onclick=\"showSection('beneficiaries', event)\"", "onclick=\"window.location.href='beneficiaries.php'\"")
    # Make 'View All' real links
    html_content = html_content.replace("onclick=\"showSection('transactions', event)\"", "onclick=\"window.location.href='transactions.php'\"")
        
    full_content = php_part + html_content + "\n<?php\nrequire_once 'includes/footer.php';\n?>"
    
    filename = f"{name}.php"
    with open(filename, 'w', encoding='utf-8') as f:
        f.write(full_content)

make_page('dashboard', sections['overview'], add_accounts_php=False)
make_page('accounts', sections['accounts'], add_accounts_php=False)
make_page('transfer', sections['transfer'], add_accounts_php=True)
make_page('transactions', sections['transactions'], add_accounts_php=False)
make_page('beneficiaries', sections['beneficiaries'], add_accounts_php=False)
make_page('settings', sections['settings'], add_accounts_php=False)

print("Refactoring complete.")
