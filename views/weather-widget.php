<?php
$weatherEnabled=setting('weather_widget_enabled','1')==='1';
$weatherUserId=(int)($weatherUserId??\Kovcheg\Auth::id());
$weatherCity=trim((string)($weatherUserId===\Kovcheg\Auth::id()?user_setting('weather_city',''):raw_user_setting($weatherUserId,'weather_city','')));
$weatherData=null;$weatherError='';
if($weatherEnabled&&$weatherCity!=='')try{$weatherData=weather_short_forecast($weatherCity,false);}catch(Throwable $e){$weatherError=$e->getMessage();}
$current=$weatherData['current']??[];$daily=$weatherData['daily']??[];$place=$weatherData['place']??['name'=>$weatherCity,'admin1'=>''];
?>
<?php if($weatherEnabled):?>
<a class="vk-right-card weather-widget weather-widget-clickable" href="<?=e($weatherCity!==''?app_url('/weather?user='.$weatherUserId):($weatherUserId===\Kovcheg\Auth::id()?app_url('/settings/appearance'):'#'))?>" data-weather-widget data-weather-user="<?=$weatherUserId?>" data-weather-city="<?=e($weatherCity)?>" <?=$weatherData?'data-weather-ready="1"':''?>>
 <header><div><b>Погода</b><small data-weather-updated><?=$weatherData?e(!empty($weatherData['stale'])?'сохранённый прогноз':'обновлено недавно'):''?></small></div><span class="weather-main-icon" data-weather-icon><?=$weatherData?weather_code_icon((int)($current['weather_code']??0)):'◌'?></span></header>
 <?php if($weatherCity===''):?>
  <div class="weather-empty"><p>Укажите город, чтобы видеть погоду.</p><span>Настроить город</span></div>
 <?php elseif($weatherData):?>
  <div class="weather-content" data-weather-content>
   <div class="weather-now"><div><b data-weather-temp><?=round((float)($current['temperature_2m']??0))?>°</b><span data-weather-description><?=e(weather_code_text((int)($current['weather_code']??0)))?></span></div><small data-weather-feels>Ощущается как <?=round((float)($current['apparent_temperature']??0))?>° · ветер <?=round((float)($current['wind_speed_10m']??0))?> км/ч</small></div>
   <div class="weather-days" data-weather-days><?php foreach(array_slice($daily['time']??[],0,3) as $i=>$date):?><div><span><?=$i===0?'Сегодня':($i===1?'Завтра':e(mb_substr((new DateTime($date))->format('D'),0,3)))?></span><i><?=weather_code_icon((int)($daily['weather_code'][$i]??0))?></i><b><?=round((float)($daily['temperature_2m_max'][$i]??0))?>°</b><small><?=round((float)($daily['temperature_2m_min'][$i]??0))?>°</small></div><?php endforeach;?></div>
   <div class="weather-city-link"><span data-weather-city-label><?=e(implode(', ',array_filter([$place['name']??$weatherCity,$place['admin1']??''])))?></span><small>Открыть прогноз →</small></div>
  </div>
 <?php else:?>
  <div class="weather-loading" data-weather-loading>Загрузка погоды…</div><div class="weather-content" data-weather-content hidden><div class="weather-now"><div><b data-weather-temp>—</b><span data-weather-description>—</span></div><small data-weather-feels>—</small></div><div class="weather-days" data-weather-days></div><div class="weather-city-link"><span data-weather-city-label><?=e($weatherCity)?></span><small>Открыть прогноз →</small></div></div><div class="weather-error" data-weather-error <?=$weatherError===''?'hidden':''?>><?=e($weatherError)?></div>
 <?php endif;?>
</a>
<?php endif;?>
