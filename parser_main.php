<?php
	set_time_limit(400);
	require_once("vendor/autoload.php");
	include 'parser_obj.php';
	$obj1 = NEW pars;
	$url_n="https://technobum.com.ua/Santehnika-c-723.html";
	$obj1->ready($url_n);
	$i=0;
	do 
	{
	$test=0;
	$url_data=$obj1->db_find($i);//ищем родительские категории уровня i
	$j=1;

	While ($url_data[$j]['category_url']>'')
	{
		$cats=$obj1->dir_find ($url_data[$j]['category_url']); //ищем дочерние категории
//		var_dump ($cats);
		if ($cats['1']['name']>'')
		{
		$test++;
		$part_n=$i+1;
		$obj1->save_new_cats_to_db($cats, $url_data[$j]['id'],$part_n);	// запись дочерних категорий в бд
		}
		$j++;
	}	
	$i++;
	} 
	while ($test>0); 
//	var_dump ($url_data);
echo $test;
	
?>