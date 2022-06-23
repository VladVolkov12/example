<?php
class pars
{

	function dir_find ($url)// достаем со страницы ссылки на категории
	{
		$str=file_get_contents($url);
		$pq = phpQuery::newDocument($str);
		$categories = $pq->find('div[class="category-list text-center"]');
		$i=1;
		foreach ($categories as $key => $category)
		{
			$category = pq($category);
			$cat[$i]['name']=trim ($category->text());
			$cat[$i]['url']= trim($category->find('a')->attr("href"));
			$i++;
		}
		return ($cat);
	}

	function db_connect ()// подключение к бд
	{
		$host='localhost';
		$log='root';
		$pass='';
		$db='parser';
		$this->mysqli = mysqli_connect($host, $log, $pass, $db);	
		if ($this->mysqli -> connect_errno) {
		echo "Failed to connect to MySQL: " . $this->mysqli -> connect_error;
		exit();
		}
	}
	
	Function ready ($main_url) // подготовка бд к записи очищаем базу и записываем начальный урл
	{
		echo $main_url;
		$this->db_connect();
		$result=mysqli_query($this->mysqli, 'TRUNCATE categories');
		$category_name='Сантехника';
		$query_cat='INSERT into categories(
		id,
		category_name,
		category_url,
		parent_cat_id,
		part
		)
		VALUES
		(
		"1",
		"'.$category_name.'", 
		"'.$main_url.'",
		"0",
		"0"
		)';
		if(mysqli_query($this->mysqli, $query_cat))
			{
//				echo "Records inserted successfully.";
			} else
			{
				echo "ERROR: Could not able to execute $sql. " . mysqli_error($this->mysqli);
			}
	}
	
	function db_find ($pos) // ищем в базе все категории одного уровня
	{
		$this->db_connect();
		$result=mysqli_query($this->mysqli, 'Select * from categories WHERE part ='.$pos);
		$count = mysqli_num_rows($result);
		for ($i=1; $i<=$count; $i++)
		{
			$row[$i] = mysqli_fetch_assoc($result);
		}
		return ($row);	
	}
	
	function save_new_cats_to_db($categ, $parent_cat_id, $part) // запись дочерних категорий в бд
	{
		for ($i=1; $i<=count($categ); $i++)
		{
		$categ_name=$categ[$i]['name'];
		echo $categ_name.'<br>';
		$url_cat=$categ[$i]['url'];
		$query_cat='INSERT into categories(
		category_name,
		category_url,
		parent_cat_id,
		part
		)
		VALUES
		(
		"'.$categ_name.'", 
		"'.$url_cat.'",
		"'.$parent_cat_id.'",
		"'.$part.'"
		)';
		$this->db_connect();
//		echo $query_cat.'<br>';
		if(mysqli_query($this->mysqli, $query_cat))
			{
//				echo "Records inserted successfully.";
			} else
			{
				echo "ERROR: Could not able to execute $sql. " . mysqli_error($this->mysqli);
			}
		}	
	}
}

?>