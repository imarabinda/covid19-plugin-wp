<?php $all_options=get_option('covid_options');wp_enqueue_style('covid');$dataAll=get_option('nycreatisAL');
$getFData=new stdClass();
$getFData->cases=0;$getFData->deaths=0;$getFData->recovered=0;
$getFData->todayCases=0;$getFData->todayDeaths=0;$getFData->active=0;if(is_array($data)){foreach($data as $key=>$value){$getFData->cases+=$value->cases;$getFData->deaths+=$value->deaths;$getFData->recovered+=$value->recovered;$getFData->todayCases+=$value->todayCases;$getFData->todayDeaths+=$value->todayDeaths;$getFData->active+=$value->active;}}else {$getFData->cases+=$data->cases;$getFData->deaths+=$data->deaths;$getFData->recovered+=$data->recovered;$getFData->todayCases+=isset($data->todayCases)?$data->todayCases:0;$getFData->todayDeaths+=isset($data->todayDeaths)?$data->todayDeaths:0;$getFData->active+=isset($data->active)?$data->active:0;} ?>
<div class="ny-covid19-slip js-covid inited <?php echo $all_options['cov_theme'];?> <?php if((isset($all_options['cov_rtl']) ? $all_options['cov_rtl'] : null)=='on') echo 'rtl_enable'; ?>" style="font-family:<?php echo $all_options['cov_font'];?>">
   <div class="ny-covid19-slip__date js-covid-date"></div>
   <div class="ny-covid19-slip__title"><?php echo esc_html($params['covid_title']); ?></div>
   <div class="ny-covid19-slip__subtitle"><?php echo esc_html__( 'Covid-19', 'covid' );?></div>
   <div class="ny-covid19-slip__tabs__wrap">
      <div class="ny-covid19-slip__tabs"><span onclick="ncrtsTab(event, 'cc')" id="ncrtsCC" class="ny-covid19-slip__tabs__item js-covid-tab tablinks"><?php if (isset($data->countryInfo->flag)) : ?><span class="r-country_flag" style="background:url(<?php echo esc_html($data->countryInfo->flag); ?>) center no-repeat;background-size:cover;"></span>
      <?php endif; ?><?php echo esc_html(isset($params['country']) ? $params['country'] : ''); ?></span>  <span onclick="ncrtsTab(event, 'ww')" class="ny-covid19-slip__tabs__item js-covid-tab tablinks"><?php echo esc_html($params['world_title']); ?></span></div>
   </div>
   <div class="ny-covid19-slip__parent">
      <div id="cc" class="tabcontent">
      <div class="ny-covid19-slip__content js-covid-content active">
         <div class="ny-covid19-slip__item">
            <div class="ny-covid19-slip__item__diff"><span class="js-covid-ny-con-day">+<?php echo number_format($getFData->todayCases); ?></span> <span class="ny-covid19-slip__item__info">(<?php echo esc_html($params['today_title']); ?>)</span></div>
            <div class="ny-covid19-slip__item__number js-covid-ny-con-total"><?php echo number_format($getFData->cases); ?></div>
            <div class="ny-covid19-slip__item__text"><?php echo esc_html($params['confirmed_title']); ?></div>
         </div>
         <div class="ny-covid19-slip__item__border">0</div>
         <div class="ny-covid19-slip__item ny-covid19-slip__item_2">
            <div class="ny-covid19-slip__item__diff ny-covid19-slip__item__fail"><span class="js-covid-ny-dea-day">+<?php echo number_format($getFData->todayDeaths); ?></span> <span class="ny-covid19-slip__item__info">(<?php echo esc_html($params['today_title']); ?>)</span></div>
            <div class="ny-covid19-slip__item__number js-covid-ny-dea-total"><?php echo number_format($getFData->deaths); ?></div>
            <div class="ny-covid19-slip__item__text"><?php echo esc_html($params['deaths_title']); ?></div>
         </div>
         <div class="ny-covid19-slip__item__border">0</div>
         <div class="ny-covid19-slip__item">
            <div class="ny-covid19-slip__item__diff ny-covid19-slip__item__grow"><span class="js-covid-ny-rec-day"><?php echo round(($getFData->recovered)/($getFData->cases)*100, 2); ?>%</span></div>
            <div class="ny-covid19-slip__item__number js-covid-ny-rec-total"><?php echo number_format($getFData->recovered); ?></div>
            <div class="ny-covid19-slip__item__text"><?php echo esc_html($params['recovered_title']); ?></div>
         </div>
         <div class="ny-covid19-slip__item__border">0</div>
         <div class="ny-covid19-slip__item ny-covid19-slip__item_2">
            <div class="ny-covid19-slip__item__diff ny-covid19-slip__item__act"><span class="js-covid-ny-act-day"><?php echo round(($getFData->active)/($getFData->cases)*100, 2); ?>%</span></div>
            <div class="ny-covid19-slip__item__number js-covid-ny-act-total"><?php echo number_format($getFData->active); ?></div>
            <div class="ny-covid19-slip__item__text"><?php echo esc_html($params['active_title']); ?></div>
         </div>
      </div>
	 </div>
	 <div id="ww" class="tabcontent">
      <div class="ny-covid19-slip__content js-covid-content">
         <div class="ny-covid19-slip__item">
            <div class="ny-covid19-slip__item__diff"><span class="js-covid-world-con-day">+<?php echo number_format($dataAll->todayCases); ?></span> <span class="ny-covid19-slip__item__info">(<?php echo esc_html($params['today_title']); ?>)</span></div>
            <div class="ny-covid19-slip__item__number js-covid-world-con-total"><?php echo number_format($dataAll->cases); ?></div>
            <div class="ny-covid19-slip__item__text"><?php echo esc_html($params['confirmed_title']); ?></div>
         </div>
         <div class="ny-covid19-slip__item__border">0</div>
         <div class="ny-covid19-slip__item ny-covid19-slip__item_2">
            <div class="ny-covid19-slip__item__diff ny-covid19-slip__item__fail"><span class="js-covid-world-dea-day">+<?php echo number_format($dataAll->todayDeaths); ?></span> <span class="ny-covid19-slip__item__info">(<?php echo esc_html($params['today_title']); ?>)</span></div>
            <div class="ny-covid19-slip__item__number js-covid-world-dea-total"><?php echo number_format($dataAll->deaths); ?></div>
            <div class="ny-covid19-slip__item__text"><?php echo esc_html($params['deaths_title']); ?></div>
         </div>
         <div class="ny-covid19-slip__item__border">0</div>
         <div class="ny-covid19-slip__item">
            <div class="ny-covid19-slip__item__diff ny-covid19-slip__item__grow"><span class="js-covid-world-rec-day"><?php echo round(($dataAll->active)/($dataAll->cases)*100, 2); ?>%</span></div>
            <div class="ny-covid19-slip__item__number js-covid-world-rec-total"><?php echo number_format($dataAll->recovered); ?></div>
            <div class="ny-covid19-slip__item__text"><?php echo esc_html($params['recovered_title']); ?></div>
         </div>
         <div class="ny-covid19-slip__item__border">0</div>
         <div class="ny-covid19-slip__item ny-covid19-slip__item_2">
            <div class="ny-covid19-slip__item__diff ny-covid19-slip__item__act"><span class="js-covid-world-act-day"><?php echo round(($dataAll->active)/($dataAll->cases)*100, 2); ?>%</span></div>
            <div class="ny-covid19-slip__item__number js-covid-world-act-total"><?php echo number_format($dataAll->active); ?></div>
            <div class="ny-covid19-slip__item__text"><?php echo esc_html($params['active_title']); ?></div>
         </div>
      </div>
	 </div>
   </div>
</div>
<script>
	document.getElementById("ncrtsCC").click();
	function ncrtsTab(evt, ncrtsName) {
	  var i, tabcontent, tablinks;
	  tabcontent = document.getElementsByClassName("tabcontent");
	  for (i = 0; i < tabcontent.length; i++) {
		tabcontent[i].style.display = "none";
	  }
	  tablinks = document.getElementsByClassName("tablinks");
	  for (i = 0; i < tablinks.length; i++) {
		tablinks[i].className = tablinks[i].className.replace(" active", "");
	  }
	  document.getElementById(ncrtsName).style.display = "block";
	  evt.currentTarget.className += " active";
	}
</script>
