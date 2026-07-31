<div class="section container position-relative z-2">
    
    <div class="d-flex justify-content-center align-items-center h-100 mw-800 mx-auto text-center m-b-120 m-t-120" data-aos="fade-down">
        <div>
            <h1 class="fs-45 fw-6"><?php _e("Frequently Asked Questions")?></h1>
            <h5 class="text-gray-600"><?php _e("Getting more information about our platform that will help you get all benefits from us. These all questions are asked for the first time")?></h5>
        </div>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <div class="faq-accordion accordion accordion-big" id="faqsContent">
        <?php if (!empty($faqs)): ?>
            <?php foreach ($faqs as $key => $value): ?>
            <div class="accordion-item mb-4">
                <h2 class="accordion-header" id="heading<?php _ec($key)?>">
                    <button class="accordion-button border b-r-10 shadow-none text-gray-800 fw-5 <?php _ec( $key == 0?"show":"collapsed shadow" )?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php _ec($key)?>" aria-expanded="true" aria-controls="collapse<?php _ec($key)?>">
                        <?php _ec($value->title)?>
                    </button>
                </h2>
                <div id="collapse<?php _ec($key)?>" class="accordion-collapse collapse <?php _ec( $key == 0?"show":"" )?>" aria-labelledby="heading<?php _ec($key)?>" data-bs-parent="#faqsContent">
                    <div class="accordion-body mb-3">
                        <?php _ec($value->content)?>
                    </div>
                </div>
            </div>
            <?php endforeach ?>
        <?php endif ?>
        </div>
    </div>
</section>