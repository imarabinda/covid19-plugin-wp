<?php
$all_options = get_option( 'covid_options' );
wp_enqueue_style( 'covid' );
$getFData=new stdClass();
$getFData->confirmed=0;
$getFData->deaths=0;
$getFData->recovered=0;
$getFData->deltaconfirmed=0;
$getFData->deltadeaths=0;
$getFData->active=0;
if(is_array($data)){
    foreach($data as $key=>$value){
        $getFData->confirmed+=$value->confirmed;
        $getFData->deaths+=$value->deaths;
        $getFData->recovered+=$value->recovered;
        $getFData->deltaconfirmed+=$value->deltaconfirmed;
        $getFData->deltadeaths+=$value->deltadeaths;
        $getFData->deltarecovered+=$data->deltarecovered;
        $getFData->active+=$value->active;}}
else {$getFData->confirmed+=$data->confirmed;
$getFData->deaths+=$data->deaths;
$getFData->recovered+=$data->recovered;
$getFData->deltaconfirmed+=isset($data->deltaconfirmed)?$data->deltaconfirmed:0;
    $getFData->deltadeaths+=isset($data->deltadeaths)?$data->deltadeaths:0;
    $getFData->deltarecovered+=isset($data->deltarecovered)?$data->deltarecovered:0;
$getFData->active+=isset($data->active)?$data->active:0;}
?>
<div class="covid19-card full-data <?php echo $all_options['cov_theme'];?> <?php if($all_options['cov_rtl']==!$checked) echo 'rtl_enable'; ?>" style="font-family:<?php echo $all_options['cov_font'];?>">
   <h4 class="covid19-title-big">
       <?php if (!$params['land'] && !$params['state'] ) :?>
      <span class="country_flag" style="background:url(https://corona.lmao.ninja/assets/img/flags/in.png) center no-repeat;background-size:cover;"></span>
      <?php endif;?>

      <?php echo esc_html(isset($params['title_widget']) ? $params['title_widget'] : ''); ?>
   </h4>
   <div class="covid19-row first-ncrts">
      <div class="covid19-col covid19-confirmed">
         <div class="covid19-title"><?php echo esc_html($params['confirmed_title']); ?></div>
         <div class="covid19-num"><?php echo number_format($getFData->confirmed); ?></div>
         <div class="covid19-sub-num" style="color: #ea6060">+<?php echo number_format($getFData->deltaconfirmed); ?> (<?php echo esc_html($params['today_cases']); ?>)</div>
      </div>
      <div class="covid19-col covid19-deaths">
         <div class="covid19-title"><?php echo esc_html($params['deaths_title']); ?></div>
         <div class="covid19-num"><?php echo number_format($getFData->deaths); ?></div>
         <div class="covid19-sub-num" style="color: #A3AAB9">+<?php echo number_format($getFData->deltadeaths); ?> (<?php echo esc_html($params['today_deaths']); ?>)</div>
      </div>
      <div class="covid19-col covid19-recovered">
         <div class="covid19-title"><?php echo esc_html($params['recovered_title']); ?></div>
         <div class="covid19-num"><?php echo number_format($getFData->recovered); ?></div>
         <div class="covid19-sub-num" style="color: #55cb70">+<?php echo number_format($getFData->deltarecovered); ?> (<?php echo esc_html($params['today_recovered']); ?>)</div>
      </div>
      <div class="covid19-col covid19-active">
         <div class="covid19-title"><?php echo esc_html($params['active_title']); ?></div>
         <div class="covid19-num"><?php echo number_format($getFData->active); ?></div>
         <div class="covid19-sub-num" style="color: #E7B526"><?php echo round(($getFData->active)/($getFData->confirmed)*100, 2); ?>%</div>
      </div>
   </div>
</div>
