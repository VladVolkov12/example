<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Document</title>
</head>
<body>
<?php
//коннект к базе
function conn ()
{
     // данные коннекта к бд!!
    $mysqli = mysqli_connect('localhost', 'root', '', 'my_db');
    if (mysqli_connect_errno()) {
        throw new RuntimeException('mysqli connection error: ' . mysqli_connect_error());
    }
    $mysqli->set_charset("utf8");
    return $mysqli;
}
//читаем файл с производителями
function read_brands()
{
    $fp=fopen ('brands.csv', 'r');
    $i=0;
    while (!feof($fp)) {
        $brand[$i]= fgetcsv($fp,1000,';');
        $i++;
    }
    fclose($fp);
    return $brand;

}
//достаем из базы производителей
function manufactures ($brands)
{
    $conn=conn();
    $query = 'select * from manufacturers';
    $results=$conn->query($query);
    $i=0; 
    foreach ($brands as $brand)
    {
        $br_nal[$i]=$brand['1'];
        $i++;
    }


    ?>
    <form method="POST" action="pn_price_4_m.php">
        <? 
        $i=1;
        while ($res=$results->fetch_assoc())
        {
            $man=$res["manufacturers_id"];
            $man_name=$res["manufacturers_name"];
            $br_check='';
            $no_nal='checked';
            $nal='';

            $va= array(1, 6, 12, 24, 36, 60, 120);
            $sel_var=36;
            if (in_array($man, $br_nal))
            {
                $br_check='checked';
                for ($j=0; $j<count ($brands); $j++)
                {
                    if ($brands[$j]['1']==$man)
                    {
                        $sel_var=$brands[$j]['3'];
                        if ($brands[$j]['2']=='+')
                        {
                            $nal='checked';
                            $no_nal='';
                        }
                    }
                }

            }
            echo '<div class="brands">'.$i.'<input name="brand_'.$man.'" type="checkbox" '.$br_check.'/>    ' .$man_name. 
            '</div><input type="radio" name="nal_'.$man.'" value="+" '.$nal.'>в наличии  <input type="radio" name="nal_'.$man.'" value="-" '.$no_nal.'> нету'.'</br>';
            echo '<select name="var_'.$man.'">';
            for ($t=0; $t<count ($va); $t++)
            {
                if ($va[$t]==$sel_var)
                {
                    $sel="selected";
                }
                else 
                {
                    $sel="";
                }

                echo '<option value="'.$va[$t].'" '.$sel.'>'.$va[$t].'</option>';
            }
            

          echo '</select></br></br>';
            $i++;
        }
        ?>
        <input type="submit" name="submit_btn" value="Выбрать">
    </form>
<?}


// обрабатываем результаты формы
function new_brands ($brands)
{
    $i=0;
    foreach ($brands as $key=>$value)
    {
    
        if (mb_substr($key, 0, 6)=='brand_')
        {
           
            $br=mb_substr($key, 6);
            $nal=$brands['nal_'.$br];
            $var=$brands['var_'.$br];
            $new_brands[$i]=array($br, $nal, $var);
            $i++;
        }

    }
    return $new_brands;
}
//сохраняем бренды в файл

function update_brands_file ($brands)
{
    $fp=fopen ('brands.csv', 'w+');
    $conn=conn();
    foreach ($brands as $brand)
    {
        $query = 'select manufacturers_name from manufacturers where manufacturers_id='.$brand[0];
        $results=$conn->query($query);
        $res=$results->fetch_assoc();
        echo $res['manufacturers_name'].'<br>';
        $str=$res['manufacturers_name'].';'.$brand[0].';'.$brand[1].';'.$brand[2];
        fwrite ($fp, $str."\r\n");
    }
    fclose($fp);
}


// курс гривни
function courses()
{
    $conn=conn();
    $query='select value from currencies where code="UAH"';
    $result=$conn->query($query);
    $ks=$result->fetch_assoc();
    $kurs=$ks['value'];
    return $kurs;
}


//основная работа с базой
function prods_by_brands ($brand)
{
    $kurs=courses();
    $conn=conn();
    $query = 'select * from
    (select
    p.products_id,
    p.products_image,
    p.products_price,
    s.specials_new_products_price,
    p.products_status,
    p.products_page_url,
    d.products_name
    from products p
    join products_description d
    on p.products_id=d.products_id
    left join specials s on p.products_id=s.products_id
    where p.manufacturers_id='.$brand[0].') t
    where (t.products_price>0 and t.products_status=1)';
    $results=$conn->query($query);
    $i=0;
    while ($res=$results->fetch_assoc())
    {
        $product[$i]['name']=iconv ('utf-8', 'windows-1251', $res['products_name']);
        $product[$i]['url']='https://technobum.com.ua/'.$res['products_page_url'];
        $product[$i]['im_url']='https://technobum.com.ua/images/product_images/popup_images/'.$res['products_image'];
        if ($res['specials_new_products_price']>0)
            {
                if ($res['specials_new_products_price']<$res['products_price'])
                {
                    $product[$i]['price']=$res['specials_new_products_price']*$kurs;
                }
                else 
                {
                    echo $product[$i]['name']. '  debil<br>';
                    $product[$i]['price']=$res['products_price']*$kurs;
                }
            }
            else
            {
                $product[$i]['price']=$res['products_price']*$kurs;
            }
            $prod_data[$i]=$product[$i]['name'].';'.$product[$i]['price'].';'.$brand[2].';'.$brand[1].';'.$product[$i]['url'].';'.$product[$i]['im_url'].";"."\r\n";    
            $i++;
    }
    mysqli_close($conn);
    return $prod_data;
}

// запись в файл товаров
function pn_price($all_data)
    {
	    $uploaddir = './tmp/';
	    $filename='pn_file.csv';
	    $ffile=$uploaddir.$filename;
        $fp = fopen ($ffile, 'a');
	    foreach ($all_data as $dt)
	    {
		    fwrite($fp, $dt);
	    }
	    fclose($fp);
    }


//сама программа
set_time_limit(400);
$brands=read_brands();
manufactures($brands);
if ($_POST['submit_btn']=='Выбрать')
{
    $active_brands=new_brands($_POST);
    update_brands_file($active_brands);
    $uploaddir = './tmp/';
    $filename='pn_file.csv';
    $ffile=$uploaddir.$filename;
    $fp = fopen ($ffile, 'w');
    $dt = "Наименование товара;Цена розница (грн);Гарантия (месяцев);Наличие (+ есть, - нет);Ссылка на товар;Фото;\r\n";
   // $dt = "Наименование товара".";"."Цена розница (грн)".";"."Гарантия (месяцев)".";"."Ссылка на товар".";"."Фото".";"."\r\n";
    $dt=iconv ('utf-8', 'windows-1251', $dt);
    fwrite($fp, $dt);
    fclose($fp);
    foreach ($active_brands as $brand)
    {
        $prods=prods_by_brands ($brand);
        echo $brand[0].'<br>';
        pn_price ($prods);
    }

}




?>
</body>
</html>