<?php
/*
 * Template Name: Budget Configurator
 */
?>
<?php
if(session_id() == ''){
    ob_start();
    session_start();
}
if(session_id() == ''){
   echo ';;--;;'.json_encode(array('type'=>'error', 'msg'=>'Error: Unable to initialize Session!'));
   die('Error: Unable to initialize Session!');
}
$cStep ='';
$phases = get_posts('posts_per_page=-1&post_type=phases&orderby=menu_order&order=ASC&fields=ids');
if(isset($_POST['submit'])){
    $submit = $_POST['submit'];
    if(isset($_POST[$submit.'_phase']))
        $cStep = $_POST[$submit.'_phase'];
    else $cStep = 'calculate';
    if(isset($_POST['pbc_variation']) && $_POST['submit']=='next'){
        if(!isset($_SESSION['pbc_variation']) || !is_array($_SESSION['pbc_variation'])){
            $_SESSION['pbc_variation'] = array();
        }
        //unset($_SESSION['pbc_variation']);
        foreach($_POST['pbc_variation'] as $key => $pbc_variation)
        {
            if(!empty($phases)){
                $price = $option_name = '';
                $pricegroup = get_post_meta($pbc_variation, 'pbc_pricegroup', true);
                if(isset($pbc_pricevar)){
                    $term = get_term_by( 'id', $pbc_pricevar, 'measures');
                    if(!empty($term)){
                        foreach($pricegroup as $details ){
                            if($term->term_id == $details['pbc_meaprice']){
                                $option_name = $term->name;
                                $price = $details['pbc_pricem'];
                            }
                        }
                    }
                }else{
                    if(isset($pricegroup[0]['pbc_pricem']))
                        $price = $pricegroup[0]['pbc_pricem'];
                }
                $phase_id = $phases[((int)$key-1)];
                $_SESSION['pbc_variation'][$key]['phase']['id'] = $phase_id;
                $_SESSION['pbc_variation'][$key]['phase']['name'] = get_the_title($phase_id);
                $_SESSION['pbc_variation'][$key]['var']['id'] = $pbc_variation;
                $_SESSION['pbc_variation'][$key]['var']['name'] = get_the_title($pbc_variation);
                if($option_name) $_SESSION['pbc_variation'][$key]['var']['name'].= ' ['.$option_name.']';
                $_SESSION['pbc_variation'][$key]['var']['price'] = $price;
            }
        }
        ksort($_SESSION['pbc_variation'], SORT_NUMERIC);
    }
}elseif(isset($_GET['phase'])) $cStep = $_GET['phase'];
if(empty($cStep)) $cStep = 1;
?>
<?php if(!defined('DOING_AJAX')) get_header(); ?>
<?php if(!defined('DOING_AJAX')){ ?>
<style>
    .btn{
        background: #c0c0c0;
        color: #333;
    }
    .btn-share{
        background: #c0c0c0;
        color: #333;
    }
    .hidden{display: none !important;}
    #content{max-width: 1200px;margin: 0 auto 40px;}
    .configurator_steps_nav{width: 100%;margin: 10px auto;float: left;padding-right: 15px;}
    .configurator_steps_nav ul{margin: 0;padding: 0; list-style: none;}
    .configurator_steps_nav li.configurator_steps{
        padding: 0;
        margin: 0;
        margin-bottom: 3px;
        background: #ededed;
        color: #000;
        position: relative;
        float: left;
        width: 12.5%;
        height: 40px;
        line-height: 40px;
        vertical-align: middle;
        text-align: center;
        word-wrap: break-word;
        padding: 5px 10px;
        border-right: 2px solid #fff;
    }
    .configurator_steps_nav li.configurator_steps .step-name{
        background: none;
        border: none;
        padding: 0;
        display: inline-block;
        height: auto;
        width: 100%;
        padding-right: 0;
        height: 30px;
        line-height: 30px;
        vertical-align: top;
        font-size: 14px;
    }
    .configurator_steps_nav li.configurator_steps .step-arrow-button {
        width: 30px;
        height: 30px;
        position: absolute;
        top: 5px;
        z-index: 1;
        right: -15px;
        -webkit-transform: rotate(-45deg);
        -moz-transform: rotate(-45deg);
        -ms-transform: rotate(-45deg);
        transform: rotate(-45deg);
        border-bottom: 2px solid #FFFFFF;
        border-right: 2px solid #FFFFFF;
        background: #EDEDED;
        display: inline-block;
    }
    .configurator_steps_nav li.configurator_steps.active {background: #949697;}
    .configurator_steps_nav li.configurator_steps.active .step-name{
        color: #fff;
        font-weight: bold;
    }
    .configurator_steps_nav li.configurator_steps.active .step-arrow-button{background: #949697;}
    .configurator-left {
        width: 50%;
        float: left;
    }
    .configurator-right {
        display: block;
        width: 49%;
        float: right;
        vertical-align: top;
    }
    .configurator-left .phase_title{text-transform: uppercase;font-size: 20px;}
    .phase_variations{margin-top: 40px;}
    .phase_variations ul{margin: 0; list-style: none;}
    .phase_variations ul li.variation_list {
    width: 24%;
    display: inline-block;
    font-size: 14px;
    margin-bottom: 20px;
    text-align: center;
    }
    .product_preview {
        position: relative;
        text-align: left;
        max-width: 570px;
        overflow: hidden;
    }
    .product_preview .image-wrap img{width: 100%;max-width: 570px;}
    .configurator_form_action {
        text-align: right;
        clear: both;
        margin: 20px 0;
    }
    .configurator_summary {
        clear: both;
        border: 1px solid #949697;
        padding: 10px;
        margin: 0 auto;
        max-width: 400px;
    }
    .configurator_result_share{max-width: 400px;margin: 20px auto;text-align: center;}
    .email_submit_fields{margin-top: 20px;}
    .configurator_summary .title {
        text-transform: uppercase;
        font-size: 20px;
        margin-bottom: 10px;
    }
    .configurator_summary table{width: 100%;border: 0px;}
    .configurator_summary table td {
        font-size: 16px;
    }
    .configurator_summary table td.price {
        text-align: right;
    }
    .configurator_summary tr.phase-total_price td {
        padding-top: 15px;
    }
    .configurator_form_action .prev, .configurator_form_action .next{display: inline-block;}
    .status_loader.fixed{position: fixed;width: 100%;height: 100%;background: rgba(0, 0, 0, 0.8);vertical-align: middle;text-align: center;top: 0;z-index: 9999;left:0;}
    .status_loader.product_preview_status.fixed{position: absolute;}
    .status_loader.fixed > div {
        position: relative;
        top: 50%;
        transform: translateY(-50%);
        z-index: 9999;
        color: #fff;
        border-radius: 100%;
        display: inline-block;
        -webkit-animation: bouncedelay 1.4s infinite ease-in-out;
        animation: bouncedelay 1.4s infinite ease-in-out;
        -webkit-animation-fill-mode: both;
        animation-fill-mode: both;
    }
    @media (max-width:768px) {
        .configurator_steps_nav{padding-right: 0px;}
        .configurator_steps_nav li.configurator_steps{overflow: hidden;padding: 5px;}
        .configurator_steps_nav li.configurator_steps .step-arrow-button{display: none;}
    }
</style>

<?php $queried_object = get_queried_object();?>
<div id="content" class="clearfix row">
    <div id="main" class="col-sm-12 clearfix" role="main">
        <div class="page-header">
            <h1><?php echo get_the_title($queried_object->ID); ?></h1>
        </div>
        <div class="page-content">
            <p><?php $post_object = get_post( $queried_object->ID );
                echo $post_object->post_content; ?></p>
        </div>
        <div class="page-configurator">
<?php } //defined('DOING_AJAX')?>
        <?php
        if(!empty($phases)){?>
            <div class="configurator_steps_nav" id="configurator_steps_nav">
                <ul>
                <?php $steps=1;
                foreach($phases as $phase){?>
                    <li class="configurator_steps step-<?php echo $steps;?> <?php if($cStep == $steps) echo 'active';?>">
                        <div class="stepContainer">
                            <div class="step-name"><?php echo get_the_title($phase);?></div>
                            <span class="step-arrow-button"></span>
                        </div>
                    </li>
                <?php $steps++;
                }?>
                </ul>
            </div>
            <div class="phase_detail">
                <form action="" method="post" name="configurator-form" id="configurator-form">
                <?php
                if($cStep !='calculate'){
                    $phase_id = $phases[((int)$cStep-1)];?>
                    <div class="configurator-left">
                        <div class="phase_title"><?php echo get_the_title($phase_id);?></div>
                        <div class="phase_content">
                            <?php $post_object = get_post( $phase_id );
                                echo $post_object->post_content;?>
                        </div>
                        <div class="phase_variations">
                        <?php $variations = get_posts('posts_per_page=-1&post_type=variation&meta_key=pbc_phase&meta_value='.$phase_id.'&fields=ids');
                            if(!empty($variations))
                            {
                                foreach($variations as $key => $variation)
                                {
                                    $pbc_depends = get_post_meta($variation, 'pbc_depends', true);
                                    if(!empty($pbc_depends))
                                    {
                                        $prevVar = array();
                                        foreach($pbc_depends as $deps)
                                        {
                                            $arr = explode('|', $deps['pbc_depvar']);
                                            if(!empty($arr[0]) && !empty($arr[1])){
                                                $prevVar[(int)$arr[0]][] = $arr[1];
                                            }
                                        }
                                        if($cStep != 1 && isset($_SESSION['pbc_variation'][$cStep-1]))
                                        {
                                            foreach($_SESSION['pbc_variation'] as $sPhaseKey => $sVariations)
                                            {
                                                if(isset($prevVar[$sPhaseKey]) && !in_array($_SESSION['pbc_variation'][$sPhaseKey]['var']['id'], $prevVar[$sPhaseKey]))
                                                {
                                                    unset($variations[$key]);
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    $variations = array_values($variations);
                                    sort($variations);
                                }
                                if(isset($_SESSION) && is_array($_SESSION['pbc_variation']) && isset($_SESSION['pbc_variation'][$cStep]) &&
                                    in_array($_SESSION['pbc_variation'][$cStep]['var']['id'], $variations)
                                )
                                    $sVar = $_SESSION['pbc_variation'][$cStep]['var']['id'];
                                else $sVar = $variations[0];
                                if(!empty($variations)){
                                ?>
                                <ul>
                                    <?php foreach($variations as $variation){?>
                                    <li class="variation_list">
                                        <?php $imgicon = get_post_meta($variation, 'pbc_imgicon', true);
                                        if($imgicon){
                                            $icon_image = wp_get_attachment_image_src($imgicon, 'pbc_icon', true);
                                        ?>
                                            <div class="variation_img"><img src="<?php echo $icon_image[0];?>" width="<?php echo $icon_image[1]; ?>" height="<?php echo $icon_image[2]; ?>" /></div>
                                        <?php }?>
                                        <label>
                                            <input type="radio" class="pbc_variation" name="pbc_variation[<?php echo $cStep;?>]" value="<?php echo $variation;?>" <?php if($variation == $sVar) echo 'checked="checked"';?>/> <?php echo get_the_title($variation);?>
                                        </label>
                                        <?php
                                        $priceVar = array();
                                        $pricegroup = get_post_meta($variation, 'pbc_pricegroup', true);
                                        if(!empty($pricegroup) && isset($pricegroup[0]['pbc_meaprice'])){
                                            foreach($pricegroup as $key => $details){
                                                if(!empty($details['pbc_meaprice'])){
                                                    $term = get_term_by( 'id', $details['pbc_meaprice'], 'measures');
                                                    if(!empty($term)){
                                                        $priceVar[$term->term_id] = $term->name;
                                                    }
                                                }
                                            }
                                        }
                                        if(!empty($priceVar)){?>
                                            <div class="pbc_pricevarwrap"><select name="pbc_pricevar">
                                                <?php foreach($priceVar as $termid => $termname){?>
                                                <option value="<?php echo $termid;?>"><?php echo $termname;?></option>
                                                <?php }?>
                                            </select></div>
                                        <?php
                                        }?>
                                    </li>
                                <?php }?>
                                </ul>
                            <?php }
                            }else{?>
                            <div class="error"><?php _e('No Variations Avaiable','pbc');?></div>
                        <?php }?>
                        </div>
                    </div>
                <div class="configurator-right">
                <?php }//cStep!=calculate?>
                    <div class="product_preview">
                        <div class="image-wrap">
                            <?php
                            if($sVar){
                                $imgprod = get_post_meta($sVar, 'pbc_imgprod', true);
                                if($imgprod){ $imgprodurl = wp_get_attachment_image_src($imgprod, 'full', true);}?>
                            <?php }
                            if(isset($imgprodurl) && $imgprodurl){?>
                                <img src="<?php echo $imgprodurl[0];?>" alt="product image"/>
                            <?php }
                            /* else{?>
                                <img src="<?php echo WPPBC_PLUGIN_URL.'preview-img.png';?>" alt="product image"/>
                            <?php }*/?>
                        </div>
                        <div class="status_loader product_preview_status fixed hidden"></div>
                    </div>
                    <div class="configurator_form_action">
                        <?php
                            if($cStep == 1)
                            {
                                $prev_step = '';
                                $prev_button = '';
                            }elseif($cStep == 'calculate'){
                                $prev_step = count($phases);
                                $prev_button = __('Back', 'pbc');
                            }else{
                                $prev_step = $cStep-1;
                                $prev_button = __('Back', 'pbc');
                            }

                            if($cStep == 'calculate'){
                                $next_step = 'calculate';
                                $next_button = '';
                            }elseif($cStep == count($phases)){
                                $next_step = 'calculate';
                                $next_button = __('Calculate', 'pbc');
                            }else{
                                $next_step = $cStep+1;
                                $next_button = __('Next', 'pbc');
                            }
                        ?>
                        <input type="hidden" name="pbc_current_phase" value="<?php echo $cStep;?>"/>
                        <?php if($prev_step && $prev_button){?>
                        <div class="prev <?php if(empty($prev_step)) echo 'hidden';?>">
            			    <input type="hidden" name="prev_phase" value="<?php echo $prev_step;?>"/>
            			    <button type="submit" name="submit" value="prev" class="btn btn-prev"><?php echo $prev_button;?></button>
            		    </div>
                        <?php }?>
            		    <div class="next">
            			    <?php if($next_step){?><input type="hidden" name="next_phase" value="<?php echo $next_step;?>"/><?php }?>
            			    <?php if($next_button){?><button type="submit" name="submit" value="next" class="btn btn-next"><?php echo $next_button;?></button><?php }?>
            		    </div>
                    </div>
                    <div class="configurator_summary">
                        <?php if(isset($_SESSION) && isset($_SESSION['pbc_variation']) && is_array($_SESSION['pbc_variation'])){?>
                            <div class="title"><?php _e('Actual Configuration','pbc');?></div>
                            <table>
                            <?php
                                if($cStep == 'calculate'){
                                    $count = count($phases);
                                    $total_price = '';
                                }
                                else $count = $cStep;
                                for($i=1; $i<=$count; $i++){
                                    if(!isset($_SESSION['pbc_variation'][$i]))
                                        continue;
                                    $phaseKey = $i;
                                    $varId = $_SESSION['pbc_variation'][$i]['var']['id'];
                                    $varName = $_SESSION['pbc_variation'][$i]['var']['name'];
                                    $varPrice = $_SESSION['pbc_variation'][$i]['var']['price'];
                                    if($cStep == 'calculate'){
                                        $total_price += (int) $varPrice;
                                    }
                                ?>
                                <tr class="variation_selected phase-<?php echo $phaseKey;?>">
                                    <td class="name"><?php echo $varName;?></td>
                                    <td class="price">
                                        <?php
                                            if($varPrice) echo $varPrice.' €';
                                            else echo '-';
                                        ?>
                                    </td>
                                </tr>
                            <?php }?>
                            <?php if($cStep == 'calculate'){?>
                                <tr class="variation_selected phase-total_price">
                                    <td class="name"><?php _e('Total','pbc');?></td>
                                    <td class="price">
                                        <?php
                                            if($total_price) echo $total_price.' €';
                                            else echo '-';
                                        ?>
                                    </td>
                                </tr>
                            <?php }?>
                            </table>
                        <?php }?>
                    </div>
                    <?php if($cStep == 'calculate'){?>
                        <div class="configurator_result_share">
                            <button type="submit" name="submit" class="btn btn-share" value="result_email"><?php _e('Email','pbc');?></button>
                            <a href="?phase=calculate&amp;configurator=pdf" target="_blank" class="btn btn-share" title="Generate PDF"><?php _e('PDF','pbc');?></a>
                            <?php if(isset($_POST['submit']) && ($_POST['submit'] == 'result_email' || $_POST['submit'] == 'email_send')){?>
                                <?php if(!isset($_SESSION['pbc_output']) || $_SESSION['pbc_output']['type'] != 'success'){?>
                                <div class="email_submit_fields">
                                    <input type="text" name="email_field" placeholder="separate multiple email by comma"/>
                                    <button type="submit" name="submit" class="btn btn-submit" value="email_send"><?php _e('Send','pbc');?></button>
                                </div>
                                <?php }?>
                                <?php if(isset($_SESSION['pbc_output'])){?>
                                    <div class="result_submit_action <?php echo $_SESSION['pbc_output']['type'];?>">
                                        <?php echo $_SESSION['pbc_output']['response'];?>
                                    </div>
                                <?php unset($_SESSION['pbc_output']);}?>
                            <?php }?>
                        </div>
                    <?php }?>
                <?php if($cStep != 'calculate'){?>
                </div>
                <?php }//$cStep != 'calculate'?>
                </form>
            </div>

            <?php if(!defined('DOING_AJAX')){?>
            <script type="text/javascript">
            jQuery(function($){
                $(document).on('click', 'input[type=radio].pbc_variation', function(){
                    $('.product_preview').find('.product_preview_status').removeClass('hidden').html('<div><img src="<?php echo WPPBC_PLUGIN_URL;?>loading.gif"/></div>').show();
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php');?>',  //server script to process data
                        type: 'POST',
                        data: $('#configurator-form').serialize()+'&current_phase='+$('input[name=pbc_current_phase]').val()+'&action=variation_selected',
                        dataType: "html",
                        success: function(response) {
                            var resArr = response.split(';;--;;');
                            var obj = jQuery.parseJSON(resArr[1]);
                            if(obj.type == 'error'){
                                $('.product_preview').find('.product_preview_status').html('<div>'+obj.msg+'</div>').show().delay(4000, function(){
                                    window.setTimeout( function(){
                                        $('.product_preview').find('.product_preview_status').html('').addClass('hidden');
                                    }, 1000 );
                                });
                            }else if(obj.type == 'success'){
                                $('.product_preview').find('.product_preview_status').addClass('hidden');
                                if(obj.url)
                                    $('.product_preview').find('.image-wrap').html('<img src="'+obj.url+'" alt="product image"/>').show();
                            }
                        }
                    });
                });
                $(document).on('click', 'button[name=submit]', function(e){
                    var submit_val = $(this).val();
            		var form_id = 'configurator-form';
            		e.preventDefault();
                    $(document).find('.status_loader.phase_detail_loader').removeClass('hidden').html('<div><img src="<?php echo WPPBC_PLUGIN_URL;?>loading.gif"/></div>').show();
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php');?>',  //server script to process data
                        type: 'POST',
                        data: $('#'+form_id).serialize()+'&current_phase='+$('input[name=pbc_current_phase]').val()+'&submit='+submit_val+'&action=configurator_submit',
                        dataType: "html",
                        success: function(response) {
                            $('.page-configurator').html(response);
                            if(
                                '<?php echo $next_step;?>' != 'calculate' &&
                                (submit_val == 'prev' || submit_val == 'next') && $(document).find('input[type=radio].pbc_variation').length == 0
                            )
                            {
                                $(document).find('button[name=submit][value='+submit_val+']').trigger('click');
                            }else{
                                $(document).find('.status_loader.phase_detail_loader').html('').addClass('hidden');
                                //$('.page-configurator').html(response);
                                if($(document).find('.result_submit_action').length > 0){
                                    $(document).find('.result_submit_action').show().delay(3000).fadeOut(400);
                                }
                            }
                        }
                    });
                });
            });
            </script>
            <?php }//defined('DOING_AJAX')?>
            <div class="status_loader phase_detail_loader fixed hidden"></div>
        <?php
        }?>
<?php if(!defined('DOING_AJAX')){?>
        </div>
    </div>
</div>
<?php }//defined('DOING_AJAX')?>
<?php if(!defined('DOING_AJAX')) get_footer(); ?>
