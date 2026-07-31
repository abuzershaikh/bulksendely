<?php
$wagrow_menu_meta = [
    "dashboard" => ["name" => "Dashboard", "icon" => "fad fa-home", "color" => "#28abf5", "order" => 10],
    "whatsapp" => ["name" => "WhatsApp", "icon" => "fab fa-whatsapp", "color" => "#25d366", "order" => 20],
    "crm" => ["name" => "CRM", "icon" => "fad fa-address-card", "color" => "#14b8a6", "order" => 25],
    "account_manager" => ["name" => "Team Hub", "icon" => "fad fa-users-cog", "color" => "#2563eb", "order" => 210],
    "file_manager" => ["name" => "File Manager", "icon" => "fad fa-folder-open", "color" => "#6366f1", "order" => 220],
    "tutorials" => ["name" => "Launch Guides", "icon" => "fad fa-rocket", "color" => "#fb7185", "order" => 260],
    "tools" => ["name" => "Automation Lab", "icon" => "fad fa-magic", "color" => "#22c55e", "order" => 230],
    "caption" => ["name" => "Caption Studio", "order" => 240],
    "group_manager" => ["name" => "Group Manager", "order" => 250],
    "openai" => ["name" => "OpenAI", "icon" => "fad fa-sparkles", "color" => "#7c3aed", "order" => 60],
    "ai_content_generator" => ["name" => "AI Content", "icon" => "fad fa-robot", "color" => "#4f46e5", "order" => 65],
    "ai-agent" => ["name" => "AI Agent", "icon" => "fad fa-robot", "color" => "#7c3aed", "order" => 140],
    "form-builder" => ["name" => "Form Builder", "icon" => "fad fa-clipboard-list", "color" => "#f59e0b", "order" => 141],
    "ai_prompt_templates" => ["name" => "Prompt Library", "icon" => "fad fa-brain", "color" => "#8b5cf6", "order" => 270],
    "blog_manager" => ["name" => "Blog", "order" => 280],
    "faqs_manager" => ["name" => "FAQs", "order" => 290],
    "plans" => ["name" => "Plans", "order" => 610],
    "subscriptions" => ["name" => "Subscriptions", "order" => 620],
    "payments" => ["name" => "Payments", "order" => 630],
    "memberships" => ["name" => "Revenue", "icon" => "fad fa-badge-dollar", "color" => "#ec4899", "order" => 600],
    "users" => ["name" => "Users", "icon" => "fad fa-users", "color" => "#0ea5e9", "order" => 300],
    "languages" => ["name" => "Languages", "order" => 310],
    "roles" => ["name" => "Roles", "order" => 320],
    "admin_mods" => ["name" => "Admin Suite", "icon" => "fad fa-shield-check", "color" => "#475569", "order" => 920],
    "admin_api" => ["name" => "Admin API", "icon" => "fad fa-code", "color" => "#4f46e5", "order" => 930],
    "settings" => ["name" => "Settings", "icon" => "fad fa-cog", "color" => "#64748b", "order" => 900],
    "profile" => ["name" => "Profile", "icon" => "fad fa-user", "color" => "#64748b", "order" => 910],
];

$wagrow_parent_meta = [
    "dashboard" => ["name" => "Core"],
    "features" => ["name" => "Feature"],
    "tools" => ["name" => "Automation"],
    "memberships" => ["name" => "Revenue"],
];

$wagrow_sort_group = function ($menus) use ($wagrow_menu_meta) {
    usort($menus, function ($a, $b) use ($wagrow_menu_meta) {
        $aId = $a["id"] ?? "";
        $bId = $b["id"] ?? "";
        $aOrder = $wagrow_menu_meta[$aId]["order"] ?? 9999;
        $bOrder = $wagrow_menu_meta[$bId]["order"] ?? 9999;
        if ($aOrder === $bOrder) {
            return strcmp($aId, $bId);
        }
        return $aOrder <=> $bOrder;
    });
    return $menus;
};

$wagrow_flatten_groups = function ($sidebar_groups) {
    $merged = [];
    foreach ($sidebar_groups as $group) {
        if (is_array($group)) {
            $merged = array_merge($merged, $group);
        }
    }
    return [$merged];
};

$wagrow_apply_meta = function ($row) use ($wagrow_menu_meta, $wagrow_parent_meta, $wagrow_sort_group) {
    $id = $row["id"] ?? "";
    if (isset($wagrow_menu_meta[$id])) {
        $row = array_merge($row, array_filter($wagrow_menu_meta[$id], fn($v) => $v !== null));
    }

    if (in_array($id, ["whatsapp", "crm"], true)) {
        $row["parent"] = ["id" => "dashboard", "name" => ""];
    }

    if (in_array($id, ["account_manager", "file_manager", "caption", "group_manager", "tutorials", "ai_prompt_templates", "blog_manager", "faqs_manager", "users"], true)) {
        $row["parent"] = ["id" => "features", "name" => "Feature"];
    }

    if (isset($row["parent"]["id"])) {
        $parentId = strtolower($row["parent"]["id"]);
        if (isset($wagrow_parent_meta[$parentId]["name"])) {
            $row["parent"]["name"] = $wagrow_parent_meta[$parentId]["name"];
        }
    }

    if (isset($row["sub_menu"]) && is_array($row["sub_menu"])) {
        foreach ($row["sub_menu"] as $index => $sub) {
            $subId = $sub["id"] ?? "";
            if (isset($wagrow_menu_meta[$subId])) {
                $row["sub_menu"][$index] = array_merge($sub, array_filter($wagrow_menu_meta[$subId], fn($v) => $v !== null));
            }
        }
        $row["sub_menu"] = $wagrow_sort_group($row["sub_menu"]);
    }

    return $row;
};

$wagrow_is_active = function ($row) {
    $current = trim(uri("segment", 1)."/".uri("segment", 2), "/");
    if (!empty($row["match"])) {
        return $current === trim($row["match"], "/");
    }
    return uri("segment", 1) == ($row["id"] ?? "");
};
?>

<div class="sidebar-wrapper">
    <div class="sidebar d-flex flex-column align-items-lg-center flex-row-auto h-100">
        <div class="sidebar-logo d-flex flex-column align-items-center flex-column-auto py-3">
            <a href="<?php _ec( base_url("dashboard") )?>" class="d-flex align-items-center text-decoration-none">
                <img alt="Logo" src="<?php _ec( base_url("assets/img/wagrow/wagrow-logo.png") )?>" class="logo-big">
                <img alt="Logo" src="<?php _ec( base_url("assets/img/wagrow/wagrow-logo.png") )?>" class="logo-small h-39">
            </a>
        </div>

        <div class="sidebar-nav sidebar-nav-one d-flex flex-column flex-column-fluid w-100 pt-lg-0 hide-x-scroll">
            <ul class="nav flex-column">
                <?php 
                $request = \Config\Services::request();
                $wagrow_last_parent_name = null;
                $top_sidebar = $request->top_sidebar;
                $wagrow_custom_primary = [
                    [
                        "id" => "crm",
                        "name" => "CRM",
                        "icon" => "fad fa-address-card",
                        "color" => "#14b8a6",
                        "order" => 25,
                        "url" => base_url("workspace/crm"),
                        "match" => "workspace/crm",
                        "parent" => ["id" => "dashboard", "name" => ""],
                    ],
                    [
                        "id" => "ai-agent",
                        "name" => "AI Agent",
                        "icon" => "fad fa-robot",
                        "color" => "#7c3aed",
                        "order" => 140,
                        "url" => base_url("workspace/ai-agent"),
                        "match" => "workspace/ai-agent",
                        "parent" => ["id" => "tools", "name" => "Automation"],
                    ],
                    [
                        "id" => "form-builder",
                        "name" => "Form Builder",
                        "icon" => "fad fa-clipboard-list",
                        "color" => "#f59e0b",
                        "order" => 141,
                        "url" => base_url("workspace/form-builder"),
                        "match" => "workspace/form-builder",
                        "parent" => ["id" => "tools", "name" => "Automation"],
                    ],
                ];
                if (!empty($top_sidebar)) {
                    $inserted = false;
                    foreach ($top_sidebar as $groupKey => $groupMenus) {
                        foreach ($groupMenus as $groupRow) {
                            $groupParentId = strtolower($groupRow["parent"]["id"] ?? "");
                            $groupId = strtolower($groupRow["id"] ?? "");
                            if ($groupParentId === "features" || $groupId === "whatsapp") {
                                $top_sidebar[$groupKey] = array_merge($top_sidebar[$groupKey], $wagrow_custom_primary);
                                $inserted = true;
                                break 2;
                            }
                        }
                    }

                    if (!$inserted) {
                        $firstKey = array_key_first($top_sidebar);
                        $top_sidebar[$firstKey] = array_merge($top_sidebar[$firstKey], $wagrow_custom_primary);
                    }
                } else {
                    $top_sidebar = [$wagrow_custom_primary];
                }

                $top_sidebar = $wagrow_flatten_groups($top_sidebar);
                ?>

                <?php foreach ($top_sidebar as $key => $menus): ?>
                    <?php $menus = $wagrow_sort_group($menus); ?>
                    <?php $parent = false; ?>
                    <?php foreach ($menus as $key => $row): ?>
                        <?php $row = $wagrow_apply_meta($row); ?>

                        <?php
                            $parentName = $row['parent']['name'] ?? '';
                            $should_show_parent = isset($row['parent']) && isset($row['parent']['id']) && $row['parent']['id'] != $parent && $parentName !== $wagrow_last_parent_name;
                        ?>
                        <?php if ($should_show_parent): ?>
                            <?php $parent = $row['parent']['id']; ?>
                            <?php $wagrow_last_parent_name = $parentName; ?>
                            <div class="sb-head-text">
                                <div class="menu-content pb-2 p-b-10 p-l-18">
                                    <?php if (!empty($row['parent']['name'])): ?>
                                    <span class="menu-section text-muted text-uppercase fs-12 ls-1">
                                        <?php _e( $row['parent']['name'] )?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif ?>

                        <?php if (isset($row['custom']) && $row['custom']): ?>
                            <?php echo view_cell($row['custom']) ?>
                        <?php else: ?>

                            <?php if( ! isset( $row['sub_menu'] ) ){?>
                                <li class="nav-item mb-2">
                                    <a href="<?php _e( $row['url'] ?? base_url( $row['id'] ) )?>" class="nav-link d-flex p-t-12 p-b-12 <?php _e( $wagrow_is_active($row)?'active text-primary bg-light':'hoverable' )?>" <?php _ec( ( get_option("sidebar_type", "sidebar-small") == "sidebar-close"  )?'title="'.$row['name'].'" data-toggle="tooltip" data-placement="right"':'' )?> >
                                        <i class="<?php _e( $row['icon'] )?> fs-20"  style="<?php _e( ( $row['color'] )?"color: ".$row['color']:"" )?>" ></i>
                                        <span class="text-gray-600 fw-5"><?php _e( $row['name'] )?></span>
                                    </a>
                                </li>
                            <?php }else{?>

                                <?php 
                                    $ids = [];
                                    foreach ($row['sub_menu'] as $sub){
                                        $ids[] = get_data($sub, 'id');
                                    }
                                ?>

                                <li class="nav-item mb-2 have-menus-sub">
                                    <a href="javascript:void(0);" class="nav-link d-flex hoverable p-t-12 p-b-12 <?php _e( in_array( uri('segment', 1), $ids, true )?'active bg-light':'' )?>">
                                        <i class="<?php _e( $row['icon'] )?> fs-20"  style="<?php _e( ( $row['color'] )?"color: ".$row['color']:"" )?>" ></i>
                                        <span class="text-gray-600 fw-5"><?php _e( $row['name'] )?></span>
                                    </a>

                                    <div class="menu-sub menu-sub-accordion mt-3">
                                        <?php foreach ($row['sub_menu'] as $sub): ?>
                                        <div class="menu-item ">
                                            <a class="menu-link py-2 <?php _e( (uri('segment', 1) == get_data($sub, 'id'))?'text-primary':'text-gray-900 text-hover-primary' )?>" href="<?php _e( base_url( get_data($sub, 'id') ) )?>">
                                                <span class="menu-desc"><?php _e( get_data($sub, 'name') )?></span>
                                            </a>
                                        </div>
                                        <?php endforeach ?>
                                    </div>
                                </li>
                            <?php }?>

                        <?php endif ?>


                    <?php endforeach ?>
                    <li class="nav-item mb-2">
                        <div class="nav-line bg-light m-b-10 m-t-10"></div>
                    </li>
                <?php endforeach ?>
            </ul>

        </div>

        <div class="sidebar-footer d-flex flex-column-fluid mt-auto w-100 hide-x-scroll">
            <div class="nav flex-column overflow-hidden w-100">
            <?php 
                $bottom_sidebar = $request->bottom_sidebar;
                $bottom_sidebar = $wagrow_flatten_groups($bottom_sidebar);
                $parent = false;
                ?>

                <?php foreach ($bottom_sidebar as $key => $menus): ?>
                    <?php $menus = $wagrow_sort_group($menus); ?>

                    <?php foreach ($menus as $key => $row): ?>
                        <?php $row = $wagrow_apply_meta($row); ?>
                        <?php
                            $parentName = $row['parent']['name'] ?? '';
                            $should_show_parent = isset($row['parent']) && isset($row['parent']['id']) && $row['parent']['id'] != $parent && $parentName !== $wagrow_last_parent_name;
                        ?>
                        <?php if ($should_show_parent): ?>
                            <?php $parent = $row['parent']['id']; ?>
                            <?php $wagrow_last_parent_name = $parentName; ?>
                            <div class="sb-head-text">
                                <div class="menu-content pb-2 p-b-10 p-l-18">
                                    <?php if (!empty($row['parent']['name'])): ?>
                                    <span class="menu-section text-muted text-uppercase fs-12 ls-1">
                                        <?php _e( $row['parent']['name'] )?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if( ! isset( $row['sub_menu'] ) ){?>
                            <div class="nav-item mb-2">
                                <a href="<?php _e( $row['url'] ?? base_url( $row['id'] ) )?>" class="nav-link d-flex p-t-12 p-b-12 <?php _e( $wagrow_is_active($row)?'active text-primary bg-light':'hoverable' )?>" <?php _ec( ( get_option("sidebar_type", "sidebar-small") == "sidebar-close"  )?'title="'.$row['name'].'" data-toggle="tooltip" data-placement="right"':'' )?>>
                                    <i class="<?php _e( $row['icon'] )?> fs-20" style="<?php _e( ( $row['color'] )?"color: ".$row['color']:"" )?>"></i>
                                    <span class="text-gray-600 fw-5"><?php _e( $row['name'] )?></span>
                                </a>
                            </div>
                        <?php }else{?>

                            <?php 
                                $ids = [];
                                foreach ($row['sub_menu'] as $sub){
                                    $ids[] = get_data($sub, 'id');
                                }
                            ?>

                            <li class="nav-item mb-2 have-menus-sub">
                                <a href="javascript:void(0);" class="nav-link d-flex hoverable p-t-12 p-b-12 <?php _e( in_array( uri('segment', 1), $ids, true )?'active text-primary bg-light':'' )?>">
                                    <i class="<?php _e( $row['icon'] )?> fs-20"  style="<?php _e( ( $row['color'] )?"color: ".$row['color']:"" )?>" ></i>
                                    <span class="text-gray-600 fw-5"><?php _e( $row['name'] )?></span>
                                </a>

                                <div class="menu-sub menu-sub-accordion mt-3">
                                    <?php foreach ($row['sub_menu'] as $sub): ?>
                                    <div class="menu-item ">
                                        <a class="menu-link py-2 <?php _e( uri('segment', 1) == get_data($sub, 'id')?'text-primary':'text-hover-primary' )?>" href="<?php _e( base_url( get_data($sub, 'id') ) )?>">
                                            <span class="menu-desc"><?php _e( get_data($sub, 'name') )?></span>
                                        </a>
                                    </div>
                                    <?php endforeach ?>
                                </div>
                            </li>

                        <?php }?>

                    <?php endforeach ?>
                    <div class="menu-separator"></div>
                <?php endforeach ?>
            </div>
        </div>

        <!-- <a href="javascript:void(0);" class="sidebar-toggle">
            <div class="btn btn-sm btn-icon btn-white btn-active-primary position-absolute translate-middle start-100 end-0 bottom-0 shadow-sm d-none d-lg-flex">
                <i class="fad fa-chevron-right"></i>
            </div>
        </a> -->
    </div>
</div>
<!--end::Sidebar-->
