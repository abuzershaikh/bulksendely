<?php

$filter_list = [
    //"title" => __("Title"),
    //"description" => __("Description"),
    //"url" => __("URL"),
    "subscription_url" => __("Subscription URL"),
    "subscription_date" => __("Subscription Date"),
    "total_visits" => __("Total Visits"),
    "first_visit" => __("First visit"),
    "last_visit" => __("Last visit"),
    //"city" => __("City"),
    "country" => __("Country"),
    "language" => __("Language"),
    "device" => __("Device"),
    "os" => __("OS"),
    "browser" => __("Browser"),
];

?>

<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center">
    <div class="bd-search position-relative me-auto">
        <h2 class="mb-0 py-4 text-gray-800"> <i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> 
            <?php if (empty($result)): ?>
                <?php _e( $config['name'] )?>
            <?php else: ?>
                <?php _e("Audience: ")?> <?php _ec( get_data($result, "name") )?>
            <?php endif ?>
            <div class="fs-14 fw-4 mt-2"><?php _e( $config['desc'] )?></div>
        </h2>
    </div>

    <div class="">
        <div class="dropdown me-2">
            <div class="input-group input-group-sm sp-input-group border b-r-4">
                <a href="<?php _e( get_module_url() )?>" class="btn btn-light btn-active-light-success m-r-1"><i class="fad fa-th-list text-success pe-0"></i> <?php _e("All Audience")?></a>
            </div>
        </div>
    </div>
</div>

<form class="actionForm" action="<?php _e( get_module_url("save")."/".get_data($result, "ids") )?>" method="POST" data-result="html" data-content="ajax-result" data-redirect="<?php _ec( get_module_url() )?>" data-loading="true">
    <div class="container my-5">

        <div class="card border b-r-10">
            <div class="card-body p-15">
                <input type="text" class="form-control" name="name" placeholder="<?php _e("Audience Name")?>" required value="<?php _ec( get_data($result, "name") )?>">
            </div>
        </div>

        <div class="audience-wrap">

            <?php if (!empty($result)): ?>
                

                <?php 
                $data = $result->data;
                if(!empty($data)){
                    $data = json_decode($data, true);

                    $filters = $data['filters'];
                    $comparators = $data['comparators'];
                    $values = $data['values'];
                }

                ?>

                <?php if (!empty($filters) && !empty($comparators)): ?>

                    <?php $filter_count = 0; ?>

                    <?php foreach ($filters as $filter_key => $filter): ?>

                        <div class="audience-item mt-4" data-index="<?php _ec($filter_key)?>">
                            <div class="audience-item-main d-flex border bg-white">
                                <div class="border-start border-success border-4 fs-14 fw-6 text-primary py-4 bg-gray-100 px-4 w-90">
                                    <span class="audience-select-text <?php _ec($filter_count==0?"":"d-none")?>"><?php _e("Select")?></span>
                                    <span class="audience-and-text <?php _ec($filter_count==0?"d-none":"")?>"><?php _e("And")?></span>
                                </div>
                                <div class="d-flex p-10 justify-content-between w-100">
                                    <div class="audience-filter-wrap d-flex">
                                        
                                        <select class="form-select audience-filter w-200" name="filter[<?php _ec($filter_count)?>]" required>
                                            <option value=""><?php _e("Select")?></option>                                
                                            <?php foreach ($filter_list as $key => $value): ?>
                                                <option value="<?php _ec( $key )?>" <?php _ec( $key == $filter?"selected":"" )?> ><?php _e( $value )?></option>                                
                                            <?php endforeach ?>
                                        </select>

                                        <div class="audience-sub-filter px-3 d-flex">
                                            <?php $comparator_count = 0; ?>

                                            <?php foreach ($comparators[$filter_key] as $comparator_key => $comparator): ?>

                                                <?php if ($comparator_count == 0): ?>

                                                    <?php if (!is_array($comparator)): ?>
                                                        <?php $value_data = isset($values[$filter_key][$comparator_key])?$values[$filter_key][$comparator_key]:"" ?>
                                                        
                                                        <?php if ($filter == "title"): ?>
                                                                <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][]">
                                                                    <option value="contains" <?php _ec( $comparator=="contains"?"selected":"" )?>><?php _e("Contains")?></option>
                                                                    <option value="not_contains" <?php _ec( $comparator=="not_contains"?"selected":"" )?>><?php _e("Not Contains")?></option>
                                                                </select>    
                                                                <input type="text" class="form-control" name="value[<?php _ec( $filter_key )?>][]" required placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data )?>">

                                                            <?php elseif ($filter == "subscription_url"): ?>

                                                                <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][]">
                                                                    <option value="contains" <?php _ec( $comparator=="contains"?"selected":"" )?>><?php _e("Contains")?></option>
                                                                    <option value="not_contains" <?php _ec( $comparator=="contains"?"selected":"" )?>><?php _e("Not Contains")?></option>
                                                                </select>    
                                                                <input type="text" class="form-control" name="value[<?php _ec( $filter_key )?>][]" required placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data )?>">

                                                            <?php elseif ($filter == "total_visits"): ?>

                                                                <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][]">
                                                                    <option value="equal" <?php _ec( $comparator=="equal"?"selected":"" )?>><?php _e("Equal To")?></option>
                                                                    <option value="greater_than" <?php _ec( $comparator=="greater_than"?"selected":"" )?>><?php _e("Greater Than")?></option>
                                                                    <option value="less_than" <?php _ec( $comparator=="less_than"?"selected":"" )?>><?php _e("Less Than")?></option>
                                                                </select>   
                                                                <span class="audience-time-input d-flex align-items-center"> 
                                                                    <input type="number" min="1" class="form-control" name="value[<?php _ec( $filter_key )?>][]" required placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data )?>"> <span class="ms-2"><?php _e("Hs")?></span>
                                                                </span>

                                                            <?php elseif($filter == "last_visit"): ?>
                                                                <?php 
                                                                $value_data_date = isset($values[$filter_key]["date"][$comparator_key])?$values[$filter_key]["date"][$comparator_key]:"";
                                                                $value_data_hour = isset($values[$filter_key]["hour"][$comparator_key])?$values[$filter_key]["hour"][$comparator_key]:"";
                                                                ?>

                                                                <select class="form-select audience-select-time me-3" name="comparator[<?php _ec( $filter_key )?>][]">
                                                                    <option value="within" <?php _ec( $comparator=="within"?"selected":"" )?>><?php _e("Within")?></option>
                                                                    <option value="earlier_than" <?php _ec( $comparator=="earlier_than"?"selected":"" )?>><?php _e("Earlier Than")?></option>
                                                                    <option value="on" <?php _ec( $comparator=="on"?"selected":"" )?>><?php _e("On")?></option>
                                                                    <option value="before" <?php _ec( $comparator=="before"?"selected":"" )?>><?php _e("Before")?></option>
                                                                    <option value="after" <?php _ec( $comparator=="after"?"selected":"" )?>><?php _e("After")?></option>
                                                                </select>    
                                                                <input type="text" class="form-control audience-time-date <?php _ec( (($comparator != "within" && $comparator != "earlier_than")?"":"d-none") )?>" name="value[<?php _ec( $filter_key )?>][date][]" placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data_date )?>">
                                                                <span class="audience-time-input d-flex align-items-center <?php _ec( (($comparator != "within" && $comparator != "earlier_than")?"d-none":"") )?>">
                                                                    <input type="number" min="1" class="form-control" name="value[<?php _ec( $filter_key )?>][hour][]" placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data_hour )?>"> <span class="ms-2"><?php _e("Hs")?></span>
                                                                </span>

                                                            <?php elseif($filter == "first_visit"): ?>

                                                                <?php 
                                                                $value_data_date = isset($values[$filter_key]["date"][$comparator_key])?$values[$filter_key]["date"][$comparator_key]:"";
                                                                $value_data_hour = isset($values[$filter_key]["hour"][$comparator_key])?$values[$filter_key]["hour"][$comparator_key]:"";
                                                                ?>

                                                                <select class="form-select audience-select-time me-3" name="comparator[<?php _ec( $filter_key )?>][]">
                                                                    <option value="within" <?php _ec( $comparator=="within"?"selected":"" )?>><?php _e("Within")?></option>
                                                                    <option value="earlier_than" <?php _ec( $comparator=="earlier_than"?"selected":"" )?>><?php _e("Earlier Than")?></option>
                                                                    <option value="on" <?php _ec( $comparator=="on"?"selected":"" )?>><?php _e("On")?></option>
                                                                    <option value="before" <?php _ec( $comparator=="before"?"selected":"" )?>><?php _e("Before")?></option>
                                                                    <option value="after" <?php _ec( $comparator=="after"?"selected":"" )?>><?php _e("After")?></option>
                                                                </select>    
                                                                <input type="text" class="form-control audience-time-date <?php _ec( (($comparator != "within" && $comparator != "earlier_than")?"":"d-none") )?>" name="value[<?php _ec( $filter_key )?>][date][]" placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data_date )?>">
                                                                <span class="audience-time-input d-flex align-items-center <?php _ec( (($comparator != "within" && $comparator != "earlier_than")?"d-none":"") )?>">
                                                                    <input type="number" min="1" class="form-control" name="value[<?php _ec( $filter_key )?>][hour][]" placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data_hour )?>"> <span class="ms-2"><?php _e("Hs")?></span>
                                                                </span>

                                                            <?php endif ?>

                                                    <?php else: ?>

                                                        <?php if($filter == "city"): ?>

                                                            <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][city][]">
                                                                <option value="is"><?php _e("Is")?></option>
                                                            </select>    
                                                            <input type="text" class="form-control" name="value[<?php _ec( $filter_key )?>][]" required placeholder="<?php _e("Value")?>">

                                                        <?php elseif($filter == "country"): ?>

                                                            <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][country][]" required>
                                                                <?php foreach (list_countries() as $key => $value): ?>
                                                                    <option value="<?php _e($key)?>" <?php _ec( reset($comparator)==$key?"selected":"" )?>><?php _e($value)?></option>
                                                                <?php endforeach ?>
                                                            </select> 

                                                        <?php elseif($filter == "language"): ?>

                                                            <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][language][]" required>
                                                                <?php foreach (get_language_codes() as $key => $value): ?>
                                                                    <option value="<?php _e($key)?>" <?php _ec( reset($comparator)==$key?"selected":"" )?>><?php _e($value)?></option>
                                                                <?php endforeach ?>
                                                            </select>   

                                                        <?php elseif($filter == "device"): ?>

                                                            <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][device][]" required>
                                                                <option value="all"><?php _e("All")?></option>
                                                                <option value="desktop" <?php _ec( reset($comparator)=="desktop"?"selected":"" )?>><?php _e("Desktop")?></option>
                                                                <option value="mobile" <?php _ec( reset($comparator)=="mobile"?"selected":"" )?>><?php _e("Mobile")?></option>
                                                                <option value="tablet" <?php _ec( reset($comparator)=="tablet"?"selected":"" )?>><?php _e("Tablet")?></option>
                                                            </select>

                                                        <?php elseif($filter == "browser"): ?>

                                                            <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][browser][]" required>
                                                                <option value="all"><?php _e("All")?></option>
                                                                <option value="chrome" <?php _ec( reset($comparator)=="chrome"?"selected":"" )?>><?php _e("Chrome")?></option>
                                                                <option value="safari" <?php _ec( reset($comparator)=="safari"?"selected":"" )?>><?php _e("Safari")?></option>
                                                                <option value="firefox" <?php _ec( reset($comparator)=="firefox"?"selected":"" )?>><?php _e("Firefox")?></option>
                                                                <option value="opera" <?php _ec( reset($comparator)=="opera"?"selected":"" )?>><?php _e("Opera")?></option>
                                                            </select>

                                                        <?php elseif($filter == "os"): ?>
                                                        
                                                            <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][os][]" required>
                                                                <option value="all"><?php _e("All")?></option>
                                                                <option value="windows" <?php _ec( reset($comparator)=="windows"?"selected":"" )?>><?php _e("Windows")?></option>
                                                                <option value="mac" <?php _ec( reset($comparator)=="mac"?"selected":"" )?>><?php _e("Mac")?></option>
                                                                <option value="android" <?php _ec( reset($comparator)=="android"?"selected":"" )?>><?php _e("Android")?></option>
                                                                <option value="linux" <?php _ec( reset($comparator)=="linux"?"selected":"" )?>><?php _e("Linux")?></option>
                                                            </select>

                                                        <?php endif ?>
                                                    
                                                    <?php endif ?>

                                                <?php endif ?>
                                                
                                                <?php $comparator_count++; ?>
                                            <?php endforeach ?>
                                        </div>
                                    </div>
                                    <div class="p-10">
                                        <span class="audience-actions">
                                            <button type="button" class="btn-sm px-2 py-1 btn btn-primary audience-btn-action audience-and-fitter"><?php _e("And")?></button>
                                            <button type="button" class="btn-sm px-2 py-1 btn btn-primary audience-btn-action audience-or-fitter"><?php _e("Or")?></button>
                                        </span>
                                        <button type="button" class="btn-sm px-2 py-1 btn btn-secondary audience-remove-filter audience-main-remove-filter <?php _e(count($filters)>1)?"":"d-none"?>"><i class="fad fa-trash-alt fs-12 p-0"></i></button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="audience-sub-list">
                                <?php $comparator_count = 0; ?>

                                <?php foreach ($comparators[$filter_key] as $comparator_key => $comparator): ?>
                                    <?php //if ($comparator_count != 0): pr($filter_key); ?>

                                        <?php if (!is_array($comparator)): ?>

                                            <?php if ($comparator_count != 0): ?>
                                            <div class="audience-sub-item d-flex border bg-white m-l-90">
                                                <div class="border-start border-success border-4 fs-14 fw-6 text-primary py-4 bg-gray-100 px-4"><?php _e("Or")?></div>
                                                <div class="d-flex p-10 w-100 justify-content-between">
                                                    <div class="audience-filter-wrap d-flex ">
                                                        <div class="audience-sub-filter px-3 d-flex">

                                                            <?php $value_data = isset($values[$filter_key][$comparator_key])?$values[$filter_key][$comparator_key]:"" ?>
                                                            
                                                            <?php if ($filter == "title"): ?>
                                                                <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][]">
                                                                    <option value="contains" <?php _ec( $comparator=="contains"?"selected":"" )?>><?php _e("Contains")?></option>
                                                                    <option value="not_contains" <?php _ec( $comparator=="not_contains"?"selected":"" )?>><?php _e("Not Contains")?></option>
                                                                </select>    
                                                                <input type="text" class="form-control" name="value[<?php _ec( $filter_key )?>][]" required placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data )?>">

                                                            <?php elseif ($filter == "subscription_url"): ?>

                                                                <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][]">
                                                                    <option value="contains" <?php _ec( $comparator=="contains"?"selected":"" )?>><?php _e("Contains")?></option>
                                                                    <option value="not_contains" <?php _ec( $comparator=="contains"?"selected":"" )?>><?php _e("Not Contains")?></option>
                                                                </select>    
                                                                <input type="text" class="form-control" name="value[<?php _ec( $filter_key )?>][]" required placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data )?>">

                                                            <?php elseif ($filter == "total_visits"): ?>

                                                                <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][]">
                                                                    <option value="equal" <?php _ec( $comparator=="equal"?"selected":"" )?>><?php _e("Equal To")?></option>
                                                                    <option value="greater_than" <?php _ec( $comparator=="greater_than"?"selected":"" )?>><?php _e("Greater Than")?></option>
                                                                    <option value="less_than" <?php _ec( $comparator=="less_than"?"selected":"" )?>><?php _e("Less Than")?></option>
                                                                </select>   
                                                                <span class="audience-time-input d-flex align-items-center"> 
                                                                    <input type="number" min="1" class="form-control" name="value[<?php _ec( $filter_key )?>][]" required placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data )?>"> <span class="ms-2"><?php _e("Hs")?></span>
                                                                </span>

                                                            <?php elseif($filter == "last_visit"): ?>

                                                                <?php 
                                                                $value_data_date = isset($values[$filter_key]["date"][$comparator_key])?$values[$filter_key]["date"][$comparator_key]:"";
                                                                $value_data_hour = isset($values[$filter_key]["hour"][$comparator_key])?$values[$filter_key]["hour"][$comparator_key]:"";
                                                                ?>

                                                                <select class="form-select audience-select-time me-3" name="comparator[<?php _ec( $filter_key )?>][]">
                                                                    <option value="within" <?php _ec( $comparator=="within"?"selected":"" )?>><?php _e("Within")?></option>
                                                                    <option value="earlier_than" <?php _ec( $comparator=="earlier_than"?"selected":"" )?>><?php _e("Earlier Than")?></option>
                                                                    <option value="on" <?php _ec( $comparator=="on"?"selected":"" )?>><?php _e("On")?></option>
                                                                    <option value="before" <?php _ec( $comparator=="before"?"selected":"" )?>><?php _e("Before")?></option>
                                                                    <option value="after" <?php _ec( $comparator=="after"?"selected":"" )?>><?php _e("After")?></option>
                                                                </select>    
                                                                <input type="text" class="form-control audience-time-date <?php _ec( (($comparator != "within" && $comparator != "earlier_than")?"":"d-none") )?>" name="value[<?php _ec( $filter_key )?>][date][]" placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data_date )?>">
                                                                <span class="audience-time-input d-flex align-items-center <?php _ec( (($comparator != "within" && $comparator != "earlier_than")?"d-none":"") )?>">
                                                                    <input type="number" min="1" class="form-control" name="value[<?php _ec( $filter_key )?>][hour][]" placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data_hour )?>"> <span class="ms-2"><?php _e("Hs")?></span>
                                                                </span>

                                                            <?php elseif($filter == "first_visit"): ?>

                                                                <?php 
                                                                $value_data_date = isset($values[$filter_key]["date"][$comparator_key])?$values[$filter_key]["date"][$comparator_key]:"";
                                                                $value_data_hour = isset($values[$filter_key]["hour"][$comparator_key])?$values[$filter_key]["hour"][$comparator_key]:"";
                                                                ?>

                                                                <select class="form-select audience-select-time me-3" name="comparator[<?php _ec( $filter_key )?>][]">
                                                                    <option value="within" <?php _ec( $comparator=="within"?"selected":"" )?>><?php _e("Within")?></option>
                                                                    <option value="earlier_than" <?php _ec( $comparator=="earlier_than"?"selected":"" )?>><?php _e("Earlier Than")?></option>
                                                                    <option value="on" <?php _ec( $comparator=="on"?"selected":"" )?>><?php _e("On")?></option>
                                                                    <option value="before" <?php _ec( $comparator=="before"?"selected":"" )?>><?php _e("Before")?></option>
                                                                    <option value="after" <?php _ec( $comparator=="after"?"selected":"" )?>><?php _e("After")?></option>
                                                                </select>    
                                                                <input type="text" class="form-control audience-time-date <?php _ec( (($comparator != "within" && $comparator != "earlier_than")?"":"d-none") )?>" name="value[<?php _ec( $filter_key )?>][date][]" placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data_date )?>">
                                                                <span class="audience-time-input d-flex align-items-center <?php _ec( (($comparator != "within" && $comparator != "earlier_than")?"d-none":"") )?>">
                                                                    <input type="number" min="1" class="form-control" name="value[<?php _ec( $filter_key )?>][hour][]" placeholder="<?php _e("Value")?>" value="<?php _ec( $value_data_hour )?>"> <span class="ms-2"><?php _e("Hs")?></span>
                                                                </span>

                                                            <?php endif ?>

                                                        </div>
                                                    </div>
                                                    <div class="p-10 audience-actions">
                                                        <button type="button" class="btn-sm px-2 py-1 btn btn-primary audience-or-fitter"><?php _e("Or")?></button>
                                                        <button type="button" class="btn-sm px-2 py-1 btn btn-secondary audience-remove-filter audience-sub-remove-filter"><i class="fad fa-trash-alt fs-12 p-0"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif ?>

                                            <?php $comparator_count++; ?>

                                        <?php elseif( is_array($comparator) ): ?>

                                            <?php if($filter == "city"): ?>

                                                <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][city][]">
                                                    <option value="is"><?php _e("Is")?></option>
                                                </select>    
                                                <input type="text" class="form-control" name="value[<?php _ec( $filter_key )?>][]" required placeholder="<?php _e("Value")?>">

                                            <?php elseif($filter == "country"): ?>

                                                <?php $sub_count = 0; ?>
                                                <?php foreach ($comparator as $sub_comparator): ?>

                                                    <?php if ($sub_count != 0): ?>
                                                    <div class="audience-sub-item d-flex border bg-white m-l-90">
                                                        <div class="border-start border-success border-4 fs-14 fw-6 text-primary py-4 bg-gray-100 px-4"><?php _e("Or")?></div>
                                                        <div class="d-flex p-10 w-100 justify-content-between">
                                                            <div class="audience-filter-wrap d-flex ">
                                                                <div class="audience-sub-filter px-3 d-flex">
                                                                    <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][country][]" required>
                                                                        <?php foreach (list_countries() as $key => $value): ?>
                                                                            <option value="<?php _e($key)?>" <?php _ec( $sub_comparator==$key?"selected":"" )?>><?php _e($value)?></option>
                                                                        <?php endforeach ?>
                                                                    </select>  
                                                                </div>
                                                            </div>
                                                            <div class="p-10 audience-actions">
                                                                <button type="button" class="btn-sm px-2 py-1 btn btn-primary audience-or-fitter"><?php _e("Or")?></button>
                                                                <button type="button" class="btn-sm px-2 py-1 btn btn-secondary audience-remove-filter audience-sub-remove-filter d-none"><i class="fad fa-trash-alt fs-12 p-0"></i></button>
                                                            </div>
                                                        </div>
                                                    </div> 
                                                    <?php endif ?>

                                                <?php $sub_count++; ?>
                                                <?php endforeach ?>

                                            <?php elseif($filter == "language"): ?>

                                                <?php $sub_count = 0; ?>
                                                <?php foreach ($comparator as $sub_comparator): ?>

                                                    <?php if ($sub_count != 0): ?>
                                                    <div class="audience-sub-item d-flex border bg-white m-l-90">
                                                        <div class="border-start border-success border-4 fs-14 fw-6 text-primary py-4 bg-gray-100 px-4"><?php _e("Or")?></div>
                                                        <div class="d-flex p-10 w-100 justify-content-between">
                                                            <div class="audience-filter-wrap d-flex ">
                                                                <div class="audience-sub-filter px-3 d-flex">
                                                                    <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][language][]" required>
                                                                        <?php foreach (get_language_codes() as $key => $value): ?>
                                                                            <option value="<?php _e($key)?>" <?php _ec( $sub_comparator==$key?"selected":"" )?>><?php _e($value)?></option>
                                                                        <?php endforeach ?>
                                                                    </select> 
                                                                </div>
                                                            </div>
                                                            <div class="p-10 audience-actions">
                                                                <button type="button" class="btn-sm px-2 py-1 btn btn-primary audience-or-fitter"><?php _e("Or")?></button>
                                                                <button type="button" class="btn-sm px-2 py-1 btn btn-secondary audience-remove-filter audience-sub-remove-filter d-none"><i class="fad fa-trash-alt fs-12 p-0"></i></button>
                                                            </div>
                                                        </div>
                                                    </div> 
                                                    <?php endif ?>
                                                    
                                                <?php $sub_count++; ?>
                                                <?php endforeach ?>

                                            <?php elseif($filter == "device"): ?>

                                                <?php $sub_count = 0; ?>
                                                <?php foreach ($comparator as $sub_comparator): ?>

                                                    <?php if ($sub_count != 0): ?>
                                                    <div class="audience-sub-item d-flex border bg-white m-l-90">
                                                        <div class="border-start border-success border-4 fs-14 fw-6 text-primary py-4 bg-gray-100 px-4"><?php _e("Or")?></div>
                                                        <div class="d-flex p-10 w-100 justify-content-between">
                                                            <div class="audience-filter-wrap d-flex ">
                                                                <div class="audience-sub-filter px-3 d-flex">
                                                                    <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][device][]" required>
                                                                        <option value="all"><?php _e("All")?></option>
                                                                        <option value="desktop" <?php _ec( $sub_comparator=="desktop"?"selected":"" )?>><?php _e("Desktop")?></option>
                                                                        <option value="mobile" <?php _ec( $sub_comparator=="mobile"?"selected":"" )?>><?php _e("Mobile")?></option>
                                                                        <option value="tablet" <?php _ec( $sub_comparator=="tablet"?"selected":"" )?>><?php _e("Tablet")?></option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="p-10 audience-actions">
                                                                <button type="button" class="btn-sm px-2 py-1 btn btn-primary audience-or-fitter"><?php _e("Or")?></button>
                                                                <button type="button" class="btn-sm px-2 py-1 btn btn-secondary audience-remove-filter audience-sub-remove-filter d-none"><i class="fad fa-trash-alt fs-12 p-0"></i></button>
                                                            </div>
                                                        </div>
                                                    </div> 
                                                    <?php endif ?>
                                                    
                                                <?php $sub_count++; ?>
                                                <?php endforeach ?>
                                                
                                            <?php elseif($filter == "browser"): ?>

                                                <?php $sub_count = 0; ?>
                                                <?php foreach ($comparator as $sub_comparator): ?>

                                                    <?php if ($sub_count != 0): ?>
                                                    <div class="audience-sub-item d-flex border bg-white m-l-90">
                                                        <div class="border-start border-success border-4 fs-14 fw-6 text-primary py-4 bg-gray-100 px-4"><?php _e("Or")?></div>
                                                        <div class="d-flex p-10 w-100 justify-content-between">
                                                            <div class="audience-filter-wrap d-flex ">
                                                                <div class="audience-sub-filter px-3 d-flex">
                                                                    <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][browser][]" required>
                                                                        <option value="all"><?php _e("All")?></option>
                                                                        <option value="chrome" <?php _ec( $sub_comparator=="chrome"?"selected":"" )?>><?php _e("Chrome")?></option>
                                                                        <option value="safari" <?php _ec( $sub_comparator=="safari"?"selected":"" )?>><?php _e("Safari")?></option>
                                                                        <option value="firefox" <?php _ec( $sub_comparator=="firefox"?"selected":"" )?>><?php _e("Firefox")?></option>
                                                                        <option value="opera" <?php _ec( $sub_comparator=="opera"?"selected":"" )?>><?php _e("Opera")?></option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="p-10 audience-actions">
                                                                <button type="button" class="btn-sm px-2 py-1 btn btn-primary audience-or-fitter"><?php _e("Or")?></button>
                                                                <button type="button" class="btn-sm px-2 py-1 btn btn-secondary audience-remove-filter audience-sub-remove-filter d-none"><i class="fad fa-trash-alt fs-12 p-0"></i></button>
                                                            </div>
                                                        </div>
                                                    </div> 
                                                    <?php endif ?>
                                                    
                                                <?php $sub_count++; ?>
                                                <?php endforeach ?>


                                            <?php elseif($filter == "os"): ?>

                                                <?php $sub_count = 0; ?>
                                                <?php foreach ($comparator as $sub_comparator): ?>

                                                    <?php if ($sub_count != 0): ?>
                                                    <div class="audience-sub-item d-flex border bg-white m-l-90">
                                                        <div class="border-start border-success border-4 fs-14 fw-6 text-primary py-4 bg-gray-100 px-4"><?php _e("Or")?></div>
                                                        <div class="d-flex p-10 w-100 justify-content-between">
                                                            <div class="audience-filter-wrap d-flex ">
                                                                <div class="audience-sub-filter px-3 d-flex">
                                                                    <select class="form-select me-3" name="comparator[<?php _ec( $filter_key )?>][os][]" required>
                                                                        <option value="all"><?php _e("All")?></option>
                                                                        <option value="windows" <?php _ec( $sub_comparator=="windows"?"selected":"" )?>><?php _e("Windows")?></option>
                                                                        <option value="mac" <?php _ec( $sub_comparator=="mac"?"selected":"" )?>><?php _e("Mac")?></option>
                                                                        <option value="android" <?php _ec( $sub_comparator=="android"?"selected":"" )?>><?php _e("Android")?></option>
                                                                        <option value="linux" <?php _ec( $sub_comparator=="linux"?"selected":"" )?>><?php _e("Linux")?></option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="p-10 audience-actions">
                                                                <button type="button" class="btn-sm px-2 py-1 btn btn-primary audience-or-fitter"><?php _e("Or")?></button>
                                                                <button type="button" class="btn-sm px-2 py-1 btn btn-secondary audience-remove-filter audience-sub-remove-filter d-none"><i class="fad fa-trash-alt fs-12 p-0"></i></button>
                                                            </div>
                                                        </div>
                                                    </div> 
                                                    <?php endif ?>
                                                    
                                                <?php $sub_count++; ?>
                                                <?php endforeach ?>
                                                
                                            <?php endif ?>

                                        <?php endif ?>

                                    <?php //endif ?>

                                    <?php $comparator_count++; ?>
                                <?php endforeach ?>

                            </div>
                        </div>

                    <?php $filter_count++; ?>
                    <?php endforeach ?>

                <?php endif ?>

            <?php endif ?>
                
        </div>

        <div class="card border mt-5 b-r-10">
            <div class="card-body px-4 py-3 text-center">
                <button type="submit" class="btn btn-dark"><?php _e("Submit")?></button>
            </div>
        </div>
    </div>
</form>

<div class="audience-main d-none">
    <div class="audience-item mt-4" data-index="{index}">
        <div class="audience-item-main d-flex border bg-white">
            <div class="border-start border-success border-4 fs-14 fw-6 text-primary py-4 bg-gray-100 px-4 w-90">
                <span class="audience-select-text"><?php _e("Select")?></span>
                <span class="audience-and-text d-none"><?php _e("And")?></span>
            </div>
            <div class="d-flex p-10 justify-content-between w-100">
                <div class="audience-filter-wrap d-flex">
                    <select class="form-select audience-filter w-200" name="filter[{index}]" required>
                        <option value=""><?php _e("Select")?></option>                                
                        <?php foreach ($filter_list as $key => $value): ?>
                            <option value="<?php _ec( $key )?>"><?php _e( $value )?></option>                                
                        <?php endforeach ?>
                    </select>
                    <div class="audience-sub-filter px-3 d-flex"></div>
                </div>
                <div class="p-10">
                    <span class="audience-actions d-none">
                        <button type="button" class="btn-sm px-2 py-1 btn btn-primary audience-btn-action audience-and-fitter"><?php _e("And")?></button>
                        <button type="button" class="btn-sm px-2 py-1 btn btn-primary audience-btn-action audience-or-fitter"><?php _e("Or")?></button>
                    </span>
                    <button type="button" class="btn-sm px-2 py-1 btn btn-secondary audience-remove-filter audience-main-remove-filter d-none"><i class="fad fa-trash-alt fs-12 p-0"></i></button>
                </div>
            </div>
        </div>

        <div class="audience-sub-list">

        </div>
    </div>
</div>

<div class="audience-sub-main d-none">
    <div class="audience-sub-item d-flex border bg-white m-l-90">
        <div class="border-start border-success border-4 fs-14 fw-6 text-primary py-4 bg-gray-100 px-4"><?php _e("Or")?></div>
        <div class="d-flex p-10 w-100 justify-content-between">
            <div class="audience-filter-wrap d-flex ">
                <div class="audience-sub-filter px-3 d-flex"></div>
            </div>
            <div class="p-10 audience-actions">
                <button type="button" class="btn-sm px-2 py-1 btn btn-primary audience-or-fitter"><?php _e("Or")?></button>
                <button type="button" class="btn-sm px-2 py-1 btn btn-secondary audience-remove-filter audience-sub-remove-filter d-none"><i class="fad fa-trash-alt fs-12 p-0"></i></button>
            </div>
        </div>
    </div>
</div>

<div class="audience-type d-none" data-type="title">
    <select class="form-select me-3" name="comparator[{index}][]" required>
        <option value="contains"><?php _e("Contains")?></option>
        <option value="not_contains"><?php _e("Not Contains")?></option>
    </select>    
    <input type="text" class="form-control" name="value[{index}][]" required placeholder="<?php _e("Value")?>">
</div>

<div class="audience-type d-none" data-type="description">
    <select class="form-select me-3" name="comparator[{index}][]" required>
        <option value="contains"><?php _e("Contains")?></option>
        <option value="not_contains"><?php _e("Not Contains")?></option>
    </select>    
    <input type="text" class="form-control" name="value[{index}][]" required placeholder="<?php _e("Value")?>">
</div>

<div class="audience-type d-none" data-type="url">
    <select class="form-select me-3" name="comparator[{index}][]" required>
        <option value="equals"><?php _e("Equals")?></option>
        <option value="not_rquals"><?php _e("Not Equals")?></option>
        <option value="contains"><?php _e("Contains")?></option>
        <option value="not_contains"><?php _e("Not Contains")?></option>
    </select>    
    <input type="text" class="form-control" name="value[{index}][]" required placeholder="<?php _e("Value")?>">
</div>

<div class="audience-type d-none" data-type="subscription_url">
    <select class="form-select me-3" name="comparator[{index}][]" required>
        <option value="contains"><?php _e("Contains")?></option>
        <option value="not_contains"><?php _e("Not Contains")?></option>
    </select>    
    <input type="text" class="form-control" name="value[{index}][]" required placeholder="<?php _e("Value")?>">
</div>

<div class="audience-type d-none" data-type="subscription_date">
    <select class="form-select audience-select-time me-3" name="comparator[{index}][]" required>
        <option value="within"><?php _e("Within")?></option>
        <option value="earlier_than"><?php _e("Earlier Than")?></option>
        <option value="on"><?php _e("On")?></option>
        <option value="before"><?php _e("Before")?></option>
        <option value="after"><?php _e("After")?></option>
    </select>    
    <input type="text" class="form-control audience-time-date d-none" name="value[{index}][date][]" placeholder="<?php _e("Value")?>">
    <span class="audience-time-input d-flex align-items-center">
        <input type="number" min="1" class="form-control" name="value[{index}][hour][]" placeholder="<?php _e("Value")?>"> <span class="ms-2"><?php _e("Hs")?></span>
    </span>
</div>

<div class="audience-type d-none" data-type="total_visits">
    <select class="form-select me-3" name="comparator[{index}][]" required>
        <option value="equal"><?php _e("Equal To")?></option>
        <option value="greater_than"><?php _e("Greater Than")?></option>
        <option value="less_than"><?php _e("Less Than")?></option>
    </select>  
    <span class="audience-time-input d-flex align-items-center">  
        <input type="number" min="1" class="form-control" name="value[{index}][]" required placeholder="<?php _e("Value")?>"> <span class="ms-2"><?php _e("Hs")?></span>
    </span>
</div>

<div class="audience-type d-none" data-type="first_visit">
    <select class="form-select audience-select-time me-3" name="comparator[{index}][]" required>
        <option value="within"><?php _e("Within")?></option>
        <option value="earlier_than"><?php _e("Earlier Than")?></option>
        <option value="on"><?php _e("On")?></option>
        <option value="before"><?php _e("Before")?></option>
        <option value="after"><?php _e("After")?></option>
    </select>    
    <input type="text" class="form-control audience-time-date d-none" name="value[{index}][date][]" placeholder="<?php _e("Value")?>">
    <span class="audience-time-input d-flex align-items-center">
        <input type="number" min="1" class="form-control" name="value[{index}][hour][]" placeholder="<?php _e("Value")?>"> <span class="ms-2"><?php _e("Hs")?></span>
    </span>
</div>

<div class="audience-type d-none" data-type="last_visit">
    <select class="form-select audience-select-time me-3" name="comparator[{index}][]" required>
        <option value="within"><?php _e("Within")?></option>
        <option value="earlier_than"><?php _e("Earlier Than")?></option>
        <option value="on"><?php _e("On")?></option>
        <option value="before"><?php _e("Before")?></option>
        <option value="after"><?php _e("After")?></option>
    </select>    
    <input type="text" class="form-control audience-time-date d-none" name="value[{index}][date][]" placeholder="<?php _e("Value")?>">
    <span class="audience-time-input d-flex align-items-center">
        <input type="number" min="1" class="form-control" name="value[{index}][hour][]" placeholder="<?php _e("Value")?>"> <span class="ms-2"><?php _e("Hs")?></span>
    </span>
</div>

<div class="audience-type d-none" data-type="city">
    <select class="form-select me-3" name="comparator[{index}][city][]">
        <option value="is"><?php _e("Is")?></option>
    </select>    
    <input type="text" class="form-control" name="value[{index}][]" required placeholder="<?php _e("Value")?>">
</div>

<div class="audience-type d-none" data-type="country">
    <select class="form-select me-3" name="comparator[{index}][country][]">
        <?php foreach (list_countries() as $key => $value): ?>
            <option value="<?php _e($key)?>"><?php _e($value)?></option>
        <?php endforeach ?>
    </select>  
</div>

<div class="audience-type d-none" data-type="language">
    <select class="form-select me-3" name="comparator[{index}][language][]">
        <?php foreach (get_language_codes() as $key => $value): ?>
            <option value="<?php _e($key)?>"><?php _e($value)?></option>
        <?php endforeach ?>
    </select>    
</div>

<div class="audience-type d-none" data-type="device">
    <select class="form-select me-3" name="comparator[{index}][device][]">
        <option value="all"><?php _e("All")?></option>
        <option value="desktop"><?php _e("Desktop")?></option>
        <option value="mobile"><?php _e("Mobile")?></option>
        <option value="tablet"><?php _e("Tablet")?></option>
    </select>    
</div>

<div class="audience-type d-none" data-type="os">
    <select class="form-select me-3" name="comparator[{index}][os][]">
        <option value="all"><?php _e("All")?></option>
        <option value="windows"><?php _e("Windows")?></option>
        <option value="mac"><?php _e("Mac")?></option>
        <option value="android"><?php _e("Android")?></option>
        <option value="linux"><?php _e("Linux")?></option>
    </select>    
</div>

<div class="audience-type d-none" data-type="browser">
    <select class="form-select me-3" name="comparator[{index}][browser][]">
        <option value="all"><?php _e("All")?></option>
        <option value="chrome"><?php _e("Chrome")?></option>
        <option value="safari"><?php _e("Safari")?></option>
        <option value="firefox"><?php _e("Firefox")?></option>
        <option value="opera"><?php _e("Opera")?></option>
    </select>    
</div>


<script type="text/javascript">
    
    var data = JSON.parse('<?php _ec( json_encode($filter_list) )?>');

    $(function(){
        var count = 1;
        var html = $(".audience-main").html();
        html = html.replaceAll("{index}", 0);
        var wrap = $(".audience-wrap");
        var check_empty = wrap.html().trim().replaceAll(" ", "");
        if(check_empty == ""){
            wrap.append(html);
        }else{
            Core.calendar();
        }

        $(document).on("click", ".audience-and-fitter", function(){
            var main = $(".audience-main");
            /**/
            var html = $(".audience-main").html();


            html = html.replaceAll("{index}", count);
            var wrap = $(".audience-wrap");
            wrap.append(html);
            count++;

            if($(".audience-wrap .audience-item").length > 0){
                $(".audience-remove-filter").removeClass("d-none");
            }

            $(".audience-wrap .audience-item").each(function(index){
                if(index){
                    $(this).find(".audience-select-text").addClass("d-none");
                    $(this).find(".audience-and-text").removeClass("d-none");
                }else{
                    $(this).find(".audience-select-text").removeClass("d-none");
                    $(this).find(".audience-and-text").addClass("d-none");
                }
            });
        });

        $(document).on("click", ".audience-or-fitter", function(){
            var index = $(this).parents(".audience-item").attr("data-index");
            var value = $(this).parents(".audience-item").find(".audience-filter").val();
            if(value != ""){
                var sub_filter = $(".audience-sub-main");
                var sub_item = $(".audience-type[data-type='"+value+"']").html();
                sub_filter.find(".audience-sub-filter").append(sub_item).html();
                var html = sub_filter.html();
                html = html.replaceAll("{index}", index);
                sub_filter.find(".audience-sub-filter").html("");

                var wrap = $(this).parents(".audience-item").find(".audience-sub-list");
                wrap.append(html);

                $(".audience-sub-remove-filter").removeClass("d-none");

            }
        });

        $(document).on("click", ".audience-remove-filter", function(){
            var item = $(this).parents(".audience-sub-item");
            console.log(item);
            if(item.length > 0){
                item.remove()
            }else{
                $(this).parents(".audience-item").remove();
            }

            if($(".audience-wrap .audience-item").length <= 1){
                $(".audience-main-remove-filter").addClass("d-none");
            }

            $(".audience-wrap .audience-item").each(function(index){
                if(index){
                    $(this).find(".audience-select-text").addClass("d-none");
                    $(this).find(".audience-and-text").removeClass("d-none");
                }else{
                    $(this).find(".audience-select-text").removeClass("d-none");
                    $(this).find(".audience-and-text").addClass("d-none");
                }
            });
        });

        $(document).on("change", ".audience-select-time", function(){
            var val = $(this).val();
            $(this).next(".audience-time-date").addClass("date");
            Core.calendar();

            if(val == "on" || val == "before" || val == "after"){
                $(this).parents(".audience-sub-filter").find(".audience-time-date").removeClass("d-none");
                $(this).parents(".audience-sub-filter").find(".audience-time-input").addClass("d-none");
            }else{
                $(this).parents(".audience-sub-filter").find(".audience-time-date").addClass("d-none");
                $(this).parents(".audience-sub-filter").find(".audience-time-input").removeClass("d-none");
            }

        });

        $(document).on("change", ".audience-filter", function(){
            var index = $(this).parents(".audience-item").attr("data-index");
            var value = $(this).val();
            var wrap = $(this).parent(".audience-filter-wrap");
            var html = $(".audience-type[data-type='"+value+"']").html();
            
            if (html != undefined) {
                html = html.replaceAll("{index}", index);
                wrap.find(".audience-sub-filter").html(html);
                $(this).parents(".audience-item").find(".audience-actions").removeClass("d-none");
            }else{
                wrap.find(".audience-sub-filter").html("");
                $(this).parents(".audience-item").find(".audience-actions").addClass("d-none");
            }

            wrap.parents(".audience-item").find(".audience-sub-list").html("");
        });

    });

</script>
