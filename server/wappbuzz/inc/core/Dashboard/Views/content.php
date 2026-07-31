<?php
$display_name = get_user("fullname") ?: get_user("username") ?: "Operator";
$avatar = get_user("avatar") ? get_file_url(get_user("avatar")) : base_url("assets/img/wagrow/wagrow-logo.png");
$expiration = get_user("expiration_date");
$expire_text = $expiration ? date_show($expiration) : __("Unlimited");
$plan_text = __("Active Workspace");

$feature_cards = [
    [
        "title" => "AI Content",
        "desc" => "Generate responses and campaign copy",
        "icon" => "fad fa-robot",
        "color" => "#4f46e5",
        "link" => base_url("ai_content_generator"),
        "button" => "Manage",
    ],
    [
        "title" => "File Manager",
        "desc" => "Assets, imports, and shared media",
        "icon" => "fad fa-folder-open",
        "color" => "#6366f1",
        "link" => base_url("file_manager"),
        "button" => "Open",
    ],
    [
        "title" => "Autoresponder",
        "desc" => "Instant WhatsApp follow-up flows",
        "icon" => "fad fa-reply-all",
        "color" => "#22c55e",
        "link" => base_url("whatsapp_autoresponder"),
        "button" => "Launch",
    ],
    [
        "title" => "Bulk Messaging",
        "desc" => "Run campaigns across saved contacts",
        "icon" => "fad fa-paper-plane",
        "color" => "#8b5cf6",
        "link" => base_url("whatsapp_bulk"),
        "button" => "Start",
    ],
];

$quick_tools = [
    ["name" => "Account Manager", "icon" => "fad fa-users-cog", "link" => base_url("account_manager"), "color" => "#1d4ed8"],
    ["name" => "Single Message", "icon" => "fad fa-comment-dots", "link" => base_url("whatsapp_single_message"), "color" => "#06b6d4"],
    ["name" => "Chatbot", "icon" => "fad fa-user-robot", "link" => base_url("whatsapp_chatbot"), "color" => "#d97706"],
    ["name" => "Link Generator", "icon" => "fad fa-link", "link" => base_url("whatsapp_link_generator"), "color" => "#16a34a"],
    ["name" => "OpenAI", "icon" => "fad fa-sparkles", "link" => base_url("openai"), "color" => "#7c3aed"],
];

$stats = [
    ["title" => "Messages Sent", "icon" => "fad fa-paper-plane-top", "value" => "Campaign Ready", "bg" => "linear-gradient(135deg,#5b5ff0,#7c6df6)"],
    ["title" => "Bulk Delivered", "icon" => "fad fa-layer-group", "value" => "Audience Synced", "bg" => "linear-gradient(135deg,#10b981,#34d399)"],
    ["title" => "Autoresponder", "icon" => "fad fa-reply-all", "value" => "Flows Active", "bg" => "linear-gradient(135deg,#f59e0b,#fbbf24)"],
    ["title" => "Chatbot", "icon" => "fad fa-robot", "value" => "AI Assisted", "bg" => "linear-gradient(135deg,#ec4899,#f472b6)"],
];
?>

<style>
.header {
    background: rgba(255,255,255,0.96);
    backdrop-filter: blur(18px);
    border-bottom: 1px solid #e5e7eb;
}

.main-wrapper.dashboard-main,
.main-wrapper.flex-grow-1.dashboard-main {
    background:
        radial-gradient(circle at top left, rgba(99,102,241,0.07), transparent 28%),
        radial-gradient(circle at right top, rgba(14,165,233,0.06), transparent 24%),
        #f8fafc;
    min-height: calc(100vh - 70px);
}

.sidebar-wrapper {
    border-right: 1px solid #e5e7eb;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.sidebar {
    align-items: flex-start !important;
    width: 250px;
    padding: 0 14px;
}

.sidebar-logo {
    align-items: flex-start !important;
    padding: 18px 12px 12px !important;
}

.sidebar-logo .logo-small {
    display: none;
}

.sidebar-logo .logo-big {
    height: 36px;
    width: auto;
}

.sidebar .nav-link {
    border-radius: 14px;
    padding-left: 16px !important;
    padding-right: 16px !important;
    gap: 12px;
}

.sidebar .nav-link.active {
    box-shadow: 0 10px 25px -18px rgba(79,70,229,0.9);
}

.sidebar .text-gray-600,
.sidebar .text-primary,
.sidebar .text-white {
    color: #475569 !important;
}

.sidebar .nav-link.active .text-gray-600,
.sidebar .nav-link.active .text-white,
.sidebar .nav-link.active .text-primary {
    color: #4f46e5 !important;
    font-weight: 700;
}

.sb-head-text .menu-section {
    font-size: 11px !important;
    letter-spacing: 0.08em;
    color: #94a3b8 !important;
}

.wagrow-shell {
    padding: 28px 32px 36px;
}

.wagrow-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.wagrow-card,
.wagrow-welcome,
.wagrow-tools,
.wagrow-stat {
    border: 1px solid #dbe4f0;
    background: rgba(255,255,255,0.96);
    border-radius: 26px;
    box-shadow: 0 20px 55px -40px rgba(15,23,42,0.28);
}

.wagrow-card {
    padding: 28px;
    min-height: 176px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.wagrow-card .icon-wrap,
.wagrow-tool .icon-wrap {
    width: 54px;
    height: 54px;
    border-radius: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 22px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.25);
}

.wagrow-card h4,
.wagrow-tools h4,
.wagrow-analytics h3,
.wagrow-welcome h3 {
    color: #1e293b;
    margin: 0;
}

.wagrow-card p,
.wagrow-welcome p,
.wagrow-analytics p {
    color: #94a3b8;
    margin: 0;
}

.wagrow-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 14px;
    color: #fff !important;
    font-weight: 700;
    padding: 11px 18px;
    text-decoration: none;
}

.wagrow-row {
    display: grid;
    grid-template-columns: 1.1fr 2.2fr;
    gap: 20px;
    margin-bottom: 28px;
}

.wagrow-welcome {
    position: relative;
    overflow: hidden;
    padding: 30px;
    min-height: 210px;
}

.wagrow-welcome:after {
    content: "";
    position: absolute;
    right: -28px;
    top: -28px;
    width: 140px;
    height: 140px;
    border-radius: 999px;
    background: radial-gradient(circle, rgba(99,102,241,0.18), rgba(99,102,241,0.02));
}

.wagrow-welcome .user-badge {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    object-fit: cover;
    border: 3px solid rgba(99,102,241,0.12);
    margin-bottom: 18px;
}

.wagrow-plan {
    display: inline-flex;
    gap: 8px;
    align-items: center;
    padding: 7px 12px;
    border-radius: 999px;
    background: rgba(34,197,94,0.12);
    color: #15803d;
    font-weight: 700;
    font-size: 12px;
}

.wagrow-tools {
    padding: 24px;
}

.wagrow-tool-list {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 14px;
    margin-top: 18px;
}

.wagrow-tool {
    border: 1px solid #e2e8f0;
    border-radius: 22px;
    background: linear-gradient(180deg, #f8fbff 0%, #eef4fb 100%);
    padding: 18px 16px;
    text-align: center;
    text-decoration: none;
    color: #334155;
    min-height: 138px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.wagrow-tool span {
    font-weight: 700;
    line-height: 1.3;
}

.wagrow-analytics-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.wagrow-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(34,197,94,0.14);
    color: #166534;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 700;
}

.wagrow-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 28px;
}

.wagrow-stat {
    color: #fff;
    padding: 24px;
    border: 0;
    overflow: hidden;
    position: relative;
}

.wagrow-stat:after {
    content: "";
    position: absolute;
    right: -24px;
    top: -24px;
    width: 120px;
    height: 120px;
    border-radius: 999px;
    background: rgba(255,255,255,0.12);
}

.wagrow-stat .stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 16px;
    background: rgba(255,255,255,0.18);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 14px;
}

.wagrow-stat small {
    display: block;
    opacity: 0.85;
    font-size: 13px;
}

.wagrow-stat strong {
    display: block;
    font-size: 24px;
    line-height: 1.2;
}

.wagrow-modules {
    border-top: 1px solid #e5e7eb;
    padding-top: 24px;
}

.wagrow-module-row > * {
    margin-bottom: 18px;
}

@media (max-width: 1200px) {
    .wagrow-grid,
    .wagrow-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .wagrow-tool-list {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .wagrow-shell {
        padding: 20px 18px 28px;
    }

    .wagrow-row,
    .wagrow-grid,
    .wagrow-stats,
    .wagrow-tool-list {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="wagrow-shell">
    <div class="wagrow-grid">
        <?php foreach ($feature_cards as $card): ?>
            <div class="wagrow-card">
                <div>
                    <div class="icon-wrap" style="background: <?php _ec($card["color"]) ?>;">
                        <i class="<?php _ec($card["icon"]) ?>"></i>
                    </div>
                    <div class="mt-4">
                        <h4 class="fs-22 fw-bold"><?php _e($card["title"]) ?></h4>
                        <p class="fs-14 mt-2"><?php _e($card["desc"]) ?></p>
                    </div>
                </div>
                <div class="mt-4">
                    <a class="wagrow-btn" href="<?php _ec($card["link"]) ?>" style="background: <?php _ec($card["color"]) ?>;"><?php _e($card["button"]) ?></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="wagrow-row">
        <div class="wagrow-welcome">
            <img src="<?php _ec($avatar) ?>" class="user-badge" alt="Avatar">
            <p class="fs-16 fw-semibold text-primary m-b-8"><?php _e("Welcome back") ?></p>
            <h3 class="fs-32 fw-bold m-b-8"><?php _ec($display_name) ?></h3>
            <p class="fs-15 m-b-20"><?php _e("Your local workspace is now being rebuilt in the WaGrow direction.") ?></p>
            <div class="wagrow-plan m-b-20">
                <i class="fad fa-circle fs-7"></i>
                <span><?php _e($plan_text) ?></span>
            </div>
            <p class="fs-14 text-gray-500 m-b-20"><?php _e("Expire date:") ?> <?php _ec($expire_text) ?></p>
            <a href="<?php _ec(base_url("profile/index/plan")) ?>" class="btn btn-light-primary b-r-14 px-4 py-3 fw-bold"><?php _e("View plan") ?></a>
        </div>

        <div class="wagrow-tools">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fs-24 fw-bold"><?php _e("Quick Actions") ?></h4>
                    <p class="fs-14 mt-2"><?php _e("Jump into the most-used modules without digging through menus.") ?></p>
                </div>
                <a href="<?php _ec(base_url("whatsapp")) ?>" class="btn btn-primary b-r-14 px-4 py-3 fw-bold"><?php _e("Open WhatsApp") ?></a>
            </div>
            <div class="wagrow-tool-list">
                <?php foreach ($quick_tools as $tool): ?>
                    <a class="wagrow-tool" href="<?php _ec($tool["link"]) ?>">
                        <div class="icon-wrap" style="background: <?php _ec($tool["color"]) ?>;">
                            <i class="<?php _ec($tool["icon"]) ?>"></i>
                        </div>
                        <span><?php _e($tool["name"]) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="wagrow-analytics">
        <div class="wagrow-analytics-bar">
            <div>
                <h3 class="fs-32 fw-bold"><?php _e("WhatsApp Analytics") ?></h3>
                <p class="fs-15 mt-2"><?php _e("Overview of messaging performance, automations, and operator readiness.") ?></p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="wagrow-pill"><i class="fad fa-circle fs-7"></i><?php _e("Live") ?></span>
                <a href="<?php _ec(base_url("whatsapp_single_message")) ?>" class="btn btn-primary b-r-14 px-4 py-3 fw-bold"><?php _e("Send Message") ?></a>
            </div>
        </div>

        <div class="wagrow-stats">
            <?php foreach ($stats as $stat): ?>
                <div class="wagrow-stat" style="background: <?php _ec($stat["bg"]) ?>;">
                    <div class="stat-icon"><i class="<?php _ec($stat["icon"]) ?>"></i></div>
                    <small><?php _e($stat["title"]) ?></small>
                    <strong><?php _e($stat["value"]) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($result)): ?>
    <div class="wagrow-modules">
        <div class="d-flex justify-content-between align-items-center m-b-20">
            <div>
                <h4 class="fs-24 fw-bold text-gray-800"><?php _e("Module Insights") ?></h4>
                <p class="text-gray-500 fs-14 m-b-0"><?php _e("Existing Wazipar dashboard blocks are still available below while we rework the full interface.") ?></p>
            </div>
        </div>
        <div class="row wagrow-module-row">
            <?php foreach ($result as $value): ?>
                <?php _ec($value['data']['html']) ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
