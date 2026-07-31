<div class="container-fluid py-4 py-lg-5">
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 28px; background: linear-gradient(135deg, #ffffff 0%, #eef4ff 100%);">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                        <div>
                            <div class="text-uppercase fw-bold mb-2" style="letter-spacing: .16em; color: #8b5cf6; font-size: 12px;">WaGrow Module</div>
                            <h1 class="mb-2" style="font-size: 34px; color: #0f172a;"><?php _e($title); ?></h1>
                            <p class="mb-0 text-muted" style="max-width: 720px; font-size: 15px;"><?php _e($description); ?></p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?php _e(base_url("dashboard")); ?>" class="btn btn-light-primary px-4">Back to Dashboard</a>
                            <a href="<?php _e(base_url("local-whatsapp-access")); ?>" class="btn btn-primary px-4">Open WhatsApp</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php foreach ($cards as $card): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 24px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <span class="badge rounded-pill text-bg-light px-3 py-2"><?php _e($card["badge"]); ?></span>
                        <span style="width: 14px; height: 14px; border-radius: 999px; background: linear-gradient(135deg, #8b5cf6 0%, #22c55e 100%);"></span>
                    </div>
                    <h3 class="mb-2" style="font-size: 22px; color: #0f172a;"><?php _e($card["title"]); ?></h3>
                    <p class="mb-0 text-muted" style="font-size: 14px; line-height: 1.7;"><?php _e($card["desc"]); ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 24px;">
                <div class="card-body p-4 p-lg-5">
                    <h4 class="mb-3" style="color: #0f172a;">Next Build Step</h4>
                    <p class="mb-0 text-muted">This section is now wired into the sidebar and ready for deeper module UI work. We can next convert it into a full data table, kanban board, settings panel, or analytics screen depending on which module you want to build first.</p>
                </div>
            </div>
        </div>
    </div>
</div>
